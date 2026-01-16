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
    <title>Diagnostics - ResQTech</title>

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
        .row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border: 2px solid var(--nb-black); box-shadow: 3px 3px 0px var(--nb-black); font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: var(--resq-yellow); }
        .badge.red { background: var(--resq-red); color: white; }
        .badge.blue { background: var(--resq-blue); color: white; }
        .badge.lime { background: var(--resq-lime); color: var(--nb-black); }
        .badge.gray { background: #e5e7eb; color: var(--nb-black); }
        [data-theme="dark"] .badge.gray { background: #2b2f36; color: #e5e7eb; }
        .status-line { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .status-line .left { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .hint { font-size: 0.875rem; color: var(--text-secondary); }
        .k { font-family: var(--font-mono); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); }
        .v { font-family: var(--font-display); font-size: 1.75rem; font-weight: 900; }
        .mono { font-family: var(--font-mono); }
        .kv { display: grid; gap: 6px; }
        .list { display: grid; gap: 10px; }
        .item { border: 2px solid var(--nb-black); box-shadow: 3px 3px 0px var(--nb-black); padding: 10px 12px; background: var(--bg-card); }
        .item .top { display: flex; gap: 10px; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .item .bottom { margin-top: 8px; font-family: var(--font-mono); font-size: 0.875rem; color: var(--text-secondary); }
        .controls { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="logo-box">R</div>
                <div class="title">
                    <h1>Diagnostics</h1>
                    <p>Server ↔ LINE / Config</p>
                </div>
            </div>
            <nav class="nav">
                <a href="<?php echo getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th'); ?>" class="nb-btn nb-btn-outline">🌐 <?php echo getCurrentLang() === 'th' ? 'EN' : 'TH'; ?></a>
                <button class="nb-btn nb-btn-outline" onclick="toggleTheme()">🌙</button>
                <a href="index.php" class="nb-btn nb-btn-primary">🏠 Home</a>
                <a href="dashboard.php" class="nb-btn nb-btn-warning">📊 Dashboard</a>
                <a href="perf-dashboard.php" class="nb-btn nb-btn-warning">⏱️ Latency</a>
                <a href="status-dashboard.php" class="nb-btn nb-btn-warning">📡 Status</a>
                <a href="history-dashboard.php" class="nb-btn nb-btn-warning">🧾 History</a>
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
                </div>
                <div class="controls">
                    <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 Refresh</button>
                    <a class="nb-btn nb-btn-outline" href="api/connection-diagnostics.php" target="_blank" rel="noopener">🧾 Raw JSON</a>
                </div>
            </div>
            <div style="height: 12px;"></div>
            <div class="hint">หน้านี้ช่วยเช็ค DNS/TLS ไป LINE, สิทธิ์เขียน logs, และสถานะ config โดยไม่โชว์ secrets</div>
        </section>

        <section class="row" id="cards"></section>

        <section class="nb-card panel">
            <div class="badge gray">Hints</div>
            <div style="height: 12px;"></div>
            <div class="list" id="hints"></div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const elCards = document.getElementById('cards');
        const elHints = document.getElementById('hints');
        const elRefresh = document.getElementById('refreshBtn');
        const elLast = document.getElementById('lastUpdated');
        const elLive = document.getElementById('liveBadge');

        function badge(text, cls) {
            const b = document.createElement('span');
            b.className = 'badge ' + (cls || '');
            b.textContent = text;
            return b;
        }

        function card(title, color, main, subLines) {
            const section = document.createElement('section');
            section.className = 'nb-card panel';
            const sub = (subLines || []).map(s => `<div class="mono">${s}</div>`).join('');
            section.innerHTML = `
                <div class="badge ${color}">${title}</div>
                <div style="height: 10px;"></div>
                <div class="kv">
                    <div class="v">${main}</div>
                    <div class="mono" style="color: var(--text-secondary);">${sub}</div>
                </div>
            `;
            return section;
        }

        function addHint(text, tone) {
            const div = document.createElement('div');
            div.className = 'item';
            const top = document.createElement('div');
            top.className = 'top';
            top.appendChild(badge(tone, tone === 'OK' ? 'lime' : tone === 'WARN' ? '' : 'red'));
            const t = document.createElement('div');
            t.className = 'mono';
            t.textContent = text;
            top.appendChild(t);
            div.appendChild(top);
            elHints.appendChild(div);
        }

        async function fetchDiag() {
            elRefresh.disabled = true;
            elLive.textContent = 'LIVE';
            elLive.className = 'badge blue';
            elHints.innerHTML = '';
            try {
                const res = await fetch('api/connection-diagnostics.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();

                const net = data.network || {};
                const cfg = data.config || {};
                const fs = data.filesystem || {};
                const php = data.php || {};

                const dnsOk = !!net.dns_ok;
                const tlsOk = !!net.tls_ok;
                const connectMs = typeof net.connect_ms === 'number' ? net.connect_ms : null;
                const logWritable = !!fs.log_dir_writable;

                const tokenSet = !!cfg.line_channel_access_token_set;
                const userSet = !!cfg.line_user_id_set;
                const espKeySet = !!cfg.esp32_api_key_set;
                const sslVerify = cfg.line_ssl_verify !== undefined ? !!cfg.line_ssl_verify : true;

                elCards.innerHTML = '';
                elCards.appendChild(card('LINE DNS', dnsOk ? 'lime' : 'red', dnsOk ? 'OK' : 'FAIL', [
                    'host=' + (net.line_host || '-'),
                    'ip=' + (net.resolved_ip || '-')
                ]));

                elCards.appendChild(card('LINE TLS', tlsOk ? 'lime' : 'red', tlsOk ? 'OK' : 'FAIL', [
                    'connect_ms=' + (connectMs === null ? '-' : String(connectMs)),
                    'ssl_verify=' + String(sslVerify),
                    'errno=' + String(net.errno || 0)
                ]));

                elCards.appendChild(card('CONFIG', (tokenSet && userSet && espKeySet) ? 'lime' : 'red', (tokenSet && userSet && espKeySet) ? 'READY' : 'MISSING', [
                    'esp32_api_key_set=' + String(espKeySet),
                    'line_user_id_set=' + String(userSet),
                    'line_token_set=' + String(tokenSet)
                ]));

                elCards.appendChild(card('FILESYSTEM', logWritable ? 'lime' : 'red', logWritable ? 'WRITABLE' : 'NOT WRITABLE', [
                    'log_dir=' + (fs.log_dir || '-'),
                    'exists=' + String(!!fs.log_dir_exists)
                ]));

                elCards.appendChild(card('PHP/OPENSSL', 'blue', (php.php_version || '-'), [
                    String(php.openssl || '-')
                ]));

                if (!dnsOk) addHint('DNS ไป api.line.me ไม่ได้ (ตรวจเน็ต/DNS ของเครื่อง Server)', 'FAIL');
                if (dnsOk && !tlsOk) addHint('ต่อ TLS ไป LINE ไม่ได้ (มักเป็น cert/CA ใน PHP หรือ proxy/AV)', 'FAIL');
                if (!tokenSet) addHint('ยังไม่ตั้งค่า LINE_CHANNEL_ACCESS_TOKEN ใน .env', 'WARN');
                if (!userSet) addHint('ยังไม่ตั้งค่า LINE_USER_ID ใน .env', 'WARN');
                if (!espKeySet) addHint('ยังไม่ตั้งค่า ESP32_API_KEY ใน .env', 'WARN');
                if (!logWritable) addHint('โฟลเดอร์ logs เขียนไม่ได้ (permissions)', 'FAIL');
                if (dnsOk && tlsOk && tokenSet && userSet && espKeySet && logWritable) addHint('ทุกอย่างดูพร้อมแล้ว', 'OK');

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = 'ERROR';
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elCards.innerHTML = '';
                elLive.textContent = 'ERROR';
                elLive.className = 'badge red';
                addHint('เรียก API diagnostics ไม่ได้ (อาจยังไม่ล็อกอิน หรือ server error)', 'FAIL');
            } finally {
                elRefresh.disabled = false;
            }
        }

        elRefresh.addEventListener('click', fetchDiag);
        fetchDiag();
    </script>
</body>
</html>

