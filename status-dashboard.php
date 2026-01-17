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
    <title><?= htmlspecialchars(t('page_status_title')) ?> - ResQTech</title>

    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/monitoring-ui.css') ?>">
</head>
<body>
    <?php renderNavigation('status', 'page_status_title', 'page_status_subtitle'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge"><?= htmlspecialchars(t('common_live')) ?></span>
                    <span class="badge" id="lastUpdated"><?= htmlspecialchars(t('common_last')) ?>: -</span>
                    <span class="badge" id="overallBadge">-</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/check-status.php" target="_blank" rel="noopener">🧾 <?= htmlspecialchars(t('common_raw_json')) ?></a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="auto"><?= htmlspecialchars(t('common_auto_refresh')) ?></label>
                    <select id="auto" class="nb-input">
                        <option value="off"><?= htmlspecialchars(t('common_off')) ?></option>
                        <option value="2">2s</option>
                        <option value="5" selected>5s</option>
                        <option value="10">10s</option>
                    </select>
                </div>
                <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 <?= htmlspecialchars(t('common_refresh')) ?></button>
                <span class="hint"><?= htmlspecialchars(t('status_hint')) ?></span>
            </div>
        </section>

        <section class="grid" id="devices"></section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const I18N = <?= json_encode([
            'live' => t('common_live'),
            'error' => t('common_error'),
            'last' => t('common_last'),
            'connected' => t('status_connected'),
            'disconnected' => t('status_disconnected'),
            'online' => t('status_online'),
            'offline' => t('status_offline'),
            'last_seen' => t('status_last_seen'),
            'seconds_ago' => t('status_seconds_ago'),
            'no_heartbeat' => t('status_no_heartbeat'),
            'fetch_failed' => t('common_fetch_failed')
        ], JSON_UNESCAPED_UNICODE) ?>;

        const elDevices = document.getElementById('devices');
        const elAuto = document.getElementById('auto');
        const elRefresh = document.getElementById('refreshBtn');
        const elLast = document.getElementById('lastUpdated');
        const elOverall = document.getElementById('overallBadge');
        const elLive = document.getElementById('liveBadge');

        let timer = null;

        function resetTimer() {
            if (timer) clearInterval(timer);
            timer = null;
            const v = elAuto.value;
            if (v !== 'off') timer = setInterval(fetchStatus, Number(v) * 1000);
        }

        function deviceCard(d) {
            const online = !!d.is_online;
            const section = document.createElement('section');
            section.className = 'nb-card device ' + (online ? 'online' : 'offline');
            const badgeClass = online ? 'lime' : 'red';
            const secs = typeof d.seconds_ago === 'number' ? d.seconds_ago : null;
            section.innerHTML = `
                <div class="device-top">
                    <div>
                        <div class="device-id">${d.id || '-'}</div>
                        <div class="device-loc">${d.location || '-'}</div>
                    </div>
                    <span class="badge ${badgeClass}">${online ? I18N.online : I18N.offline}</span>
                </div>
                <div class="device-meta">
                    <div>${I18N.last_seen}: ${d.last_seen || '-'}</div>
                    <div>${I18N.seconds_ago}: ${secs === null ? '-' : String(secs)}</div>
                </div>
            `;
            return section;
        }

        async function fetchStatus() {
            elRefresh.disabled = true;
            elLive.textContent = I18N.live;
            elLive.className = 'badge blue';
            try {
                const res = await fetch('api/check-status.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                const list = Array.isArray(data.devices_list) ? data.devices_list : [];

                elDevices.innerHTML = '';
                for (const d of list) elDevices.appendChild(deviceCard(d));
                if (list.length === 0) elDevices.innerHTML = '<div class="hint">' + I18N.no_heartbeat + '</div>';

                elLast.textContent = I18N.last + ': ' + new Date().toLocaleTimeString();
                const connected = !!data.is_connected;
                elOverall.textContent = connected ? I18N.connected : I18N.disconnected;
                elOverall.className = 'badge ' + (connected ? 'lime' : 'red');

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = I18N.error;
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elDevices.innerHTML = '<div class="hint">' + I18N.fetch_failed + '</div>';
                elLive.textContent = I18N.error;
                elLive.className = 'badge red';
            } finally {
                elRefresh.disabled = false;
            }
        }

        elRefresh.addEventListener('click', fetchStatus);
        elAuto.addEventListener('change', () => { resetTimer(); fetchStatus(); });
        resetTimer();
        fetchStatus();
    </script>
</body>
</html>

