<?php
$page_title = 'All Payslips';
$page_subtitle = 'View all issued payslips';
require_once __DIR__ . '/config.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$pp = 20;
$search = trim($_GET['search'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $like = "%$search%";
    $where[] = "(full_name LIKE ? OR payslip_no LIKE ? OR service LIKE ?)";
    array_push($params, $like, $like, $like);
}
if ($from) { $where[] = "DATE(created_at) >= ?"; $params[] = $from; }
if ($to) { $where[] = "DATE(created_at) <= ?"; $params[] = $to; }
$wc = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$rows = [];
$total_records = 0;
$total_pages = 1;
$stats = ['due' => 0, 'paid' => 0];
$db_err = null;

try {
    $pdo = getDB();
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM payslips $wc");
    $stmtCount->execute($params);
    $total_records = (int)$stmtCount->fetchColumn();
    $total_pages = max(1, (int)ceil($total_records / $pp));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $pp;

    $stmtRows = $pdo->prepare("SELECT * FROM payslips $wc ORDER BY id DESC LIMIT $pp OFFSET $offset");
    $stmtRows->execute($params);
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

    $stmtStats = $pdo->prepare("SELECT SUM(amount_due) AS due, SUM(amount_paid) AS paid FROM payslips $wc");
    $stmtStats->execute($params);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (Exception $e) {
    $db_err = $e->getMessage();
}

$qs = http_build_query(array_filter(['search' => $search, 'from' => $from, 'to' => $to]));
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($db_err): ?>
<div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:13px 16px;margin-bottom:16px;color:var(--red);font-size:13px">
  ⚠ Database error: <?= htmlspecialchars($db_err) ?>
</div>
<?php endif; ?>

<form method="GET" class="fbar">
  <div class="sbox">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, payslip no or service">
  </div>
  <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="f-input" onchange="this.form.submit()">
  <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="f-input" onchange="this.form.submit()">
  <button type="submit" class="btn btn-s btn-sm">Search</button>
  <?php if ($search || $from || $to): ?>
  <a href="payslips.php" class="btn btn-s btn-sm">✕ Clear</a>
  <?php endif; ?>
</form>

<div class="sg-3">
  <div class="mstat">
    <div class="mstat-lbl">Total Payslips</div>
    <div class="mstat-val"><?= number_format($total_records) ?></div>
  </div>
  <div class="mstat">
    <div class="mstat-lbl">Amount Supposed To Be Paid</div>
    <div class="mstat-val" style="color:var(--blue)">GH₵ <?= number_format((float)($stats['due'] ?? 0), 2) ?></div>
  </div>
  <div class="mstat">
    <div class="mstat-lbl">Amount Paid</div>
    <div class="mstat-val" style="color:var(--green)">GH₵ <?= number_format((float)($stats['paid'] ?? 0), 2) ?></div>
  </div>
</div>

<div class="card">
  <?php if (empty($rows)): ?>
  <div class="empty">
    <div class="empty-ic"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg></div>
    <h3>No payslips found</h3>
    <p>Create a payslip to see records here.</p>
    <a href="new-payslip.php" class="btn btn-p">Create Payslip</a>
  </div>
  <?php else: ?>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Payslip No</th>
          <th>Services</th>
          <th>Supposed To Pay</th>
          <th>Paid</th>
          <th>Date</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td style="font-family:monospace"><?= htmlspecialchars($r['payslip_no']) ?></td>
          <td><?= htmlspecialchars($r['service']) ?></td>
          <td class="am-cell">GH₵ <?= number_format((float)$r['amount_due'], 2) ?></td>
          <td class="am-cell">GH₵ <?= number_format((float)$r['amount_paid'], 2) ?></td>
          <td><?= htmlspecialchars($r['issue_date']) ?></td>
          <td style="text-align:right"><a class="btn btn-b btn-sm" href="view-payslip.php?id=<?= (int)$r['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($total_pages > 1): ?>
  <div class="pag">
    <div class="pag-inf">
      Showing <?= number_format(($page - 1) * $pp + 1) ?>-<?= number_format(min($page * $pp, $total_records)) ?>
      of <?= number_format($total_records) ?> payslips
    </div>
    <div class="pag-btns">
      <?php if ($page > 1): ?><a class="pb" href="?<?= $qs ?>&page=<?= $page - 1 ?>">&#8249;</a><?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a class="pb <?= $i === $page ? 'active' : '' ?>" href="?<?= $qs ?>&page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?><a class="pb" href="?<?= $qs ?>&page=<?= $page + 1 ?>">&#8250;</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
