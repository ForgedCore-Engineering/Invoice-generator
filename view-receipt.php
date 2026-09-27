<?php
/**
 * View Receipt — ForgedCore Receipt Manager
 */
require_once __DIR__ . '/config.php';

$id      = (int)($_GET['id'] ?? 0);
$receipt = null;
$error   = null;

if ($id <= 0) {
    header('Location: clients.php');
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receipt) $error = 'Receipt not found.';
} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title    = $receipt ? 'Receipt ' . htmlspecialchars($receipt['invoice_no']) : 'View Receipt';
$page_subtitle = $receipt ? 'Issued to ' . htmlspecialchars($receipt['name']) . ' · ' . htmlspecialchars($receipt['date']) : '';

require_once __DIR__ . '/includes/header.php';

if ($error): ?>
<div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:14px 18px;color:var(--red);font-size:13px;margin-bottom:16px">
  ⚠ <?= htmlspecialchars($error) ?>
</div>
<a href="clients.php" class="btn btn-s">← Back to Receipts</a>

<?php else:
  $bal  = (float)$receipt['total'] - (float)$receipt['paid'];
  $paid = (float)$receipt['paid'];
  if ($bal <= 0)   { $st='paid';    $sl='Fully Paid'; }
  elseif ($paid>0) { $st='partial'; $sl='Partial Payment'; }
  else             { $st='unpaid';  $sl='Unpaid'; }
  $init = strtoupper(substr(trim($receipt['name']),0,2));
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
  <a href="clients.php" class="btn btn-s btn-sm">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    All Receipts
  </a>
  <span>›</span>
  <span style="color:var(--acc);font-family:monospace"><?= htmlspecialchars($receipt['invoice_no']) ?></span>
</div>

<div class="g2">

  <!-- ── Main Receipt Card ── -->
  <div class="pv-col">

    <!-- Header -->
    <div class="card">
      <div class="card-hdr">
        <div style="display:flex;align-items:center;gap:14px">
          <div class="av" style="width:48px;height:48px;font-size:16px;flex-shrink:0"><?= htmlspecialchars($init) ?></div>
          <div>
            <div style="font-size:16px;font-weight:600;color:var(--txt)"><?= htmlspecialchars($receipt['name']) ?></div>
            <div style="font-size:12.5px;color:var(--txt3)"><?= htmlspecialchars($receipt['contact']) ?></div>
          </div>
        </div>
        <span class="badge bg-<?= $st ?>" style="font-size:13px">
          <span class="bdot"></span><?= $sl ?>
        </span>
      </div>

      <!-- Info row -->
      <div class="pv-section">
        <div class="info-row">
          <div class="inf-item">
            <div class="inf-lbl">Invoice No</div>
            <div class="inf-val" style="font-family:monospace;color:var(--acc)"><?= htmlspecialchars($receipt['invoice_no']) ?></div>
          </div>
          <div class="inf-item">
            <div class="inf-lbl">Date Issued</div>
            <div class="inf-val"><?= htmlspecialchars($receipt['date']) ?></div>
          </div>
          <div class="inf-item">
            <div class="inf-lbl">Address</div>
            <div class="inf-val"><?= htmlspecialchars($receipt['address']) ?></div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="pv-section">
        <div class="inf-lbl" style="margin-bottom:8px">Service / Description</div>
        <div class="desc-box">
          <?= htmlspecialchars($receipt['description']) ?>
        </div>
      </div>

      <!-- Payment Breakdown -->
      <div class="pv-section">
        <div class="inf-lbl" style="margin-bottom:12px">Payment Breakdown</div>

        <div class="pay-table">
          <div class="pay-row-hdr">
            <span>Description</span><span>Amount (GHS)</span>
          </div>

          <div class="pay-row">
            <span class="pay-row-lbl">Total Invoice Amount</span>
            <span class="pay-row-val">GH₵ <?= number_format($receipt['total'],2) ?></span>
          </div>
          <div class="pay-row">
            <span class="pay-row-lbl">Amount Paid</span>
            <span class="pay-row-val" style="color:var(--green)">GH₵ <?= number_format($receipt['paid'],2) ?></span>
          </div>
          <div class="pay-row pay-row-total">
            <span class="pay-row-lbl">Outstanding Balance</span>
            <span class="pay-row-val" style="color:<?= $bal<=0?'var(--green)':'var(--red)' ?>">
              GH₵ <?= number_format(max(0,$bal),2) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- ── Right Column ── -->
  <div class="pv-col">

    <!-- Actions -->
    <div class="card">
      <div class="card-hdr"><div class="card-title">Actions</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <button id="dlBtn" onclick="downloadPDF()" class="btn btn-p btn-lg" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Download Receipt PDF
        </button>
        <a href="edit-receipt.php?id=<?= $receipt['id'] ?>" class="btn btn-b" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          Edit Receipt
        </a>
        <a href="clients.php" class="btn btn-s" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
          All Receipts
        </a>
        <a href="new-receipt.php" class="btn btn-s" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          Create New Receipt
        </a>
        <button onclick="openDelModal()" class="btn btn-d" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          Delete This Receipt
        </button>
      </div>
    </div>

    <!-- Receipt Meta -->
    <div class="card">
      <div class="card-hdr"><div class="card-title">Receipt Info</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <div>
          <div class="inf-lbl">Record ID</div>
          <div class="inf-val" style="font-family:monospace">#<?= $receipt['id'] ?></div>
        </div>
        <div style="height:1px;background:var(--br)"></div>
        <div>
          <div class="inf-lbl">Saved On</div>
          <div class="inf-val" style="font-size:13px">
            <?= isset($receipt['created_at']) ? date('D, d M Y · g:i A', strtotime($receipt['created_at'])) : '—' ?>
          </div>
        </div>
        <?php if ($bal > 0): ?>
        <div style="height:1px;background:var(--br)"></div>
        <div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.2);border-radius:var(--rs);padding:10px 12px;font-size:12.5px;color:var(--red)">
          ⚠ This receipt has an outstanding balance of <strong>GH₵ <?= number_format($bal,2) ?></strong>.
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /right column -->

