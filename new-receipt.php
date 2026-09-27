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
  <div class="card card-form">
    <div class="card-hdr">
      <div>
        <div class="card-title">Receipt Details</div>
        <div class="card-sub">Fields marked * are required</div>
      </div>
    </div>
    <div class="card-body">

      <form id="receiptForm" novalidate>

        <!-- ── Outstanding Lookup ── -->
        <div id="outstandingPanel" style="background:var(--acc-bg);border:1px solid rgba(0,153,153,.25);border-radius:var(--r);padding:14px 16px;margin-bottom:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
            <div style="font-size:12.5px;font-weight:700;color:var(--acc)">⚡ Continue from an outstanding invoice</div>
            <button type="button" id="clearOutBtn" onclick="clearOutstanding()" style="display:none;font-size:11px;color:var(--red);background:none;border:none;cursor:pointer;padding:2px 6px">✕ Clear</button>
          </div>
          <div style="position:relative">
            <input type="text" id="outSearch" placeholder="Search by client name…" autocomplete="off"
              style="width:100%;background:var(--inp);border:1px solid var(--br);border-radius:var(--rs);padding:9px 12px;color:var(--txt);font-size:13px;outline:none"
              oninput="searchOutstanding(this.value)">
            <div id="outDropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--card);border:1px solid var(--br);border-radius:var(--rs);box-shadow:var(--sh);z-index:50;max-height:220px;overflow-y:auto"></div>
          </div>
          <div id="outSelected" style="display:none;margin-top:10px;padding:10px 12px;background:var(--inp);border-radius:var(--rs);font-size:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="color:var(--txt2)">Invoice</span><span id="outSelInv" style="color:var(--acc);font-family:monospace"></span>
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

          <div class="fg">
            <label class="req" for="name">Client Name</label>
            <div class="ac-wrap">
              <input type="text" id="name" name="name" placeholder="e.g. Kwame Mensah" required autocomplete="off" aria-autocomplete="list" aria-controls="clientAcList">
            </div>
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

          <div class="fg" id="totalFg">
            <label class="req" for="total">Total Amount (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="total" name="total" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>

          <div class="fg" id="paidFg">
            <label class="req" for="paid" id="paidLabel">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="paid" name="paid" value="0" step="0.01" min="0" required>
            </div>
            <!-- shown only in continuation mode -->
            <div id="paidHelper" style="display:none;font-size:11px;color:var(--txt3);margin-top:4px">This new payment will be <strong style="color:var(--green)">added</strong> to the existing paid amount.</div>
          </div>

        </div>

        <!-- Error box -->
        <div id="errBox" class="err-box" style="display:none"></div>

        <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
          Generate &amp; Download Receipt
        </button>
      </form>

      <!-- Success state (hidden initially) -->
      <div id="successBox" class="success-state" style="display:none">
        <div class="success-ic">
          <svg viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        </div>
        <h3 class="success-title">Receipt Generated!</h3>
        <p class="success-sub">Invoice <strong id="successInv" style="color:var(--acc)"></strong> has been created.</p>
        <p class="success-sub2">The PDF was downloaded to your computer and saved to the system.</p>
        <div class="success-acts">
          <button onclick="resetForm()" class="btn btn-p">+ Create Another</button>
          <a href="clients.php" class="btn btn-s">View All Receipts</a>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Live Preview ── -->
  <div class="pv-col">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Live Preview</div></div>
      <div class="card-body" style="padding:16px">

        <!-- Company header mini -->
        <div class="pv-hdr">
          <div class="pv-co-name">FORGEDCORE ENGINEERING LTD</div>
          <div class="pv-co-detail">Kpobiman (Amasaman), Accra</div>
          <div class="pv-co-detail">0540202096 / 0545286665</div>
        </div>

        <div style="text-align:center;font-size:12.5px;font-weight:700;color:var(--acc);letter-spacing:2px;margin-bottom:14px">RECEIPT</div>

        <div style="font-size:10.5px;color:var(--txt3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px">Bill To</div>
        <div id="pv-name"    style="font-size:13.5px;font-weight:600;color:var(--txt);margin-bottom:2px">—</div>
        <div id="pv-address" style="font-size:11.5px;color:var(--txt2);margin-bottom:1px">—</div>
        <div id="pv-contact" style="font-size:11.5px;color:var(--txt2);margin-bottom:14px">—</div>

        <div id="pv-desc" style="font-size:11.5px;font-weight:700;color:var(--txt);text-align:center;letter-spacing:.5px;margin-bottom:12px;text-transform:uppercase">—</div>

        <!-- Payment preview table -->
        <div class="pay-table" style="font-size:12px">
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
<script src="assets/letterhead-pdf.js?v=3"></script>
<script src="assets/receipt-pdf.js?v=2"></script>
<script>
const { jsPDF } = window.jspdf;

/* ══════════════════════════════════════════
   OUTSTANDING LOOKUP
══════════════════════════════════════════ */
let _outRecord = null; // holds the selected outstanding record
let _outTimer  = null;

function searchOutstanding(q) {
  clearTimeout(_outTimer);
  const dd = document.getElementById('outDropdown');
  if (q.length < 2) { dd.style.display = 'none'; return; }
  _outTimer = setTimeout(async () => {
    try {
      const r = await fetch('search-outstanding.php?type=receipt&q=' + encodeURIComponent(q));
      const rows = await r.json();
      if (!rows.length) {
        dd.innerHTML = '<div style="padding:10px 14px;font-size:12.5px;color:var(--txt3)">No outstanding invoices found for that name.</div>';
      } else {
        dd.innerHTML = rows.map((row, i) => {
          const name = row.name || '';
          const inv = row.invoice_no || '';
          const out = row.outstanding ? row.outstanding.toFixed(2) : '0.00';
          return '<div class="out-item" data-idx="' + i + '" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--br);font-size:12.5px;transition:background .15s" onmouseover="this.style.background=\'var(--card-h)\'" onmouseout="this.style.background=\'\'"><div style="font-weight:600;color:var(--txt)">' + name + '</div><div style="color:var(--txt3);font-size:11px">' + inv + ' &middot; Outstanding: <span style="color:var(--red);font-weight:600">GH&#x20B5; ' + out + '</span></div></div>';
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
  document.getElementById('outSearch').value = row.name;
  document.getElementById('clearOutBtn').style.display = 'inline';
  document.getElementById('outSelected').style.display = 'block';
  document.getElementById('outSelInv').textContent  = row.invoice_no;
  document.getElementById('outSelPaid').textContent = 'GH₵ ' + row.paid.toFixed(2);
  document.getElementById('outSelBal').textContent  = 'GH₵ ' + row.outstanding.toFixed(2);

  // Auto-fill and lock fields
  const lock = (id, val) => {
    const el = document.getElementById(id);
    el.value = val;
    el.readOnly = true;
    el.style.opacity = '0.65';
    el.style.cursor = 'not-allowed';
  };
  lock('name',        row.name);
  lock('contact',     row.contact);
  lock('address',     row.address);
  lock('description', row.description);
  lock('total',       row.total.toFixed(2));

  // Switch paid field to "new payment" mode
  document.getElementById('paidLabel').textContent = 'New Payment Amount (GHS) *';
  document.getElementById('paid').value = '';
  document.getElementById('paid').placeholder = `Max GH₵ ${row.outstanding.toFixed(2)}`;
  document.getElementById('paid').max = row.outstanding.toFixed(2);
  document.getElementById('paid').readOnly = false;
  document.getElementById('paid').style.opacity = '';
  document.getElementById('paid').style.cursor = '';
  document.getElementById('paidHelper').style.display = 'block';

  // Update submit button
  document.getElementById('submitBtn').innerHTML =
    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Record Payment &amp; Issue Receipt';

  updatePreview();
}

function clearOutstanding() {
  _outRecord = null;
  document.getElementById('outSearch').value = '';
  document.getElementById('outDropdown').style.display = 'none';
  document.getElementById('outSelected').style.display = 'none';
  document.getElementById('clearOutBtn').style.display = 'none';

  ['name','contact','address','description','total'].forEach(id => {
    const el = document.getElementById(id);
    el.value = '';
    el.readOnly = false;
    el.style.opacity = '';
    el.style.cursor = '';
  });
  document.getElementById('paid').value = '0';
  document.getElementById('paid').removeAttribute('max');
  document.getElementById('paid').placeholder = '';
  document.getElementById('paidLabel').textContent = 'Amount Paid (GHS)';
  document.getElementById('paidHelper').style.display = 'none';
  document.getElementById('submitBtn').innerHTML =
    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Generate &amp; Download Receipt';
  updatePreview();
}

// Close dropdown on outside click
document.addEventListener('click', e => {
  if (!e.target.closest('#outstandingPanel')) {
    document.getElementById('outDropdown').style.display = 'none';
  }
});

/* ══════════════════════════════════════════
   LIVE PREVIEW
══════════════════════════════════════════ */
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
  errBox.style.display = 'none';

  /* ── CONTINUATION MODE: recording a new payment on an existing record ── */
  if (_outRecord) {
    const newPmt = parseFloat(document.getElementById('paid').value) || 0;
    if (newPmt <= 0)                      { showErr('Please enter a payment amount greater than 0.'); return; }
    if (newPmt > _outRecord.outstanding)  { showErr(`Payment cannot exceed the outstanding balance of GH₵ ${_outRecord.outstanding.toFixed(2)}.`); return; }

    btn.disabled = true;
    btn.innerHTML = '<div class="spin"></div> Recording & Issuing…';

    try {
      // 1. Record the payment in DB
      const pr = await fetch('record-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: _outRecord.id, type: 'receipt', new_payment: newPmt })
      });
      const pres = await pr.json();
      if (!pr.ok || !pres.success) throw new Error(pres.error || 'Failed to record payment');

      // 2. Build the PDF data using the existing invoice_no and date (no new number needed)
      const pdfData = {
        name:        _outRecord.name,
        address:     _outRecord.address,
        contact:     _outRecord.contact,
        description: _outRecord.description,
        total:       _outRecord.total,
        paid:        pres.new_paid,          // updated cumulative paid
        invoice_no:  _outRecord.invoice_no,
        date:        _outRecord.date,
      };

      await generatePDF(pdfData);

      document.getElementById('receiptForm').style.display = 'none';
      document.getElementById('successInv').textContent = _outRecord.invoice_no;
      document.getElementById('successBox').style.display = 'block';
      return;
    } catch(err) {
      showErr('Error: ' + err.message);
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg> Record Payment &amp; Issue Receipt';
      return;
    }
  }

  /* ── NORMAL MODE: brand new receipt ── */
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
</script>
<script src="assets/client-autocomplete.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
