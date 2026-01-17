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
    <title><?= htmlspecialchars(t('page_latency_title')) ?> - ResQTech</title>

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
    <?php renderNavigation('latency', 'page_latency_title', 'page_latency_subtitle'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge"><?= htmlspecialchars(t('common_live')) ?></span>
                    <span class="badge" id="lastUpdated"><?= htmlspecialchars(t('common_last')) ?>: -</span>
                    <span class="badge lime" id="countBadge"><?= htmlspecialchars(t('common_count')) ?>: -</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/perf-report.php" target="_blank" rel="noopener">🧾 <?= htmlspecialchars(t('common_raw_json')) ?></a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="action"><?= htmlspecialchars(t('common_action')) ?></label>
                    <select id="action" class="nb-input">
                        <option value=""><?= htmlspecialchars(t('common_all')) ?></option>
                        <option value="emergency" selected><?= htmlspecialchars(t('latency_emergency')) ?></option>
                        <option value="heartbeat"><?= htmlspecialchars(t('latency_heartbeat')) ?></option>
                        <option value="auth_fail"><?= htmlspecialchars(t('latency_auth_fail')) ?></option>
                        <option value="rate_limit"><?= htmlspecialchars(t('latency_rate_limit')) ?></option>
                        <option value="invalid_action"><?= htmlspecialchars(t('latency_invalid_action')) ?></option>
                    </select>
                </div>
                <div class="field">
                    <label for="limit"><?= htmlspecialchars(t('common_limit')) ?></label>
                    <input id="limit" class="nb-input" type="number" min="1" max="5000" value="500">
                </div>
                <div class="field">
                    <label for="auto"><?= htmlspecialchars(t('common_auto_refresh')) ?></label>
                    <select id="auto" class="nb-input">
                        <option value="off"><?= htmlspecialchars(t('common_off')) ?></option>
                        <option value="5">5s</option>
                        <option value="10" selected>10s</option>
                        <option value="30">30s</option>
                    </select>
                </div>
                <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 <?= htmlspecialchars(t('common_refresh')) ?></button>
                <span class="hint"><?= htmlspecialchars(t('latency_hint')) ?></span>
            </div>
        </section>

        <section class="row" id="summaryRow"></section>

        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge"><?= htmlspecialchars(t('latency_recent_events')) ?></span>
                    <span class="badge" id="tableHint">-</span>
                </div>
            </div>
            <div style="height: 12px;"></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(t('latency_table_time')) ?></th>
                            <th><?= htmlspecialchars(t('latency_table_action')) ?></th>
                            <th><?= htmlspecialchars(t('latency_table_device')) ?></th>
                            <th><?= htmlspecialchars(t('latency_table_location')) ?></th>
                            <th class="right"><?= htmlspecialchars(t('latency_table_seq')) ?></th>
                            <th class="right"><?= htmlspecialchars(t('latency_table_server_ms')) ?></th>
                            <th class="right"><?= htmlspecialchars(t('latency_table_line_ms')) ?></th>
                            <th class="right"><?= htmlspecialchars(t('latency_table_line_http')) ?></th>
                            <th><?= htmlspecialchars(t('latency_table_request_id')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="eventsBody">
                        <tr><td colspan="9" class="muted"><?= htmlspecialchars(t('common_loading')) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const I18N = <?= json_encode([
            'live' => t('common_live'),
            'error' => t('common_error'),
            'last' => t('common_last'),
            'count' => t('common_count'),
            'no_data' => t('common_no_data'),
            'fetch_failed' => t('common_fetch_failed'),
            'showing_last' => t('latency_showing_last'),
            'events' => t('latency_events'),
            'summary_emergency' => t('latency_emergency'),
            'summary_heartbeat' => t('latency_heartbeat'),
            'summary_auth_fail' => t('latency_auth_fail'),
            'summary_rate_limit' => t('latency_rate_limit'),
            'summary_invalid_action' => t('latency_invalid_action')
        ], JSON_UNESCAPED_UNICODE) ?>;

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

            if (emergency) elSummary.appendChild(metricCard(I18N.summary_emergency, 'red', emergency.server_total_ms, emergency.line_api_ms));
            if (heartbeat) elSummary.appendChild(metricCard(I18N.summary_heartbeat, 'lime', heartbeat.server_total_ms, heartbeat.line_api_ms));
            if (authFail) elSummary.appendChild(metricCard(I18N.summary_auth_fail, 'blue', authFail.server_total_ms, authFail.line_api_ms));
            if (rateLimit) elSummary.appendChild(metricCard(I18N.summary_rate_limit, 'blue', rateLimit.server_total_ms, rateLimit.line_api_ms));
            if (invalidAction) elSummary.appendChild(metricCard(I18N.summary_invalid_action, 'blue', invalidAction.server_total_ms, invalidAction.line_api_ms));
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
                cell.textContent = I18N.no_data;
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
            elLive.textContent = I18N.live;
            elLive.className = 'badge blue';
            try {
                const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();

                elLast.textContent = I18N.last + ': ' + new Date().toLocaleTimeString();
                elCount.textContent = I18N.count + ': ' + String(data.count ?? '-');
                elHint.textContent = I18N.showing_last + ' ' + String(data.count ?? 0) + ' ' + I18N.events;

                renderSummary(data.summary || {});
                renderEvents(data.events || []);

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = I18N.error;
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elLive.textContent = I18N.error;
                elLive.className = 'badge red';
                elEvents.innerHTML = '<tr><td colspan="9" class="muted">' + I18N.fetch_failed + '</td></tr>';
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

