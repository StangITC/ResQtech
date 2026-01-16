<?php

require_once __DIR__ . '/includes/init.php';
requireLogin();

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#0066ff">
    <title>History - ResQTech</title>

    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <style>
        body { display: block; min-height: 100vh; padding: 0; }
        .header { background: var(--bg-card); border-bottom: var(--nb-border-thick); padding: 16px 24px; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .header-left { display: flex; align-items: center; gap: 14px; }
        .logo-box { width: 48px; height: 48px; background: var(--resq-blue); border: 3px solid var(--nb-black); box-shadow: 4px 4px 0px var(--nb-black); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 900; font-size: 1.5rem; color: white; transform: rotate(-5deg); }
        .title h1 { font-family: var(--font-display); font-size: 1.25rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .title p { font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 2px; }
        .nav { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .main { max-width: 1400px; margin: 0 auto; padding: 24px; display: grid; gap: 20px; }
        .panel { padding: 16px; }
        .controls { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .field { display: grid; gap: 6px; }
        .field label { font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border: 2px solid var(--nb-black); box-shadow: 3px 3px 0px var(--nb-black); font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: var(--resq-yellow); }
        .badge.blue { background: var(--resq-blue); color: white; }
        .badge.lime { background: var(--resq-lime); color: var(--nb-black); }
        .badge.red { background: var(--resq-red); color: white; }
        .status-line { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .status-line .left { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .hint { font-size: 0.875rem; color: var(--text-secondary); }
        .table-wrap { overflow: auto; border: var(--nb-border-thick); box-shadow: var(--nb-shadow-lg); background: var(--bg-card); }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { border-bottom: 2px solid var(--nb-black); padding: 10px 12px; text-align: left; vertical-align: top; }
        th { position: sticky; top: 0; background: var(--resq-yellow); font-family: var(--font-display); font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        td { font-family: var(--font-mono); font-size: 0.875rem; }
        .muted { color: var(--text-secondary); }
        .right { text-align: right; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="logo-box">R</div>
                <div class="title">
                    <h1>History</h1>
                    <p>Emergency Events</p>
                </div>
            </div>
            <nav class="nav">
                <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="nb-btn nb-btn-outline">🌐 <?php echo getCurrentLang() === 'th' ? 'EN' : 'TH'; ?></a>
                <button class="nb-btn nb-btn-outline" onclick="toggleTheme()">🌙</button>
                <a href="index.php" class="nb-btn nb-btn-primary">🏠 Home</a>
                <a href="dashboard.php" class="nb-btn nb-btn-warning">📊 Dashboard</a>
                <a href="perf-dashboard.php" class="nb-btn nb-btn-warning">⏱️ Latency</a>
                <a href="status-dashboard.php" class="nb-btn nb-btn-warning">📡 Status</a>
                <a href="diagnostics-dashboard.php" class="nb-btn nb-btn-warning">🧪 Diagnostics</a>
                <a href="live-dashboard.php" class="nb-btn nb-btn-warning">🟢 Live</a>
                <a href="logout.php" class="nb-btn nb-btn-danger">🚪 Logout</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge">LIVE</span>
                    <span class="badge" id="lastUpdated">Last: -</span>
                    <span class="badge lime" id="countBadge">Count: -</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/get-history.php" target="_blank" rel="noopener">🧾 Raw JSON</a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="device">Device</label>
                    <input id="device" class="nb-input" type="text" placeholder="ESP32-001">
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" class="nb-input" type="text" placeholder="Main Entrance">
                </div>
                <div class="field">
                    <label for="auto">Auto Refresh</label>
                    <select id="auto" class="nb-input">
                        <option value="off">off</option>
                        <option value="5">5s</option>
                        <option value="10" selected>10s</option>
                        <option value="30">30s</option>
                    </select>
                </div>
                <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 Refresh</button>
                <button id="exportBtn" class="nb-btn nb-btn-outline">⬇️ Export CSV</button>
                <span class="hint">API ส่งกลับล่าสุด 50 รายการ แล้วค่อยกรองในหน้า</span>
            </div>
        </section>

        <section class="nb-card panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>time</th>
                            <th>device</th>
                            <th>location</th>
                            <th>event</th>
                            <th>status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr><td colspan="5" class="muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const elDevice = document.getElementById('device');
        const elLocation = document.getElementById('location');
        const elAuto = document.getElementById('auto');
        const elRefresh = document.getElementById('refreshBtn');
        const elExport = document.getElementById('exportBtn');
        const elBody = document.getElementById('tbody');
        const elLast = document.getElementById('lastUpdated');
        const elCount = document.getElementById('countBadge');
        const elLive = document.getElementById('liveBadge');

        let timer = null;
        let lastRows = [];

        function norm(s) {
            return String(s || '').trim().toLowerCase();
        }

        function filterRows(rows) {
            const d = norm(elDevice.value);
            const l = norm(elLocation.value);
            return rows.filter(r => {
                const okD = !d || norm(r.device).includes(d);
                const okL = !l || norm(r.location).includes(l);
                return okD && okL;
            });
        }

        function render(rows) {
            elBody.innerHTML = '';
            if (!Array.isArray(rows) || rows.length === 0) {
                elBody.innerHTML = '<tr><td colspan="5" class="muted">No data</td></tr>';
                return;
            }
            for (const r of rows) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${r.time || '-'}</td>
                    <td>${r.device || '-'}</td>
                    <td>${r.location || '-'}</td>
                    <td>${r.event || '-'}</td>
                    <td>${r.status || '-'}</td>
                `;
                elBody.appendChild(tr);
            }
        }

        function resetTimer() {
            if (timer) clearInterval(timer);
            timer = null;
            const v = elAuto.value;
            if (v !== 'off') {
                timer = setInterval(fetchHistory, Number(v) * 1000);
            }
        }

        function downloadCsv(rows) {
            const headers = ['time', 'device', 'location', 'event', 'status'];
            const escape = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
            const lines = [headers.map(escape).join(',')];
            for (const r of rows) {
                lines.push(headers.map(h => escape(r[h])).join(','));
            }
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'resqtech_history.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        async function fetchHistory() {
            elRefresh.disabled = true;
            elLive.textContent = 'LIVE';
            elLive.className = 'badge blue';
            try {
                const res = await fetch('api/get-history.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                const rows = Array.isArray(data.data) ? data.data : [];
                lastRows = rows;
                const filtered = filterRows(rows);
                render(filtered);
                elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();
                elCount.textContent = 'Count: ' + String(filtered.length);

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = 'ERROR';
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elBody.innerHTML = '<tr><td colspan="5" class="muted">Fetch failed</td></tr>';
                elLive.textContent = 'ERROR';
                elLive.className = 'badge red';
            } finally {
                elRefresh.disabled = false;
            }
        }

        elRefresh.addEventListener('click', fetchHistory);
        elDevice.addEventListener('input', () => render(filterRows(lastRows)));
        elLocation.addEventListener('input', () => render(filterRows(lastRows)));
        elAuto.addEventListener('change', () => { resetTimer(); fetchHistory(); });
        elExport.addEventListener('click', () => downloadCsv(filterRows(lastRows)));

        resetTimer();
        fetchHistory();
    </script>
</body>
</html>

