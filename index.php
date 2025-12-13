<?php
/**
 * Main form page for ForgedCore Receipt Generator
 */
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>ForgedCore Receipt Generator</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      background: #f2f2f2; 
      padding: 30px; 
      margin: 0;
    }
    .container {
      max-width: 500px;
      margin: 0 auto;
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
    }
    form { 
      background: white; 
      padding: 30px; 
      border-radius: 8px; 
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    label { 
      display: block; 
      margin: 15px 0 5px 0; 
      font-weight: bold;
      color: #555;
    }
    input[type="text"], 
    input[type="number"], 
    textarea { 
      width: 100%; 
      padding: 10px; 
      margin: 5px 0 15px 0; 
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
      font-size: 14px;
    }
    textarea {
      resize: vertical;
      min-height: 80px;
    }
    button { 
      width: 100%; 
      padding: 12px; 
      background: #007bff; 
      color: white; 
      border: none; 
      border-radius: 5px; 
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 10px;
    }
    button:hover {
      background: #0056b3;
    }
    a { 
      text-align: center; 
      display: block; 
      margin-top: 20px; 
      color: #444;
      text-decoration: none;
    }
    a:hover {
      color: #007bff;
      text-decoration: underline;
    }
    .error {
      color: red;
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>ForgedCore Engineering - Receipt Generator</h2>
    <form action="submit.php" method="POST">
      <label for="name">Client Name:</label>
      <input type="text" id="name" name="name" required>

      <label for="address">Address:</label>
      <input type="text" id="address" name="address" required>

      <label for="contact">Contact:</label>
      <input type="text" id="contact" name="contact" required>

      <label for="description">Description:</label>
      <textarea id="description" name="description" required></textarea>

      <label for="total">Total Payment (GHS):</label>
      <input type="number" id="total" name="total" step="0.01" min="0" required>

      <label for="paid">Amount Paid (GHS):</label>
      <input type="number" id="paid" name="paid" step="0.01" min="0" required>

      <button type="submit">Generate PDF Receipt</button>
    </form>
    <a href="clients.php">View All Receipts</a>
  </div>
</body>
</html>

