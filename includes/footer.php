<footer class="site-footer">
  <div class="wrap footer-inner">
    <div class="footer-brand">
      <img src="Pictures/logo.png" alt="ComMEETtee" class="brand-logo">
      <p>Connecting aspirants and clients through university committees.</p>
    </div>
    <nav class="footer-links" aria-label="Footer">
      <a href="index.php">Home</a>
      <a href="index.php#committees">Committees</a>
      <a href="contact.php">Contact Us</a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="account.php">My Account</a>
      <?php else: ?>
        <a href="login.php">Log In</a>
      <?php endif; ?>
    </nav>
    <div class="footer-contact">
      <p>hello@commeettee.com</p>
      <p>+63 915 532 4760</p>
      <p>Dumaguete, Philippines</p>
    </div>
  </div>
  <p class="footer-copy">&copy; <?php echo date('Y'); ?> ComMEETtee. All rights reserved.</p>
</footer>
