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

        <!-- ── Outstanding Lookup ── -->
        <div id="outstandingPanel" style="background:var(--acc-bg);border:1px solid rgba(0,153,153,.25);border-radius:var(--r);padding:14px 16px;margin-bottom:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
            <div style="font-size:12.5px;font-weight:700;color:var(--acc)">⚡ Continue from an outstanding payslip</div>
            <button type="button" id="clearOutBtn" onclick="clearOutstanding()" style="display:none;font-size:11px;color:var(--red);background:none;border:none;cursor:pointer;padding:2px 6px">✕ Clear</button>
          </div>
          <div style="position:relative">
            <input type="text" id="outSearch" placeholder="Search by name…" autocomplete="off"
              style="width:100%;background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);padding:9px 12px;color:var(--txt);font-size:13px;outline:none"
              oninput="searchOutstanding(this.value)">
            <div id="outDropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--card);border:1px solid var(--br);border-radius:var(--rs);box-shadow:var(--sh);z-index:50;max-height:220px;overflow-y:auto"></div>
          </div>
          <div id="outSelected" style="display:none;margin-top:10px;padding:10px 12px;background:var(--inp);border-radius:var(--rs);font-size:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="color:var(--txt2)">Payslip No</span><span id="outSelNo" style="color:var(--acc);font-family:monospace"></span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="color:var(--txt2)">Already paid</span><span id="outSelPaid" style="color:var(--green);font-weight:600"></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--txt2)">Outstanding</span><span id="outSelBal" style="color:var(--red);font-weight:700"></span>
            </div>
          </div>
        </div>

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
          <div class="fg" id="paidFg">
            <label class="req" for="amount_paid" id="paidLabel">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="amount_paid" name="amount_paid" placeholder="0.00" step="0.01" min="0" required>
            </div>
            <div id="paidHelper" style="display:none;font-size:11px;color:var(--txt3);margin-top:4px">This new payment will be <strong style="color:var(--green)">added</strong> to the existing paid amount.</div>
          </div>
        </div>

        <div id="eduBox" class="edu-box">
          <div class="edu-top">
            <div>
              <div class="edu-lbl">Payment Progress</div>
              <div id="pv-status-text" class="edu-val">Enter amounts to calculate balance.</div>
            </div>
            <span id="pv-status-badge" class="badge">Pending</span>
          </div>
          <div class="pay-grid">
            <div class="pay-grid-cell">
              <div class="pay-grid-lbl">Due</div>
              <div id="edu-due" class="pay-grid-val">GH₵ 0.00</div>
            </div>
            <div class="pay-grid-cell">
              <div class="pay-grid-lbl">Paid</div>
              <div id="edu-paid" class="pay-grid-val" style="color:var(--green)">GH₵ 0.00</div>
            </div>
            <div class="pay-grid-cell">
              <div class="pay-grid-lbl">Left To Pay</div>
              <div id="edu-left" class="pay-grid-val" style="color:var(--red)">GH₵ 0.00</div>
            </div>
          </div>
          <div id="edu-note" class="edu-note">Tip: this helps you explain payment status to the person immediately.</div>
        </div>

        <div id="errBox" class="err-box" style="display:none"></div>

        <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Generate &amp; Download Payslip
        </button>
      </form>

      <div id="successBox" class="success-state" style="display:none">
        <div class="success-ic">
          <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        </div>
        <h3 class="success-title">Payslip Generated!</h3>
        <p class="success-sub">Payslip <strong id="successNo" style="color:var(--acc)"></strong> has been created.</p>
        <p class="success-sub2">The PDF is downloaded and the record is saved for future reference.</p>
        <div class="success-acts">
          <button onclick="resetForm()" class="btn btn-p">+ Create Another</button>
          <a href="payslips.php" class="btn btn-s">View All Payslips</a>
        </div>
      </div>
    </div>
  </div>

  <div class="pv-col">
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
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Left To Pay</span>
            <span id="pv-left" class="am-cell" style="font-weight:700">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="assets/letterhead-pdf.js?v=3"></script>
<script src="assets/payslip-pdf.js?v=2"></script>
<script>
const { jsPDF } = window.jspdf;

/* ══════════════════════════════════════════
   OUTSTANDING LOOKUP
══════════════════════════════════════════ */
let _outRecord = null;
let _outTimer  = null;

