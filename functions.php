<?php
/**
 * Helper functions for ForgedCore Receipt Generator
 */

require_once __DIR__ . '/config.php';

/**
 * Generate invoice number based on client name
 * Format: FC/{SHORT_NAME}/{COUNT}
 */
function generateInvoice($name) {
    $prefix = "FC";
    $short = strtoupper(substr(trim($name), 0, 2));
    if (empty($short)) {
        $short = "XX";
    }
    
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    $count = $stmt->fetchColumn() + 1;
    
    return sprintf("%s/%s/%03d", $prefix, $short, $count);
}

/**
 * Get ordinal suffix for day (1st, 2nd, 3rd, 4th, etc.)
 */
function ordinal($n) {
    $n = (int)$n;
    if ($n >= 10 && $n <= 20) {
        return $n . "th";
    }
    
    $lastDigit = $n % 10;
    switch ($lastDigit) {
        case 1:
            return $n . "st";
        case 2:
            return $n . "nd";
        case 3:
            return $n . "rd";
        default:
            return $n . "th";
    }
}

/**
 * Format date with ordinal day
 * Format: "1st January 2024"
 */
function formattedDate($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $day = date('j', $timestamp);
    $month = date('F', $timestamp);
    $year = date('Y', $timestamp);
    
    return ordinal($day) . " " . $month . " " . $year;
}

/**
 * Create PDF receipt using TCPDF
 */
