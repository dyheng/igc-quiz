/**
 * ═══════════════════════════════════════════════════════════════
 *  IGC Quiz — k6 Load Test
 * ═══════════════════════════════════════════════════════════════
 *
 * Tiga scenario:
 *   A) page_load   — flood GET /q/{code}  → test Apache + PHP-FPM
 *   B) websocket   — 100 WS connections   → test Reverb
 *   C) full_flow   — join + answer quiz   → test full stack (butuh LOADTEST_SECRET)
 *
 * ── Prasyarat ──────────────────────────────────────────────────
 * 1. Install k6:
 *      AlmaLinux:  sudo dnf install -y https://dl.k6.io/rpm/repo.rpm && sudo dnf install -y k6
 *      macOS:      brew install k6
 *      Windows:    winget install k6
 *
 * 2. Di VPS, set .env:
 *      LOADTEST_SECRET=rahasia-acak-anda
 *
 * 3. Prepare quiz session di admin panel (status: waiting atau running).
 *    Catat SESSION_CODE dan REVERB_APP_KEY dari .env.
 *
 * ── Run ────────────────────────────────────────────────────────
 *
 * Hanya test page load:
 *   k6 run loadtest.js --env SCENARIO=page_load \
 *     --env BASE_URL=https://igc.dylab.id \
 *     --env SESSION_CODE=ABC12345
 *
 * Hanya test WebSocket:
 *   k6 run loadtest.js --env SCENARIO=websocket \
 *     --env BASE_URL=https://igc.dylab.id \
 *     --env SESSION_CODE=ABC12345 \
 *     --env REVERB_APP_KEY=your-reverb-key
 *
 * Full flow (join + jawab):
 *   k6 run loadtest.js --env SCENARIO=full_flow \
 *     --env BASE_URL=https://igc.dylab.id \
 *     --env SESSION_CODE=ABC12345 \
 *     --env REVERB_APP_KEY=your-reverb-key \
 *     --env LOADTEST_SECRET=rahasia-acak-anda
 *
 * Semua scenario sekaligus:
 *   k6 run loadtest.js \
 *     --env BASE_URL=https://igc.dylab.id \
 *     --env SESSION_CODE=ABC12345 \
 *     --env REVERB_APP_KEY=your-reverb-key \
 *     --env LOADTEST_SECRET=rahasia-acak-anda
 */

import http from 'k6/http';
import ws from 'k6/ws';
import { check, sleep, group } from 'k6';
import { Counter, Trend, Rate, Gauge } from 'k6/metrics';

// ── Config dari environment variables ────────────────────────────────────────
const BASE_URL        = __ENV.BASE_URL        || 'https://igc.dylab.id';
const SESSION_CODE    = __ENV.SESSION_CODE    || 'GANTI_KODE_SESI';
const REVERB_APP_KEY  = __ENV.REVERB_APP_KEY  || 'GANTI_REVERB_KEY';
const LOADTEST_SECRET = __ENV.LOADTEST_SECRET || '';
const SCENARIO        = __ENV.SCENARIO        || 'all'; // page_load | websocket | full_flow | all

