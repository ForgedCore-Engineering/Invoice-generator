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

    <div style="padding:16px 20px;border-bottom:1px solid var(--br)">
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

    <div style="padding:16px 20px;border-bottom:1px solid var(--br)">
      <div class="inf-lbl" style="margin-bottom:8px">Services</div>
      <div style="background:var(--inp);border-radius:var(--rs);padding:13px 16px;font-size:14px;color:var(--txt);line-height:1.65;font-weight:500">
        <?= htmlspecialchars($payslip['service']) ?>
      </div>
    </div>

    <div style="padding:16px 20px">
      <div class="inf-lbl" style="margin-bottom:12px">Payment Summary</div>
      <div style="background:var(--inp);border-radius:var(--rs);overflow:hidden">
        <div style="display:flex;justify-content:space-between;padding:10px 14px;background:rgba(255,255,255,.04);font-size:11px;font-weight:600;color:var(--txt2);text-transform:uppercase;letter-spacing:.5px">
          <span>Description</span><span>Amount (GHS)</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:1px solid var(--br)">
          <span style="font-size:13.5px;color:var(--txt2)">Amount Supposed To Be Paid</span>
          <span style="font-size:16px;font-weight:700;color:var(--txt)">GH₵ <?= number_format((float)$payslip['amount_due'], 2) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:1px solid var(--br)">
          <span style="font-size:13.5px;color:var(--txt2)">Amount Paid</span>
          <span style="font-size:16px;font-weight:700;color:var(--green)">GH₵ <?= number_format((float)$payslip['amount_paid'], 2) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:1px solid var(--br)">
          <span style="font-size:13.5px;color:var(--txt2)">Left To Pay</span>
          <span style="font-size:16px;font-weight:700;color:<?= $leftToPay > 0 ? 'var(--red)' : 'var(--green)' ?>">GH₵ <?= number_format($leftToPay, 2) ?></span>
        </div>
      </div>
      <div style="margin-top:10px;padding:9px 11px;border-radius:var(--rs);background:<?= $leftToPay > 0 ? 'var(--ylw-bg)' : 'var(--grn-bg)' ?>;color:<?= $leftToPay > 0 ? 'var(--ylw)' : 'var(--green)' ?>;font-size:12px">
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
  const doc = new jsPDF();
  const pw = doc.internal.pageSize.getWidth();
  const ph = doc.internal.pageSize.getHeight();
  const mg = 18;
  let y = 25;
  const amountLeft = Math.max(0, Number(d.amount_due) - Number(d.amount_paid));
  const status = amountLeft <= 0 ? 'FULLY PAID' : (Number(d.amount_paid) > 0 ? 'PARTIALLY PAID' : 'UNPAID');

  doc.setFillColor(242, 247, 250);
  doc.roundedRect(mg, y - 4, pw - (mg * 2), 30, 3, 3, 'F');

  try {
    const logo = await loadImg('receipts/static/logo.png');
    doc.addImage(logo, 'PNG', mg + 2, y - 1, 24, 24);
  } catch (e) {}

  doc.setFont('times', 'bold');
  doc.setFontSize(13);
  doc.text('FORGEDCORE ENGINEERING LTD', pw - mg - 2, y + 6, { align: 'right' });
  doc.setFont('times', 'normal');
  doc.setFontSize(9);
  doc.text('Kpobiman (Amasaman), Accra', pw - mg - 2, y + 12, { align: 'right' });
  doc.text('0540202096 / 0545286665', pw - mg - 2, y + 17, { align: 'right' });
  doc.text('forgedcoreengineering@gmail.com', pw - mg - 2, y + 22, { align: 'right' });
  y += 38;

  doc.setFont('times', 'bold');
  doc.setFontSize(20);
  doc.text('PAYSLIP', pw / 2, y, { align: 'center' });
  doc.setFontSize(10);
  doc.setTextColor(70, 70, 70);
  doc.text('Official payment record', pw / 2, y + 5, { align: 'center' });
  doc.setTextColor(0, 0, 0);
  y += 14;

  doc.setDrawColor(220, 220, 220);
  doc.line(mg, y, pw - mg, y);
  y += 8;

  doc.setFontSize(10.5);
  doc.setFont('times', 'bold');
  doc.text('Payslip No:', mg, y);
  doc.setFont('times', 'normal');
  doc.text(d.payslip_no, mg + 23, y);
  doc.setFont('times', 'bold');
  doc.text('Issue Date:', pw - mg - 40, y);
  doc.setFont('times', 'normal');
  doc.text(d.issue_date, pw - mg, y, { align: 'right' });
  y += 14;

  doc.setFont('times', 'bold');
  doc.text('Full Name:', mg, y);
  doc.setFont('times', 'normal');
  doc.text(d.full_name, mg + 20, y);
  y += 10;

  doc.setFont('times', 'bold');
  doc.text('Services:', mg, y);
  doc.setFont('times', 'normal');
  const serviceLines = doc.splitTextToSize(d.service, pw - (mg * 2) - 18);
  doc.text(serviceLines, mg + 18, y);
  y += Math.max(16, serviceLines.length * 6 + 6);

  const tableW = pw - mg * 2;
  doc.setDrawColor(210, 210, 210);
  doc.roundedRect(mg, y, tableW, 39, 2, 2, 'S');
  doc.setFillColor(17, 32, 56);
  doc.roundedRect(mg, y, tableW, 9, 2, 2, 'F');
  doc.setFont('times', 'bold');
  doc.setTextColor(255, 255, 255);
  doc.text('DESCRIPTION', mg + 4, y + 5.5);
  doc.text('AMOUNT (GHS)', mg + tableW - 4, y + 5.5, { align: 'right' });
  doc.setTextColor(0, 0, 0);

  doc.setFont('times', 'normal');
  doc.text('Amount Supposed To Be Paid', mg + 4, y + 16);
  doc.text(Number(d.amount_due).toFixed(2), mg + tableW - 4, y + 16, { align: 'right' });
  doc.text('Amount Paid', mg + 4, y + 25);
  doc.text(Number(d.amount_paid).toFixed(2), mg + tableW - 4, y + 25, { align: 'right' });
  doc.setFont('times', 'bold');
  doc.text('Amount Left To Pay', mg + 4, y + 34);
  doc.setTextColor(amountLeft > 0 ? 220 : 0, amountLeft > 0 ? 0 : 128, 0);
  doc.text(Number(amountLeft).toFixed(2), mg + tableW - 4, y + 34, { align: 'right' });
  doc.setTextColor(0, 0, 0);
  y += 48;

  doc.setFillColor(
    amountLeft <= 0 ? 231 : (Number(d.amount_paid) > 0 ? 254 : 254),
    amountLeft <= 0 ? 245 : (Number(d.amount_paid) > 0 ? 243 : 242),
    amountLeft <= 0 ? 232 : (Number(d.amount_paid) > 0 ? 199 : 199)
  );
  doc.roundedRect(mg, y - 2, 70, 10, 2, 2, 'F');
  doc.setFontSize(9.5);
  doc.setFont('times', 'bold');
  doc.setTextColor(
    amountLeft <= 0 ? 22 : (Number(d.amount_paid) > 0 ? 161 : 185),
    amountLeft <= 0 ? 101 : (Number(d.amount_paid) > 0 ? 98 : 28),
    amountLeft <= 0 ? 52 : (Number(d.amount_paid) > 0 ? 7 : 28)
  );
  doc.text('STATUS: ' + status, mg + 4, y + 4.5);
  doc.setTextColor(0, 0, 0);

  doc.setFont('times', 'bold');
  doc.setFontSize(10.5);
  doc.text('Authorized Signature:', pw - mg - 68, y);
  y += 12;
  try {
    const sig = await loadImg('receipts/static/signature.png');
    doc.addImage(sig, 'PNG', pw - mg - 68, y, 50, 16);
    y += 20;
  } catch (e) {
    y += 8;
  }
  doc.setFont('times', 'normal');
  doc.text('Eyram Dela Kuwornu', pw - mg - 68, y);
  doc.setFontSize(9);
  doc.text('(Director - Forgedcore Engineering Ltd)', pw - mg - 68, y + 6);

  doc.setFont('times', 'italic');
  doc.setTextColor(80, 80, 80);
  doc.text('This document is computer generated and valid without stamp.', pw / 2, ph - 12, { align: 'center' });
  doc.setTextColor(0, 0, 0);

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
