<?php
/**
 * New Receipt Form — ForgedCore Receipt Manager
 */
$page_title    = 'New Receipt';
$page_subtitle  = 'Fill in client details to generate a PDF receipt';
require_once __DIR__ . '/includes/header.php';
?>

<div class="g2-form">

  <!-- ── Form Card ── -->
  <div class="card">
    <div class="card-hdr">
      <div>
        <div class="card-title">Receipt Details</div>
        <div class="card-sub">Fields marked * are required</div>
      </div>
    </div>
    <div class="card-body">

      <form id="receiptForm" novalidate>
        <div class="fgrid">

          <div class="fg">
            <label class="req" for="name">Client Name</label>
            <input type="text" id="name" name="name" placeholder="e.g. Kwame Mensah" required autocomplete="off">
          </div>

          <div class="fg">
            <label class="req" for="contact">Contact / Phone</label>
            <input type="text" id="contact" name="contact" placeholder="e.g. 0244000000" required>
          </div>

          <div class="fg full">
            <label class="req" for="address">Address</label>
            <input type="text" id="address" name="address" placeholder="e.g. East Legon, Accra" required>
          </div>

          <div class="fg full">
            <label class="req" for="description">Description / Service Rendered</label>
            <textarea id="description" name="description" placeholder="e.g. Electrical Installation and Wiring at Client Premises" required></textarea>
          </div>

          <div class="fg">
            <label class="req" for="total">Total Amount (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="total" name="total" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>

          <div class="fg">
            <label class="req" for="paid">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="paid" name="paid" value="0" step="0.01" min="0" required>
            </div>
          </div>

        </div>

        <!-- Error box -->
        <div id="errBox" style="display:none;background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:11px 14px;color:var(--red);font-size:13px;margin-bottom:14px"></div>

        <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Generate &amp; Download Receipt
        </button>
      </form>

      <!-- Success state (hidden initially) -->
      <div id="successBox" style="display:none;text-align:center;padding:28px 0">
        <div style="width:62px;height:62px;background:var(--grn-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--green)">
          <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:700;color:var(--txt);margin-bottom:8px">Receipt Generated!</h3>
        <p style="color:var(--txt2);font-size:13.5px;margin-bottom:5px">Invoice <strong id="successInv" style="color:var(--acc)"></strong> has been created.</p>
        <p style="color:var(--txt3);font-size:12.5px;margin-bottom:22px">The PDF was downloaded to your computer and saved to the system.</p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
          <button onclick="resetForm()" class="btn btn-p">+ Create Another</button>
          <a href="clients.php" class="btn btn-s">View All Receipts</a>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Live Preview ── -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Live Preview</div></div>
      <div class="card-body" style="padding:16px">

        <!-- Company header mini -->
        <div style="text-align:right;border-bottom:1px solid var(--br);padding-bottom:12px;margin-bottom:12px">
          <div style="font-size:10.5px;font-weight:700;color:var(--txt2);letter-spacing:.5px">FORGEDCORE ENGINEERING LTD</div>
          <div style="font-size:10px;color:var(--txt3)">Kpobiman (Amasaman), Accra</div>
          <div style="font-size:10px;color:var(--txt3)">0540202096 / 0545286665</div>
        </div>

        <div style="text-align:center;font-size:12.5px;font-weight:700;color:var(--acc);letter-spacing:2px;margin-bottom:14px">RECEIPT</div>

        <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Bill To</div>
        <div id="pv-name"    style="font-size:13.5px;font-weight:600;color:var(--txt);margin-bottom:2px">—</div>
        <div id="pv-address" style="font-size:11.5px;color:var(--txt2);margin-bottom:1px">—</div>
        <div id="pv-contact" style="font-size:11.5px;color:var(--txt2);margin-bottom:14px">—</div>

        <div id="pv-desc" style="font-size:11.5px;font-weight:700;color:var(--txt);text-align:center;letter-spacing:.5px;margin-bottom:12px;text-transform:uppercase">—</div>

        <!-- Payment preview table -->
        <div style="background:var(--inp);border-radius:var(--rs);overflow:hidden;font-size:12px">
          <div style="display:flex;justify-content:space-between;padding:8px 10px;background:rgba(255,255,255,.04);font-weight:600;color:var(--txt2)">
            <span>DESCRIPTION</span><span>AMOUNT</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Total Sum</span>
            <span id="pv-total" class="am-cell">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br);color:var(--green)">
            <span>Amount Paid</span>
            <span id="pv-paid" class="am-cell">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Outstanding</span>
            <span id="pv-bal" class="am-cell" style="font-weight:700">—</span>
          </div>
        </div>

        <div style="text-align:center;margin-top:12px">
          <span id="pv-badge" class="badge" style="font-size:11.5px">···</span>
        </div>

      </div>
    </div>

    <div class="card">
      <div class="card-body" style="padding:14px 16px">
        <div style="font-size:12px;color:var(--txt3);line-height:1.75">
          <strong style="color:var(--txt2)">ℹ Note:</strong> The PDF receipt will be automatically downloaded after you click generate. A record is also saved to the system so you can view details or re-download at any time from <a href="clients.php" style="color:var(--acc)">All Receipts</a>.
        </div>
      </div>
    </div>
  </div>

