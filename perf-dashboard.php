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
    <title>Latency Monitor - ResQTech</title>

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
        .row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .metric { display: grid; gap: 8px; }
        .metric .k { font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); }
        .metric .v { font-family: var(--font-display); font-size: 1.75rem; font-weight: 900; }
        .metric .s { font-family: var(--font-mono); font-size: 0.875rem; }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border: 2px solid var(--nb-black); box-shadow: 3px 3px 0px var(--nb-black); font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: var(--resq-yellow); }
        .badge.red { background: var(--resq-red); color: white; }
        .badge.blue { background: var(--resq-blue); color: white; }
        .badge.lime { background: var(--resq-lime); color: var(--nb-black); }
        .table-wrap { overflow: auto; border: var(--nb-border-thick); box-shadow: var(--nb-shadow-lg); background: var(--bg-card); }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { border-bottom: 2px solid var(--nb-black); padding: 10px 12px; text-align: left; vertical-align: top; }
        th { position: sticky; top: 0; background: var(--resq-yellow); font-family: var(--font-display); font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        td { font-family: var(--font-mono); font-size: 0.875rem; }
        .muted { color: var(--text-secondary); }
        .right { text-align: right; }
        .mono { font-family: var(--font-mono); }
        .status-line { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .status-line .left { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .status-line .right { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .hint { font-size: 0.875rem; color: var(--text-secondary); }
    </style>
</head>
<body>
    <?php renderNavigation('latency', 'Latency Monitor', 'ESP32 → Server → LINE'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge">LIVE</span>
                    <span class="badge" id="lastUpdated">Last: -</span>
                    <span class="badge lime" id="countBadge">Count: -</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/perf-report.php" target="_blank" rel="noopener">🧾 Raw JSON</a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="action">Action</label>
                    <select id="action" class="nb-input">
                        <option value="">all</option>
                        <option value="emergency" selected>emergency</option>
                        <option value="heartbeat">heartbeat</option>
                        <option value="auth_fail">auth_fail</option>
                        <option value="rate_limit">rate_limit</option>
                        <option value="invalid_action">invalid_action</option>
                    </select>
                </div>
                <div class="field">
                    <label for="limit">Limit</label>
                    <input id="limit" class="nb-input" type="number" min="1" max="5000" value="500">
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
                <span class="hint">ดู p50/p95/p99 ที่ฝั่ง Server และเวลา LINE API ตอบกลับ</span>
            </div>
        </section>

        <section class="row" id="summaryRow"></section>

        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge">Recent Events</span>
                    <span class="badge" id="tableHint">-</span>
                </div>
            </div>
            <div style="height: 12px;"></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>time</th>
                            <th>action</th>
                            <th>device</th>
                            <th>location</th>
                            <th class="right">seq</th>
                            <th class="right">server_ms</th>
                            <th class="right">line_ms</th>
                            <th class="right">line_http</th>
                            <th>request_id</th>
                        </tr>
                    </thead>
                    <tbody id="eventsBody">
                        <tr><td colspan="9" class="muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const elAction = document.getElementById('action');
        const elLimit = document.getElementById('limit');
        const elAuto = document.getElementById('auto');
        const elRefresh = document.getElementById('refreshBtn');
        const elSummary = document.getElementById('summaryRow');
        const elEvents = document.getElementById('eventsBody');
        const elLast = document.getElementById('lastUpdated');
        const elCount = document.getElementById('countBadge');
        const elHint = document.getElementById('tableHint');
        const elLive = document.getElementById('liveBadge');

        let timer = null;

        function fmt(n) {
            if (n === null || n === undefined) return '-';
            if (typeof n !== 'number' || !isFinite(n)) return '-';
            return n.toFixed(0);
        }

        function fmtFloat(n) {
            if (n === null || n === undefined) return '-';
            if (typeof n !== 'number' || !isFinite(n)) return '-';
            return n.toFixed(1);
        }

        function fmtTime(ms) {
            if (!ms || typeof ms !== 'number') return '-';
            const d = new Date(ms);
            return d.toLocaleString();
        }

        function metricCard(title, color, server, line) {
            const serverText = server ? `p50 ${fmtFloat(server.p50)} / p95 ${fmtFloat(server.p95)} / p99 ${fmtFloat(server.p99)}` : '-';
            const lineText = line ? `p50 ${fmtFloat(line.p50)} / p95 ${fmtFloat(line.p95)} / p99 ${fmtFloat(line.p99)}` : '-';

            const serverCount = server && typeof server.count === 'number' ? server.count : 0;
            const lineCount = line && typeof line.count === 'number' ? line.count : 0;

            const card = document.createElement('section');
            card.className = 'nb-card metric';
            card.innerHTML = `
                <div class="badge ${color}">${title}</div>
                <div class="metric">
                    <div class="k">server_total_ms</div>
                    <div class="v">${server ? fmtFloat(server.p50) : '-'}</div>
                    <div class="s muted">${serverText} · n=${serverCount}</div>
                </div>
                <div class="metric">
                    <div class="k">line_api_ms</div>
                    <div class="v">${line ? fmtFloat(line.p50) : '-'}</div>
                    <div class="s muted">${lineText} · n=${lineCount}</div>
                </div>
            `;
            return card;
        }

        function renderSummary(summary) {
            elSummary.innerHTML = '';
            if (!summary || typeof summary !== 'object') return;

            const emergency = summary.emergency || null;
            const heartbeat = summary.heartbeat || null;
            const authFail = summary.auth_fail || null;
            const rateLimit = summary.rate_limit || null;
            const invalidAction = summary.invalid_action || null;

            if (emergency) elSummary.appendChild(metricCard('EMERGENCY', 'red', emergency.server_total_ms, emergency.line_api_ms));
            if (heartbeat) elSummary.appendChild(metricCard('HEARTBEAT', 'lime', heartbeat.server_total_ms, heartbeat.line_api_ms));
            if (authFail) elSummary.appendChild(metricCard('AUTH_FAIL', 'blue', authFail.server_total_ms, authFail.line_api_ms));
            if (rateLimit) elSummary.appendChild(metricCard('RATE_LIMIT', 'blue', rateLimit.server_total_ms, rateLimit.line_api_ms));
            if (invalidAction) elSummary.appendChild(metricCard('INVALID_ACTION', 'blue', invalidAction.server_total_ms, invalidAction.line_api_ms));
        }

        function td(text, cls) {
            const cell = document.createElement('td');
            if (cls) cell.className = cls;
            cell.textContent = text;
            return cell;
        }

        function renderEvents(events) {
            elEvents.innerHTML = '';
            if (!Array.isArray(events) || events.length === 0) {
                const tr = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 9;
                cell.className = 'muted';
                cell.textContent = 'No events';
                tr.appendChild(cell);
                elEvents.appendChild(tr);
                return;
            }

            for (const e of events.slice().reverse()) {
                const tr = document.createElement('tr');
                tr.appendChild(td(fmtTime(e.server_recv_ms)));
                tr.appendChild(td(String(e.action || '-')));
                tr.appendChild(td(String(e.device_id || '-')));
                tr.appendChild(td(String(e.location || '-')));
                tr.appendChild(td(e.seq === null || e.seq === undefined ? '-' : String(e.seq), 'right'));
                tr.appendChild(td(e.server_total_ms === undefined ? '-' : String(e.server_total_ms), 'right'));
                tr.appendChild(td(e.line_api_ms === undefined ? '-' : String(e.line_api_ms), 'right'));
                tr.appendChild(td(e.line_http_code === undefined ? '-' : String(e.line_http_code), 'right'));
                tr.appendChild(td(String(e.request_id || '-')));
                elEvents.appendChild(tr);
            }
        }

        function buildUrl() {
            const params = new URLSearchParams();
            const limit = Number(elLimit.value || 500);
            params.set('limit', String(Math.max(1, Math.min(limit, 5000))));
            const action = elAction.value;
            if (action) params.set('action', action);
            params.set('_t', String(Date.now()));
            return 'api/perf-report.php?' + params.toString();
        }

        async function fetchPerf() {
            const url = buildUrl();
            elRefresh.disabled = true;
            elLive.textContent = 'LIVE';
            elLive.className = 'badge blue';
            try {
                const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();

                elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();
                elCount.textContent = 'Count: ' + String(data.count ?? '-');
                elHint.textContent = 'Showing last ' + String(data.count ?? 0) + ' events';

                renderSummary(data.summary || {});
                renderEvents(data.events || []);

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = 'ERROR';
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elLive.textContent = 'ERROR';
                elLive.className = 'badge red';
                elEvents.innerHTML = '<tr><td colspan="9" class="muted">Fetch failed</td></tr>';
            } finally {
                elRefresh.disabled = false;
            }
        }

        function resetTimer() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            const v = elAuto.value;
            if (v !== 'off') {
                const ms = Number(v) * 1000;
                timer = setInterval(fetchPerf, ms);
            }
        }

        elRefresh.addEventListener('click', fetchPerf);
        elAction.addEventListener('change', fetchPerf);
        elLimit.addEventListener('change', fetchPerf);
        elAuto.addEventListener('change', () => { resetTimer(); fetchPerf(); });

        resetTimer();
        fetchPerf();
    </script>
</body>
</html>

