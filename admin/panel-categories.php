<div class="dash-panel">
  <h3>Add Category</h3>
  <form class="dash-form" method="post">
    <input type="hidden" name="action" value="add_category">
    <input type="hidden" name="panel" value="categories">
    <div class="dash-form-row">
      <label>Name<input type="text" name="name" placeholder="e.g. Marketing" required></label>
      <label>Description<input type="text" name="description" placeholder="Short description shown to aspirants"></label>
    </div>
    <button class="dash-btn-primary" type="submit">Add Category</button>
  </form>
</div>

<div class="dash-panel">
  <h3>Categories</h3>
  <?php if (!$allCategories): ?>
    <p class="dash-empty">No categories yet.</p>
  <?php else: ?>
    <div class="dash-row-list">
      <?php foreach ($allCategories as $category): ?>
        <div class="dash-row-item">
          <form class="dash-inline-edit-form" method="post">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="panel" value="categories">
            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            <input type="text" name="description" value="<?php echo htmlspecialchars($category['description']); ?>" placeholder="Description">
            <span class="dash-row-meta"><?php echo (int) $category['posting_count']; ?> posting<?php echo (int) $category['posting_count'] === 1 ? '' : 's'; ?></span>
            <button type="submit" class="dash-btn-small">Save</button>
          </form>
          <form method="post" onsubmit="return confirm('Delete the <?php echo addslashes(htmlspecialchars($category['name'])); ?> category?');">
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="panel" value="categories">
            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
            <button type="submit" class="dash-btn-small dash-btn-small--danger" <?php echo $category['posting_count'] > 0 ? 'disabled title="Move or remove its postings first"' : ''; ?>>Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>