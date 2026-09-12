<div class="nav-actions">
    <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="notif-wrap">
            <button class="nav-action notif-toggle" id="notif-toggle-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Application updates">
                <svg class="notif-bell" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3a6 6 0 0 0-6 6v3.2c0 .5-.16.99-.46 1.4L4 15.5c-.6.8-.02 2 .98 2h14.04c1 0 1.58-1.2.98-2l-1.54-1.9c-.3-.41-.46-.9-.46-1.4V9a6 6 0 0 0-6-6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9.5 19a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                <span class="notif-count" id="notif-count" style="display:none;">0</span>
            </button>
            <div class="notif-panel" id="notif-panel" aria-hidden="true">
                <div class="notif-panel-head">Updates</div>
                <div class="notif-list" id="notif-list"><p class="notif-empty">Nothing new yet.</p></div>
            </div>
        </div>
        <a href="account.php" class="link-ghost">MY ACCOUNT</a>
        <?php if (isAdmin()): ?><a href="admin.php" class="link-ghost">ADMIN</a><?php endif; ?>
        <a href="logout.php" class="link-ghost">LOG OUT</a>
    <?php else: ?>
        <a href="login.php" class="link-ghost">LOG IN</a>
        <span class="divider" aria-hidden="true"></span>
        <a href="register.php" class="link-ghost">SIGN UP</a>
    <?php endif; ?>
    <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</div>