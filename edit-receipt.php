<?php
/**
 * Edit Receipt — ForgedCore Receipt Manager
 */
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$receipt = null;

if ($id <= 0) {
    header('Location: clients.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching receipt: " . $e->getMessage());
}

if (!$receipt) {
    die("Receipt not found.");
}

$page_title    = 'Edit Receipt';
$page_subtitle = 'Update details for Invoice ' . htmlspecialchars($receipt['invoice_no']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="g2-form">

  <!-- ── Form Card ── -->
  <div class="card">
    <div class="card-hdr">
      <div>
        <div class="card-title">Update Receipt Details</div>
        <div class="card-sub">Invoice: <?= htmlspecialchars($receipt['invoice_no']) ?></div>
      </div>
    </div>
    <div class="card-body">

      <form id="editForm" novalidate>
        <input type="hidden" name="id" value="<?= $receipt['id'] ?>">
        <div class="fgrid">

          <div class="fg">
            <label class="req" for="name">Client Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($receipt['name']) ?>" required>
          </div>

          <div class="fg">
            <label class="req" for="contact">Contact / Phone</label>
            <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($receipt['contact']) ?>" required>
          </div>

          <div class="fg full">
            <label class="req" for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($receipt['address']) ?>" required>
          </div>

          <div class="fg full">
            <label class="req" for="description">Description / Service Rendered</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($receipt['description']) ?></textarea>
          </div>

          <div class="fg">
            <label class="req" for="total">Total Amount (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="total" name="total" value="<?= $receipt['total'] ?>" step="0.01" min="0" required>
            </div>
          </div>

          <div class="fg">
            <label class="req" for="paid">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="paid" name="paid" value="<?= $receipt['paid'] ?>" step="0.01" min="0" required>
            </div>
          </div>

          <div class="fg">
            <label class="req" for="date">Receipt Date</label>
            <input type="text" id="date" name="date" value="<?= htmlspecialchars($receipt['date']) ?>" placeholder="e.g. 15th April, 2026" required>
            <div style="font-size:10px;color:var(--txt3);margin-top:2px">Format as you want it shown on PDF (e.g. 12th Jan, 2025)</div>
          </div>

          <div class="fg">
            <label for="invoice_no">Invoice No (Read-only)</label>
            <input type="text" id="invoice_no" value="<?= htmlspecialchars($receipt['invoice_no']) ?>" readonly style="background:var(--bg2);opacity:0.7">
          </div>

        </div>

        <div id="errBox" style="display:none;background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:11px 14px;color:var(--red);font-size:13px;margin-bottom:14px"></div>

        <div style="display:flex;gap:12px">
            <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="flex:1;justify-content:center">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 7L9 19l-5.5-5.5 1.41-1.41L9 16.17 19.59 5.59 21 7z"/></svg>
              Save Changes
            </button>
            <a href="clients.php" class="btn btn-s btn-lg">Cancel</a>
        </div>
      </form>

    </div>
  </div>

  <!-- ── Live Preview ── -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Live Preview Update</div></div>
      <div class="card-body" style="padding:16px">
        <div style="text-align:right;border-bottom:1px solid var(--br);padding-bottom:12px;margin-bottom:12px">
          <div style="font-size:10.5px;font-weight:700;color:var(--txt2);letter-spacing:.5px">FORGEDCORE ENGINEERING LTD</div>
        </div>
        <div id="pv-name" style="font-size:13.5px;font-weight:600;color:var(--txt);margin-bottom:2px">—</div>
        <div id="pv-date" style="font-size:11.5px;color:var(--acc);margin-bottom:14px">—</div>
        <div id="pv-desc" style="font-size:11.5px;font-weight:700;color:var(--txt);text-align:center;margin-bottom:12px;text-transform:uppercase">—</div>
        <div style="background:var(--inp);border-radius:var(--rs);overflow:hidden;font-size:12px">
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br)">
            <span style="color:var(--txt2)">Total Sum</span>
            <span id="pv-total" class="am-cell">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 10px;border-top:1px solid var(--br);color:var(--green)">
            <span>Amount Paid</span>
            <span id="pv-paid" class="am-cell">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function updatePreview() {
  const name  = document.getElementById('name').value.trim();
  const date  = document.getElementById('date').value.trim();
  const desc  = document.getElementById('description').value.trim();
  const total = parseFloat(document.getElementById('total').value) || 0;
  const paid  = parseFloat(document.getElementById('paid').value) || 0;

  document.getElementById('pv-name').textContent = name || '—';
  document.getElementById('pv-date').textContent = date || '—';
  document.getElementById('pv-desc').textContent = desc ? desc.toUpperCase() : '—';
  document.getElementById('pv-total').textContent = 'GH₵ ' + total.toFixed(2);
  document.getElementById('pv-paid').textContent = 'GH₵ ' + paid.toFixed(2);
}

['name','date','description','total','paid'].forEach(id => {
  document.getElementById(id).addEventListener('input', updatePreview);
});
updatePreview();

document.getElementById('editForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const errBox = document.getElementById('errBox');
  errBox.style.display = 'none';

  const formData = new FormData(this);
  const data = Object.fromEntries(formData.entries());

  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Saving…';

  try {
    const res = await fetch('update-receipt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      toast('Receipt updated successfully');
      setTimeout(() => location.href = 'view-receipt.php?id=' + data.id, 1000);
    } else {
      throw new Error(result.error || 'Update failed');
    }
  } catch (err) {
    errBox.textContent = err.message;
    errBox.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 7L9 19l-5.5-5.5 1.41-1.41L9 16.17 19.59 5.59 21 7z"/></svg> Save Changes';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
