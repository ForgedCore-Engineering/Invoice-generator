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
<script src="assets/letterhead-pdf.js"></script>
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
async function generatePDF(d) {
  // Create A4 doc with ForgedCore letterhead background
  const { doc, pw, ph, mg, safeTop, safeBottom } = await createLetterheadDoc();
  const cw = pw - mg * 2;
  let y = safeTop;

  doc.setFont('times');

  // ── Document Title ──
  doc.setFontSize(20); doc.setFont('times', 'bold');
  doc.setTextColor(13, 46, 70);
  doc.text('RECEIPT', pw / 2, y, { align: 'center' });
  y += 7;

  // Teal underline accent
  doc.setDrawColor(0, 128, 115);
  doc.setLineWidth(0.6);
  doc.line(pw / 2 - 18, y, pw / 2 + 18, y);
  doc.setLineWidth(0.2);
  y += 10;

  // ── Invoice Meta (two-column) ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('INVOICE NO', mg, y);
  doc.text('DATE ISSUED', pw / 2 + 2, y);
  y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(13, 46, 70);
  doc.text(d.invoice_no, mg, y);
  doc.text(d.date, pw / 2 + 2, y);
  y += 10;

  // ── Bill To ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('BILL TO', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  doc.text(d.name, mg, y); y += 6;
  doc.setFontSize(9.5); doc.setTextColor(80, 80, 80);
  doc.text(d.address + '   |   ' + d.contact, mg, y); y += 12;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Description ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('SERVICE / DESCRIPTION', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  const descLines = doc.splitTextToSize(d.description, cw);
  doc.text(descLines, mg, y);
  y += descLines.length * 6 + 10;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Payment Summary Table ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('PAYMENT SUMMARY', mg, y); y += 7;

  const rh = 10, tTop = y;
  // Table header row
  doc.setFillColor(13, 46, 70);
  doc.roundedRect(mg, tTop, cw, rh, 1.5, 1.5, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(255, 255, 255);
  doc.text('DESCRIPTION', mg + 4, tTop + 6.5);
  doc.text('AMOUNT (GHS)', pw - mg - 4, tTop + 6.5, { align: 'right' });

  const bal = d.total - d.paid;
  [
    { desc: 'Total Invoice Amount', amt: d.total.toFixed(2), color: null },
    { desc: 'Amount Paid',          amt: d.paid.toFixed(2),  color: [0, 128, 64] },
    { desc: 'Outstanding Balance',  amt: Math.max(0, bal).toFixed(2), color: bal > 0 ? [200, 30, 30] : [0, 128, 64] },
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
  const status = bal <= 0 ? 'FULLY PAID' : (d.paid > 0 ? 'PARTIALLY PAID' : 'UNPAID');
  const [br, bg, bb] = bal <= 0 ? [220, 252, 231] : (d.paid > 0 ? [254, 243, 199] : [254, 226, 226]);
  const [tr, tg, tb] = bal <= 0 ? [21, 128, 61]  : (d.paid > 0 ? [146, 64, 14]  : [153, 27, 27]);
  doc.setFillColor(br, bg, bb);
  doc.roundedRect(mg, y, 58, 9, 2, 2, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(tr, tg, tb);
  doc.text('STATUS: ' + status, mg + 4, y + 6);
  doc.setTextColor(0, 0, 0);
  y += 16;

  // ── Signature ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(60, 60, 60);
  doc.text('Authorized Signature:', pw - mg - 68, y); y += 4;
  doc.setDrawColor(180, 180, 180); doc.setLineWidth(0.2);
  try {
    const si = await loadImg('receipts/static/signature.png');
    doc.addImage(si, 'PNG', pw - mg - 68, y, 55, 18); y += 22;
  } catch(e) { y += 14; }
  doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(20, 20, 20);
  doc.text('Eyram Dela Kuwornu', pw - mg - 68, y);
  doc.setFontSize(8.5); doc.setTextColor(100, 100, 100);
  doc.text('Director — Forgedcore Engineering Ltd', pw - mg - 68, y + 5);

  // ── Footer Note ──
  doc.setFont('times', 'italic'); doc.setFontSize(8); doc.setTextColor(140, 140, 140);
  doc.text('This document is computer generated and valid without stamp.', pw / 2, safeBottom + 4, { align: 'center' });

  doc.save('receipt_' + d.invoice_no.replace(/\//g, '_') + '.pdf');
}

function loadImg(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload  = () => resolve(img);
    img.onerror = () => reject(new Error('Not found'));
    img.src = url;
  });
}
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
