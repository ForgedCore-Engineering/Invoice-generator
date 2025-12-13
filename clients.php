<?php
/**
 * Display all clients and receipts
 */

require_once __DIR__ . '/config.php';

// Get all clients from database
try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT id, name, invoice_no, total, paid, date, pdf_file 
        FROM clients 
        ORDER BY id DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
} catch (PDOException $e) {
    die("Error fetching clients: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Clients - ForgedCore</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      background: #f7f7f7; 
      padding: 20px; 
      margin: 0;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    h2 {
      color: #333;
      margin-bottom: 20px;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: #007bff;
      text-decoration: none;
    }
    .back-link:hover {
      text-decoration: underline;
    }
    table { 
      width: 100%; 
      background: white; 
      border-collapse: collapse; 
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    th, td { 
      padding: 12px; 
      border-bottom: 1px solid #eee; 
      text-align: left;
    }
    th { 
      background: #f0f0f0;
      font-weight: bold;
      color: #333;
    }
    tr:hover {
      background-color: #f9f9f9;
    }
    .download-link { 
      text-decoration: none; 
      color: white; 
      background: #007bff; 
      padding: 6px 12px; 
      border-radius: 4px;
      display: inline-block;
      font-size: 14px;
    }
    .download-link:hover {
      background: #0056b3;
    }
    .amount {
      font-weight: bold;
    }
    .no-data {
      text-align: center;
      padding: 40px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="back-link">← Back to Form</a>
    <h2>Saved Clients & Receipts</h2>
    <?php if (empty($rows)): ?>
      <div class="no-data">
        <p>No receipts found. <a href="index.php">Create your first receipt</a></p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Invoice No</th>
            <th>Total (GHS)</th>
            <th>Paid (GHS)</th>
            <th>Date</th>
            <th>Receipt</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars($row[0]); ?></td>
              <td><?php echo htmlspecialchars($row[1]); ?></td>
              <td><?php echo htmlspecialchars($row[2]); ?></td>
              <td class="amount"><?php echo number_format($row[3], 2); ?></td>
              <td class="amount"><?php echo number_format($row[4], 2); ?></td>
              <td><?php echo htmlspecialchars($row[5]); ?></td>
              <td>
                <a href="download.php?file=<?php echo urlencode($row[6]); ?>" class="download-link">
                  Download
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>

