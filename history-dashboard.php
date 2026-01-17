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
    <title><?= htmlspecialchars(t('page_history_title')) ?> - ResQTech</title>

    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/monitoring-ui.css') ?>">
</head>
<body>
    <?php renderNavigation('history', 'page_history_title', 'page_history_subtitle'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge"><?= htmlspecialchars(t('common_live')) ?></span>
                    <span class="badge" id="lastUpdated"><?= htmlspecialchars(t('common_last')) ?>: -</span>
                    <span class="badge lime" id="countBadge"><?= htmlspecialchars(t('common_count')) ?>: -</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/get-history.php" target="_blank" rel="noopener">🧾 <?= htmlspecialchars(t('common_raw_json')) ?></a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="device"><?= htmlspecialchars(t('history_device')) ?></label>
                    <input id="device" class="nb-input" type="text" placeholder="ESP32-001">
                </div>
                <div class="field">
                    <label for="location"><?= htmlspecialchars(t('history_location')) ?></label>
                    <input id="location" class="nb-input" type="text" placeholder="Main Entrance">
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
                <button id="exportBtn" class="nb-btn nb-btn-outline">⬇️ <?= htmlspecialchars(t('history_export_csv')) ?></button>
                <span class="hint"><?= htmlspecialchars(t('history_hint')) ?></span>
            </div>
        </section>

        <section class="nb-card panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(t('history_table_time')) ?></th>
                            <th><?= htmlspecialchars(t('history_table_device')) ?></th>
                            <th><?= htmlspecialchars(t('history_table_location')) ?></th>
                            <th><?= htmlspecialchars(t('history_table_event')) ?></th>
                            <th><?= htmlspecialchars(t('history_table_status')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr><td colspan="5" class="muted"><?= htmlspecialchars(t('common_loading')) ?></td></tr>
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
            'fetch_failed' => t('common_fetch_failed')
        ], JSON_UNESCAPED_UNICODE) ?>;

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
                elBody.innerHTML = '<tr><td colspan="5" class="muted">' + I18N.no_data + '</td></tr>';
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
            elLive.textContent = I18N.live;
            elLive.className = 'badge blue';
            try {
                const res = await fetch('api/get-history.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                const rows = Array.isArray(data.data) ? data.data : [];
                lastRows = rows;
                const filtered = filterRows(rows);
                render(filtered);
                elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();
                elLast.textContent = I18N.last + ': ' + new Date().toLocaleTimeString();
                elCount.textContent = I18N.count + ': ' + String(filtered.length);

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = I18N.error;
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elBody.innerHTML = '<tr><td colspan="5" class="muted">' + I18N.fetch_failed + '</td></tr>';
                elLive.textContent = I18N.error;
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