function searchOutstanding(q) {
  clearTimeout(_outTimer);
  const dd = document.getElementById('outDropdown');
  if (q.length < 2) { dd.style.display = 'none'; return; }
  _outTimer = setTimeout(async () => {
    try {
      const r = await fetch('search-outstanding.php?type=payslip&q=' + encodeURIComponent(q));
      const rows = await r.json();
      if (!rows.length) {
        dd.innerHTML = '<div style="padding:10px 14px;font-size:12.5px;color:var(--txt3)">No outstanding payslips found for that name.</div>';
      } else {
        dd.innerHTML = rows.map((row, i) => {
          const name = row.full_name || '';
          const no = row.payslip_no || '';
          const out = row.outstanding ? row.outstanding.toFixed(2) : '0.00';
          return '<div class="out-item" data-idx="' + i + '" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--br);font-size:12.5px;transition:background .15s" onmouseover="this.style.background=\'var(--card-h)\'" onmouseout="this.style.background=\'\'"><div style="font-weight:600;color:var(--txt)">' + name + '</div><div style="color:var(--txt3);font-size:11px">' + no + ' &middot; Outstanding: <span style="color:var(--red);font-weight:600">GH&#x20B5; ' + out + '</span></div></div>';
        }).join('');
        dd._rows = rows;
        dd.querySelectorAll('.out-item').forEach(function(el) {
          el.addEventListener('mousedown', function(ev) {
            ev.preventDefault();
            selectOutstanding(dd._rows[parseInt(this.dataset.idx)]);
          });
        });
      }
      dd.style.display = 'block';
    } catch(e) { dd.style.display = 'none'; }
  }, 280);
}

function selectOutstanding(row) {
  _outRecord = row;
  document.getElementById('outDropdown').style.display = 'none';
  document.getElementById('outSearch').value = row.full_name;
  document.getElementById('clearOutBtn').style.display = 'inline';
  document.getElementById('outSelected').style.display = 'block';
  document.getElementById('outSelNo').textContent   = row.payslip_no;
  document.getElementById('outSelPaid').textContent = 'GH₵ ' + row.amount_paid.toFixed(2);
  document.getElementById('outSelBal').textContent  = 'GH₵ ' + row.outstanding.toFixed(2);

  const lock = (id, val) => {
    const el = document.getElementById(id);
    el.value = val;
    el.readOnly = true;
    el.style.opacity = '0.65';
    el.style.cursor  = 'not-allowed';
  };
  lock('full_name',  row.full_name);
  lock('service',    row.service);
  lock('amount_due', row.amount_due.toFixed(2));

  document.getElementById('paidLabel').textContent = 'New Payment Amount (GHS) *';
  document.getElementById('amount_paid').value = '';
  document.getElementById('amount_paid').placeholder = `Max GH₵ ${row.outstanding.toFixed(2)}`;
  document.getElementById('amount_paid').max = row.outstanding.toFixed(2);
  document.getElementById('amount_paid').readOnly = false;
  document.getElementById('amount_paid').style.opacity = '';
  document.getElementById('amount_paid').style.cursor  = '';
  document.getElementById('paidHelper').style.display = 'block';

  document.getElementById('submitBtn').innerHTML =
    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Record Payment &amp; Issue Payslip';
  updatePreview();
}

function clearOutstanding() {
  _outRecord = null;
  document.getElementById('outSearch').value = '';
  document.getElementById('outDropdown').style.display = 'none';
  document.getElementById('outSelected').style.display = 'none';
  document.getElementById('clearOutBtn').style.display = 'none';
  ['full_name','service','amount_due'].forEach(id => {
    const el = document.getElementById(id);
    el.value = ''; el.readOnly = false; el.style.opacity = ''; el.style.cursor = '';
  });
  document.getElementById('amount_paid').value = '';
  document.getElementById('amount_paid').removeAttribute('max');
  document.getElementById('amount_paid').placeholder = '0.00';
  document.getElementById('paidLabel').textContent = 'Amount Paid (GHS)';
  document.getElementById('paidHelper').style.display = 'none';
  document.getElementById('submitBtn').innerHTML =
    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Payslip';
  updatePreview();
}

document.addEventListener('click', e => {
  if (!e.target.closest('#outstandingPanel')) document.getElementById('outDropdown').style.display = 'none';
});

