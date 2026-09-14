<div class="dash-panel">
  <h3>All Postings</h3>
  <?php if (!$allPostings): ?>
    <p class="dash-empty">No postings yet.</p>
  <?php else: ?>
    <table class="dash-table">
      <thead><tr><th>Title</th><th>Category</th><th>Client</th><th>Slots</th><th>Applications</th><th>Moderation</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($allPostings as $posting): $moderation = $posting['moderation_status'] ?: 'approved'; ?>
          <tr>
            <td><?php echo htmlspecialchars($posting['title']); ?></td>
            <td><?php echo htmlspecialchars($posting['category_name']); ?></td>
            <td><?php echo htmlspecialchars($posting['client_org'] ?: $posting['client_name']); ?></td>
            <td><?php echo (int) $posting['slots']; ?></td>
            <td><?php echo (int) $posting['application_count']; ?></td>
            <td>
              <span class="dash-badge dash-badge--<?php echo $moderation === 'approved' ? 'good' : ($moderation === 'rejected' ? 'bad' : 'muted'); ?>"><?php echo htmlspecialchars(ucfirst($moderation)); ?></span>
            </td>
            <td><span class="dash-badge dash-badge--<?php echo $posting['status'] === 'open' ? 'good' : 'muted'; ?>"><?php echo htmlspecialchars(ucfirst($posting['status'])); ?></span></td>
            <td class="dash-table-actions">
              <?php if ($moderation === 'pending'): ?>
                <form method="post">
                  <input type="hidden" name="panel" value="postings">
                  <input type="hidden" name="posting_id" value="<?php echo (int) $posting['id']; ?>">
                  <input type="hidden" name="action" value="approve_posting">
                  <button type="submit" class="dash-btn-small">Approve</button>
                </form>
                <form method="post" onsubmit="return confirm('Reject this posting? It will be closed and hidden from the site.');">
                  <input type="hidden" name="panel" value="postings">
                  <input type="hidden" name="posting_id" value="<?php echo (int) $posting['id']; ?>">
                  <input type="hidden" name="action" value="reject_posting">
                  <button type="submit" class="dash-btn-small dash-btn-small--danger">Reject</button>
                </form>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="panel" value="postings">
                  <input type="hidden" name="posting_id" value="<?php echo (int) $posting['id']; ?>">
                  <input type="hidden" name="action" value="toggle_posting_status">
                  <input type="hidden" name="new_status" value="<?php echo $posting['status'] === 'open' ? 'closed' : 'open'; ?>">
                  <button type="submit" class="dash-btn-small" <?php echo $moderation === 'rejected' ? 'disabled title="Rejected postings stay closed"' : ''; ?>><?php echo $posting['status'] === 'open' ? 'Close' : 'Reopen'; ?></button>
                </form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Delete this posting and all its applications? This is for removing inappropriate postings and can\'t be undone.');">
                <input type="hidden" name="panel" value="postings">
                <input type="hidden" name="posting_id" value="<?php echo (int) $posting['id']; ?>">
                <input type="hidden" name="action" value="delete_posting">
                <button type="submit" class="dash-btn-small dash-btn-small--danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>