<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: payslips.php');
    exit;
}

$payslip = null;
$error = null;
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM payslips WHERE id = ?");
    $stmt->execute([$id]);
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payslip) $error = 'Payslip not found.';
} catch (Exception $e) {
    $error = $e->getMessage();
}

$leftToPay = $payslip ? max(0, (float)$payslip['amount_due'] - (float)$payslip['amount_paid']) : 0;
$payStatus = $leftToPay <= 0 ? 'Fully Paid' : ((float)$payslip['amount_paid'] > 0 ? 'Partially Paid' : 'Unpaid');
$payStatusClass = $leftToPay <= 0 ? 'bg-paid' : ((float)$payslip['amount_paid'] > 0 ? 'bg-partial' : 'bg-unpaid');

$page_title = $payslip ? 'Payslip ' . htmlspecialchars($payslip['payslip_no']) : 'View Payslip';
$page_subtitle = $payslip ? htmlspecialchars($payslip['full_name']) : '';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?>
<div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:14px 18px;color:var(--red);font-size:13px;margin-bottom:16px">
  ⚠ <?= htmlspecialchars($error) ?>
</div>
<a href="payslips.php" class="btn btn-s">← Back to Payslips</a>
<?php else: ?>

<div class="breadcrumb">
  <a href="payslips.php" class="btn btn-s btn-sm">All Payslips</a>
  <span>›</span>
  <span style="color:var(--acc);font-family:monospace"><?= htmlspecialchars($payslip['payslip_no']) ?></span>
</div>

<div class="g2">
  <div class="card">
    <div class="card-hdr">
      <div class="card-title"><?= htmlspecialchars($payslip['full_name']) ?></div>
      <span class="badge <?= $payStatusClass ?>"><span class="bdot"></span><?= htmlspecialchars($payStatus) ?></span>
    </div>

    <div class="pv-section">
      <div class="info-row">
        <div class="inf-item">
          <div class="inf-lbl">Payslip No</div>
          <div class="inf-val" style="font-family:monospace;color:var(--acc)"><?= htmlspecialchars($payslip['payslip_no']) ?></div>
        </div>
        <div class="inf-item">
          <div class="inf-lbl">Issue Date</div>
          <div class="inf-val"><?= htmlspecialchars($payslip['issue_date']) ?></div>
        </div>
        <div class="inf-item">
          <div class="inf-lbl">Created</div>
          <div class="inf-val"><?= !empty($payslip['created_at']) ? date('D, d M Y · g:i A', strtotime($payslip['created_at'])) : '—' ?></div>
        </div>
        <div class="inf-item">
          <div class="inf-lbl">Last Updated</div>
          <div class="inf-val"><?= !empty($payslip['updated_at']) ? date('D, d M Y · g:i A', strtotime($payslip['updated_at'])) : '—' ?></div>
        </div>
      </div>
    </div>

    <div class="pv-section">
      <div class="inf-lbl" style="margin-bottom:8px">Services</div>
      <div class="desc-box">
        <?= htmlspecialchars($payslip['service']) ?>
      </div>
    </div>

    <div class="pv-section">
      <div class="inf-lbl" style="margin-bottom:12px">Payment Summary</div>
      <div class="pay-table">
        <div class="pay-row-hdr">
          <span>Description</span><span>Amount (GHS)</span>
        </div>
        <div class="pay-row">
          <span class="pay-row-lbl">Amount Supposed To Be Paid</span>
          <span class="pay-row-val">GH₵ <?= number_format((float)$payslip['amount_due'], 2) ?></span>
        </div>
        <div class="pay-row">
          <span class="pay-row-lbl">Amount Paid</span>
          <span class="pay-row-val" style="color:var(--green)">GH₵ <?= number_format((float)$payslip['amount_paid'], 2) ?></span>
        </div>
        <div class="pay-row">
          <span class="pay-row-lbl">Left To Pay</span>
          <span class="pay-row-val" style="color:<?= $leftToPay > 0 ? 'var(--red)' : 'var(--green)' ?>">GH₵ <?= number_format($leftToPay, 2) ?></span>
        </div>
      </div>
      <div class="status-note" style="background:<?= $leftToPay > 0 ? 'var(--ylw-bg)' : 'var(--grn-bg)' ?>;color:<?= $leftToPay > 0 ? 'var(--ylw)' : 'var(--green)' ?>">
        <?= $leftToPay > 0 ? 'There is still an outstanding amount to be paid.' : 'Payment is complete. No outstanding amount.' ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-hdr"><div class="card-title">Actions</div></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
      <button id="dlBtn" onclick="downloadPDF()" class="btn btn-p btn-lg" style="justify-content:center">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        Download Payslip PDF
      </button>
      <a href="edit-payslip.php?id=<?= (int)$payslip['id'] ?>" class="btn btn-b" style="justify-content:center">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        Edit Payslip
      </a>
      <button onclick="openDelModal()" class="btn btn-d" style="justify-content:center">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        Delete Payslip
      </button>
      <a href="payslips.php" class="btn btn-s" style="justify-content:center">All Payslips</a>
      <a href="new-payslip.php" class="btn btn-s" style="justify-content:center">Create New Payslip</a>
    </div>
  </div>
