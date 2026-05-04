/**
 * ═══════════════════════════════════════════════════════════════
 *  IGC Quiz — Puppeteer Browser Load Test
 * ═══════════════════════════════════════════════════════════════
 *
 * Simulasi user nyata menggunakan Chrome headless.
 * Alpine.js, Livewire, WebSocket Echo — semua jalan seperti browser asli.
 *
 * ── Install ────────────────────────────────────────────────────
 *   npm install puppeteer puppeteer-cluster puppeteer-extra puppeteer-extra-plugin-stealth
 *
 * ── Run ────────────────────────────────────────────────────────
 *   # Simulasi 20 user join ke waiting room (tanpa start quiz)
 *   node loadtest-browser.js --users=20 --phase=join
 *
 *   # Simulasi full: join + tunggu admin start + jawab semua soal
 *   # (Admin harus tekan Start Quiz secara manual setelah semua join)
 *   node loadtest-browser.js --users=10 --phase=full
 *
 *   # Lihat browser (non-headless) — debug mode, max 3 user
 *   node loadtest-browser.js --users=3 --phase=full --headed
 */

const { Cluster }  = require('puppeteer-cluster');
const puppeteer    = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

// Stealth mode: bypass Cloudflare bot detection
puppeteer.use(StealthPlugin());

// ── Config ────────────────────────────────────────────────────────────────────
const args = Object.fromEntries(
    process.argv.slice(2)
        .filter(a => a.startsWith('--'))
        .map(a => { const [k, v] = a.slice(2).split('='); return [k, v ?? true]; })
);

const BASE_URL      = args['base-url']    || 'https://igc.dylab.id';
const SESSION_CODE  = args['code']        || 'GANTI_KODE_SESI';
const TOTAL_USERS   = parseInt(args['users']  || '10');
const PHASE         = args['phase']       || 'join';   // join | full
const HEADED        = args['headed']      === true || args['headed'] === 'true';
const JOIN_DELAY_MS = parseInt(args['join-delay'] || '1500');  // delay antar user join (ms)

const JOIN_URL = `${BASE_URL}/q/${SESSION_CODE}`;

// ── Metrics ───────────────────────────────────────────────────────────────────
const results = {
    joined:       0,
    joinFailed:   0,
    answered:     0,
    finished:     0,
    errors:       [],
    joinTimes:    [],
    answerTimes:  [],
};

// ── Main ──────────────────────────────────────────────────────────────────────
(async () => {
    console.log('\n🚀 IGC Quiz — Browser Load Test (Puppeteer)');
    console.log(`   URL      : ${JOIN_URL}`);
    console.log(`   Users    : ${TOTAL_USERS}`);
    console.log(`   Phase    : ${PHASE}`);
    console.log(`   Headed   : ${HEADED}`);
    console.log(`   Delay    : ${JOIN_DELAY_MS}ms antar user\n`);

    const cluster = await Cluster.launch({
        concurrency: Cluster.CONCURRENCY_CONTEXT,  // 1 browser, N context (hemat RAM)
        maxConcurrency: TOTAL_USERS,
        puppeteerOptions: {
            headless: !HEADED,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-extensions',
                '--no-first-run',
                '--disable-default-apps',
            ],
        },
        monitor: false,
        timeout: 5 * 60 * 1000,  // 5 menit max per task
    });

    cluster.on('taskerror', (err, data) => {
        results.errors.push({ user: data?.userName || '?', error: err.message });
        console.error(`  ❌ [${data?.userName || '?'}] Error: ${err.message}`);
    });

    // Queue semua user
    for (let i = 1; i <= TOTAL_USERS; i++) {
        const userName = `Peserta-${String(i).padStart(2, '0')}`;

        // Stagger join: setiap user join dengan delay bertahap
        await sleep(JOIN_DELAY_MS);

        cluster.queue({ userName, userIndex: i }, async ({ page, data }) => {
            await simulateUser(page, data.userName, data.userIndex);
        });
    }

    await cluster.idle();
    await cluster.close();

    printSummary();
})();