<?php if ($bal > 0): ?>
  <!-- ── Record Payment Panel ── -->
  <div class="card" id="recordPaymentCard" style="margin-top:0">
    <div class="card-hdr">
      <div>
        <div class="card-title">Record a Payment</div>
        <div class="card-sub">Outstanding: <span id="rpOutstanding" style="color:var(--red);font-weight:700">GH₵ <?= number_format(max(0,$bal),2) ?></span></div>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

      <div class="fg" style="margin:0">
        <label class="req" for="rpAmount" style="font-size:12px;color:var(--txt2);font-weight:600;margin-bottom:6px;display:block">New Payment Amount (GHS)</label>
        <div class="ipfx">
          <span>₵</span>
          <input type="number" id="rpAmount" step="0.01" min="0.01" max="<?= number_format(max(0,$bal),2,'.','') ?>" placeholder="0.00" style="width:100%" oninput="rpUpdatePreview()">
        </div>
      </div>

      <!-- Live balance preview -->
      <div id="rpPreview" style="display:none;background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);padding:12px 14px;font-size:12.5px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="color:var(--txt2)">Current paid</span>
          <span style="color:var(--txt)">GH₵ <?= number_format((float)$receipt['paid'],2) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="color:var(--txt2)">+ This payment</span>
          <span id="rpThisAmt" style="color:var(--green);font-weight:600">GH₵ 0.00</span>
        </div>
        <div style="height:1px;background:var(--br);margin:4px 0 8px"></div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--txt2)">New outstanding</span>
          <span id="rpNewBal" style="font-weight:700">GH₵ 0.00</span>
        </div>
      </div>

      <div id="rpErr" style="display:none;background:var(--red-bg);border:1px solid rgba(239,68,68,.25);border-radius:var(--rs);padding:10px 12px;font-size:12.5px;color:var(--red)"></div>

      <button id="rpBtn" onclick="submitPayment('receipt', <?= (int)$receipt['id'] ?>)" class="btn btn-p btn-lg" style="justify-content:center">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
        Record Payment
      </button>
    </div>
  </div>
<?php endif; ?>

</div><!-- /g2 -->

