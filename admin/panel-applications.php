<div class="dash-panel">
  <h3>Recent Applications</h3>
  <?php if (!$allApplications): ?>
    <p class="dash-empty">No applications yet.</p>
  <?php else: ?>
    <table class="dash-table">
      <thead><tr><th>Aspirant</th><th>Posting</th><th>Client</th><th>Status</th><th>Applied</th></tr></thead>
      <tbody>
        <?php foreach ($allApplications as $application): ?>
          <tr>
            <td><?php echo htmlspecialchars($application['aspirant_name']); ?></td>
            <td><?php echo htmlspecialchars($application['posting_title']); ?></td>
            <td><?php echo htmlspecialchars($application['client_name']); ?></td>
            <td><span class="dash-badge dash-badge--<?php echo $application['status'] === 'accepted' ? 'good' : ($application['status'] === 'declined' ? 'bad' : 'muted'); ?>"><?php echo htmlspecialchars(ucfirst($application['status'])); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($application['created_at'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>