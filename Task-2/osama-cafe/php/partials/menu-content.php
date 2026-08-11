<?php
/**
 * The inner content of admin-menu.php's <div class="admin-content">.
 * require()'d from admin-menu.php itself — either standalone (as the body
 * of an AJAX fragment response) or wrapped in the full HTML document (a
 * normal page load). Relies on variables already set up by admin-menu.php:
 * $categories, $editItem, $status, $search, $categoryFilter, $totalItems,
 * $pag, $items, $filterBaseParams — and the field_label() helper.
 */
?>
  <div class="admin-header">
    <h2>Manage Menu</h2>
  </div>

  <?php render_status_toast($status, MENU_STATUS_MESSAGES); ?>

  <div class="panel">
    <h3>Categories</h3>
    <div>
      <?php foreach ($categories as $cat): ?>
        <span class="category-tag">
          <?= h($cat['name']) ?>
          <form method="post" action="menu_admin.php" data-confirm="<?= h('Delete the "' . $cat['name'] . '" category?') ?>" data-confirm-label="Delete" data-confirm-danger="true">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
            <button type="submit" title="Delete category">&times;</button>
          </form>
        </span>
      <?php endforeach; ?>
      <?php if (!$categories): ?><p class="empty-note">No categories yet.</p><?php endif; ?>
    </div>
    <form class="inline-form" method="post" action="menu_admin.php" style="margin-top:14px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <input type="text" name="name" placeholder="New category name (e.g. Drinks)" required>
      <button type="submit" class="btn btn-primary btn-small">Add Category</button>
    </form>
  </div>

  <div class="panel">
    <h3><?= $editItem ? 'Edit "' . h($editItem['title']) . '"' : 'Add a New Menu Item' ?></h3>
    <form method="post" action="menu_admin.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editItem ? 'update_item' : 'add_item' ?>">
      <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>

      <?= field_label('Title', "The item's name, shown as the card's heading.") ?>
      <input type="text" name="title" value="<?= h($editItem['title'] ?? '') ?>" required>

      <?= field_label('Description', 'The short paragraph under the title on the card.') ?>
      <textarea name="description" required><?= h($editItem['description'] ?? '') ?></textarea>

      <div class="field-row">
        <div>
          <?= field_label('Category', 'Controls which filter button ("All", "Coffee", etc.) shows this item.') ?>
          <select name="category_id" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ($editItem && (int)$editItem['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                <?= h($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <?= field_label('Price ($)', 'Shown as the price tag on the corner of the card.') ?>
          <input type="number" step="0.01" min="0" name="price" value="<?= h((string)($editItem['price'] ?? '')) ?>" required>
        </div>
      </div>

      <div class="field-row">
        <div>
          <?= field_label('Button Label', 'The text on the card\'s link, e.g. "Order Now" or "View Menu".') ?>
          <input type="text" name="link_label" value="<?= h($editItem['link_label'] ?? 'Order Now') ?>" placeholder="Order Now">
        </div>
        <div>
          <?= field_label('Sort Order', 'Lower numbers show first in the grid.') ?>
          <input type="number" name="sort_order" value="<?= h((string)($editItem['sort_order'] ?? 0)) ?>">
        </div>
      </div>

      <?= field_label('Photo' . ($editItem ? ' (leave empty to keep the current photo)' : ''), 'The card\'s main image. JPG, PNG, or WEBP, up to 5MB.') ?>
      <?php if ($editItem): ?>
        <img class="thumb" style="margin-bottom:14px;" src="../images/<?= h($editItem['image']) ?>" alt="">
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $editItem ? '' : 'required' ?>>

      <div style="margin-top:10px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editItem ? 'Save Changes' : 'Add Item' ?></button>
        <?php if ($editItem): ?><a href="admin-menu.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="section-header-row">
    <h3>Current Menu (<?= $totalItems ?>)</h3>
  </div>

  <form class="filter-bar" method="get">
    <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search by title…">
    <select name="cat">
      <option value="0">All categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-small">Filter</button>
    <?php if ($search !== '' || $categoryFilter > 0): ?><a class="filter-clear" href="admin-menu.php">Clear</a><?php endif; ?>
  </form>

  <?php if (!$items): ?>
    <p class="empty-note"><?= ($search !== '' || $categoryFilter > 0) ? 'No menu items match your filters.' : 'No menu items yet — add one above.' ?></p>
  <?php else: ?>
    <div class="table-scroll">
      <table>
        <tr><th></th><th>Title</th><th>Category</th><th>Price</th><th></th></tr>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><img class="thumb" src="../images/<?= h($item['image']) ?>" alt=""></td>
            <td><?= highlight($item['title'], $search) ?></td>
            <td><?= h($item['category_name']) ?></td>
            <td>$<?= number_format((float)$item['price'], 2) ?></td>
            <td>
              <div class="row-actions">
                <a class="btn btn-primary btn-small" href="admin-menu.php?edit=<?= (int)$item['id'] ?>">Edit</a>
                <form method="post" action="menu_admin.php" data-confirm="<?= h('Delete "' . $item['title'] . '"?') ?>" data-confirm-label="Delete" data-confirm-danger="true">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_item">
                  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-small">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?= render_pagination($pag['page'], $pag['totalPages'], 'ipage', $filterBaseParams) ?>
  <?php endif; ?>
