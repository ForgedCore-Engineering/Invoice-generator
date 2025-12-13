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
    
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
        $count = $stmt->fetchColumn() + 1;
    } catch (Exception $e) {
        // If database error, use timestamp as fallback
        $count = time() % 1000;
    }
    
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

// PDF generation is now handled client-side using jsPDF
// No server-side PDF generation needed
?>

