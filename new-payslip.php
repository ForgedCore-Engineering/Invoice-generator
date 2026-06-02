<?php
/**
 * New Payslip Form
 */
$page_title = 'New Payslip';
$page_subtitle = 'Create a branded payslip and save it to your records';
require_once __DIR__ . '/includes/header.php';
?>

<div class="g2-form">
  <div class="card card-form">
    <div class="card-hdr">
      <div>
        <div class="card-title">Payslip Details</div>
        <div class="card-sub">Fields marked * are required</div>
      </div>
    </div>
    <div class="card-body">
      <form id="payslipForm" novalidate>
        <div class="fgrid">
          <div class="fg full">
            <label class="req" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="e.g. Kwame Mensah" required>
          </div>
          <div class="fg full">
            <label class="req" for="service">Services</label>
            <textarea id="service" name="service" placeholder="e.g. Site supervision and electrical maintenance" required></textarea>
          </div>
          <div class="fg">
            <label class="req" for="amount_due">Amount Supposed To Be Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="amount_due" name="amount_due" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>
          <div class="fg">
            <label class="req" for="amount_paid">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="amount_paid" name="amount_paid" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>
        </div>

        <div id="errBox" style="display:none;background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:11px 14px;color:var(--red);font-size:13px;margin-bottom:14px"></div>

        <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Generate &amp; Download Payslip
        </button>
      </form>

      <div id="successBox" style="display:none;text-align:center;padding:28px 0">
        <div style="width:62px;height:62px;background:var(--grn-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--green)">
          <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:700;color:var(--txt);margin-bottom:8px">Payslip Generated!</h3>
        <p style="color:var(--txt2);font-size:13.5px;margin-bottom:5px">Payslip <strong id="successNo" style="color:var(--acc)"></strong> has been created.</p>
        <p style="color:var(--txt3);font-size:12.5px;margin-bottom:22px">The PDF is downloaded and the record is saved for future reference.</p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
          <button onclick="resetForm()" class="btn btn-p">+ Create Another</button>
          <a href="payslips.php" class="btn btn-s">View All Payslips</a>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Live Preview</div></div>
      <div class="card-body" style="padding:16px">
        <div style="text-align:right;border-bottom:1px solid var(--br);padding-bottom:12px;margin-bottom:12px">
          <div style="font-size:10.5px;font-weight:700;color:var(--txt2);letter-spacing:.5px">FORGEDCORE ENGINEERING LTD</div>
          <div style="font-size:10px;color:var(--txt3)">Kpobiman (Amasaman), Accra</div>
          <div style="font-size:10px;color:var(--txt3)">0540202096 / 0545286665</div>
        </div>
        <div style="text-align:center;font-size:12.5px;font-weight:700;color:var(--acc);letter-spacing:2px;margin-bottom:14px">PAYSLIP</div>
        <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Name</div>
        <div id="pv-name" style="font-size:13.5px;font-weight:600;color:var(--txt);margin-bottom:10px">—</div>
        <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Services</div>
        <div id="pv-service" style="font-size:12px;color:var(--txt2);margin-bottom:14px">—</div>
        <div style="background:var(--inp);border-radius:var(--rs);overflow:hidden;font-size:12px">
          <div style="display:flex;justify-content:space-between;padding:8px 10px;background:rgba(255,255,255,.04);font-weight:600;color:var(--txt2)">
            <span>DESCRIPTION</span><span>AMOUNT</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Amount Supposed To Be Paid</span>
            <span id="pv-due" class="am-cell">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Amount Paid</span>
            <span id="pv-paid" class="am-cell">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const { jsPDF } = window.jspdf;

function updatePreview() {
  const fullName = document.getElementById('full_name').value.trim();
  const service = document.getElementById('service').value.trim();
  const amountDue = parseFloat(document.getElementById('amount_due').value) || 0;
  const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;

  document.getElementById('pv-name').textContent = fullName || '—';
  document.getElementById('pv-service').textContent = service || '—';
  document.getElementById('pv-due').textContent = amountDue > 0 ? 'GH₵ ' + amountDue.toFixed(2) : '—';
  document.getElementById('pv-paid').textContent = amountPaid >= 0 ? 'GH₵ ' + amountPaid.toFixed(2) : '—';
}

['full_name', 'service', 'amount_due', 'amount_paid'].forEach((id) => {
  document.getElementById(id).addEventListener('input', updatePreview);
});

document.getElementById('payslipForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const errBox = document.getElementById('errBox');

  const data = {
    full_name: document.getElementById('full_name').value.trim(),
    service: document.getElementById('service').value.trim(),
    amount_due: parseFloat(document.getElementById('amount_due').value) || 0,
    amount_paid: parseFloat(document.getElementById('amount_paid').value) || 0,
  };

  if (!data.full_name) return showErr('Full name is required.');
  if (!data.service) return showErr('Services field is required.');
  if (data.amount_due <= 0) return showErr('Amount supposed to be paid must be greater than 0.');
  if (data.amount_paid < 0) return showErr('Amount paid cannot be negative.');

  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Generating…';
  errBox.style.display = 'none';

  try {
    const infoRes = await fetch('get-payslip-info.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ full_name: data.full_name })
    });
    const info = await infoRes.json();
    if (!infoRes.ok) throw new Error(info.error || 'Failed to get payslip info');

    const full = { ...data, ...info };
    await generatePDF(full);

    fetch('save-payslip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(full)
    }).catch(() => {});

    document.getElementById('payslipForm').style.display = 'none';
    document.getElementById('successNo').textContent = full.payslip_no;
    document.getElementById('successBox').style.display = 'block';
  } catch (err) {
    showErr('Error: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Payslip';
  }
});

function showErr(msg) {
  const e = document.getElementById('errBox');
  e.textContent = msg;
  e.style.display = 'block';
}

function resetForm() {
  document.getElementById('payslipForm').reset();
  document.getElementById('payslipForm').style.display = 'block';
  document.getElementById('successBox').style.display = 'none';
  const btn = document.getElementById('submitBtn');
  btn.disabled = false;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Payslip';
  updatePreview();
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
