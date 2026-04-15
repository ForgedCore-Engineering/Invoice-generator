<?php
/**
 * Dashboard — ForgedCore Receipt Manager
 */
$page_title    = 'Dashboard';
$page_subtitle = 'Your receipt activity at a glance';

require_once __DIR__ . '/config.php';

$stats   = ['total' => 0, 'revenue' => 0, 'collected' => 0, 'outstanding' => 0];
$recent  = [];
$ml = $mr = $mc = [];
$db_err  = null;

try {
    $pdo = getDB();

    // Global stats
    // Global stats - Grouped by project (prefix + total) to avoid double counting installments
    $s = $pdo->query("
        SELECT 
            COUNT(*) as c,
            SUM(proj_val) as rev,
            SUM(proj_coll) as coll
        FROM (
            SELECT 
                SUBSTRING_INDEX(invoice_no, '/', 2) as prefix,
                total as proj_val,
                MAX(paid) as proj_coll
            FROM clients
            GROUP BY prefix, total
        ) as projects
    ")->fetch();
    
    $stats['total']       = (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $stats['revenue']     = (float)$s['rev'];
    $stats['collected']   = (float)$s['coll'];
    $stats['outstanding'] = $stats['revenue'] - $stats['collected'];

    // Recent 10
    $recent = $pdo->query("
        SELECT id, name, contact, invoice_no, total, paid, date
        FROM clients ORDER BY id DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Monthly (last 6 months) - Project based aggregation
    $monthly = $pdo->query("
        SELECT 
            lbl,
            SUM(proj_val) as rev,
            SUM(proj_coll) as coll
        FROM (
            SELECT 
                DATE_FORMAT(created_at,'%b') as lbl,
                MAX(total) as proj_val,
                MAX(paid) as proj_coll,
                YEAR(created_at) as y,
                MONTH(created_at) as m
            FROM clients
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY SUBSTRING_INDEX(invoice_no, '/', 2), total, YEAR(created_at), MONTH(created_at)
        ) as sub
        GROUP BY y, m
        ORDER BY y, m
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monthly as $m) {
        $ml[] = $m['lbl'];
        $mr[] = (float)$m['rev'];
        $mc[] = (float)$m['coll'];
    }
} catch (Exception $e) {
    $db_err = $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($db_err): ?>
<div style="background:var(--red-bg);border:1px solid rgba(239,68,68,.3);border-radius:var(--rs);padding:13px 16px;margin-bottom:18px;color:var(--red);font-size:13px">
  ⚠ Database error: <?= htmlspecialchars($db_err) ?> —
  <a href="setup-database.php" style="color:var(--acc);text-decoration:underline">Run database setup</a>
</div>
<?php endif; ?>

<!-- ── Stats ── -->
<div class="sg">
  <div class="sc am">
    <div class="sc-bar"></div>
    <div class="sc-ic">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM6 10h2v2H6zm0 4h8v2H6zm10 0h2v2h-2zm-6-4h8v2h-8z"/></svg>
    </div>
    <div class="sc-val"><?= number_format($stats['total']) ?></div>
    <div class="sc-lbl">Receipts Issued</div>
  </div>
  <div class="sc bl">
    <div class="sc-bar"></div>
    <div class="sc-ic">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    </div>
    <div class="sc-val">GH₵ <?= number_format($stats['revenue'], 2) ?></div>
    <div class="sc-lbl">Total Invoice Value</div>
  </div>
  <div class="sc gn">
    <div class="sc-bar"></div>
    <div class="sc-ic">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
    </div>
    <div class="sc-val">GH₵ <?= number_format($stats['collected'], 2) ?></div>
    <div class="sc-lbl">Total Collected</div>
  </div>
  <div class="sc rd">
    <div class="sc-bar"></div>
    <div class="sc-ic">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    </div>
    <div class="sc-val">GH₵ <?= number_format($stats['outstanding'], 2) ?></div>
    <div class="sc-lbl">Outstanding Balance</div>
  </div>
</div>

<!-- ── Main Grid ── -->
<div class="g2">

  <!-- Recent Receipts -->
  <div class="card">
    <div class="card-hdr">
      <div>
        <div class="card-title">Recent Receipts</div>
        <div class="card-sub">Last 10 receipts issued</div>
      </div>
      <a href="clients.php" class="btn btn-s btn-sm">View All →</a>
    </div>
    <?php if (empty($recent)): ?>
    <div class="empty">
      <div class="empty-ic"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg></div>
      <h3>No receipts yet</h3>
      <p>Create your first receipt to see it here</p>
      <a href="new-receipt.php" class="btn btn-p">Create Receipt</a>
    </div>
    <?php else: ?>
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Client</th>
            <th>Invoice No</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $r):
            $bal  = (float)$r['total'] - (float)$r['paid'];
            $paid = (float)$r['paid'];
            if ($bal <= 0)   { $st='paid';    $sl='Paid'; }
            elseif ($paid>0) { $st='partial'; $sl='Partial'; }
            else             { $st='unpaid';  $sl='Unpaid'; }
            $init = strtoupper(substr(trim($r['name']),0,2));
          ?>
          <tr style="cursor:pointer" onclick="location.href='view-receipt.php?id=<?= $r['id'] ?>'">
            <td>
              <div class="cc">
                <div class="av"><?= htmlspecialchars($init) ?></div>
                <div>
                  <div class="cn"><?= htmlspecialchars($r['name']) ?></div>
                  <div class="csub"><?= htmlspecialchars($r['contact']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-family:monospace;font-size:12px;color:var(--txt2)"><?= htmlspecialchars($r['invoice_no']) ?></td>
            <td class="am-cell">GH₵ <?= number_format($r['total'],2) ?></td>
            <td><span class="badge bg-<?= $st ?>"><span class="bdot"></span><?= $sl ?></span></td>
            <td style="color:var(--txt3);font-size:12px;white-space:nowrap"><?= htmlspecialchars($r['date']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right Column -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-hdr"><div class="card-title">Quick Actions</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="new-receipt.php" class="btn btn-p btn-lg" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          Create New Receipt
        </a>
        <a href="clients.php" class="btn btn-s" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
          All Receipts
        </a>
        <a href="clients.php?status=unpaid" class="btn btn-d" style="justify-content:center">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          View Unpaid Receipts
        </a>
        <a href="clients.php?status=partial" class="btn" style="justify-content:center;background:var(--ylw-bg);color:var(--ylw);border:1px solid rgba(234,179,8,.2)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
          Partial Payments
        </a>
      </div>
    </div>

    <!-- Collection Rate -->
    <div class="card">
      <div class="card-hdr"><div class="card-title">Collection Rate</div></div>
      <div class="card-body">
        <?php $rate = $stats['revenue'] > 0 ? round(($stats['collected'] / $stats['revenue']) * 100, 1) : 0; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <span style="font-size:13px;color:var(--txt2)">Amount collected</span>
          <span style="font-size:20px;font-weight:700;color:var(--green)"><?= $rate ?>%</span>
        </div>
        <div style="background:var(--inp);border-radius:20px;height:8px;overflow:hidden">
          <div style="width:<?= $rate ?>%;height:100%;background:linear-gradient(90deg,var(--green),#34D399);border-radius:20px;transition:.8s ease;min-width:<?= $rate>0?'8px':'0' ?>"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:11.5px;color:var(--txt3)">
          <span>GH₵ <?= number_format($stats['collected'],2) ?> collected</span>
          <span>GH₵ <?= number_format(max(0,$stats['outstanding']),2) ?> pending</span>
        </div>
      </div>
    </div>

    <!-- Revenue Chart -->
    <?php if (!empty($ml)): ?>
    <div class="card">
      <div class="card-hdr"><div class="card-title">Revenue · Last 6 Months</div></div>
      <div class="card-body" style="padding:16px">
        <canvas id="revChart" height="190"></canvas>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if (!empty($ml)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode($ml) ?>,
    datasets: [
      {
        label: 'Revenue',
        data: <?= json_encode($mr) ?>,
        borderColor:'#3B82F6', backgroundColor:'rgba(59,130,246,.08)',
        fill:true, tension:.4, pointBackgroundColor:'#3B82F6', pointRadius:4, pointHoverRadius:6
      },
      {
        label: 'Collected',
        data: <?= json_encode($mc) ?>,
        borderColor:'#10B981', backgroundColor:'rgba(16,185,129,.08)',
        fill:true, tension:.4, pointBackgroundColor:'#10B981', pointRadius:4, pointHoverRadius:6
      }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:true,
    plugins:{
      legend:{ labels:{ color:'#94A3B8', boxWidth:10, font:{size:11} } },
      tooltip:{
        backgroundColor:'#141D35', borderColor:'#1B2B48', borderWidth:1,
        titleColor:'#F1F5F9', bodyColor:'#94A3B8',
        callbacks:{ label: ctx => ' GH₵ ' + ctx.parsed.y.toLocaleString(undefined,{minimumFractionDigits:2}) }
      }
    },
    scales:{
      x:{ ticks:{color:'#64748B',font:{size:11}}, grid:{color:'rgba(255,255,255,.04)'} },
      y:{ ticks:{color:'#64748B',font:{size:11},callback:v=>'₵'+Number(v).toLocaleString()}, grid:{color:'rgba(255,255,255,.04)'} }
    }
  }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
