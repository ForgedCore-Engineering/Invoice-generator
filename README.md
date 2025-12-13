# ForgedCore Engineering Receipt Generator

A PHP-based web application for generating professional PDF receipts for ForgedCore Engineering Ltd.

## Features

- **Client Management**: Store client information (name, address, contact)
- **Receipt Generation**: Automatically generate professional PDF receipts
- **Invoice Numbering**: Automatic invoice number generation (FC/{SHORT_NAME}/{COUNT})
- **Payment Tracking**: Track total payments and amounts paid with outstanding balance calculation
- **Receipt History**: View all generated receipts with download capability
- **Professional PDFs**: Generate PDFs with company logo, signature, and formatted layout

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- PDO MySQL extension (usually included with PHP)
- Composer (for dependency management)
- Web server (Apache/Nginx) or PHP built-in server

## Installation

1. **Install dependencies using Composer:**
   ```bash
   composer install
   ```
   
   If you don't have Composer installed, you can install it from [getcomposer.org](https://getcomposer.org/)

2. **Configure MySQL Database:**
   - Open `db_config.php` and update MySQL credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'forgedcore_receipts');
     define('DB_USER', 'root');
     define('DB_PASS', '');  // Your MySQL password
     ```
   - Start MySQL in XAMPP Control Panel
   - Visit `http://localhost/invoice/setup-database.php` to create database and tables

3. **Ensure directories exist:**
   - `receipts/` - For storing generated PDF files
   - `receipts/static/` - For storing logo.png and signature.png

4. **Place static files:**
   - Place your company logo as `receipts/static/logo.png`
   - Place your signature image as `receipts/static/signature.png`

5. **Set permissions (Linux/Mac):**
   ```bash
   chmod 755 receipts
   chmod 755 receipts/static
   ```

## Usage

### Using PHP Built-in Server (Development)

```bash
php -S localhost:8000
```

Then open your browser to `http://localhost:8000`

### Using Apache/Nginx

1. Place the application in your web server's document root
2. Ensure mod_rewrite is enabled (for .htaccess)
3. Access via your web server URL

## Application Structure

```
invoice/
├── index.php          # Main form page
├── submit.php         # Form submission handler
├── clients.php        # Clients/receipts listing page
├── download.php       # PDF download handler
├── config.php         # Configuration and database setup
├── functions.php      # Helper functions (PDF generation, utilities)
├── composer.json      # PHP dependencies
├── .htaccess          # Apache configuration
├── db_config.php      # MySQL database configuration
├── setup-database.php # Database setup script
├── receipts/          # Generated PDF files
│   └── static/       # Logo and signature images
│       ├── logo.png
│       └── signature.png
└── vendor/            # Composer dependencies (after install)
```

## Database Schema

The application uses MySQL with the following table structure:

```sql
CREATE TABLE clients (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    contact VARCHAR(255),
    description TEXT,
    total DECIMAL(10,2) DEFAULT 0.00,
    paid DECIMAL(10,2) DEFAULT 0.00,
    date VARCHAR(255),
    invoice_no VARCHAR(100),
    pdf_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_invoice_no (invoice_no),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

See `MYSQL_SETUP.md` for detailed MySQL setup instructions.

## Features in Detail

### Invoice Number Generation
- Format: `FC/{SHORT_NAME}/{COUNT}`
- Example: `FC/CH/001` for client "Charles"
- Automatically increments based on total client count

### Date Formatting
- Format: "1st January 2024" (with ordinal suffixes)
- Automatically uses current date when generating receipt

### PDF Receipt Contents
- Company header with logo and contact information
- Invoice number and date
- Client billing information
- Service description
- Payment summary table with:
  - Total sum
  - Amount paid (highlighted in green)
  - Outstanding balance (highlighted in red)
- Authorized signature section

## Security Notes

- File downloads are validated to only allow PDF files
- Filenames are sanitized using `basename()` to prevent directory traversal
- SQL queries use prepared statements to prevent SQL injection
- Input validation on all form fields

## Troubleshooting

### PDF Generation Fails
- Ensure TCPDF library is installed: `composer install`
- Check that `receipts/` directory is writable
- Verify PHP has write permissions

### Database Errors
- Ensure MySQL is running in XAMPP Control Panel
- Check MySQL credentials in `db_config.php`
- Verify database exists (run `setup-database.php`)
- Ensure PDO MySQL extension is enabled in PHP

### Images Not Showing in PDF
- Ensure `logo.png` and `signature.png` exist in `receipts/static/`
- Check file permissions on image files
- Verify image file formats are supported (PNG, JPG)

## License

This application is proprietary software for ForgedCore Engineering Ltd.

## Support

For issues or questions, contact: forgedcoreengineering@gmail.com

