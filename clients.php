<?php
/**
 * All Receipts — ForgedCore Receipt Manager
 */
$page_title    = 'All Receipts';
$page_subtitle = 'Search, filter and manage every receipt you have issued';

require_once __DIR__ . '/config.php';

// ── Filters ──────────────────────────────────
$page   = max(1, (int)($_GET['page']   ?? 1));
$pp     = 20;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status']  ?? 'all';
$from   = $_GET['from']    ?? '';
$to     = $_GET['to']      ?? '';

$where  = [];
$params = [];

if ($search) {
    $like    = "%$search%";
    $where[] = "(name LIKE ? OR invoice_no LIKE ? OR contact LIKE ? OR address LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}
switch ($status) {
    case 'paid':    $where[] = "(total - paid) <= 0";                break;
    case 'partial': $where[] = "paid > 0 AND (total - paid) > 0";   break;
    case 'unpaid':  $where[] = "paid = 0";                           break;
}
if ($from) { $where[] = "DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $where[] = "DATE(created_at) <= ?"; $params[] = $to; }

$wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = [];
$total_records = 0;
$total_pages   = 1;
$fs  = ['c' => 0, 'rev' => 0, 'coll' => 0];
$db_err = null;

try {
    $pdo = getDB();

    $cr = $pdo->prepare("SELECT COUNT(*) FROM clients $wc");
    $cr->execute($params);
    $total_records = (int)$cr->fetchColumn();
    $total_pages   = max(1, (int)ceil($total_records / $pp));
    $page          = min($page, $total_pages);
    $offset        = ($page - 1) * $pp;

    $sr = $pdo->prepare("
        SELECT id, name, address, contact, invoice_no, total, paid, date, created_at
        FROM clients $wc ORDER BY id DESC LIMIT $pp OFFSET $offset
    ");
    $sr->execute($params);
    $rows = $sr->fetchAll(PDO::FETCH_ASSOC);

    $fr = $pdo->prepare("
        SELECT 
            SUM(p_val) as rev, 
            SUM(p_paid) as coll 
        FROM (
            SELECT 
                SUBSTRING_INDEX(invoice_no, '/', 2) as prefix, 
                total as p_val, 
                MAX(paid) as p_paid 
            FROM clients $wc 
            GROUP BY prefix, total
        ) as sub
    ");
    $fr->execute($params);
    $fs = $fr->fetch(PDO::FETCH_ASSOC);
    $fs['c'] = $total_records;

} catch (Exception $e) {
    $db_err = $e->getMessage();
}

// Build query string without page for pagination links
$qs = http_build_query(array_filter(['search'=>$search,'status'=>$status,'from'=>$from,'to'=>$to]));

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($db_err): ?>
<div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:13px 16px;margin-bottom:16px;color:var(--red);font-size:13px">
  ⚠ Database error: <?= htmlspecialchars($db_err) ?> — <a href="setup-database.php" style="color:var(--acc);text-decoration:underline">Run setup</a>
</div>
<?php endif; ?>

<!-- ── Filter Bar ── -->
<form method="GET" id="filterForm">
  <div class="fbar">
    <div class="sbox">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, invoice, contact…">
    </div>

    <select name="status" class="f-sel" onchange="this.form.submit()">
      <option value="all"     <?= $status==='all'    ?'selected':'' ?>>All Statuses</option>
      <option value="paid"    <?= $status==='paid'   ?'selected':'' ?>>Paid</option>
      <option value="partial" <?= $status==='partial'?'selected':'' ?>>Partial</option>
      <option value="unpaid"  <?= $status==='unpaid' ?'selected':'' ?>>Unpaid</option>
    </select>

    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="f-input" title="From date" onchange="this.form.submit()">
    <input type="date" name="to"   value="<?= htmlspecialchars($to) ?>"   class="f-input" title="To date"   onchange="this.form.submit()">

    <button type="submit" class="btn btn-s btn-sm">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      Search
    </button>

    <a href="export-csv.php?<?= $qs ?>" class="btn btn-g btn-sm" title="Download filtered receipts as CSV">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
      Export CSV
    </a>

    <?php if ($search || $status !== 'all' || $from || $to): ?>
    <a href="clients.php" class="btn btn-s btn-sm">✕ Clear</a>
    <?php endif; ?>
  </div>
</form>

<!-- ── Mini Stats ── -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
  <div style="background:var(--card);border:1px solid var(--br);border-radius:var(--rs);padding:13px 16px">
    <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Total Results</div>
    <div style="font-size:19px;font-weight:700;color:var(--txt)"><?= number_format($total_records) ?> <span style="font-size:13px;font-weight:400;color:var(--txt3)">receipts</span></div>
  </div>
  <div style="background:var(--card);border:1px solid var(--br);border-radius:var(--rs);padding:13px 16px">
    <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Total Value</div>
    <div style="font-size:19px;font-weight:700;color:var(--blue)">GH₵ <?= number_format($fs['rev'],2) ?></div>
  </div>
  <div style="background:var(--card);border:1px solid var(--br);border-radius:var(--rs);padding:13px 16px">
    <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Collected</div>
    <div style="font-size:19px;font-weight:700;color:var(--green)">GH₵ <?= number_format($fs['coll'],2) ?></div>
  </div>
</div>

<!-- ── Table ── -->
<div class="card">
  <?php if (empty($rows)): ?>
  <div class="empty">
    <div class="empty-ic">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>
    </div>
    <h3>No receipts found</h3>
    <p><?= ($search || $status !== 'all' || $from || $to)
        ? 'No receipts match your current filters.'
        : 'You have not issued any receipts yet.' ?></p>
    <?php if ($search || $status !== 'all' || $from || $to): ?>
      <a href="clients.php" class="btn btn-s">Clear Filters</a>
    <?php else: ?>
      <a href="new-receipt.php" class="btn btn-p">Create First Receipt</a>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Client</th>
          <th>Invoice No</th>
          <th>Total (GHS)</th>
          <th>Paid (GHS)</th>
          <th>Balance (GHS)</th>
          <th>Status</th>
          <th>Date</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $bal  = (float)$r['total'] - (float)$r['paid'];
          $paid = (float)$r['paid'];
          if ($bal <= 0)   { $st='paid';    $sl='Paid'; }
          elseif ($paid>0) { $st='partial'; $sl='Partial'; }
          else             { $st='unpaid';  $sl='Unpaid'; }
          $init = strtoupper(substr(trim($r['name']),0,2));
        ?>
        <tr>
          <td style="color:var(--txt3);font-size:12px"><?= $r['id'] ?></td>
          <td>
            <div class="cc">
              <div class="av"><?= htmlspecialchars($init) ?></div>
              <div>
                <div class="cn"><?= htmlspecialchars($r['name']) ?></div>
                <div class="csub"><?= htmlspecialchars($r['contact']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-family:monospace;font-size:12px;color:var(--txt2)"><?= htmlspecialchars($r['invoice_no']) ?></td>
          <td class="am-cell">GH₵&nbsp;<?= number_format($r['total'],2) ?></td>
          <td class="am-cell am-zero">GH₵&nbsp;<?= number_format($r['paid'],2) ?></td>
          <td class="am-cell <?= $bal<=0?'am-zero':($paid>0?'am-part':'am-full') ?>">
            GH₵&nbsp;<?= number_format(max(0,$bal),2) ?>
          </td>
          <td><span class="badge bg-<?= $st ?>"><span class="bdot"></span><?= $sl ?></span></td>
          <td style="color:var(--txt3);font-size:12px;white-space:nowrap"><?= htmlspecialchars($r['date']) ?></td>
          <td>
            <div class="acts" style="justify-content:flex-end">
              <a href="view-receipt.php?id=<?= $r['id'] ?>" class="btn btn-b btn-sm" title="View Details">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </a>
              <a href="edit-receipt.php?id=<?= $r['id'] ?>" class="btn btn-s btn-sm" title="Edit Receipt">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </a>
              <button
                class="btn btn-d btn-sm"
                onclick="confirmDel(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['name'])) ?>')"
                title="Delete Receipt">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pag">
    <div class="pag-inf">
      Showing <?= number_format(($page-1)*$pp+1) ?>–<?= number_format(min($page*$pp,$total_records)) ?>
      of <?= number_format($total_records) ?> receipts
    </div>
    <div class="pag-btns">
      <?php if ($page > 1): ?>
        <a class="pb" href="?<?= $qs ?>&page=<?= $page-1 ?>">&#8249;</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
        <a class="pb <?= $i===$page?'active':'' ?>" href="?<?= $qs ?>&page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a class="pb" href="?<?= $qs ?>&page=<?= $page+1 ?>">&#8250;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── Delete Modal ── -->
<div class="overlay" id="delOverlay">
  <div class="modal">
    <h3>Delete Receipt?</h3>
    <p>
      You are about to permanently delete the receipt for
      <strong id="delName" style="color:var(--txt)"></strong>.
      This action <strong>cannot be undone</strong>.
    </p>
    <div class="m-acts">
      <button class="btn btn-s" onclick="document.getElementById('delOverlay').classList.remove('show')">Cancel</button>
      <button class="btn btn-d" id="delBtn" onclick="doDelete()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        Delete
      </button>
    </div>
  </div>
</div>

<script>
let _delId = null;

function confirmDel(id, name) {
  _delId = id;
  document.getElementById('delName').textContent = name;
  document.getElementById('delOverlay').classList.add('show');
}

async function doDelete() {
  if (!_delId) return;
  const btn = document.getElementById('delBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div>';
  try {
    const r = await fetch('delete-receipt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: _delId })
    });
    const result = await r.json();
    if (r.ok && result.success) {
      document.getElementById('delOverlay').classList.remove('show');
      toast('Receipt deleted successfully');
      setTimeout(() => location.reload(), 1100);
    } else {
      throw new Error(result.error || 'Delete failed');
    }
  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = 'Delete';
    toast('Error: ' + err.message, 'err');
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
