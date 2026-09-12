<aside class="dash-sidebar">
  <div class="dash-brand">
    <img src="Pictures/logo.png" alt="ComMEETtee" onerror="this.style.display='none'">
    <span>ComMEETtee</span>
  </div>
  <nav class="dash-nav">
    <a href="admin.php?panel=overview" class="<?php echo $activePanel === 'overview' ? 'is-active' : ''; ?>">Overview</a>
    <a href="admin.php?panel=postings" class="<?php echo $activePanel === 'postings' ? 'is-active' : ''; ?>">Postings</a>
    <a href="admin.php?panel=applications" class="<?php echo $activePanel === 'applications' ? 'is-active' : ''; ?>">Applications</a>
    <a href="admin.php?panel=users" class="<?php echo $activePanel === 'users' ? 'is-active' : ''; ?>">Users</a>
    <a href="admin.php?panel=messages" class="<?php echo $activePanel === 'messages' ? 'is-active' : ''; ?>">Messages</a>
  </nav>
  <a href="logout.php" class="dash-logout">Log Out</a>
</aside>