</div>

<?php if ($leftToPay > 0): ?>
<!-- ── Record Payment Panel ── -->
<div class="card" id="recordPaymentCard">
  <div class="card-hdr">
    <div>
      <div class="card-title">Record a Payment</div>
      <div class="card-sub">Outstanding: <span id="rpOutstanding" style="color:var(--red);font-weight:700">GH₵ <?= number_format($leftToPay,2) ?></span></div>
    </div>
  </div>
  <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

    <div class="fg" style="margin:0">
      <label class="req" for="rpAmount" style="font-size:12px;color:var(--txt2);font-weight:600;margin-bottom:6px;display:block">New Payment Amount (GHS)</label>
      <div class="ipfx">
        <span>₵</span>
        <input type="number" id="rpAmount" step="0.01" min="0.01" max="<?= number_format($leftToPay,2,'.','') ?>" placeholder="0.00" style="width:100%" oninput="rpUpdatePreview()">
      </div>
    </div>

    <!-- Live balance preview -->
    <div id="rpPreview" style="display:none;background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);padding:12px 14px;font-size:12.5px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px">
        <span style="color:var(--txt2)">Current paid</span>
        <span style="color:var(--txt)">GH₵ <?= number_format((float)$payslip['amount_paid'],2) ?></span>
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

    <button id="rpBtn" onclick="submitPayment('payslip', <?= (int)$payslip['id'] ?>)" class="btn btn-p btn-lg" style="justify-content:center">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
      Record Payment
    </button>
  </div>
</div>
<?php endif; ?>

<div class="overlay" id="delOverlay">
  <div class="modal">
    <h3>Delete This Payslip?</h3>
    <p>
      This will permanently delete payslip
      <strong style="color:var(--acc)"><?= htmlspecialchars($payslip['payslip_no']) ?></strong>
      for <strong style="color:var(--txt)"><?= htmlspecialchars($payslip['full_name']) ?></strong>.
    </p>
    <div class="m-acts">
      <button class="btn btn-s" onclick="document.getElementById('delOverlay').classList.remove('show')">Cancel</button>
      <button class="btn btn-d" id="delBtn" onclick="doDelete()">Delete</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/letterhead-pdf.js"></script>
