<div class="dash-panel">
  <h3>Reports</h3>
  <?php if (!$allReports): ?>
    <p class="dash-empty">No reports yet.</p>
  <?php else: ?>
    <div class="dash-row-list">
      <?php foreach ($allReports as $report): ?>
        <div class="dash-row-item dash-row-item--stack">
          <div class="dash-report-head">
            <span class="dash-badge dash-badge--<?php echo $report['target_type'] === 'posting' ? 'muted' : 'bad'; ?>"><?php echo $report['target_type'] === 'posting' ? 'Posting' : 'Account'; ?></span>
            <strong><?php echo htmlspecialchars($report['target_label']); ?></strong>
            <span class="dash-badge dash-badge--<?php echo $report['status'] === 'pending' ? 'muted' : ($report['status'] === 'resolved' ? 'good' : 'bad'); ?>"><?php echo htmlspecialchars(ucfirst($report['status'])); ?></span>
          </div>
          <p class="dash-report-reason"><?php echo htmlspecialchars($report['reason']); ?></p>
          <span class="dash-row-meta">Reported by <?php echo htmlspecialchars($report['reporter_current_name'] ?? $report['reporter_name']); ?> on <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($report['created_at']))); ?></span>
          <?php if ($report['status'] === 'pending'): ?>
            <div class="dash-table-actions">
              <form method="post">
                <input type="hidden" name="action" value="resolve_report">
                <input type="hidden" name="panel" value="reports">
                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                <button type="submit" class="dash-btn-small">Mark Resolved</button>
              </form>
              <form method="post">
                <input type="hidden" name="action" value="dismiss_report">
                <input type="hidden" name="panel" value="reports">
                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                <button type="submit" class="dash-btn-small dash-btn-small--danger">Dismiss</button>
              </form>
              <?php if ($report['target_type'] === 'posting'): ?>
                <form method="post" onsubmit="return confirm('Delete the reported posting? This also resolves the report.');">
                  <input type="hidden" name="action" value="delete_posting">
                  <input type="hidden" name="panel" value="reports">
                  <input type="hidden" name="posting_id" value="<?php echo (int) $report['target_id']; ?>">
                  <button type="submit" class="dash-btn-small dash-btn-small--danger">Remove Posting</button>
                </form>
              <?php else: ?>
                <form method="post" onsubmit="return confirm('Suspend the reported account?');">
                  <input type="hidden" name="action" value="toggle_user_status">
                  <input type="hidden" name="panel" value="reports">
                  <input type="hidden" name="user_id" value="<?php echo (int) $report['target_id']; ?>">
                  <input type="hidden" name="new_status" value="banned">
                  <button type="submit" class="dash-btn-small dash-btn-small--danger">Suspend Account</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>