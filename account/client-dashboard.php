<section class="dash-section">
  <h2>Post a New Opening</h2>
  <form method="post" class="account-form">
    <input type="hidden" name="action" value="create_posting">
    <label>Title<input type="text" name="title" required placeholder="e.g. Documentation Committee Head"></label>
    <label>Committee Category
      <select name="category_id" required>
        <?php foreach ($categories as $category): ?>
          <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Description<textarea name="description" rows="3" placeholder="What will this role be responsible for?"></textarea></label>
    <label>Skills Needed<input type="text" name="skills_needed" placeholder="e.g. Photography, Video Editing"></label>
    <label>Number of Slots<input type="number" name="slots" min="1" value="1"></label>
    <button class="cart-button" type="submit">Post Opening</button>
  </form>
</section>

<section class="dash-section">
  <h2>My Postings</h2>
  <?php if (!$myPostings): ?>
    <p class="postings-empty">You haven't posted any openings yet — use the form above to create one.</p>
  <?php else: ?>
    <div class="posting-manage-list">
      <?php foreach ($myPostings as $posting): ?>
        <div class="posting-manage-row">
          <div>
            <h4><?php echo htmlspecialchars($posting['title']); ?></h4>
            <p class="profile-panel-hint"><?php echo htmlspecialchars($posting['category_name']); ?> · <?php echo (int) $posting['slots']; ?> slot<?php echo (int) $posting['slots'] === 1 ? '' : 's'; ?> · <span class="application-status application-status--<?php echo $posting['status'] === 'open' ? 'pending' : 'declined'; ?>"><?php echo htmlspecialchars(ucfirst($posting['status'])); ?></span></p>
          </div>
          <form method="post">
            <input type="hidden" name="posting_id" value="<?php echo (int) $posting['id']; ?>">
            <?php if ($posting['status'] === 'open'): ?>
              <input type="hidden" name="action" value="close_posting">
              <button class="cart-button posting-withdraw-button" type="submit">Close</button>
            <?php else: ?>
              <input type="hidden" name="action" value="reopen_posting">
              <button class="cart-button" type="submit">Reopen</button>
            <?php endif; ?>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="dash-section">
  <h2>Applicants</h2>
  <?php if (!$applicants): ?>
    <p class="postings-empty">No one has applied to your postings yet.</p>
  <?php else: ?>
    <div class="application-list">
      <?php foreach ($applicants as $applicant): ?>
        <div class="application-row application-row--wide">
          <div>
            <h4><?php echo htmlspecialchars($applicant['aspirant_name']); ?> <span class="profile-panel-hint">applied to <?php echo htmlspecialchars($applicant['posting_title']); ?></span></h4>
            <p class="profile-panel-hint"><?php echo htmlspecialchars($applicant['program']); ?> <?php echo $applicant['year_level'] ? '· ' . htmlspecialchars($applicant['year_level']) : ''; ?><?php echo $applicant['aspirant_skills'] ? ' · Skills: ' . htmlspecialchars($applicant['aspirant_skills']) : ''; ?></p>
          </div>
          <div class="application-row-actions">
            <span class="application-status application-status--<?php echo htmlspecialchars($applicant['status']); ?>"><?php echo htmlspecialchars(ucfirst($applicant['status'])); ?></span>
            <?php if (in_array($applicant['status'], ['pending', 'reviewed'], true)): ?>
              <form method="post">
                <input type="hidden" name="action" value="decide_application">
                <input type="hidden" name="application_id" value="<?php echo (int) $applicant['id']; ?>">
                <input type="hidden" name="decision" value="accepted">
                <button class="cart-button" type="submit">Accept</button>
              </form>
              <form method="post">
                <input type="hidden" name="action" value="decide_application">
                <input type="hidden" name="application_id" value="<?php echo (int) $applicant['id']; ?>">
                <input type="hidden" name="decision" value="declined">
                <button class="cart-button posting-withdraw-button" type="submit">Decline</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>