// ── Simulasi 1 User ───────────────────────────────────────────────────────────
async function simulateUser(page, userName, userIndex) {
    // Bersihkan localStorage agar tidak ada cache dari test sebelumnya
    await page.goto(JOIN_URL, { waitUntil: 'networkidle2', timeout: 30000 });
    await page.evaluate((code) => {
        localStorage.removeItem('igc-quiz:participant:' + code);
    }, SESSION_CODE);

    // ── Step 1: JOIN ──────────────────────────────────────────────────────────
    const t0Join = Date.now();

    try {
        // Tunggu form join muncul
        await page.waitForSelector('input[name="name"]', { timeout: 15000 });

        // Isi nama
        await page.type('input[name="name"]', userName, { delay: 30 });

        // Klik tombol Gabung
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 20000 })
                .catch(() => {}),  // navigasi mungkin via Livewire redirect
            page.click('button[type="submit"]'),
        ]);

        // Tunggu sampai di halaman play (waiting room atau langsung running)
        await page.waitForFunction(
            () => document.querySelector('[wire\\:id]') !== null,
            { timeout: 20000 }
        );

        const joinMs = Date.now() - t0Join;
        results.joinTimes.push(joinMs);
        results.joined++;
        console.log(`  ✅ [${userName}] Joined (${joinMs}ms) — URL: ${page.url()}`);
    } catch (err) {
        results.joinFailed++;
        throw new Error(`Join failed: ${err.message}`);
    }

    if (PHASE === 'join') {
        // Tetap di halaman (simulasi user menunggu) selama 30 detik
        await sleep(30000);
        return;
    }

    // ── Step 2: TUNGGU QUIZ START ─────────────────────────────────────────────
    console.log(`  ⏳ [${userName}] Menunggu quiz dimulai…`);

    try {
        // Tunggu sampai muncul soal pertama (state berubah dari waiting ke running)
        await page.waitForSelector('.badge-brand', { timeout: 10 * 60 * 1000 }); // max 10 menit tunggu admin
        console.log(`  🎯 [${userName}] Quiz started!`);
    } catch {
        console.warn(`  ⚠ [${userName}] Timeout menunggu quiz start. Pastikan admin sudah tekan Start Quiz.`);
        return;
    }

    // ── Step 3: JAWAB SEMUA SOAL ──────────────────────────────────────────────
    let questionNum = 1;

    while (true) {
        const t0Ans = Date.now();

        try {
            // Tunggu option buttons muncul untuk soal yang sedang tampil
            await page.waitForSelector('button[wire\\:key^="opt-"]', { visible: true, timeout: 10000 });

            // Ambil semua option button yang visible
            const optionButtons = await page.$$eval(
                'button[wire\\:key^="opt-"]',
                (btns) => btns
                    .filter(b => b.offsetParent !== null)  // hanya yang visible
                    .map((b, i) => i)                      // ambil index
            );

            if (optionButtons.length === 0) {
                console.warn(`  ⚠ [${userName}] Soal ${questionNum}: tidak ada opsi visible`);
                break;
            }

            // Pilih opsi secara acak
            const randomIdx = Math.floor(Math.random() * optionButtons.length);
            const visibleOptions = await page.$$('button[wire\\:key^="opt-"]');
            const visibleFiltered = [];
            for (const btn of visibleOptions) {
                const visible = await btn.evaluate(el => el.offsetParent !== null);
                if (visible) visibleFiltered.push(btn);
            }

            if (visibleFiltered.length > 0) {
                const chosen = visibleFiltered[Math.floor(Math.random() * visibleFiltered.length)];
                await chosen.click();
                await sleep(300); // tunggu optimistic UI Alpine.js update
            }

            const ansMs = Date.now() - t0Ans;
            results.answerTimes.push(ansMs);
            results.answered++;

            // Cek apakah ini soal terakhir
            const isLast = await page.evaluate(() => {
                // Alpine.js data: idx === total - 1
                const el = document.querySelector('[x-data*="isLast"]');
                if (!el) return false;
                return el.__x?.$data?.isLast?.() ?? false;
            });

            if (isLast) {
                console.log(`  📝 [${userName}] Soal ${questionNum} (last) dijawab (${ansMs}ms)`);

                // Delay realistis membaca soal (3–8 detik)
                await sleep(randomBetween(3000, 8000));

                // Klik Selesai → buka modal konfirmasi
                await page.click('button.w-full.btn-lg');
                await sleep(500);

                // Konfirmasi di modal
                const yaBtn = await page.$('button[wire\\:click="finish"]');
                if (yaBtn) {
                    await yaBtn.click();
                    console.log(`  🏁 [${userName}] Selesai diklik`);
                }
                break;
            } else {
                console.log(`  📝 [${userName}] Soal ${questionNum} dijawab (${ansMs}ms)`);
                questionNum++;

                // Delay realistis membaca soal (3–10 detik)
                await sleep(randomBetween(3000, 10000));

                // Klik Selanjutnya
                await page.click('button.w-full.btn-lg');
                await sleep(500);
            }

        } catch (err) {
            // Mungkin quiz sudah selesai (timeout)
            const state = await page.evaluate(() => {
                return document.querySelector('.badge-success') ? 'finished' : 'unknown';
            });
            if (state === 'finished') break;
            throw new Error(`Answer Q${questionNum} failed: ${err.message}`);
        }
    }

    // ── Step 4: CEK HALAMAN HASIL ─────────────────────────────────────────────
    try {
        await page.waitForSelector('.text-emerald-700', { timeout: 15000 });
        const score = await page.$eval(
            '.text-brand-700',
            el => el.textContent.trim().replace(/\/100.*/, '') + '/100'
        ).catch(() => '?');
        results.finished++;
        console.log(`  🎉 [${userName}] Selesai! Skor: ${score}`);
    } catch {
        console.warn(`  ⚠ [${userName}] Tidak bisa baca halaman hasil`);
    }
}

// ── Print Summary ─────────────────────────────────────────────────────────────
function printSummary() {
    const avg = arr => arr.length ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0;
    const p95 = arr => {
        if (!arr.length) return 0;
        const sorted = [...arr].sort((a, b) => a - b);
        return sorted[Math.floor(sorted.length * 0.95)];
    };

    console.log('\n' + '═'.repeat(55));
    console.log('  HASIL LOAD TEST');
    console.log('═'.repeat(55));
    console.log(`  Total user   : ${TOTAL_USERS}`);
    console.log(`  Join sukses  : ${results.joined} (${results.joinFailed} gagal)`);
    console.log(`  Soal dijawab : ${results.answered}`);
    console.log(`  Selesai      : ${results.finished}`);
    console.log(`  Error        : ${results.errors.length}`);
    console.log('─'.repeat(55));
    console.log(`  Join time    : avg ${avg(results.joinTimes)}ms | p95 ${p95(results.joinTimes)}ms`);
    if (results.answerTimes.length) {
        console.log(`  Answer time  : avg ${avg(results.answerTimes)}ms | p95 ${p95(results.answerTimes)}ms`);
    }
    if (results.errors.length) {
        console.log('\n  Errors:');
        results.errors.forEach(e => console.log(`    [${e.user}] ${e.error}`));
    }
    console.log('═'.repeat(55) + '\n');
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function randomBetween(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
