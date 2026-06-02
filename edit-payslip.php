<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: payslips.php');
    exit;
}

$payslip = null;
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM payslips WHERE id = ?");
    $stmt->execute([$id]);
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching payslip: " . $e->getMessage());
}

if (!$payslip) {
    die("Payslip not found.");
}

$page_title = 'Edit Payslip';
$page_subtitle = 'Update details for Payslip ' . htmlspecialchars($payslip['payslip_no']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="g2-form">
  <div class="card">
    <div class="card-hdr">
      <div>
        <div class="card-title">Update Payslip Details</div>
        <div class="card-sub">Payslip: <?= htmlspecialchars($payslip['payslip_no']) ?></div>
      </div>
    </div>
    <div class="card-body">
      <form id="editPayslipForm" novalidate>
        <input type="hidden" name="id" value="<?= (int)$payslip['id'] ?>">
        <div class="fgrid">
          <div class="fg full">
            <label class="req" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($payslip['full_name']) ?>" required>
          </div>
          <div class="fg full">
            <label class="req" for="service">Services</label>
            <textarea id="service" name="service" required><?= htmlspecialchars($payslip['service']) ?></textarea>
          </div>
          <div class="fg">
            <label class="req" for="amount_due">Amount Supposed To Be Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="amount_due" name="amount_due" step="0.01" min="0" value="<?= htmlspecialchars($payslip['amount_due']) ?>" required>
            </div>
          </div>
          <div class="fg">
            <label class="req" for="amount_paid">Amount Paid (GHS)</label>
            <div class="ipfx">
              <span>₵</span>
              <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="0" value="<?= htmlspecialchars($payslip['amount_paid']) ?>" required>
            </div>
          </div>
          <div class="fg">
            <label class="req" for="issue_date">Issue Date</label>
            <input type="text" id="issue_date" name="issue_date" value="<?= htmlspecialchars($payslip['issue_date']) ?>" required>
          </div>
          <div class="fg">
            <label>Payslip No (Read-only)</label>
            <input type="text" value="<?= htmlspecialchars($payslip['payslip_no']) ?>" readonly style="background:var(--bg2);opacity:.75">
          </div>
        </div>

        <div id="errBox" style="display:none;background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:11px 14px;color:var(--red);font-size:13px;margin-bottom:14px"></div>

        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <button type="submit" id="submitBtn" class="btn btn-p btn-lg" style="flex:1;justify-content:center">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 7L9 19l-5.5-5.5 1.41-1.41L9 16.17 19.59 5.59 21 7z"/></svg>
            Save Changes
          </button>
          <a href="view-payslip.php?id=<?= (int)$payslip['id'] ?>" class="btn btn-s btn-lg">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('editPayslipForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const err = document.getElementById('errBox');
  const data = Object.fromEntries(new FormData(this).entries());

  if ((parseFloat(data.amount_paid) || 0) > (parseFloat(data.amount_due) || 0)) {
    err.textContent = 'Amount paid cannot be greater than amount supposed to be paid.';
    err.style.display = 'block';
    return;
  }

  err.style.display = 'none';
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Saving...';
  try {
    const res = await fetch('update-payslip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (!res.ok || !result.success) throw new Error(result.error || 'Update failed');
    toast('Payslip updated successfully');
    setTimeout(() => location.href = 'view-payslip.php?id=' + data.id, 900);
  } catch (e) {
    err.textContent = e.message;
    err.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 7L9 19l-5.5-5.5 1.41-1.41L9 16.17 19.59 5.59 21 7z"/></svg> Save Changes';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
