<?php
/**
 * The inner content of admin-settings.php's <div class="admin-content">.
 * require()'d from admin-settings.php itself — standalone for an AJAX
 * fragment response, or wrapped in the full HTML document on a normal page
 * load. Relies on variables already set up by admin-settings.php:
 * $settings, $branches, $editBranch, $status — and the field_label() helper.
 */
?>
  <div class="admin-header">
    <h2>Site Settings</h2>
  </div>

  <?php render_status_toast($status, SETTINGS_STATUS_MESSAGES); ?>

  <div class="panel">
    <h3>Site Text &amp; Contact Info</h3>
    <p class="panel-hint">Changes here appear across the whole site immediately.</p>
    <form method="post" action="settings_admin.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_settings">

      <div class="field-row-3">
        <div>
          <?= field_label('Contact Email', 'Shown in the Contact section & footer. Also where the "Reply" buttons in the admin panel are addressed to.') ?>
          <input type="email" name="site_email" value="<?= h($settings['site_email'] ?? '') ?>">
        </div>
        <div>
          <?= field_label('Phone', 'Shown in the Contact section & footer as the click-to-call number.') ?>
          <input type="text" name="site_phone" value="<?= h($settings['site_phone'] ?? '') ?>">
        </div>
        <div>
          <?= field_label('WhatsApp Number', 'Used by the "Chat on WhatsApp" button on the contact form. Country code + number, digits only — no +, spaces, or leading 0 (e.g. Egypt: 20 then the number).') ?>
          <input type="text" name="whatsapp_number" value="<?= h($settings['whatsapp_number'] ?? '') ?>" placeholder="201142520095">
        </div>
      </div>

      <?= field_label('Hero — Small Line Above the Title', 'The small italic line above the café name, at the very top of the homepage.') ?>
      <input type="text" name="hero_subtitle" value="<?= h($settings['hero_subtitle'] ?? '') ?>">

      <?= field_label('Hero — Café Name / Title', 'The big headline on the first screen visitors see — usually your café\'s name.') ?>
      <input type="text" name="hero_title" value="<?= h($settings['hero_title'] ?? '') ?>">

      <?= field_label('Hero — Description', 'The paragraph under that headline.') ?>
      <textarea name="hero_description"><?= h($settings['hero_description'] ?? '') ?></textarea>

      <?= field_label('Our Story — Heading', 'The heading of the "About" section further down the page (currently "Our Story").') ?>
      <input type="text" name="about_title" value="<?= h($settings['about_title'] ?? '') ?>">

      <?= field_label('Our Story — Text', 'The paragraph under that heading, telling visitors who you are.') ?>
      <textarea name="about_text"><?= h($settings['about_text'] ?? '') ?></textarea>

      <?= field_label('Footer — About Blurb', 'The short paragraph in the footer\'s first column, under "About Osama Café".') ?>
      <textarea name="footer_about_text"><?= h($settings['footer_about_text'] ?? '') ?></textarea>

      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>

  <div class="panel">
    <h3><?= $editBranch ? 'Edit "' . h($editBranch['name']) . '"' : 'Add a Branch / Location' ?></h3>
    <p class="panel-hint">The primary branch's address &amp; phone are shown in the main Contact section. Any other branches appear as additional locations.</p>
    <form method="post" action="settings_admin.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editBranch ? 'update_branch' : 'add_branch' ?>">
      <?php if ($editBranch): ?><input type="hidden" name="id" value="<?= (int)$editBranch['id'] ?>"><?php endif; ?>

      <?= field_label('Branch Name', "Its title — shown on this branch's card if it's not the primary one.") ?>
      <input type="text" name="name" value="<?= h($editBranch['name'] ?? '') ?>" placeholder="e.g. Osama Café — Maadi" required>

      <?= field_label('Address', 'Shown under "Find Us Here" if this is the primary branch, or on its own card if it\'s an extra location.') ?>
      <textarea name="address" required><?= h($editBranch['address'] ?? '') ?></textarea>

      <div class="field-row">
        <div>
          <?= field_label('Phone', "This branch's own number.") ?>
          <input type="text" name="phone" value="<?= h($editBranch['phone'] ?? '') ?>" required>
        </div>
        <div></div>
      </div>

      <?= field_label('Google Maps Link (optional)', 'Paste a link straight from Google Maps (the "Share" button, or the address bar) and the "Open in Google Maps" button uses it as-is. Set this and it wins over the Latitude/Longitude below — leave it empty to fall back to those instead.') ?>
      <input type="url" name="maps_url" value="<?= h($editBranch['maps_url'] ?? '') ?>" placeholder="https://maps.app.goo.gl/…">

      <div class="field-row">
        <div>
          <?= field_label('Latitude (optional)', 'Used only if the Google Maps Link above is empty. Leave both empty and we\'ll search by the address text instead. Find them by right-clicking the spot on Google Maps and copying the numbers shown.') ?>
          <input type="text" name="lat" value="<?= h((string)($editBranch['lat'] ?? '')) ?>" placeholder="29.9733">
        </div>
        <div>
          <label class="field-label">Longitude (optional)</label>
          <input type="text" name="lng" value="<?= h((string)($editBranch['lng'] ?? '')) ?>" placeholder="30.9464">
        </div>
      </div>

      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editBranch ? 'Save Changes' : 'Add Branch' ?></button>
        <?php if ($editBranch): ?><a href="admin-settings.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <h3 style="margin-bottom:15px;">Branches (<?= count($branches) ?>)</h3>
  <div class="table-scroll">
    <table>
      <tr><th>Name</th><th>Address</th><th>Phone</th><th></th></tr>
      <?php foreach ($branches as $b): ?>
        <tr>
          <td><?= h($b['name']) ?><?php if ($b['is_primary']): ?><span class="primary-badge">Primary</span><?php endif; ?></td>
          <td><?= h($b['address']) ?></td>
          <td><?= h($b['phone']) ?></td>
          <td>
            <div class="row-actions">
              <a class="btn btn-primary btn-small" href="admin-settings.php?edit_branch=<?= (int)$b['id'] ?>">Edit</a>
              <?php if (!$b['is_primary']): ?>
                <form method="post" action="settings_admin.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="set_primary_branch">
                  <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button type="submit" class="btn btn-secondary btn-small">Make Primary</button>
                </form>
              <?php endif; ?>
              <form method="post" action="settings_admin.php" data-confirm="<?= h('Delete "' . $b['name'] . '"?') ?>" data-confirm-label="Delete" data-confirm-danger="true">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_branch">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-small">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
