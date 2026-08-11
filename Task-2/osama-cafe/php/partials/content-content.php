<?php
/**
 * The inner content of admin-content.php's <div class="admin-content">.
 * require()'d from admin-content.php itself — standalone for an AJAX
 * fragment response, or wrapped in the full HTML document on a normal page
 * load. Relies on variables already set up by admin-content.php: $faqs,
 * $testimonials, $galleryItems, $editFaq, $editTestimonial, $editGallery,
 * $status — and the field_label() / render_star_rating() helpers.
 */
$ratingOptions = ['5', '4.5', '4', '3.5', '3', '2.5', '2', '1.5', '1', '0.5'];
?>
  <div class="admin-header">
    <h2>Site Content</h2>
    <p>Manage the Gallery, Testimonials, and FAQ sections shown on the homepage.</p>
  </div>

  <?php render_status_toast($status, CONTENT_STATUS_MESSAGES); ?>

  <!-- ================= Gallery ================= -->
  <div class="panel">
    <h3>Gallery Photos (<?= count($galleryItems) ?>)</h3>
    <p class="panel-hint">The photo grid in the "Our Gallery" section. Order below controls the order on the page.</p>

    <?php if ($galleryItems): ?>
      <div class="table-scroll">
        <table>
          <tr><th></th><th>Caption</th><th>Alt Text</th><th></th></tr>
          <?php foreach ($galleryItems as $g): ?>
            <tr>
              <td><img class="thumb" src="../images/<?= h($g['image']) ?>" alt=""></td>
              <td><?= h($g['caption']) ?></td>
              <td><?= h($g['alt_text']) ?></td>
              <td>
                <div class="row-actions">
                  <a class="btn btn-primary btn-small" href="admin-content.php?edit_gallery=<?= (int)$g['id'] ?>">Edit</a>
                  <form method="post" action="content_admin.php" data-confirm="<?= h('Remove "' . $g['caption'] . '" from the gallery?') ?>" data-confirm-label="Delete" data-confirm-danger="true">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_gallery">
                    <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-small">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php else: ?>
      <p class="empty-note">No gallery photos yet.</p>
    <?php endif; ?>

    <h4 style="margin:20px 0 14px;"><?= $editGallery ? 'Edit Photo' : 'Add a Photo' ?></h4>
    <form method="post" action="content_admin.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editGallery ? 'update_gallery' : 'add_gallery' ?>">
      <?php if ($editGallery): ?><input type="hidden" name="id" value="<?= (int)$editGallery['id'] ?>"><?php endif; ?>

      <?= field_label('Caption', 'Shown on the photo when a visitor hovers over it in the gallery grid.') ?>
      <input type="text" name="caption" value="<?= h($editGallery['caption'] ?? '') ?>" required>

      <?= field_label('Alt Text (optional)', 'Describes the photo for screen readers and search engines. Falls back to the caption if left empty.') ?>
      <input type="text" name="alt_text" value="<?= h($editGallery['alt_text'] ?? '') ?>">

      <?= field_label('Sort Order (optional)', 'Lower numbers show first in the grid.') ?>
      <input type="number" name="sort_order" value="<?= h((string)($editGallery['sort_order'] ?? 0)) ?>">

      <?= field_label('Photo' . ($editGallery ? ' (leave empty to keep the current photo)' : ''), 'JPG, PNG, or WEBP, up to 5MB.') ?>
      <?php if ($editGallery): ?>
        <img class="thumb" style="margin-bottom:14px;" src="../images/<?= h($editGallery['image']) ?>" alt="">
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $editGallery ? '' : 'required' ?>>

      <div style="margin-top:10px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editGallery ? 'Save Changes' : 'Add Photo' ?></button>
        <?php if ($editGallery): ?><a href="admin-content.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ================= Testimonials ================= -->
  <div class="panel">
    <h3>Testimonials (<?= count($testimonials) ?>)</h3>
    <p class="panel-hint">The reviews carousel in the "What People Are Saying" section.</p>

    <?php if ($testimonials): ?>
      <div class="table-scroll">
        <table>
          <tr><th>Name</th><th>Role</th><th>Rating</th><th>Quote</th><th></th></tr>
          <?php foreach ($testimonials as $t): ?>
            <tr>
              <td><?= h($t['author_name']) ?></td>
              <td><?= h($t['author_role']) ?></td>
              <td style="color: var(--accent-color); white-space: nowrap;"><?= render_star_rating((float)$t['rating']) ?></td>
              <td><?= h(mb_strimwidth($t['quote'], 0, 70, '…')) ?></td>
              <td>
                <div class="row-actions">
                  <a class="btn btn-primary btn-small" href="admin-content.php?edit_testimonial=<?= (int)$t['id'] ?>">Edit</a>
                  <form method="post" action="content_admin.php" data-confirm="<?= h('Delete the testimonial from "' . $t['author_name'] . '"?') ?>" data-confirm-label="Delete" data-confirm-danger="true">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_testimonial">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-small">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php else: ?>
      <p class="empty-note">No testimonials yet.</p>
    <?php endif; ?>

    <h4 style="margin:20px 0 14px;"><?= $editTestimonial ? 'Edit Testimonial' : 'Add a Testimonial' ?></h4>
    <form method="post" action="content_admin.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editTestimonial ? 'update_testimonial' : 'add_testimonial' ?>">
      <?php if ($editTestimonial): ?><input type="hidden" name="id" value="<?= (int)$editTestimonial['id'] ?>"><?php endif; ?>

      <div class="field-row">
        <div>
          <?= field_label('Guest Name', 'Shown under the quote, e.g. "Sarah M.".') ?>
          <input type="text" name="author_name" value="<?= h($editTestimonial['author_name'] ?? '') ?>" required>
        </div>
        <div>
          <?= field_label('Role / Description', 'Shown under the name, e.g. "Regular Guest".') ?>
          <input type="text" name="author_role" value="<?= h($editTestimonial['author_role'] ?? '') ?>" required>
        </div>
      </div>

      <?= field_label('Quote', 'The review text itself.') ?>
      <textarea name="quote" required><?= h($editTestimonial['quote'] ?? '') ?></textarea>

      <div class="field-row">
        <div>
          <?= field_label('Rating', 'How many stars to show, in half-star steps.') ?>
          <select name="rating">
            <?php $currentRating = (string)($editTestimonial['rating'] ?? '5'); ?>
            <?php foreach ($ratingOptions as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ((float)$currentRating === (float)$opt) ? 'selected' : '' ?>><?= h($opt) ?> stars</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <?= field_label('Sort Order (optional)', 'Lower numbers show first in the carousel.') ?>
          <input type="number" name="sort_order" value="<?= h((string)($editTestimonial['sort_order'] ?? 0)) ?>">
        </div>
      </div>

      <div style="margin-top:10px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editTestimonial ? 'Save Changes' : 'Add Testimonial' ?></button>
        <?php if ($editTestimonial): ?><a href="admin-content.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ================= FAQ ================= -->
  <div class="panel">
    <h3>FAQ (<?= count($faqs) ?>)</h3>
    <p class="panel-hint">The accordion in the "Frequently Asked Questions" section.</p>

    <?php if ($faqs): ?>
      <div class="table-scroll">
        <table>
          <tr><th>Question</th><th>Answer</th><th></th></tr>
          <?php foreach ($faqs as $f): ?>
            <tr>
              <td><?= h($f['question']) ?></td>
              <td><?= h(mb_strimwidth($f['answer'], 0, 80, '…')) ?></td>
              <td>
                <div class="row-actions">
                  <a class="btn btn-primary btn-small" href="admin-content.php?edit_faq=<?= (int)$f['id'] ?>">Edit</a>
                  <form method="post" action="content_admin.php" data-confirm="Delete this FAQ?" data-confirm-label="Delete" data-confirm-danger="true">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_faq">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-small">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php else: ?>
      <p class="empty-note">No FAQ entries yet.</p>
    <?php endif; ?>

    <h4 style="margin:20px 0 14px;"><?= $editFaq ? 'Edit FAQ' : 'Add a Question' ?></h4>
    <form method="post" action="content_admin.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editFaq ? 'update_faq' : 'add_faq' ?>">
      <?php if ($editFaq): ?><input type="hidden" name="id" value="<?= (int)$editFaq['id'] ?>"><?php endif; ?>

      <?= field_label('Question', "Shown as the accordion row's title.") ?>
      <input type="text" name="question" value="<?= h($editFaq['question'] ?? '') ?>" required>

      <?= field_label('Answer', 'Revealed when a visitor taps the question.') ?>
      <textarea name="answer" required><?= h($editFaq['answer'] ?? '') ?></textarea>

      <?= field_label('Sort Order (optional)', 'Lower numbers show first in the list.') ?>
      <input type="number" name="sort_order" value="<?= h((string)($editFaq['sort_order'] ?? 0)) ?>">

      <div style="margin-top:10px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editFaq ? 'Save Changes' : 'Add Question' ?></button>
        <?php if ($editFaq): ?><a href="admin-content.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
