<?php
/**
 * The inner content of admin-messages.php's <div class="admin-content">.
 * require()'d from admin-messages.php itself — standalone for an AJAX
 * fragment response, or wrapped in the full HTML document on a normal page
 * load. Relies on variables already set up by admin-messages.php: $mq, $sq,
 * $totalMessages, $totalSubscribers, $messages, $subscribers, $mPag, $sPag,
 * $allSubscriberEmails, $allSubscriberCount.
 *
 * The broadcast form's template-picker is wired up in admin.js (delegated
 * on #template-select), not an inline <script> here — a <script> tag
 * injected via innerHTML (as an AJAX-fetched fragment is) never executes.
 */
?>
  <div class="admin-header">
    <h2>Messages &amp; Subscribers</h2>
  </div>

  <?php if (($_GET['broadcast'] ?? '') === 'done'):
    $sent = (int)($_GET['sent'] ?? 0);
    $failed = (int)($_GET['failed'] ?? 0);
    $broadcastText = "Newsletter sent — $sent delivered" . ($failed > 0 ? ", $failed failed (check the PHP error log)" : '') . '.';
    render_toast('success', $broadcastText);
  elseif (($_GET['broadcast'] ?? '') === 'empty'):
    render_toast('warn', 'Please fill in both a subject and a message before sending.');
  elseif (($_GET['broadcast'] ?? '') === 'not_configured'):
    render_toast('warn', "SMTP isn't configured yet — see the note below the subscriber list.");
  endif; ?>

  <div class="section-header-row">
    <h3>Messages (<?= $totalMessages ?>)</h3>
  </div>

  <form class="filter-bar" method="get">
    <input type="search" name="q" value="<?= h($mq) ?>" placeholder="Search name, email, or message…">
    <button type="submit" class="btn btn-secondary btn-small">Search</button>
    <?php if ($mq !== ''): ?><a class="filter-clear" href="admin-messages.php">Clear</a><?php endif; ?>
  </form>

  <?php if (!$messages): ?>
    <p class="empty-note"><?= $mq !== '' ? 'No messages match your search.' : 'No messages yet.' ?></p>
  <?php else: ?>
    <div class="table-scroll">
      <table>
        <tr><th>Date</th><th>Name</th><th>Email</th><th>Message</th><th></th></tr>
        <?php foreach ($messages as $m):
          $replySubject = rawurlencode('Re: your message to Osama Café');
          $replyBody = rawurlencode("Hi {$m['name']},\n\n");
        ?>
          <tr>
            <td><?= h($m['created_at']) ?></td>
            <td><?= highlight($m['name'], $mq) ?></td>
            <td><?= highlight($m['email'], $mq) ?></td>
            <td><?= nl2br(highlight($m['message'], $mq)) ?></td>
            <td>
              <a class="btn btn-primary btn-small" target="_blank" rel="noopener"
                 href="mailto:<?= h($m['email']) ?>?subject=<?= $replySubject ?>&body=<?= $replyBody ?>">Reply</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?= render_pagination($mPag['page'], $mPag['totalPages'], 'mpage', $mq !== '' ? ['q' => $mq] : []) ?>
  <?php endif; ?>

  <div class="section-header-row">
    <h3>Newsletter Subscribers (<?= $totalSubscribers ?>)</h3>
    <?php if ($allSubscriberCount):
      $allEmails = implode(',', $allSubscriberEmails);
      $bccSubject = rawurlencode('News from Osama Café');
    ?>
      <div class="table-actions">
        <button type="button" class="btn btn-secondary btn-small" id="copy-emails-btn" data-emails="<?= h($allEmails) ?>">Copy All Emails</button>
        <a class="btn btn-primary btn-small" target="_blank" rel="noopener"
           href="mailto:?bcc=<?= rawurlencode($allEmails) ?>&subject=<?= $bccSubject ?>">Email All (BCC), manually</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (mail_is_configured() && $allSubscriberCount): ?>
    <form class="broadcast-form" method="post" action="broadcast.php" id="broadcast-form" data-confirm="<?= h('Send this to all ' . $allSubscriberCount . ' subscribers now?') ?>" data-confirm-label="Send">
      <?= csrf_field() ?>
      <h4 style="margin-bottom:14px;">Send Newsletter Automatically</h4>

      <div class="template-picker">
        <label for="template-select">Start from a template (optional)</label>
        <select id="template-select">
          <option value="">— Choose a template… —</option>
          <option value="new_item">☕ New Menu Item</option>
          <option value="seasonal">🍂 Limited-Time Seasonal Special</option>
          <option value="discount">💸 Weekly Discount</option>
          <option value="announcement">📣 General Announcement</option>
          <option value="we_miss_you">👋 We Miss You</option>
        </select>
      </div>

      <input type="text" name="subject" id="broadcast-subject" placeholder="Subject" required>
      <textarea name="body" id="broadcast-body" placeholder="Write your update…" required></textarea>
      <button type="submit" class="btn btn-primary">Send to All <?= $allSubscriberCount ?> Subscribers</button>
    </form>
  <?php elseif (!mail_is_configured()): ?>
    <p class="mail-off-note">
      Automatic sending is off. Fill in <code>SMTP_USERNAME</code> / <code>SMTP_PASSWORD</code> and set <code>MAIL_ENABLED</code> to <code>true</code> in <code>php/config.php</code> to enable one-click sending here — until then, use "Email All (BCC), manually" above.
    </p>
  <?php endif; ?>

  <form class="filter-bar" method="get">
    <?php if ($mq !== ''): ?><input type="hidden" name="q" value="<?= h($mq) ?>"><?php endif; ?>
    <input type="search" name="sq" value="<?= h($sq) ?>" placeholder="Search subscriber email…">
    <button type="submit" class="btn btn-secondary btn-small">Search</button>
    <?php if ($sq !== ''): ?><a class="filter-clear" href="admin-messages.php<?= $mq !== '' ? '?q=' . urlencode($mq) : '' ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$subscribers): ?>
    <p class="empty-note"><?= $sq !== '' ? 'No subscribers match your search.' : 'No subscribers yet.' ?></p>
  <?php else: ?>
    <div class="table-scroll">
      <table>
        <tr><th>Date</th><th>Email</th><th></th></tr>
        <?php foreach ($subscribers as $s): ?>
          <tr>
            <td><?= h($s['created_at']) ?></td>
            <td><?= highlight($s['email'], $sq) ?></td>
            <td><a class="btn btn-primary btn-small" target="_blank" rel="noopener" href="mailto:<?= h($s['email']) ?>">Email</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?= render_pagination($sPag['page'], $sPag['totalPages'], 'spage', $sq !== '' ? ['sq' => $sq] : []) ?>
  <?php endif; ?>
