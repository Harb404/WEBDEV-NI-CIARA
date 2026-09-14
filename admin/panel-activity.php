<div class="dash-panel">
  <h3>Platform Activity</h3>
  <?php if (!$activityLog): ?>
    <p class="dash-empty">No activity recorded yet.</p>
  <?php else: ?>
    <div class="dash-row-list">
      <?php foreach ($activityLog as $entry): ?>
        <div class="dash-row-item">
          <div>
            <strong><?php echo htmlspecialchars($entry['action']); ?></strong>
            <?php if ($entry['details'] !== ''): ?><span class="dash-row-meta"><?php echo htmlspecialchars($entry['details']); ?></span><?php endif; ?>
          </div>
          <span class="dash-row-meta"><?php echo htmlspecialchars($entry['actor_name']); ?> · <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($entry['created_at']))); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>