<!-- ── Delete Modal ── -->
<div class="overlay" id="delOv">
  <div class="modal">
    <h3>Delete This Receipt?</h3>
    <p>
      This will permanently delete receipt
      <strong style="color:var(--acc)"><?= htmlspecialchars($receipt['invoice_no']) ?></strong>
      for <strong style="color:var(--txt)"><?= htmlspecialchars($receipt['name']) ?></strong>.
      This action cannot be undone.
    </p>
    <div class="m-acts">
      <button class="btn btn-s" onclick="document.getElementById('delOv').classList.remove('show')">Cancel</button>
      <button class="btn btn-d" id="delBtn" onclick="doDelete()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        Delete
      </button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/letterhead-pdf.js?v=2"></script>
<script src="assets/receipt-pdf.js?v=1"></script>
<script>
/* ── Record Payment ── */
const _rpMax = <?= number_format(max(0,$bal),2,'.','') ?>;

function rpUpdatePreview() {
  const inp = document.getElementById('rpAmount');
  const val = parseFloat(inp.value) || 0;
  const preview = document.getElementById('rpPreview');
  const err = document.getElementById('rpErr');
  err.style.display = 'none';
  if (val <= 0) { preview.style.display = 'none'; return; }
  preview.style.display = 'block';
  const applied = Math.min(val, _rpMax);
  const newBal  = Math.max(0, _rpMax - applied);
  document.getElementById('rpThisAmt').textContent = 'GH₵ ' + applied.toFixed(2);
  const balEl = document.getElementById('rpNewBal');
  balEl.textContent = 'GH₵ ' + newBal.toFixed(2);
  balEl.style.color = newBal <= 0 ? 'var(--green)' : 'var(--ylw)';
  document.getElementById('rpOutstanding').textContent = 'Outstanding: GH₵ ' + _rpMax.toFixed(2);
  if (val > _rpMax) inp.style.borderColor = 'var(--ylw)';
  else inp.style.borderColor = '';
}

async function submitPayment(type, id) {
  const inp = document.getElementById('rpAmount');
  const btn = document.getElementById('rpBtn');
  const err = document.getElementById('rpErr');
  const val = parseFloat(inp.value) || 0;
  err.style.display = 'none';
  if (val <= 0) {
    err.textContent = 'Please enter a payment amount greater than zero.';
    err.style.display = 'block';
    return;
  }
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Recording…';
  try {
    const r = await fetch('record-payment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, type, new_payment: val })
    });
    const res = await r.json();
    if (!r.ok || !res.success) throw new Error(res.error || 'Failed to record payment');
    let msg = res.message;
    if (res.capped) msg += ' (capped to outstanding balance)';
    toast(msg);
    setTimeout(() => location.reload(), 1200);
  } catch(e) {
    err.textContent = e.message;
    err.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Record Payment';
  }
}
</script>
<script>
const { jsPDF } = window.jspdf;
const RD = <?= json_encode([
  'name'        => $receipt['name'],
  'address'     => $receipt['address'],
  'contact'     => $receipt['contact'],
  'description' => $receipt['description'],
  'total'       => (float)$receipt['total'],
  'paid'        => (float)$receipt['paid'],
  'invoice_no'  => $receipt['invoice_no'],
  'date'        => $receipt['date'],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

async function downloadPDF() {
  const btn = document.getElementById('dlBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Generating PDF…';
  try {
    await generatePDF(RD);
    toast('PDF downloaded successfully!');
  } catch (e) {
    toast('PDF error: ' + e.message, 'err');
  }
  btn.disabled = false;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Download Receipt PDF';
}

function openDelModal() { document.getElementById('delOv').classList.add('show'); }

async function doDelete() {
  const btn = document.getElementById('delBtn');
  btn.disabled = true; btn.innerHTML = '<div class="spin"></div>';
  try {
    const r = await fetch('delete-receipt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: <?= (int)$receipt['id'] ?> })
    });
    const res = await r.json();
    if (r.ok && res.success) {
      toast('Receipt deleted');
      setTimeout(() => location.href = 'clients.php', 1200);
    } else throw new Error(res.error || 'Delete failed');
  } catch (e) {
    toast('Error: ' + e.message, 'err');
    btn.disabled = false; btn.innerHTML = 'Delete';
  }
}

/* ── PDF Generator (Letterhead) ── */
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