</div><!-- /g2-form -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const { jsPDF } = window.jspdf;

/* ── Live Preview ── */
function updatePreview() {
  const name  = document.getElementById('name').value.trim();
  const addr  = document.getElementById('address').value.trim();
  const con   = document.getElementById('contact').value.trim();
  const desc  = document.getElementById('description').value.trim();
  const total = parseFloat(document.getElementById('total').value) || 0;
  const paid  = parseFloat(document.getElementById('paid').value) || 0;
  const bal   = total - paid;

  document.getElementById('pv-name').textContent    = name  || '—';
  document.getElementById('pv-address').textContent = addr  || '—';
  document.getElementById('pv-contact').textContent = con   || '—';
  document.getElementById('pv-desc').textContent    = desc ? desc.toUpperCase() : '—';
  document.getElementById('pv-total').textContent   = total > 0 ? 'GH₵ ' + total.toFixed(2) : '—';
  document.getElementById('pv-paid').textContent    = paid  > 0 ? 'GH₵ ' + paid.toFixed(2)  : '—';

  const balEl = document.getElementById('pv-bal');
  const badge = document.getElementById('pv-badge');

  if (total > 0) {
    balEl.textContent = 'GH₵ ' + Math.max(0, bal).toFixed(2);
    if (bal <= 0) {
      balEl.style.color = 'var(--green)';
      badge.className = 'badge bg-paid';
      badge.innerHTML = '<span class="bdot"></span>Fully Paid';
    } else if (paid > 0) {
      balEl.style.color = 'var(--ylw)';
      badge.className = 'badge bg-partial';
      badge.innerHTML = '<span class="bdot"></span>Partial Payment';
    } else {
      balEl.style.color = 'var(--red)';
      badge.className = 'badge bg-unpaid';
      badge.innerHTML = '<span class="bdot"></span>Unpaid';
    }
  } else {
    balEl.textContent = '—';
    balEl.style.color = '';
    badge.className = 'badge';
    badge.textContent = '···';
  }
}

['name','address','contact','description','total','paid'].forEach(id => {
  document.getElementById(id).addEventListener('input', updatePreview);
});

/* ── Form Submit ── */
document.getElementById('receiptForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn    = document.getElementById('submitBtn');
  const errBox = document.getElementById('errBox');

  const data = {
    name:        document.getElementById('name').value.trim(),
    address:     document.getElementById('address').value.trim(),
    contact:     document.getElementById('contact').value.trim(),
    description: document.getElementById('description').value.trim(),
    total:       parseFloat(document.getElementById('total').value) || 0,
    paid:        parseFloat(document.getElementById('paid').value)  || 0,
  };

  if (!data.name)        { showErr('Client name is required.'); return; }
  if (!data.address)     { showErr('Address is required.'); return; }
  if (!data.contact)     { showErr('Contact is required.'); return; }
  if (!data.description) { showErr('Description is required.'); return; }
  if (data.total <= 0)   { showErr('Total amount must be greater than 0.'); return; }
  if (data.paid > data.total) { showErr('Amount paid cannot exceed the total amount.'); return; }

  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Generating…';
  errBox.style.display = 'none';

  try {
    const res = await fetch('get-invoice-info.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: data.name })
    });
    const inv = await res.json();
    if (!res.ok) throw new Error(inv.error || 'Failed to get invoice info');

    const full = { ...data, ...inv };

    await generatePDF(full);

    // Save record (non-blocking)
    fetch('save-receipt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(full)
    }).catch(() => {});

    document.getElementById('receiptForm').style.display = 'none';
    document.getElementById('successInv').textContent = full.invoice_no;
    document.getElementById('successBox').style.display = 'block';

  } catch (err) {
    showErr('Error: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Receipt';
  }
});

function showErr(msg) {
  const e = document.getElementById('errBox');
  e.textContent = msg;
  e.style.display = 'block';
}

function resetForm() {
  document.getElementById('receiptForm').reset();
  document.getElementById('receiptForm').style.display = 'block';
  document.getElementById('successBox').style.display  = 'none';
  const btn = document.getElementById('submitBtn');
  btn.disabled = false;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Receipt';
  updatePreview();
}