function createPDF($filepath, $data) {
    // Check if Composer dependencies are installed
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception(
            'Composer dependencies not installed. Please run: composer install' . PHP_EOL .
            'If you don\'t have Composer, download it from: https://getcomposer.org/download/'
        );
    }
    
    // Include TCPDF library via Composer autoload
    require_once $autoloadPath;
    
    // Check for image extension
    $hasGD = extension_loaded('gd');
    $hasImagick = extension_loaded('imagick');
    if (!$hasGD && !$hasImagick) {
        $errorMsg = "GD or Imagick extension is required for PNG image handling.\n\n";
        $errorMsg .= "To enable GD extension in XAMPP:\n";
        $errorMsg .= "1. Open: D:\\xampp\\php\\php.ini\n";
        $errorMsg .= "2. Find: ;extension=gd\n";
        $errorMsg .= "3. Remove semicolon: extension=gd\n";
        $errorMsg .= "4. Save and restart Apache\n";
        $errorMsg .= "5. Check: http://localhost/invoice/check-extensions.php\n\n";
        $errorMsg .= "See ENABLE_GD.md for detailed instructions.";
        throw new Exception($errorMsg);
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('ForgedCore Engineering Ltd');
    $pdf->SetAuthor('ForgedCore Engineering Ltd');
    $pdf->SetTitle('Receipt - ' . $data['invoice_no']);
    $pdf->SetSubject('Receipt');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins (30mm = ~113 points)
    $pdf->SetMargins(30, 30, 30);
    $pdf->SetAutoPageBreak(true, 30);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font to Times (serif font - professional alternative to Seaford)
    $pdf->SetFont('times', '', 10);
    
    // Header section with logo and company info
    // Build HTML table for header (matching original Python layout)
    $logoPath = STATIC_DIR . '/logo.png';
    $logoHtml = '';
    if (file_exists($logoPath)) {
        $logoPathAbsolute = realpath($logoPath);
        // Use bigger logo size
        $logoHtml = '<img src="' . $logoPathAbsolute . '" width="90" height="90" />';
    } else {
        // If logo doesn't exist, show placeholder
        $logoHtml = '<div style="width: 90px; height: 90px; background: #f0f0f0; border: 1px solid #ddd;"></div>';
    }
    
    $html = '<table cellpadding="0" cellspacing="0" border="0" style="width: 100%;">';
    $html .= '<tr>';
    $html .= '<td width="100" valign="middle" style="text-align: left;">' . $logoHtml . '</td>';
    $html .= '<td width="*" align="right" valign="middle" style="text-align: right;">';
    $html .= '<b style="font-size: 11pt; font-family: times;">FORGEDCORE ENGINEERING LTD</b><br/>';
    $html .= '<span style="font-size: 9pt; font-family: times;">Kpobiman (Amasaman), Accra</span><br/>';
    $html .= '<span style="font-size: 9pt; font-family: times;">0540202096 / 0545286665 | forgedcoreengineering@gmail.com</span><br/>';
    $html .= '<span style="font-size: 9pt; font-family: times;">www.forgedcoreengineering.com</span>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);
    
    // Title
    $pdf->SetFont('times', 'B', 18);
    $pdf->Cell(0, 10, 'RECEIPT', 0, 1, 'C');
    $pdf->Ln(6);
    
    // Invoice and Bill To section
    $pdf->SetFont('times', '', 10);
    $html = '<table cellpadding="5" cellspacing="0" border="0" style="width: 100%; font-family: times;">';
    $html .= '<tr>';
    $html .= '<td width="50%" valign="top" style="text-align: left; font-family: times;">';
    $html .= '<b style="font-family: times;">Invoice No:</b> <span style="font-family: times;">' . htmlspecialchars($data['invoice_no']) . '</span><br/>';
    $html .= '<b style="font-family: times;">Date:</b> <span style="font-family: times;">' . htmlspecialchars($data['date']) . '</span>';
    $html .= '</td>';
    $html .= '<td width="50%" valign="top" style="text-align: right; font-family: times;">';
    $html .= '<b style="font-family: times;">BILL TO</b><br/>';
    $html .= '<span style="font-family: times;">Client Name: ' . htmlspecialchars($data['name']) . '</span><br/>';
    $html .= '<span style="font-family: times;">Address: ' . htmlspecialchars($data['address']) . '</span><br/>';
    $html .= '<span style="font-family: times;">Contact: ' . htmlspecialchars($data['contact']) . '</span>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(12);
    
    // Description with justified text
    $pdf->SetFont('times', 'B', 12);
    $description = strtoupper(htmlspecialchars($data['description']));
    // Center the description title, but use justified text for content
    $pdf->MultiCell(0, 10, $description, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'M');
    $pdf->Ln(8);
    
    // Payment Summary
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(0, 10, 'PAYMENT SUMMARY', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Payment Summary Table (matching original: colWidths=[360, 140])
    $balance = $data['total'] - $data['paid'];
    
    $html = '<table cellpadding="8" cellspacing="0" border="0.5" style="width: 100%; border-color: #cccccc; font-family: times;">';
    $html .= '<tr style="background-color: #f5f5f5;">';
    $html .= '<th width="72%" align="left" style="font-weight: bold; text-align: left; font-family: times;">DESCRIPTION</th>';
    $html .= '<th width="28%" align="right" style="font-weight: bold; text-align: right; font-family: times;">AMOUNT (GHS)</th>';
    $html .= '</tr>';
    $html .= '<tr style="background-color: #ffffff;">';
    $html .= '<td style="text-align: left; font-family: times;">TOTAL SUM</td>';
    $html .= '<td align="right" style="text-align: right; font-family: times;">' . number_format($data['total'], 2) . '</td>';
    $html .= '</tr>';
    $html .= '<tr style="background-color: #f7f7f7;">';
    $html .= '<td style="text-align: left; font-family: times;">TOTAL AMOUNT PAID</td>';
    $html .= '<td align="right" style="text-align: right; font-family: times;"><span style="color: green; font-weight: bold;">' . number_format($data['paid'], 2) . '</span></td>';
    $html .= '</tr>';
    $html .= '<tr style="background-color: #ffffff;">';
    $html .= '<td style="text-align: left; font-family: times;">OUTSTANDING BALANCE</td>';
    $html .= '<td align="right" style="text-align: right; font-family: times;"><span style="color: red; font-weight: bold;">' . number_format($balance, 2) . '</span></td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(40);
    
    // Signature section - using HTML table layout
    $sigPath = STATIC_DIR . '/signature.png';
    $sigHtml = '';
    if (file_exists($sigPath)) {
        $sigPathAbsolute = realpath($sigPath);
        $sigHtml = '<img src="' . $sigPathAbsolute . '" width="120" height="40" /><br/>';
    }
    
    $html = '<table cellpadding="0" cellspacing="0" border="0" style="width: 100%; font-family: times;">';
    $html .= '<tr>';
    $html .= '<td width="64%" style="text-align: left;"></td>';
    $html .= '<td width="36%" valign="top" style="text-align: left; padding-left: 25px; font-family: times;">';
    $html .= '<b style="font-family: times;">Authorized Signature:</b><br/>';
    if ($sigHtml) {
        $html .= $sigHtml;
    } else {
        $html .= '<div style="width: 120px; height: 40px; background: #f0f0f0; border: 1px solid #ddd; margin: 5px 0;"></div>';
    }
    $html .= '<br/><span style="font-family: times;">Eyram Dela Kuwornu</span><br/>';
    $html .= '<span style="font-size: 9pt; font-family: times;">(Director – Forgedcore Engineering Ltd)</span>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Save PDF file
    $pdf->Output($filepath, 'F');
}
?>

