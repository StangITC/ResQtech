<?php
/**
 * Compact Navigation Component - ResQTech System
 * ส่วนหัวนำทางแบบกระชับและสวยงาม
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('RESQTECH_APP')) {
    http_response_code(403);
    exit('Access Denied');
}

/**
 * Render the compact navigation header
 * @param string $currentPage - Current page identifier for highlighting active link
 * @param string $pageTitle - Title to display in the header
 * @param string $pageSubtitle - Subtitle to display below the title
 */
function renderNavigation(string $currentPage = '', string $pageTitle = 'ResQTech', string $pageSubtitle = ''): void
{
    $langUrl = getLangUrl(getCurrentLang() === 'th' ? 'en' : 'th');
    $langLabel = getCurrentLang() === 'th' ? 'EN' : 'TH';
    $titleText = t($pageTitle);
    $subtitleText = $pageSubtitle !== '' ? t($pageSubtitle) : '';

    // Define navigation items - compact version
    $navItems = [
        ['id' => 'home', 'href' => 'index.php', 'icon' => '🏠', 'label' => 'nav_home'],
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => '📊', 'label' => 'nav_dashboard'],
        ['id' => 'control', 'href' => 'control-room.php', 'icon' => '🖥️', 'label' => 'nav_control'],
        ['id' => 'status', 'href' => 'status-dashboard.php', 'icon' => '📡', 'label' => 'nav_status'],
        ['id' => 'history', 'href' => 'history-dashboard.php', 'icon' => '📜', 'label' => 'nav_history'],
        ['id' => 'live', 'href' => 'live-dashboard.php', 'icon' => '🔴', 'label' => 'nav_live'],
    ];
    
    // Secondary items (in dropdown)
    $moreItems = [
        ['id' => 'latency', 'href' => 'perf-dashboard.php', 'icon' => '⏱️', 'label' => 'nav_latency'],
        ['id' => 'diagnostics', 'href' => 'diagnostics-dashboard.php', 'icon' => '🧪', 'label' => 'nav_diagnostics'],
    ];
    ?>
    <style>
        /* Compact Navigation Styles */
        .compact-nav {
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%);
            border-bottom: 3px solid var(--nb-black);
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        [data-theme="dark"] .compact-nav {
            border-bottom-color: var(--resq-cyan);
            box-shadow: 0 4px 20px rgba(92, 220, 232, 0.1);
        }
        
        .compact-nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        
        .compact-nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .compact-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--resq-red) 0%, #ff6b6b 100%);
            border: 2px solid var(--nb-black);
            box-shadow: 3px 3px 0px var(--nb-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 1.2rem;
            color: white;
            text-decoration: none;
            transform: rotate(-3deg);
            transition: all 0.2s ease;
        }
        
        .compact-logo:hover {
            transform: rotate(0deg) scale(1.05);
            box-shadow: 4px 4px 0px var(--nb-black);
        }
        
        [data-theme="dark"] .compact-logo {
            border-color: var(--resq-red);
            box-shadow: 3px 3px 0px rgba(255, 107, 107, 0.3);
        }
        
        .compact-title {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .compact-title h1 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
            margin: 0;
        }
        
        .compact-title p {
            font-size: 0.65rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .compact-nav-center {
            display: flex;
            gap: 4px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            font-family: var(--font-display);
            text-decoration: none;
            color: var(--text-primary);
            background: var(--bg-secondary);
            border: 2px solid transparent;
            border-radius: 6px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        
        .nav-item:hover {
            background: var(--resq-yellow);
            border-color: var(--nb-black);
            transform: translateY(-2px);
            box-shadow: 2px 2px 0px var(--nb-black);
        }
        
        .nav-item.active {
            background: var(--resq-blue);
            color: white;
            border-color: var(--nb-black);
            box-shadow: 2px 2px 0px var(--nb-black);
        }
        
        .nav-item .nav-icon {
            font-size: 0.85rem;
        }
        
        .nav-item .nav-label {
            display: none;
        }
        
        @media (min-width: 900px) {
            .nav-item .nav-label {
                display: inline;
            }
        }
        
        .compact-nav-right {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .nav-action {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            font-size: 0.9rem;
            background: var(--bg-secondary);
            border: 2px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .nav-action:hover {
            background: var(--resq-lime);
            border-color: var(--nb-black);
            transform: translateY(-2px);
        }
        
        .nav-action.danger:hover {
            background: var(--resq-red);
            color: white;
        }
        
        /* More dropdown */
        .nav-more {
            position: relative;
        }
        
        .nav-more-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--bg-card);
            border: 2px solid var(--nb-black);
            box-shadow: 4px 4px 0px var(--nb-black);
            border-radius: 8px;
            padding: 6px;
            display: none;
            min-width: 140px;
            z-index: 1001;
        }
        
        .nav-more:hover .nav-more-menu,
        .nav-more:focus-within .nav-more-menu {
            display: block;
        }
        
        .nav-more-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-primary);
            border-radius: 4px;
            transition: all 0.15s ease;
        }
        
        .nav-more-item:hover {
            background: var(--resq-yellow);
        }
        
        [data-theme="dark"] .nav-item:hover {
            background: var(--resq-cyan);
            color: var(--nb-black);
        }
        
        [data-theme="dark"] .nav-action:hover {
            background: var(--resq-cyan);
            color: var(--nb-black);
        }
        
        [data-theme="dark"] .nav-more-menu {
            border-color: var(--resq-cyan);
            box-shadow: 4px 4px 0px rgba(92, 220, 232, 0.2);
        }
        
        /* Mobile responsive */
        @media (max-width: 640px) {
            .compact-nav {
                padding: 8px 12px;
            }
            
            .compact-title h1 {
                font-size: 0.85rem;
            }
            
            .compact-title p {
                display: none;
            }
            
            .nav-item {
                padding: 5px 8px;
            }
        }
    </style>
    
    <header class="compact-nav">
        <div class="compact-nav-inner">
            <div class="compact-nav-left">
                <a href="index.php" class="compact-logo">R</a>
                <div class="compact-title">
                    <h1><?php echo sanitizeInput($titleText); ?></h1>
                    <?php if ($subtitleText): ?>
                    <p><?php echo sanitizeInput($subtitleText); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <nav class="compact-nav-center">
                <?php foreach ($navItems as $item): ?>
                <a href="<?php echo $item['href']; ?>" 
                   class="nav-item<?php echo $currentPage === $item['id'] ? ' active' : ''; ?>"
                   <?php echo $currentPage === $item['id'] ? 'aria-current="page"' : ''; ?>>
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <span class="nav-label"><?php echo sanitizeInput(t($item['label'])); ?></span>
                </a>
                <?php endforeach; ?>
                
                <?php if (!empty($moreItems)): ?>
                <div class="nav-more">
                    <button class="nav-item" type="button">
                        <span class="nav-icon">⋯</span>
                        <span class="nav-label"><?php echo sanitizeInput(t('nav_more')); ?></span>
                    </button>
                    <div class="nav-more-menu">
                        <?php foreach ($moreItems as $item): ?>
                        <a href="<?php echo $item['href']; ?>" class="nav-more-item">
                            <span><?php echo $item['icon']; ?></span>
                            <span><?php echo sanitizeInput(t($item['label'])); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
            
            <div class="compact-nav-right">
                <a href="<?php echo $langUrl; ?>" class="nav-action" title="<?php echo sanitizeInput(t('language')); ?>">
                    <?php echo $langLabel; ?>
                </a>
                <button class="nav-action" onclick="toggleTheme()" title="<?php echo sanitizeInput(t('theme_toggle')); ?>">🌙</button>
                <a href="logout.php" class="nav-action danger" title="<?php echo sanitizeInput(t('nav_logout')); ?>">🚪</a>
            </div>
        </div>
    </header>
    <?php
}

/**
 * Render a compact navigation for War Room / Control Room
 */
function renderWarRoomNav(): void
{
    ?>
    <style>
        .war-nav {
            display: flex;
            gap: 6px;
        }
        .war-nav-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.08);
            border: 1px solid #444;
            border-radius: 4px;
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .war-nav-item:hover {
            background: rgba(0, 255, 157, 0.15);
            border-color: var(--neon-green);
            color: var(--neon-green);
        }
        .war-nav-item.danger:hover {
            background: rgba(255, 0, 85, 0.15);
            border-color: var(--neon-red);
            color: var(--neon-red);
        }
    </style>
    <nav class="war-nav">
        <a href="index.php" class="war-nav-item" title="Home">🏠</a>
        <a href="dashboard.php" class="war-nav-item" title="Dashboard">📊</a>
        <a href="status-dashboard.php" class="war-nav-item" title="Status">📡</a>
        <a href="history-dashboard.php" class="war-nav-item" title="History">📜</a>
        <a href="live-dashboard.php" class="war-nav-item" title="Live">🔴</a>
        <a href="logout.php" class="war-nav-item danger" title="Logout">🚪</a>
    </nav>
    <?php
}
