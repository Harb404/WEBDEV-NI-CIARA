<div class="dash-panel">
  <h3>Aspirants</h3>
  <?php if (!$allUsers): ?>
    <p class="dash-empty">No users yet.</p>
  <?php else: ?>
    <table class="dash-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($allUsers as $user): ?>
          <tr>
            <td><?php echo htmlspecialchars($user['name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
            <td><span class="dash-badge dash-badge--<?php echo $user['status'] === 'active' ? 'good' : 'bad'; ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
            <td class="dash-table-actions">
              <form method="post">
                <input type="hidden" name="panel" value="users">
                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                <input type="hidden" name="action" value="toggle_user_status">
                <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'banned' : 'active'; ?>">
                <button type="submit" class="dash-btn-small <?php echo $user['status'] === 'active' ? 'dash-btn-small--danger' : ''; ?>"><?php echo $user['status'] === 'active' ? 'Suspend' : 'Reinstate'; ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>