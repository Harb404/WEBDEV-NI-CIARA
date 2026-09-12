<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
$database = getDatabase();
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — ComMEETtee</title>
<link rel="stylesheet" href="style.css?v=<?php echo file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time(); ?>">
<script src="script.js?v=<?php echo file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time(); ?>" defer></script>
</head>
<body>

<header class="site-header">
  <div class="nav-wrap">
    <a href="index.php" class="brand" aria-label="HOME"><img src="Pictures/logo.png" alt="ComMEETtee" class="brand-logo"></a>
    <nav class="primary-nav" aria-label="Primary">
      <ul>
        <li><a href="index.php" class="nav-pill">Home</a></li>
        <li><a href="index.php#aspirants" class="nav-pill">Aspirants</a></li>
        <li><a href="index.php#clients" class="nav-pill">Clients</a></li>
        <li><a href="index.php#committees" class="nav-pill">Committees</a></li>
        <li><a href="index.php#about" class="nav-pill">About Us</a></li>
      </ul>
    </nav>
    <?php include __DIR__ . '/includes/nav-actions.php'; ?>
  </div>
</header>

<main>
  <section class="contact-page">
    <div class="wrap contact-page-grid">
      <div class="contact-page-info">
        <div class="eyebrow">Get In Touch</div>
        <h1 class="display">Contact Us</h1>
        <p class="contact-intro">Have a question about applying, posting an opening, or how ComMEETtee works? Reach out — we usually reply within a day.</p>
        <div class="contact-list">
          <div class="contact-row">
            <span class="contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M4 6l8 7 8-7" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
            <div><strong>Email</strong><span>hello@commeettee.com</span></div>
          </div>
          <div class="contact-row">
            <span class="contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.9 21 3 13.1 3 3.5c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
            <div><strong>Phone</strong><span>+63 915 532 4760</span></div>
          </div>
          <div class="contact-row">
            <span class="contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.4" stroke="currentColor" stroke-width="1.4"/></svg></span>
            <div><strong>Location</strong><span>Dumaguete, Philippines</span></div>
          </div>
        </div>
      </div>

      <div class="contact-page-chat">
        <div class="chat-widget">
          <div class="chat-widget-head">Live Chat<span class="chat-widget-status">We usually reply fast</span></div>
          <div class="chat-messages" id="chat-messages"><p class="chat-empty">Say hello — we're happy to help.</p></div>
          <form class="chat-form" id="chat-form">
            <input type="text" id="chat-name-input" placeholder="Your name" autocomplete="name" <?php echo $isLoggedIn ? 'hidden' : ''; ?>>
            <div class="chat-form-row">
              <input type="text" id="chat-message-input" placeholder="Type a message…" autocomplete="off" required>
              <button type="submit" aria-label="Send message"><svg viewBox="0 0 24 24" fill="none"><path d="M4 12l16-7-6.5 16-2.8-6.7L4 12z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>