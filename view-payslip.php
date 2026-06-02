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
      <span class="badge bg-paid"><span class="bdot"></span>Payslip Issued</span>
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
      <a href="payslips.php" class="btn btn-s" style="justify-content:center">All Payslips</a>
      <a href="new-payslip.php" class="btn btn-s" style="justify-content:center">Create New Payslip</a>
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

async function generatePDF(d) {
  const doc = new jsPDF();
  const pw = doc.internal.pageSize.getWidth();
  const mg = 25;
  let y = 25;

  try {
    const logo = await loadImg('receipts/static/logo.png');
    doc.addImage(logo, 'PNG', mg, y, 26, 26);
  } catch (e) {}

  doc.setFont('times', 'bold');
  doc.setFontSize(11);
  doc.text('FORGEDCORE ENGINEERING LTD', pw - mg, y + 8, { align: 'right' });
  doc.setFont('times', 'normal');
  doc.setFontSize(9);
  doc.text('Kpobiman (Amasaman), Accra', pw - mg, y + 14, { align: 'right' });
  doc.text('0540202096 / 0545286665 | forgedcoreengineering@gmail.com', pw - mg, y + 20, { align: 'right' });
  doc.text('www.forgedcoreengineering.com', pw - mg, y + 26, { align: 'right' });
  y += 40;

  doc.setFont('times', 'bold');
  doc.setFontSize(18);
  doc.text('PAYSLIP', pw / 2, y, { align: 'center' });
  y += 14;

  doc.setFontSize(10);
  doc.text('Payslip No:', mg, y);
  doc.setFont('times', 'normal');
  doc.text(d.payslip_no, mg + 25, y);
  doc.setFont('times', 'bold');
  doc.text('Date:', pw - mg - 20, y);
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
  y += Math.max(14, serviceLines.length * 6 + 4);

  const tableW = pw - mg * 2;
  doc.setDrawColor(200, 200, 200);
  doc.rect(mg, y, tableW, 30);
  doc.setFillColor(245, 245, 245);
  doc.rect(mg, y, tableW, 8, 'F');
  doc.setFont('times', 'bold');
  doc.text('DESCRIPTION', mg + 4, y + 5.5);
  doc.text('AMOUNT (GHS)', mg + tableW - 4, y + 5.5, { align: 'right' });

  doc.setFont('times', 'normal');
  doc.text('Amount Supposed To Be Paid', mg + 4, y + 15);
  doc.text(Number(d.amount_due).toFixed(2), mg + tableW - 4, y + 15, { align: 'right' });
  doc.text('Amount Paid', mg + 4, y + 24);
  doc.text(Number(d.amount_paid).toFixed(2), mg + tableW - 4, y + 24, { align: 'right' });

  y += 44;
  doc.setFont('times', 'bold');
  doc.text('Authorized Signature:', pw - mg - 65, y);
  y += 12;
  try {
    const sig = await loadImg('receipts/static/signature.png');
    doc.addImage(sig, 'PNG', pw - mg - 65, y, 52, 16);
    y += 20;
  } catch (e) {
    y += 8;
  }
  doc.setFont('times', 'normal');
  doc.text('Eyram Dela Kuwornu', pw - mg - 65, y);
  doc.setFontSize(9);
  doc.text('(Director - Forgedcore Engineering Ltd)', pw - mg - 65, y + 6);

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
