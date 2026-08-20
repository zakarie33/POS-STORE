<!-- Expose CSRF token globally -->
<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

<header class="top-header">
    <div class="header-left">
        <button class="icon-btn" id="sidebarToggle">
            <i class="ph ph-list"></i>
        </button>
    </div>
    
    <div class="header-actions">
        <button class="icon-btn" id="themeToggle" title="Toggle Dark Mode">
            <i class="ph ph-moon"></i>
        </button>
        
        <div style="position: relative;" id="notificationContainer">
            <button class="icon-btn" title="Notifications" id="notificationBtn">
                <i class="ph ph-bell"></i>
                <span id="notifBadge" style="display: none; position: absolute; top: 4px; right: 4px; background: var(--danger-color); color: white; border-radius: 50%; font-size: 0.65rem; min-width: 18px; height: 18px; line-height: 18px; text-align: center; font-weight: bold; border: 2px solid var(--header-bg);">0</span>
            </button>
            <div id="notificationDropdown" class="notification-dropdown">
                <div class="notification-header">
                    <h4 style="margin: 0; font-size: 0.95rem;">Notifications (<span id="notifHeaderCount">0</span>)</h4>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline" id="markAllReadBtn" style="padding: 0.1rem 0.4rem; font-size: 0.75rem; border: none; color: var(--primary-color);">Mark read</button>
                        <button class="btn btn-outline" id="clearAllNotifsBtn" style="padding: 0.1rem 0.4rem; font-size: 0.75rem; border: none; color: var(--danger-color);">Clear</button>
                    </div>
                </div>
                <div class="notification-list" id="notificationList">
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                        <i class="ph ph-spinner ph-spin" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: 1rem; padding-left: 1rem; border-left: 1px solid var(--border-color);">
            <div style="text-align: right;">
                <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;"><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></div>
            </div>
            <div style="width: 36px; height: 36px; background: var(--primary-light); color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
            </div>
        </div>

        <a href="../api/logout.php" class="icon-btn" style="color: var(--danger-color);" title="Logout">
            <i class="ph ph-sign-out"></i>
        </a>
    </div>
</header>
<script>
    // Expose user role to JS for notification polling
    window.currentUserRole = '<?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?>';
</script>