<?php if ($leftToPay > 0): ?>
<script>
/* ── Record Payment ── */
const _rpMax = <?= number_format($leftToPay,2,'.','') ?>;

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
<?php endif; ?>
<script>
const { jsPDF } = window.jspdf;
const PS = <?= json_encode([
  'full_name' => $payslip['full_name'],
  'service' => $payslip['service'],
  'amount_due' => (float)$payslip['amount_due'],
  'amount_paid' => (float)$payslip['amount_paid'],
  'payslip_no' => $payslip['payslip_no'],
  'issue_date' => $payslip['issue_date'],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

async function downloadPDF() {
  const btn = document.getElementById('dlBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Generating PDF...';
  try {
    await generatePDF(PS);
    toast('Payslip PDF downloaded');
  } catch (e) {
    toast('PDF error: ' + e.message, 'err');
  }
  btn.disabled = false;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Download Payslip PDF';
}

function openDelModal() {
  document.getElementById('delOverlay').classList.add('show');
}

async function doDelete() {
  const btn = document.getElementById('delBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div>';
  try {
    const r = await fetch('delete-payslip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: <?= (int)$payslip['id'] ?> })
    });
    const result = await r.json();
    if (r.ok && result.success) {
      toast('Payslip deleted');
      setTimeout(() => location.href = 'payslips.php', 900);
    } else {
      throw new Error(result.error || 'Delete failed');
    }
  } catch (e) {
    btn.disabled = false;
    btn.innerHTML = 'Delete';
    toast('Error: ' + e.message, 'err');
  }
}

async function generatePDF(d) {
  // Create A4 doc with ForgedCore letterhead background
  const { doc, pw, ph, mg, safeTop, safeBottom } = await createLetterheadDoc();
  const cw = pw - mg * 2;
  let y = safeTop;
  const amountLeft = Math.max(0, Number(d.amount_due) - Number(d.amount_paid));
  const status = amountLeft <= 0 ? 'FULLY PAID' : (Number(d.amount_paid) > 0 ? 'PARTIALLY PAID' : 'UNPAID');

  doc.setFont('times');

  // ── Document Title ──
  doc.setFontSize(20); doc.setFont('times', 'bold');
  doc.setTextColor(13, 46, 70);
  doc.text('PAYSLIP', pw / 2, y, { align: 'center' });
  y += 7;

  // Teal underline accent
  doc.setDrawColor(0, 128, 115);
  doc.setLineWidth(0.6);
  doc.line(pw / 2 - 16, y, pw / 2 + 16, y);
  doc.setLineWidth(0.2);
  y += 10;

  // Sub-label
  doc.setFontSize(9); doc.setFont('times', 'italic'); doc.setTextColor(100, 100, 100);
  doc.text('Official Payment Record', pw / 2, y, { align: 'center' });
  y += 12;

  // ── Payslip Meta (two-column) ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('PAYSLIP NO', mg, y);
  doc.text('ISSUE DATE', pw / 2 + 2, y);
  y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(13, 46, 70);
  doc.text(d.payslip_no, mg, y);
  doc.text(d.issue_date, pw / 2 + 2, y);
  y += 12;

  // ── Employee Name ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('FULL NAME', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  doc.text(d.full_name, mg, y); y += 12;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Services ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('SERVICES', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  const serviceLines = doc.splitTextToSize(d.service, cw);
  doc.text(serviceLines, mg, y);
  y += serviceLines.length * 6 + 10;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Payment Table ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('PAYMENT SUMMARY', mg, y); y += 7;

  const rh = 10, tTop = y;
  // Table header row
  doc.setFillColor(13, 46, 70);
  doc.roundedRect(mg, tTop, cw, rh, 1.5, 1.5, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(255, 255, 255);
  doc.text('DESCRIPTION', mg + 4, tTop + 6.5);
  doc.text('AMOUNT (GHS)', pw - mg - 4, tTop + 6.5, { align: 'right' });

  [
    { desc: 'Amount Supposed To Be Paid', amt: Number(d.amount_due).toFixed(2),  color: null },
    { desc: 'Amount Paid',                amt: Number(d.amount_paid).toFixed(2), color: [0, 128, 64] },
    { desc: 'Amount Left To Pay',         amt: Number(amountLeft).toFixed(2),   color: amountLeft > 0 ? [200, 30, 30] : [0, 128, 64] },
  ].forEach((row, i) => {
    const rY = tTop + rh + i * rh;
    doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 250 : 255, i % 2 === 0 ? 252 : 255);
    doc.rect(mg, rY, cw, rh, 'F');
    doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(30, 30, 30);
    doc.text(row.desc, mg + 4, rY + 6.5);
    if (row.color) { doc.setTextColor(...row.color); doc.setFont('times', 'bold'); }
    doc.text(row.amt, pw - mg - 4, rY + 6.5, { align: 'right' });
    doc.setTextColor(30, 30, 30);
  });
  // Table border
  doc.setDrawColor(210, 215, 220); doc.setLineWidth(0.3);
  doc.roundedRect(mg, tTop, cw, rh * 4, 1.5, 1.5, 'S');
  y = tTop + rh * 4 + 8;

  // ── Status Badge ──
  const [br, bg, bb] = amountLeft <= 0 ? [220, 252, 231] : (Number(d.amount_paid) > 0 ? [254, 243, 199] : [254, 226, 226]);
  const [tr2, tg2, tb2] = amountLeft <= 0 ? [21, 128, 61] : (Number(d.amount_paid) > 0 ? [146, 64, 14] : [153, 27, 27]);
  doc.setFillColor(br, bg, bb);
  doc.roundedRect(mg, y, 62, 9, 2, 2, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(tr2, tg2, tb2);
  doc.text('STATUS: ' + status, mg + 4, y + 6);
  doc.setTextColor(0, 0, 0);
  y += 16;

  // ── Signature ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(60, 60, 60);
  doc.text('Authorized Signature:', pw - mg - 68, y); y += 4;
  try {
    const sig = await loadImg('receipts/static/signature.png');
    doc.addImage(sig, 'PNG', pw - mg - 68, y, 55, 18); y += 22;
  } catch (e) { y += 14; }
  doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(20, 20, 20);
  doc.text('Eyram Dela Kuwornu', pw - mg - 68, y);
  doc.setFontSize(8.5); doc.setTextColor(100, 100, 100);
  doc.text('Director — Forgedcore Engineering Ltd', pw - mg - 68, y + 5);

  // ── Footer Note ──
  doc.setFont('times', 'italic'); doc.setFontSize(8); doc.setTextColor(140, 140, 140);
  doc.text('This document is computer generated and valid without stamp.', pw / 2, safeBottom - 2, { align: 'center' });

  doc.save('payslip_' + d.payslip_no.replace(/\//g, '_') + '.pdf');
}

function loadImg(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Image not found'));
    img.src = url;
  });
}
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
