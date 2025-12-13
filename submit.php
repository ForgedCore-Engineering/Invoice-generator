<?php
/**
 * Handle form submission and generate PDF receipt
 */

require_once __DIR__ . '/functions.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get and sanitize form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0.0;
$paid = isset($_POST['paid']) ? floatval($_POST['paid']) : 0.0;

// Validate required fields
if (empty($name)) {
    http_response_code(400);
    die("Client name is required");
}

// Generate invoice number and date
$date = formattedDate();
$invoice_no = generateInvoice($name);
$pdf_name = 'receipt_' . str_replace('/', '_', $invoice_no) . '.pdf';
$pdf_path = RECEIPT_DIR . '/' . $pdf_name;

// Prepare data for PDF
$data = [
    'name' => $name,
    'address' => $address,
    'contact' => $contact,
    'description' => $description,
    'total' => $total,
    'paid' => $paid,
    'date' => $date,
    'invoice_no' => $invoice_no
];

// Generate PDF
try {
    createPDF($pdf_path, $data);
} catch (Exception $e) {
    http_response_code(500);
    $errorMsg = $e->getMessage();
    // If it's a dependency error, show helpful message
    if (strpos($errorMsg, 'Composer dependencies') !== false || strpos($errorMsg, 'GD or Imagick') !== false) {
        die("
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Setup Required</title>
            <style>
                body { font-family: Arial; padding: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
                h1 { color: #d32f2f; }
                .error { background: #ffebee; padding: 15px; border-radius: 4px; margin: 20px 0; }
                code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
                .steps { line-height: 1.8; }
                a { color: #1976d2; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Setup Required</h1>
                <div class='error'>
                    <strong>Error:</strong> " . htmlspecialchars($errorMsg) . "
                </div>
                <h2>Installation Steps:</h2>
                " . (strpos($errorMsg, 'GD or Imagick') !== false ? "
                <div style='background: #fff3cd; padding: 15px; border-radius: 4px; margin: 15px 0;'>
                    <strong>Enable GD Extension:</strong>
                    <ol class='steps'>
                        <li>Open: <code>D:\\xampp\\php\\php.ini</code></li>
                        <li>Find: <code>;extension=gd</code></li>
                        <li>Remove semicolon: <code>extension=gd</code></li>
                        <li>Save the file</li>
                        <li>Restart Apache in XAMPP Control Panel</li>
                        <li>Check: <a href='check-extensions.php'>check-extensions.php</a></li>
                    </ol>
                    <p>See <a href='ENABLE_GD.md' target='_blank'>ENABLE_GD.md</a> for detailed instructions.</p>
                </div>
                " : "
                <ol class='steps'>
                    <li>Open Command Prompt or Terminal in the project directory: <code>D:\\xampp\\htdocs\\invoice</code></li>
                    <li>Run: <code>composer install</code></li>
                    <li>If you don't have Composer installed:
                        <ul>
                            <li>Download from: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></li>
                            <li>Or use: <code>php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php</code></li>
                        </ul>
                    </li>
                    <li>After installation, refresh this page</li>
                </ol>
                ") . "
                <p><a href='index.php'>← Back to Form</a></p>
            </div>
        </body>
        </html>
        ");
    }
    die("Error generating PDF: " . htmlspecialchars($errorMsg));
}

// Save to database
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO clients (name, address, contact, description, total, paid, date, invoice_no, pdf_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $address,
        $contact,
        $description,
        $total,
        $paid,
        $date,
        $invoice_no,
        $pdf_name
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("Error saving to database: " . $e->getMessage());
}

// Send PDF file to browser
if (file_exists($pdf_path)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $pdf_name . '"');
    header('Content-Length: ' . filesize($pdf_path));
    readfile($pdf_path);
    exit;
} else {
    http_response_code(500);
    die("PDF file not found");
}
?>