/* ── PDF Generation ── */
async function generatePDF(d) {
  const doc = new jsPDF();
  const pw = doc.internal.pageSize.getWidth();
  const mg = 30;
  const cw = pw - mg * 2;
  let y = mg;

  doc.setFont('times');

  // Logo
  let logoLoaded = false;
  try {
    const logoImg = await loadImg('receipts/static/logo.png');
    doc.addImage(logoImg, 'PNG', mg, y, 30, 30);
    logoLoaded = true;
  } catch (e) {}

  // Company info
  doc.setFontSize(11); doc.setFont('times', 'bold');
  doc.text('FORGEDCORE ENGINEERING LTD', pw - mg, y + 10, { align: 'right' });
  doc.setFontSize(9);  doc.setFont('times', 'normal');
  doc.text('Kpobiman (Amasaman), Accra', pw - mg, y + 17, { align: 'right' });
  doc.text('0540202096 / 0545286665 | forgedcoreengineering@gmail.com', pw - mg, y + 23, { align: 'right' });
  doc.text('www.forgedcoreengineering.com', pw - mg, y + 29, { align: 'right' });
  y += logoLoaded ? 42 : 22;

  // Title
  doc.setFontSize(18); doc.setFont('times', 'bold');
  doc.text('RECEIPT', pw / 2, y, { align: 'center' });
  y += 15;

  // Invoice + Date  |  Bill To
  doc.setFontSize(10);
  doc.setFont('times', 'bold');   doc.text('Invoice No:', mg, y);
  doc.setFont('times', 'normal'); doc.text(d.invoice_no, mg + 35, y);
  doc.setFont('times', 'bold');   doc.text('Date:', mg, y + 7);
  doc.setFont('times', 'normal'); doc.text(d.date, mg + 35, y + 7);

  doc.setFont('times', 'bold');   doc.text('BILL TO', pw - mg, y, { align: 'right' });
  doc.setFont('times', 'normal');
  doc.text('Client: '  + d.name,    pw - mg, y + 7,  { align: 'right' });
  doc.text('Address: ' + d.address,  pw - mg, y + 14, { align: 'right' });
  doc.text('Contact: ' + d.contact,  pw - mg, y + 21, { align: 'right' });
  y += 35;

  // Description
  doc.setFontSize(12); doc.setFont('times', 'bold');
  doc.text(d.description.toUpperCase(), pw / 2, y, { align: 'center' });
  y += 15;

  // Payment summary
  doc.setFontSize(11); doc.setFont('times', 'bold');
  doc.text('PAYMENT SUMMARY', pw / 2, y, { align: 'center' });
  y += 12;

  const tTop = y, rh = 10;
  doc.setFillColor(245, 245, 245);
  doc.rect(mg, tTop, cw, rh, 'F');
  doc.setFont('times', 'bold');
  doc.text('DESCRIPTION',   mg + 5,      tTop + 7);
  doc.text('AMOUNT (GHS)',  pw - mg - 5, tTop + 7, { align: 'right' });

  const bal = d.total - d.paid;
  [
    { desc: 'TOTAL SUM',          amt: d.total.toFixed(2), color: null },
    { desc: 'TOTAL AMOUNT PAID',  amt: d.paid.toFixed(2),  color: [0, 128, 0] },
    { desc: 'OUTSTANDING BALANCE',amt: bal.toFixed(2),     color: bal > 0 ? [220, 0, 0] : [0, 128, 0] },
  ].forEach((row, i) => {
    const rY = tTop + rh + i * rh;
    if (i % 2 === 0) { doc.setFillColor(247, 247, 247); doc.rect(mg, rY, cw, rh, 'F'); }
    doc.setFont('times', 'normal'); doc.setTextColor(0, 0, 0);
    doc.text(row.desc, mg + 5, rY + 7);
    if (row.color) { doc.setTextColor(...row.color); doc.setFont('times', 'bold'); }
    doc.text(row.amt, pw - mg - 5, rY + 7, { align: 'right' });
    doc.setTextColor(0, 0, 0);
  });
  y = tTop + rh * 4 + 30;

  // Signature
  const sigX = mg + cw * 0.64;
  doc.setFont('times', 'bold');
  doc.text('Authorized Signature:', sigX, y);
  y += 15;
  try {
    const sigImg = await loadImg('receipts/static/signature.png');
    doc.addImage(sigImg, 'PNG', sigX, y, 60, 20);
    y += 25;
  } catch (e) { y += 10; }
  doc.setFont('times', 'normal');
  doc.text('Eyram Dela Kuwornu', sigX, y);
  doc.setFontSize(9);
  doc.text('(Director – Forgedcore Engineering Ltd)', sigX, y + 7);

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
