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
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:12.5px;color:var(--txt3)">
  <a href="clients.php" class="btn btn-s btn-sm">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    All Receipts
  </a>
  <span>›</span>
  <span style="color:var(--acc);font-family:monospace"><?= htmlspecialchars($receipt['invoice_no']) ?></span>
</div>

<div class="g2">

  <!-- ── Main Receipt Card ── -->
  <div style="display:flex;flex-direction:column;gap:16px">

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
      <div style="padding:16px 20px;border-bottom:1px solid var(--br)">
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
      <div style="padding:16px 20px;border-bottom:1px solid var(--br)">
        <div class="inf-lbl" style="margin-bottom:8px">Service / Description</div>
        <div style="background:var(--inp);border-radius:var(--rs);padding:13px 16px;font-size:14px;color:var(--txt);line-height:1.65;font-weight:500">
          <?= htmlspecialchars($receipt['description']) ?>
        </div>
      </div>

      <!-- Payment Breakdown -->
      <div style="padding:16px 20px">
        <div class="inf-lbl" style="margin-bottom:12px">Payment Breakdown</div>

        <div style="background:var(--inp);border-radius:var(--rs);overflow:hidden">
          <div style="display:flex;justify-content:space-between;padding:10px 14px;background:rgba(255,255,255,.04);font-size:11px;font-weight:600;color:var(--txt2);text-transform:uppercase;letter-spacing:.5px">
            <span>Description</span><span>Amount (GHS)</span>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:1px solid var(--br)">
            <span style="font-size:13.5px;color:var(--txt2)">Total Invoice Amount</span>
            <span style="font-size:16px;font-weight:700;color:var(--txt)">GH₵ <?= number_format($receipt['total'],2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:1px solid var(--br)">
            <span style="font-size:13.5px;color:var(--txt2)">Amount Paid</span>
            <span style="font-size:16px;font-weight:700;color:var(--green)">GH₵ <?= number_format($receipt['paid'],2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 14px;border-top:2px solid var(--br2);background:rgba(255,255,255,.02)">
            <span style="font-size:14px;font-weight:600;color:var(--txt)">Outstanding Balance</span>
            <span style="font-size:18px;font-weight:800;color:<?= $bal<=0?'var(--green)':'var(--red)' ?>">
              GH₵ <?= number_format(max(0,$bal),2) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- ── Right Column ── -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Actions -->
    <div class="card">
      <div class="card-hdr"><div class="card-title">Actions</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <button id="dlBtn" onclick="downloadPDF()" class="btn btn-p btn-lg" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Download Receipt PDF
        </button>
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

/* ── PDF Generator ── */
async function generatePDF(d) {
  const doc = new jsPDF();
  const pw = doc.internal.pageSize.getWidth();
  const mg = 30, cw = pw - mg * 2;
  let y = mg;

  doc.setFont('times');

  let logoLoaded = false;
  try { const li = await loadImg('receipts/static/logo.png'); doc.addImage(li,'PNG',mg,y,30,30); logoLoaded=true; } catch(e){}

  doc.setFontSize(11); doc.setFont('times','bold');
  doc.text('FORGEDCORE ENGINEERING LTD', pw-mg, y+10, {align:'right'});
  doc.setFontSize(9); doc.setFont('times','normal');
  doc.text('Kpobiman (Amasaman), Accra', pw-mg, y+17, {align:'right'});
  doc.text('0540202096 / 0545286665 | forgedcoreengineering@gmail.com', pw-mg, y+23, {align:'right'});
  doc.text('www.forgedcoreengineering.com', pw-mg, y+29, {align:'right'});
  y += logoLoaded ? 42 : 22;

  doc.setFontSize(18); doc.setFont('times','bold');
  doc.text('RECEIPT', pw/2, y, {align:'center'}); y += 15;

  doc.setFontSize(10);
  doc.setFont('times','bold'); doc.text('Invoice No:', mg, y);
  doc.setFont('times','normal'); doc.text(d.invoice_no, mg+35, y);
  doc.setFont('times','bold'); doc.text('Date:', mg, y+7);
  doc.setFont('times','normal'); doc.text(d.date, mg+35, y+7);
  doc.setFont('times','bold'); doc.text('BILL TO', pw-mg, y, {align:'right'});
  doc.setFont('times','normal');
  doc.text('Client: '+d.name,    pw-mg, y+7,  {align:'right'});
  doc.text('Address: '+d.address, pw-mg, y+14, {align:'right'});
  doc.text('Contact: '+d.contact, pw-mg, y+21, {align:'right'});
  y += 35;

  doc.setFontSize(12); doc.setFont('times','bold');
  doc.text(d.description.toUpperCase(), pw/2, y, {align:'center'}); y += 15;

  doc.setFontSize(11); doc.setFont('times','bold');
  doc.text('PAYMENT SUMMARY', pw/2, y, {align:'center'}); y += 12;

  const tTop=y, rh=10;
  doc.setFillColor(245,245,245); doc.rect(mg,tTop,cw,rh,'F');
  doc.setFont('times','bold');
  doc.text('DESCRIPTION', mg+5, tTop+7);
  doc.text('AMOUNT (GHS)', pw-mg-5, tTop+7, {align:'right'});

  const bal = d.total - d.paid;
  [
    {desc:'TOTAL SUM',           amt:d.total.toFixed(2), color:null},
    {desc:'TOTAL AMOUNT PAID',   amt:d.paid.toFixed(2),  color:[0,128,0]},
    {desc:'OUTSTANDING BALANCE', amt:bal.toFixed(2),     color:bal>0?[220,0,0]:[0,128,0]},
  ].forEach((row,i)=>{
    const rY=tTop+rh+i*rh;
    if(i%2===0){doc.setFillColor(247,247,247);doc.rect(mg,rY,cw,rh,'F');}
    doc.setFont('times','normal'); doc.setTextColor(0,0,0);
    doc.text(row.desc, mg+5, rY+7);
    if(row.color){doc.setTextColor(...row.color); doc.setFont('times','bold');}
    doc.text(row.amt, pw-mg-5, rY+7, {align:'right'});
    doc.setTextColor(0,0,0);
  });
  y = tTop + rh*4 + 30;

  const sigX = mg + cw*0.64;
  doc.setFont('times','bold'); doc.text('Authorized Signature:', sigX, y); y+=15;
  try { const si=await loadImg('receipts/static/signature.png'); doc.addImage(si,'PNG',sigX,y,60,20); y+=25; } catch(e){y+=10;}
  doc.setFont('times','normal'); doc.text('Eyram Dela Kuwornu', sigX, y);
  doc.setFontSize(9); doc.text('(Director – Forgedcore Engineering Ltd)', sigX, y+7);

  doc.save('receipt_' + d.invoice_no.replace(/\//g,'_') + '.pdf');
}

function loadImg(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin='anonymous';
    img.onload=()=>resolve(img);
    img.onerror=()=>reject(new Error('Not found'));
    img.src=url;
  });
}
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