/* ══════════════════════════════════════════
   LIVE PREVIEW & FORM
══════════════════════════════════════════ */
function updatePreview() {
  const fullName = document.getElementById('full_name').value.trim();
  const service = document.getElementById('service').value.trim();
  const amountDue = parseFloat(document.getElementById('amount_due').value) || 0;
  const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
  const amountLeft = amountDue - amountPaid;

  document.getElementById('pv-name').textContent = fullName || '—';
  document.getElementById('pv-service').textContent = service || '—';
  document.getElementById('pv-due').textContent = amountDue > 0 ? 'GH₵ ' + amountDue.toFixed(2) : '—';
  document.getElementById('pv-paid').textContent = amountPaid >= 0 ? 'GH₵ ' + amountPaid.toFixed(2) : '—';
  const leftEl = document.getElementById('pv-left');
  leftEl.textContent = amountDue > 0 ? 'GH₵ ' + Math.max(0, amountLeft).toFixed(2) : '—';
  leftEl.style.color = amountLeft > 0 ? 'var(--red)' : 'var(--green)';

  document.getElementById('edu-due').textContent = 'GH₵ ' + amountDue.toFixed(2);
  document.getElementById('edu-paid').textContent = 'GH₵ ' + amountPaid.toFixed(2);
  document.getElementById('edu-left').textContent = 'GH₵ ' + Math.max(0, amountLeft).toFixed(2);

  const statusText = document.getElementById('pv-status-text');
  const statusBadge = document.getElementById('pv-status-badge');
  const note = document.getElementById('edu-note');
  const leftColorEl = document.getElementById('edu-left');

  if (amountDue <= 0) {
    statusText.textContent = 'Enter amounts to calculate balance.';
    statusBadge.className = 'badge';
    statusBadge.textContent = 'Pending';
    note.textContent = 'Tip: this helps you explain payment status to the person immediately.';
    leftColorEl.style.color = 'var(--txt)';
  } else if (amountPaid > amountDue) {
    statusText.textContent = 'Amount paid is above expected amount.';
    statusBadge.className = 'badge bg-unpaid';
    statusBadge.innerHTML = '<span class="bdot"></span>Invalid';
    note.textContent = 'Please correct amount paid so it is not greater than amount supposed to be paid.';
    leftColorEl.style.color = 'var(--red)';
  } else if (amountLeft === 0) {
    statusText.textContent = 'Fully paid. Nothing left to pay.';
    statusBadge.className = 'badge bg-paid';
    statusBadge.innerHTML = '<span class="bdot"></span>Fully Paid';
    note.textContent = 'This payslip can be shared as fully settled.';
    leftColorEl.style.color = 'var(--green)';
  } else if (amountPaid > 0) {
    statusText.textContent = 'Part payment made. There is still balance remaining.';
    statusBadge.className = 'badge bg-partial';
    statusBadge.innerHTML = '<span class="bdot"></span>Partially Paid';
    note.textContent = 'Use the "Left To Pay" value to update the person on outstanding amount.';
    leftColorEl.style.color = 'var(--ylw)';
  } else {
    statusText.textContent = 'No payment yet. Full amount is still outstanding.';
    statusBadge.className = 'badge bg-unpaid';
    statusBadge.innerHTML = '<span class="bdot"></span>Unpaid';
    note.textContent = 'This payslip indicates the person has not yet been paid.';
    leftColorEl.style.color = 'var(--red)';
  }
}

['full_name', 'service', 'amount_due', 'amount_paid'].forEach((id) => {
  document.getElementById(id).addEventListener('input', updatePreview);
});

document.getElementById('payslipForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const errBox = document.getElementById('errBox');
  errBox.style.display = 'none';

  /* ── CONTINUATION MODE ── */
  if (_outRecord) {
    const newPmt = parseFloat(document.getElementById('amount_paid').value) || 0;
    if (newPmt <= 0)                     { showErr('Please enter a payment amount greater than 0.'); return; }
    if (newPmt > _outRecord.outstanding) { showErr(`Payment cannot exceed the outstanding balance of GH₵ ${_outRecord.outstanding.toFixed(2)}.`); return; }

    btn.disabled = true;
    btn.innerHTML = '<div class="spin"></div> Recording & Issuing…';

    try {
      const pr = await fetch('record-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: _outRecord.id, type: 'payslip', new_payment: newPmt })
      });
      const pres = await pr.json();
      if (!pr.ok || !pres.success) throw new Error(pres.error || 'Failed to record payment');

      const pdfData = {
        full_name:   _outRecord.full_name,
        service:     _outRecord.service,
        amount_due:  _outRecord.amount_due,
        amount_paid: pres.new_paid,
        payslip_no:  _outRecord.payslip_no,
        issue_date:  _outRecord.issue_date,
      };
      await generatePDF(pdfData);

      document.getElementById('payslipForm').style.display = 'none';
      document.getElementById('successNo').textContent = _outRecord.payslip_no;
      document.getElementById('successBox').style.display = 'block';
      return;
    } catch(err) {
      showErr('Error: ' + err.message);
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Record Payment &amp; Issue Payslip';
      return;
    }
  }

  /* ── NORMAL MODE ── */
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
  if (data.amount_paid > data.amount_due) return showErr('Amount paid cannot be greater than amount supposed to be paid.');

  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Generating...';

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

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
