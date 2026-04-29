/**
 * k6 Load Test — IGC Quiz
 * 
 * Simulasi peserta mengakses halaman join quiz secara bersamaan.
 * Karena Livewire memerlukan state component (snapshot/checksum),
 * test ini menyimulasikan beban HTTP ke halaman quiz (GET request storm)
 * yang merepresentasikan beban server saat 100 browser terbuka bersamaan.
 *
 * Install k6: https://k6.io/docs/get-started/installation/
 * 
 * Run (dari direktori project):
 *   k6 run loadtest.js
 *   k6 run loadtest.js --vus 100 --duration 60s     -- 100 concurrent users selama 60s
 *   k6 run loadtest.js --vus 50  --duration 30s     -- warm-up dulu
 *
 * Environment variables:
 *   k6 run loadtest.js -e BASE_URL=https://igc.dylab.id -e SESSION_CODE=ABC12345
 */

import http from 'k6/http';
import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { Counter, Trend, Rate } from 'k6/metrics';

// ── Config ──────────────────────────────────────────────────────────────────
const BASE_URL     = __ENV.BASE_URL     || 'https://igc.dylab.id';
const SESSION_CODE = __ENV.SESSION_CODE || 'GANTI_DENGAN_KODE_SESI';

// ── Custom metrics ───────────────────────────────────────────────────────────
const joinPageLoaded   = new Rate('join_page_loaded');
const pageLoadTime     = new Trend('page_load_ms', true);
const wsConnected      = new Rate('ws_connected');
const wsConnectTime    = new Trend('ws_connect_ms', true);

// ── k6 options ───────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        // Scenario 1: 50 users ramp up dalam 10s, hold 30s, ramp down 10s
        join_flood: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '10s', target: 50  }, // ramp up ke 50
                { duration: '30s', target: 50  }, // tahan
                { duration: '10s', target: 100 }, // spike ke 100
                { duration: '20s', target: 100 }, // tahan spike
                { duration: '10s', target: 0   }, // ramp down
            ],
        },
    },

    thresholds: {
        // Halaman harus respond < 3s untuk 95% request
        http_req_duration:  ['p(95)<3000'],
        // Minimal 90% halaman berhasil dimuat
        join_page_loaded:   ['rate>0.90'],
        // Minimal 85% WebSocket berhasil connect
        ws_connected:       ['rate>0.85'],
        // Error rate < 10%
        http_req_failed:    ['rate<0.10'],
    },
};

// ── Helper ───────────────────────────────────────────────────────────────────
function getCsrfToken(html) {
    const match = html.match(/name="csrf-token"\s+content="([^"]+)"/);
    return match ? match[1] : null;
}

// ── Main VU function ─────────────────────────────────────────────────────────
export default function () {
    const joinUrl = `${BASE_URL}/q/${SESSION_CODE}`;

    // ── 1. Load halaman join ──────────────────────────────────────────────────
    const t0 = Date.now();
    const joinRes = http.get(joinUrl, {
        headers: { 'Accept': 'text/html' },
    });
    const loadMs = Date.now() - t0;

    const joinOk = check(joinRes, {
        'join page status 200': (r) => r.status === 200,
        'join page has quiz title': (r) => r.body.includes('Quiz') || r.body.includes('quiz'),
    });

    joinPageLoaded.add(joinOk);
    pageLoadTime.add(loadMs);

    if (!joinOk) {
        console.error(`❌ Join page gagal: ${joinRes.status} — ${joinUrl}`);
        sleep(1);
        return;
    }

    // ── 2. Test WebSocket connection ke Reverb ────────────────────────────────
    // Reverb menggunakan protokol Pusher kompatibel di /app/{key}
    // VITE_REVERB_APP_KEY dari .env
    const reverbKey  = __ENV.REVERB_APP_KEY || 'GANTI_DENGAN_REVERB_APP_KEY';
    const reverbHost = __ENV.REVERB_HOST    || BASE_URL.replace('https://', '').replace('http://', '');
    const wsScheme   = BASE_URL.startsWith('https') ? 'wss' : 'ws';
    const wsUrl      = `${wsScheme}://${reverbHost}/app/${reverbKey}?protocol=7&client=js&version=8.0&flash=false`;

    const wsT0 = Date.now();
    const wsRes = ws.connect(wsUrl, {}, function (socket) {
        let connected = false;

        socket.on('open', function () {
            connected = true;
            wsConnectTime.add(Date.now() - wsT0);
        });

        socket.on('message', function (data) {
            try {
                const msg = JSON.parse(data);
                if (msg.event === 'pusher:connection_established') {
                    // Subscribe ke channel sesi
                    socket.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: { auth: '', channel: `session.${__ENV.SESSION_ID || '1'}` },
                    }));
                }
            } catch (_) {}
        });

        // Hold koneksi 5s lalu tutup
        socket.setTimeout(function () {
            socket.close();
        }, 5000);

        socket.on('close', function () {
            wsConnected.add(connected);
        });

        socket.on('error', function () {
            wsConnected.add(false);
        });
    });

    // ── 3. Simulasikan beban halaman quiz saat berjalan (opsional) ────────────
    // Ini merepresentasikan peserta yang sudah masuk dan sedang di halaman play
    const playUrl = `${BASE_URL}/q/${SESSION_CODE}`;
    const playRes = http.get(playUrl);
    check(playRes, { 'play page reachable': (r) => r.status < 500 });

    // Jeda realistis antar user actions (0.5–2s)
    sleep(Math.random() * 1.5 + 0.5);
}

/**
 * Setup: dijalankan sekali sebelum test
 */
export function setup() {
    console.log(`🚀 Mulai load test terhadap: ${BASE_URL}`);
    console.log(`📋 Session code: ${SESSION_CODE}`);
    console.log(`💡 Pastikan sesi quiz sudah dalam status 'running' di admin panel`);
    return {};
}

/**
 * Teardown: dijalankan sekali setelah test
 */
export function teardown(data) {
    console.log('✅ Load test selesai. Cek hasil di atas.');
    console.log('💡 Untuk hapus data test: php artisan quiz:loadtest {code} --clean');
}
