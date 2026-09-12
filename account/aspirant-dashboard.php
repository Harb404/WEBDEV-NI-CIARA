<section class="dash-section profile-edit-section">
  <div class="profile-avatar-block">
    <div class="profile-avatar-placeholder"><?php echo htmlspecialchars(strtoupper(substr($profile['name'], 0, 1))); ?></div>
    <div>
      <p class="profile-name" style="margin:0 0 4px;"><?php echo htmlspecialchars($profile['name']); ?></p>
      <p class="profile-panel-hint"><?php echo htmlspecialchars($profile['email']); ?></p>
    </div>
  </div>

  <h2>My Profile</h2>
  <form method="post" class="account-form">
    <input type="hidden" name="action" value="update_profile">
    <label>Program
      <select name="program">
        <option value="">Select your program…</option>
        <?php if ($profile['program'] !== '' && !in_array($profile['program'], norsuProgramOptions(), true)): ?>
          <option value="<?php echo htmlspecialchars($profile['program']); ?>" selected><?php echo htmlspecialchars($profile['program']); ?></option>
        <?php endif; ?>
        <?php foreach (norsuProgramOptions() as $programOption): ?>
          <option value="<?php echo htmlspecialchars($programOption); ?>" <?php echo $profile['program'] === $programOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($programOption); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Year Level
      <select name="year_level">
        <option value="">Select your year level…</option>
        <?php if ($profile['year_level'] !== '' && !in_array($profile['year_level'], yearLevelOptions(), true)): ?>
          <option value="<?php echo htmlspecialchars($profile['year_level']); ?>" selected><?php echo htmlspecialchars($profile['year_level']); ?></option>
        <?php endif; ?>
        <?php foreach (yearLevelOptions() as $yearOption): ?>
          <option value="<?php echo htmlspecialchars($yearOption); ?>" <?php echo $profile['year_level'] === $yearOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($yearOption); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Skills &amp; Interests<input type="text" name="skills" value="<?php echo htmlspecialchars($profile['skills']); ?>" placeholder="e.g. Graphic Design, Video Editing"></label>
    <label>Availability<input type="text" name="availability" value="<?php echo htmlspecialchars($profile['availability']); ?>" placeholder="e.g. Weekends, 5–10 hrs/week"></label>
    <label>About Me<textarea name="bio" rows="3" placeholder="A short introduction clients will see on your applications."><?php echo htmlspecialchars($profile['bio']); ?></textarea></label>
    <button class="cart-button" type="submit">Save Profile</button>
  </form>
</section>

<section class="dash-section">
  <h2>My Applications</h2>
  <?php if (!$myApplications): ?>
    <p class="postings-empty">You haven't applied to any committees yet. <a href="index.php#committees">Browse open postings</a>.</p>
  <?php else: ?>
    <div class="application-list">
      <?php foreach ($myApplications as $application): ?>
        <div class="application-row">
          <div>
            <h4><?php echo htmlspecialchars($application['posting_title']); ?></h4>
            <p class="profile-panel-hint"><?php echo htmlspecialchars($application['category_name']); ?> · <?php echo htmlspecialchars($application['client_org'] ?: $application['client_name']); ?></p>
          </div>
          <span class="application-status application-status--<?php echo htmlspecialchars($application['status']); ?>"><?php echo htmlspecialchars(ucfirst($application['status'])); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>