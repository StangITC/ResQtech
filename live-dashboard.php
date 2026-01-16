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
    <title>Live - ResQTech</title>

    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/monitoring-ui.css') ?>">
</head>
<body>
    <?php renderNavigation('live', 'Live Feed', 'Real-time SSE Stream'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="connBadge">CONNECTING</span>
                    <span class="badge" id="lastEvent">Last: -</span>
                    <span class="badge" id="msgCount">Msgs: 0</span>
                </div>
                <div class="right">
                    <button id="reconnectBtn" class="nb-btn nb-btn-primary">🔌 Reconnect</button>
                    <a class="nb-btn nb-btn-outline" href="api/stream.php" target="_blank" rel="noopener">📡 Stream</a>
                </div>
            </div>
            <div style="height: 12px;"></div>
            <div class="hint">ถ้าเครือข่าย/Proxy buffer หนัก อาจเห็นหน่วงได้ ให้ดู Diagnostics และ Status ควบคู่</div>
        </section>

        <section class="row">
            <section class="nb-card panel">
                <div class="badge">Latest Heartbeat</div>
                <div style="height: 10px;"></div>
                <div class="mini">
                    <div class="k">is_connected</div>
                    <div class="v" id="hbConn">-</div>
                </div>
                <div style="height: 8px;"></div>
                <div class="mini">
                    <div class="k">last_heartbeat</div>
                    <div class="v" style="font-size: 1rem; font-family: var(--font-mono);" id="hbTime">-</div>
                </div>
            </section>

            <section class="nb-card panel">
                <div class="badge red">Latest Emergency</div>
                <div style="height: 10px;"></div>
                <div class="mini">
                    <div class="k">last_event</div>
                    <div class="v" style="font-size: 1rem; font-family: var(--font-mono);" id="emTime">-</div>
                </div>
                <div style="height: 8px;"></div>
                <div class="mini">
                    <div class="k">seconds_ago</div>
                    <div class="v" id="emAgo">-</div>
                </div>
            </section>
        </section>

        <section class="nb-card panel">
            <div class="badge">Event Feed</div>
            <div style="height: 12px;"></div>
            <div class="feed" id="feed"></div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const elConn = document.getElementById('connBadge');
        const elLast = document.getElementById('lastEvent');
        const elCount = document.getElementById('msgCount');
        const elFeed = document.getElementById('feed');
        const elReconnect = document.getElementById('reconnectBtn');

        const elHbConn = document.getElementById('hbConn');
        const elHbTime = document.getElementById('hbTime');
        const elEmTime = document.getElementById('emTime');
        const elEmAgo = document.getElementById('emAgo');

        let es = null;
        let msgCount = 0;

        function setConn(state) {
            if (state === 'ok') {
                elConn.textContent = 'CONNECTED';
                elConn.className = 'badge lime';
            } else if (state === 'err') {
                elConn.textContent = 'ERROR';
                elConn.className = 'badge red';
            } else {
                elConn.textContent = 'CONNECTING';
                elConn.className = 'badge blue';
            }
        }

        function addLine(type, data) {
            msgCount++;
            elCount.textContent = 'Msgs: ' + msgCount;
            elLast.textContent = 'Last: ' + new Date().toLocaleTimeString();

            const div = document.createElement('div');
            div.className = 'line';
            const head = document.createElement('div');
            head.innerHTML = `<span class="badge ${type === 'emergency' ? 'red' : type === 'heartbeat' ? 'lime' : 'blue'}">${type}</span> <span class="hint">${new Date().toLocaleString()}</span>`;
            div.appendChild(head);

            const body = document.createElement('div');
            body.className = 'hint';
            body.textContent = JSON.stringify(data);
            div.appendChild(body);

            elFeed.prepend(div);
            while (elFeed.childElementCount > 60) {
                elFeed.removeChild(elFeed.lastElementChild);
            }
        }

        function connect() {
            if (es) {
                es.close();
                es = null;
            }
            setConn('wait');
            msgCount = 0;
            elCount.textContent = 'Msgs: 0';
            elFeed.innerHTML = '';

            es = new EventSource('api/stream.php');
            es.onopen = () => setConn('ok');
            es.onerror = () => setConn('err');

            es.addEventListener('heartbeat', (ev) => {
                try {
                    const data = JSON.parse(ev.data);
                    elHbConn.textContent = data.is_connected ? 'CONNECTED' : 'DISCONNECTED';
                    elHbTime.textContent = data.last_heartbeat || '-';
                    addLine('heartbeat', data);
                } catch (_) {}
            });

            es.addEventListener('emergency', (ev) => {
                try {
                    const data = JSON.parse(ev.data);
                    elEmTime.textContent = data.last_event || '-';
                    elEmAgo.textContent = typeof data.seconds_ago === 'number' ? String(data.seconds_ago) : '-';
                    addLine('emergency', data);
                } catch (_) {}
            });

            es.addEventListener('ping', (ev) => {
                addLine('ping', { t: ev.data });
            });

            es.addEventListener('error', (ev) => {
                addLine('error', { message: ev.data });
            });
        }

        elReconnect.addEventListener('click', connect);
        connect();
    </script>
</body>
</html>