// Resolve WebSocket host (tanpa http:// atau https://)
const WS_HOST   = BASE_URL.replace(/^https?:\/\//, '');
const WS_SCHEME = BASE_URL.startsWith('https') ? 'wss' : 'ws';
const WS_URL    = `${WS_SCHEME}://${WS_HOST}/app/${REVERB_APP_KEY}?protocol=7&client=k6&version=8.0&flash=false`;

// ── Custom metrics ────────────────────────────────────────────────────────────
const pageLoaded      = new Rate('page_loaded');
const pageLoadMs      = new Trend('page_load_ms', true);
const wsConnected     = new Rate('ws_connected');
const wsConnectMs     = new Trend('ws_connect_ms', true);
const wsMsgReceived   = new Rate('ws_msg_received');
const joinSuccess     = new Rate('join_success');
const answerSuccess   = new Rate('answer_success');
const answerMs        = new Trend('answer_ms', true);

// ── Shared state (diisi di setup) ─────────────────────────────────────────────
let questionIds = [];

// ── Scenarios ────────────────────────────────────────────────────────────────
function buildScenarios() {
    const all = {
        // A: GET flood halaman quiz — test Apache + PHP-FPM
        page_load: {
            executor: 'ramping-vus',
            exec: 'scenarioPageLoad',
            startVUs: 0,
            stages: [
                { duration: '10s', target: 30  },
                { duration: '20s', target: 30  },
                { duration: '5s',  target: 100 },
                { duration: '20s', target: 100 },
                { duration: '10s', target: 0   },
            ],
            gracefulRampDown: '5s',
        },

        // B: WebSocket connections — test Reverb
        websocket: {
            executor: 'ramping-vus',
            exec: 'scenarioWebSocket',
            startVUs: 0,
            stages: [
                { duration: '15s', target: 50  },
                { duration: '30s', target: 100 },
                { duration: '10s', target: 0   },
            ],
            gracefulRampDown: '5s',
        },

        // C: Full flow join + answer — test DB + broadcast (butuh LOADTEST_SECRET)
        // vus: jumlah user simultan. Mulai dari 20, naikkan perlahan.
        // Dengan delay 5–15s antar soal, 20 VU ≈ beban 20 user nyata bersamaan.
        full_flow: {
            executor: 'per-vu-iterations',
            exec: 'scenarioFullFlow',
            vus: 20,
            iterations: 1,
            maxDuration: '5m',
        },
    };

    if (SCENARIO === 'all') return all;

    const selected = {};
    if (all[SCENARIO]) selected[SCENARIO] = all[SCENARIO];
    return selected;
}

export const options = {
    scenarios: buildScenarios(),

    thresholds: {
        // Halaman harus respond dalam 3s untuk 95% request
        'http_req_duration':         ['p(95)<3000', 'p(99)<5000'],
        // Minimal 90% halaman berhasil dimuat
        'page_loaded':               ['rate>0.90'],
        // WebSocket: minimal 85% berhasil connect
        'ws_connected':              ['rate>0.85'],
        // Answer submission: p95 < 2s, success rate > 90%
        'answer_ms':                 ['p(95)<2000'],
        'answer_success':            ['rate>0.90'],
        // HTTP error rate < 10%
        'http_req_failed':           ['rate<0.10'],
    },
};

// ── Setup: ambil question list sebelum test dimulai ───────────────────────────
export function setup() {
    console.log('\n🚀 IGC Quiz Load Test');
    console.log(`   Base URL  : ${BASE_URL}`);
    console.log(`   Session   : ${SESSION_CODE}`);
    console.log(`   Scenario  : ${SCENARIO}`);
    console.log(`   Secret    : ${LOADTEST_SECRET ? '✓ set' : '✗ tidak set (full_flow tidak bisa jalan)'}`);
    console.log('');

    // Ambil question IDs untuk dipakai di full_flow scenario
    if (LOADTEST_SECRET) {
        const res = http.get(`${BASE_URL}/loadtest/questions/${SESSION_CODE}`, {
            headers: { 'X-Loadtest-Secret': LOADTEST_SECRET },
        });

        if (res.status === 200) {
            const data = res.json();
            console.log(`   Questions : ${data.question_ids.length} soal ditemukan`);
            console.log(`   Status    : ${data.status}`);
            return { questionIds: data.question_ids, sessionId: data.session_id };
        } else {
            console.warn(`   ⚠ Gagal ambil questions: ${res.status} — ${res.body}`);
        }
    }

    return { questionIds: [], sessionId: null };
}

// ── Teardown: hapus data test ─────────────────────────────────────────────────
export function teardown(data) {
    if (LOADTEST_SECRET && (SCENARIO === 'full_flow' || SCENARIO === 'all')) {
        console.log('\n🧹 Membersihkan data test...');
        const res = http.del(`${BASE_URL}/loadtest/cleanup/${SESSION_CODE}`, null, {
            headers: { 'X-Loadtest-Secret': LOADTEST_SECRET },
        });
        if (res.status === 200) {
            const d = res.json();
            console.log(`   ${d.deleted} peserta test dihapus.`);
        }
    }
    console.log('\n✅ Load test selesai. Cek summary di atas.');
}

// ════════════════════════════════════════════════════════════════
//  SCENARIO A — Page Load
//  Simulasi N browser membuka halaman join quiz bersamaan.
//  Yang ditest: Apache → PHP-FPM → Laravel → Blade render.
// ════════════════════════════════════════════════════════════════
export function scenarioPageLoad() {
    group('Page Load', () => {
        const t0  = Date.now();
        const res = http.get(`${BASE_URL}/q/${SESSION_CODE}`, {
            headers: { 'Accept': 'text/html', 'Accept-Language': 'id-ID' },
        });
        const ms  = Date.now() - t0;

        const ok = check(res, {
            'status 200':          (r) => r.status === 200,
            'has quiz title':      (r) => r.body && r.body.includes(SESSION_CODE),
            'no server error':     (r) => r.status < 500,
        });

        pageLoaded.add(ok);
        pageLoadMs.add(ms);

        if (res.status >= 500) {
            console.error(`❌ Server error ${res.status} — ${res.url}`);
        }
    });

    sleep(randomBetween(0.5, 2));
}

// ════════════════════════════════════════════════════════════════
//  SCENARIO B — WebSocket
//  Simulasi N browser membuka koneksi WebSocket ke Reverb.
//  Setiap VU: connect → subscribe ke channel sesi → hold 30s → disconnect.
//  Yang ditest: Reverb WS handler, concurrent connection limit.
// ════════════════════════════════════════════════════════════════
export function scenarioWebSocket(data) {
    const sessionId = (data && data.sessionId) ? data.sessionId : '0';

    group('WebSocket', () => {
        const t0 = Date.now();

        const res = ws.connect(WS_URL, {}, function (socket) {
            let connected   = false;
            let msgReceived = false;
            let subscribed  = false;

            socket.on('open', () => {
                wsConnectMs.add(Date.now() - t0);
                connected = true;
            });

            socket.on('message', (raw) => {
                msgReceived = true;
                try {
                    const msg = JSON.parse(raw);

                    // Setelah connection established, subscribe ke channel sesi
                    if (msg.event === 'pusher:connection_established' && !subscribed) {
                        subscribed = true;
                        socket.send(JSON.stringify({
                            event: 'pusher:subscribe',
                            data: { auth: '', channel: `session.${sessionId}` },
                        }));
                    }

                    // Ping untuk keep-alive
                    if (msg.event === 'pusher:ping') {
                        socket.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
                    }
                } catch (_) {}
            });

            socket.on('error', (e) => {
                connected = false;
                if (e && e.error) console.error(`WS error: ${e.error}`);
            });

            // Hold koneksi 25s (simulasi peserta menunggu di waiting room)
            socket.setTimeout(() => {
                socket.close();
            }, 25000);

            socket.on('close', () => {
                wsConnected.add(connected);
                wsMsgReceived.add(msgReceived);
            });
        });

        check(res, {
            'ws no error': (r) => r && r.status !== 'error',
        });
    });
}

// ════════════════════════════════════════════════════════════════
//  SCENARIO C — Full Flow (butuh LOADTEST_SECRET)
//  Setiap VU = 1 peserta: join → (opsional WS) → jawab semua soal.
//  Yang ditest: end-to-end throughput, DB write concurrency, broadcast load.
// ════════════════════════════════════════════════════════════════
export function scenarioFullFlow(data) {
    if (!LOADTEST_SECRET) {
        console.warn('⚠ LOADTEST_SECRET tidak di-set. Scenario full_flow di-skip.');
        return;
    }

    const qIds = (data && data.questionIds) ? data.questionIds : [];

    if (qIds.length === 0) {
        console.warn('⚠ Tidak ada question IDs. Pastikan session code benar dan LOADTEST_SECRET valid.');
        return;
    }

    const headers = {
        'Content-Type':       'application/json',
        'Accept':             'application/json',
        'X-Loadtest-Secret':  LOADTEST_SECRET,
    };

    const vuIndex = __VU * 100 + __ITER;

    // ── Step 1: Join ──────────────────────────────────────────────────────────
    group('Join', () => {
        const res = http.post(
            `${BASE_URL}/loadtest/join/${SESSION_CODE}`,
            JSON.stringify({ index: vuIndex }),
            { headers }
        );

        const ok = check(res, {
            'join status 201': (r) => r.status === 201,
            'got participant_id': (r) => r.json('participant_id') > 0,
        });

        joinSuccess.add(ok);

        if (!ok) {
            console.error(`❌ Join gagal: ${res.status} — ${res.body}`);
            return;
        }

        const participantId = res.json('participant_id');

        // Simpan ke VU-level variable via shared data (workaround: pakai tag)
        // k6 tidak punya per-VU state selain local variables, jadi kita teruskan
        // participantId langsung ke step berikutnya.

        sleep(randomBetween(2, 5)); // Simulasi user baca nama quiz sebelum mulai

        // ── Step 2: Jawab semua soal ──────────────────────────────────────────
        group('Answer', () => {
            for (const qId of qIds) {
                const t0 = Date.now();
                const ansRes = http.post(
                    `${BASE_URL}/loadtest/answer/${SESSION_CODE}/${participantId}`,
                    JSON.stringify({ question_id: qId }),
                    { headers }
                );
                const ms = Date.now() - t0;

                const ansOk = check(ansRes, {
                    'answer status 200': (r) => r.status === 200,
                    'answer saved':      (r) => r.json('ok') === true,
                });

                answerSuccess.add(ansOk);
                answerMs.add(ms);

                if (!ansOk) {
                    console.error(`❌ Answer gagal (Q:${qId} P:${participantId}): ${ansRes.status} — ${ansRes.body}`);
                }

                // Delay realistis: simulasi user membaca soal (5–15 detik)
                // Ubah ke 0.3–0.8 untuk stress test ekstrem (bukan simulasi user nyata)
                sleep(randomBetween(5, 15));
            }
        });
    });
}

// ── Utility ───────────────────────────────────────────────────────────────────
function randomBetween(min, max) {
    return Math.random() * (max - min) + min;
}
