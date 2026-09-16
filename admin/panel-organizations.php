<div class="dash-panel">
  <h3>Organizations</h3>
  <?php if (!$allOrganizations): ?>
    <p class="dash-empty">No client organizations yet.</p>
  <?php else: ?>
    <table class="dash-table">
      <thead><tr><th>Organization</th><th>Contact</th><th>Email</th><th>Postings</th><th>Accepted</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($allOrganizations as $organization): ?>
          <tr>
            <td><?php echo htmlspecialchars($organization['org_name'] ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($organization['name']); ?></td>
            <td><?php echo htmlspecialchars($organization['email']); ?></td>
            <td><?php echo (int) $organization['posting_count']; ?></td>
            <td><?php echo (int) $organization['accepted_count']; ?></td>
            <td><span class="dash-badge dash-badge--<?php echo $organization['status'] === 'active' ? 'good' : 'bad'; ?>"><?php echo htmlspecialchars(ucfirst($organization['status'])); ?></span></td>
            <td class="dash-table-actions">
              <form method="post" onsubmit="return confirm(<?php echo $organization['status'] === 'active' ? "'Suspend " . addslashes(htmlspecialchars($organization['org_name'] ?: $organization['name'])) . "? They won\\'t be able to log in or post openings.'" : "'Reinstate " . addslashes(htmlspecialchars($organization['org_name'] ?: $organization['name'])) . "?'"; ?>);">
                <input type="hidden" name="panel" value="organizations">
                <input type="hidden" name="user_id" value="<?php echo (int) $organization['id']; ?>">
                <input type="hidden" name="action" value="toggle_user_status">
                <input type="hidden" name="new_status" value="<?php echo $organization['status'] === 'active' ? 'banned' : 'active'; ?>">
                <button type="submit" class="dash-btn-small <?php echo $organization['status'] === 'active' ? 'dash-btn-small--danger' : ''; ?>"><?php echo $organization['status'] === 'active' ? 'Suspend' : 'Reinstate'; ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>