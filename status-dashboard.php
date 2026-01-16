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
    <title>Status - ResQTech</title>

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
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .device { padding: 16px; }
        .device-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
        .device-id { font-family: var(--font-display); font-weight: 900; font-size: 1.25rem; }
        .device-loc { font-family: var(--font-mono); color: var(--text-secondary); }
        .device-meta { margin-top: 10px; font-family: var(--font-mono); font-size: 0.875rem; color: var(--text-secondary); display: grid; gap: 4px; }
        .device.online { border-left: 8px solid var(--resq-lime); }
        .device.offline { border-left: 8px solid var(--resq-red); opacity: 0.9; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="logo-box">R</div>
                <div class="title">
                    <h1>Device Status</h1>
                    <p>Heartbeat Monitor</p>
                </div>
            </div>
            <nav class="nav">
                <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="nb-btn nb-btn-outline">🌐 <?php echo getCurrentLang() === 'th' ? 'EN' : 'TH'; ?></a>
                <button class="nb-btn nb-btn-outline" onclick="toggleTheme()">🌙</button>
                <a href="index.php" class="nb-btn nb-btn-primary">🏠 Home</a>
                <a href="dashboard.php" class="nb-btn nb-btn-warning">📊 Dashboard</a>
                <a href="perf-dashboard.php" class="nb-btn nb-btn-warning">⏱️ Latency</a>
                <a href="history-dashboard.php" class="nb-btn nb-btn-warning">🧾 History</a>
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
                    <span class="badge" id="overallBadge">-</span>
                </div>
                <div class="right">
                    <a class="nb-btn nb-btn-outline" href="api/check-status.php" target="_blank" rel="noopener">🧾 Raw JSON</a>
                </div>
            </div>
            <div style="height: 16px;"></div>
            <div class="controls">
                <div class="field">
                    <label for="auto">Auto Refresh</label>
                    <select id="auto" class="nb-input">
                        <option value="off">off</option>
                        <option value="2">2s</option>
                        <option value="5" selected>5s</option>
                        <option value="10">10s</option>
                    </select>
                </div>
                <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 Refresh</button>
                <span class="hint">สถานะ online: ภายใน 65 วินาทีล่าสุด</span>
            </div>
        </section>

        <section class="grid" id="devices"></section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
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
                    <span class="badge ${badgeClass}">${online ? 'ONLINE' : 'OFFLINE'}</span>
                </div>
                <div class="device-meta">
                    <div>last_seen: ${d.last_seen || '-'}</div>
                    <div>seconds_ago: ${secs === null ? '-' : String(secs)}</div>
                </div>
            `;
            return section;
        }

        async function fetchStatus() {
            elRefresh.disabled = true;
            elLive.textContent = 'LIVE';
            elLive.className = 'badge blue';
            try {
                const res = await fetch('api/check-status.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                const list = Array.isArray(data.devices_list) ? data.devices_list : [];

                elDevices.innerHTML = '';
                for (const d of list) elDevices.appendChild(deviceCard(d));
                if (list.length === 0) elDevices.innerHTML = '<div class="hint">ยังไม่มี heartbeat log</div>';

                elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();
                const connected = !!data.is_connected;
                elOverall.textContent = connected ? 'CONNECTED' : 'DISCONNECTED';
                elOverall.className = 'badge ' + (connected ? 'lime' : 'red');

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = 'ERROR';
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elDevices.innerHTML = '<div class="hint">Fetch failed</div>';
                elLive.textContent = 'ERROR';
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

