<?php
require_once __DIR__ . '/db.php';
start_session();
$current_page = basename($_SERVER['PHP_SELF']);
$user_type = get_user_type();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'PuneRooms - Student Accommodation'; ?></title>
    <meta name="description" content="Pune's #1 student accommodation platform. Find rooms, PGs, and shared spaces near your college.">
    <!-- Preconnect for speed -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Non-blocking font load -->
    <link rel="stylesheet" href="style.css">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap"></noscript>
    <!-- Inline critical theme script to prevent FOUC -->
    <script>
    (function(){
        var t = localStorage.getItem('pr_theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>
</head>
<body>
<div id="scrollBar"></div>
<div class="deco-blob deco-blob-1"></div>
<div class="deco-blob deco-blob-2"></div>
<div class="offline-badge" id="offlineBadge">📡 You are offline</div>

<!-- NAV -->
<nav class="nav">
    <a class="nav-brand" href="index.php">
        <div class="nav-brand-mark">🏠</div>
        <div class="nav-brand-text">Pune<span>Rooms</span></div>
    </a>
    <div class="nav-links">
        <a href="index.php" class="nav-link <?php echo $current_page==='index.php'?'active':''; ?>">Find</a>
        <?php if (is_logged_in()): ?>
        <a href="saved.php" class="nav-link <?php echo $current_page==='saved.php'?'active':''; ?>">❤ Saved</a>
        <?php if (is_owner()): ?>
        <a href="list.php" class="nav-link <?php echo $current_page==='list.php'?'active':''; ?>">List Room</a>
        <?php endif; ?>
        <a href="predict.php" class="nav-link <?php echo $current_page==='predict.php'?'active':''; ?>">🔮 Predict</a>
        <?php if (is_admin()): ?>
        <a href="admin.php" class="nav-link <?php echo $current_page==='admin.php'?'active':''; ?>">⚙ Admin</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if (is_logged_in()): ?>
            <div class="user-pill">
                <div class="user-pill-avatar"><?php echo strtoupper(substr(get_user_name(),0,1)); ?></div>
                <span class="user-pill-name"><?php echo htmlspecialchars(get_user_name()); ?></span>
                <a href="logout.php" style="border:none;background:none;color:var(--text3);cursor:pointer;font-size:.75rem;padding-left:.25rem;text-decoration:none;" title="Logout">✕</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-nav-outline">Log In</a>
            <a href="signup.php" class="btn-nav-fill">Sign Up</a>
        <?php endif; ?>
        <!-- 3-dot theme picker -->
        <div class="theme-picker-wrap" id="themePickerWrap">
            <button class="theme-dots-btn" id="themeDotsBtn" onclick="toggleThemePicker()" title="Change theme" aria-label="Theme picker">
                <span class="tdot"></span>
                <span class="tdot"></span>
                <span class="tdot"></span>
            </button>
            <div class="theme-picker-popup" id="themePickerPopup">
                <div class="theme-picker-title">Choose Theme</div>
                <div class="theme-picker-grid">
                    <button class="tpick-btn" data-theme="light"  onclick="setTheme('light')"  title="Light">
                        <span class="tpick-swatch" style="background:#EBE9E1;border-color:#ccc;"></span>
                        <span class="tpick-label">Light</span>
                    </button>
                    <button class="tpick-btn" data-theme="dark"   onclick="setTheme('dark')"   title="Dark">
                        <span class="tpick-swatch" style="background:#251C0A;border-color:#5a3a10;"></span>
                        <span class="tpick-label">Dark</span>
                    </button>
                    <button class="tpick-btn" data-theme="black"  onclick="setTheme('black')"  title="Black">
                        <span class="tpick-swatch" style="background:#000;border-color:#444;"></span>
                        <span class="tpick-label">Black</span>
                    </button>
                    <button class="tpick-btn" data-theme="ocean"  onclick="setTheme('ocean')"  title="Ocean Blue">
                        <span class="tpick-swatch" style="background:#0ea5e9;border-color:#0284c7;"></span>
                        <span class="tpick-label">Ocean</span>
                    </button>
                    <button class="tpick-btn" data-theme="forest" onclick="setTheme('forest')" title="Forest Green">
                        <span class="tpick-swatch" style="background:#16a34a;border-color:#15803d;"></span>
                        <span class="tpick-label">Forest</span>
                    </button>
                    <button class="tpick-btn" data-theme="purple" onclick="setTheme('purple')" title="Purple">
                        <span class="tpick-swatch" style="background:#7c3aed;border-color:#6d28d9;"></span>
                        <span class="tpick-label">Purple</span>
                    </button>
                </div>
            </div>
        </div>
        <button class="hamburger" id="hamburger" onclick="toggleMobileNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- MOBILE NAV -->
<div class="mobile-nav" id="mobileNav">
    <a href="index.php" class="nav-link" style="text-align:left">🔍 Find Rooms</a>
    <?php if (is_logged_in()): ?>
    <a href="saved.php" class="nav-link" style="text-align:left">❤ Saved</a>
    <?php if (is_owner()): ?>
    <a href="list.php" class="nav-link" style="text-align:left">🏠 List Room</a>
    <?php endif; ?>
    <a href="predict.php" class="nav-link" style="text-align:left">🔮 Predict Rent</a>
    <?php if (is_admin()): ?>
    <a href="admin.php" class="nav-link" style="text-align:left">⚙ Admin</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="text-align:left">👋 Log Out</a>
    <?php else: ?>
    <a href="login.php" class="nav-link" style="text-align:left">🔑 Log In</a>
    <a href="signup.php" class="nav-link" style="text-align:left">✨ Sign Up</a>
    <?php endif; ?>
</div>

<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-bottom-nav">
    <a href="index.php" class="mob-nav-btn <?php echo $current_page==='index.php'?'active':''; ?>"><span class="ico">🔍</span>Find</a>
    <?php if (is_logged_in()): ?>
    <a href="saved.php" class="mob-nav-btn <?php echo $current_page==='saved.php'?'active':''; ?>"><span class="ico">❤️</span>Saved</a>
    <a href="predict.php" class="mob-nav-btn <?php echo $current_page==='predict.php'?'active':''; ?>"><span class="ico">🔮</span>Predict</a>
    <?php if (is_owner()): ?>
    <a href="list.php" class="mob-nav-btn <?php echo $current_page==='list.php'?'active':''; ?>"><span class="ico">➕</span>List</a>
    <?php endif; ?>
    <?php if (is_admin()): ?>
    <a href="admin.php" class="mob-nav-btn <?php echo $current_page==='admin.php'?'active':''; ?>"><span class="ico">⚙️</span>Admin</a>
    <?php endif; ?>
    <?php endif; ?>
</nav>

<main class="main">
<?php
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars(trim($_GET['msg']));
    $msg_type = in_array($_GET['mtype']??'', ['success','error','info']) ? $_GET['mtype'] : 'info';
    echo '<div class="container" style="padding-top:1rem;"><div class="php-msg '.$msg_type.'">'.$msg.'</div></div>';
}
?>
