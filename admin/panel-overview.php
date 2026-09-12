<div class="dash-stats-grid">
  <div class="dash-stat-card"><span class="dash-stat-label">Aspirants</span><span class="dash-stat-value"><?php echo number_format($statUserCount); ?></span></div>
  <div class="dash-stat-card"><span class="dash-stat-label">Clients</span><span class="dash-stat-value"><?php echo number_format($statClientCount); ?></span></div>
  <div class="dash-stat-card"><span class="dash-stat-label">Open Postings</span><span class="dash-stat-value"><?php echo number_format($statOpenPostingCount); ?> <small>/ <?php echo number_format($statPostingCount); ?></small></span></div>
  <div class="dash-stat-card"><span class="dash-stat-label">Applications</span><span class="dash-stat-value"><?php echo number_format($statApplicationCount); ?></span></div>
  <div class="dash-stat-card"><span class="dash-stat-label">Accepted</span><span class="dash-stat-value"><?php echo number_format($statAcceptedCount); ?></span></div>
  <div class="dash-stat-card"><span class="dash-stat-label">Awaiting Review</span><span class="dash-stat-value"><?php echo number_format($statPendingCount); ?></span></div>
</div>

<div class="dash-panels-row">
  <div class="dash-panel dash-panel--chart">
    <h3>Applications this year</h3>
    <div class="bar-chart">
      <?php foreach ($monthNames as $index => $monthLabel): $value = $monthlyApplications[$index + 1]; ?>
        <div class="bar-chart-col">
          <div class="bar-chart-bar" style="height: <?php echo max(4, round(($value / $maxMonthlyApplications) * 100)); ?>%" title="<?php echo $value; ?>"></div>
          <span><?php echo $monthLabel; ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="dash-panel">
    <h3>Applications by Committee</h3>
    <?php if (!$topCategories): ?>
      <p class="dash-empty">No applications yet.</p>
    <?php else: ?>
      <ol class="dash-rank-list">
        <?php foreach ($topCategories as $category): ?>
          <li><span><?php echo htmlspecialchars($category['name']); ?></span><strong><?php echo (int) $category['total']; ?></strong></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</div>