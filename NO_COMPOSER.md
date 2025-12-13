# No Composer Required - JavaScript PDF Generation

This application now uses **jsPDF** (JavaScript library) instead of TCPDF (PHP/Composer), so **no Composer installation is needed**.

## How It Works

1. **Client-Side PDF Generation**: PDFs are generated in the browser using jsPDF library
2. **Server-Side Data Storage**: PHP only handles database operations
3. **No Dependencies**: No Composer, no TCPDF, no GD extension needed

## Benefits

- ✅ No Composer installation required
- ✅ No server-side PDF libraries needed
- ✅ Works on any PHP server (even shared hosting)
- ✅ Faster PDF generation (client-side)
- ✅ No server storage needed for PDFs (generated on-demand)

## Requirements

- PHP 7.4+ with MySQL/PDO support
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection (for CDN to load jsPDF)

## How to Use

1. Fill out the form
2. Click "Generate PDF Receipt"
3. PDF is generated in your browser and downloads automatically
4. Data is saved to MySQL database

## Technical Details

- **jsPDF**: Loaded from CDN (no installation needed)
- **API Endpoints**:
  - `get-invoice-info.php` - Gets invoice number and date
  - `save-receipt.php` - Saves receipt data to database
- **PDF Generation**: Happens entirely in the browser

## Notes

- Logo and signature images are loaded from `receipts/static/` directory
- If images can't be loaded (CORS issues), PDF will generate without them
- All PDFs are generated client-side and downloaded directly

