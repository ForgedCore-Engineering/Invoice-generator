# Quick Setup Guide

## Step 1: Install Dependencies

Run this command in the project directory:

```bash
composer install
```

If you don't have Composer installed:
- Download from [https://getcomposer.org/download/](https://getcomposer.org/download/)
- Or use: `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php`

## Step 2: Place Static Files

Ensure these files exist:
- `receipts/static/logo.png` - Your company logo
- `receipts/static/signature.png` - Authorized signature image

## Step 3: Set Permissions (Linux/Mac only)

```bash
chmod 755 receipts
chmod 755 receipts/static
```

## Step 4: Run Installation Check

Open in browser: `http://localhost/invoice/install.php`

This will verify:
- PHP version
- SQLite extension
- Directory permissions
- Required files

## Step 5: Start Using

- **Main Form**: `http://localhost/invoice/index.php`
- **View Receipts**: `http://localhost/invoice/clients.php`

## Using PHP Built-in Server (Alternative)

If not using Apache/XAMPP:

```bash
cd invoice
php -S localhost:8000
```

Then access: `http://localhost:8000`

## Troubleshooting

### "Class 'TCPDF' not found"
- Run: `composer install`
- Ensure `vendor/` directory exists

### "Database connection failed"
- Check PHP has SQLite extension enabled
- Verify write permissions on directory

### Images not showing in PDF
- Check `receipts/static/logo.png` and `signature.png` exist
- Verify file permissions (readable)

### PDF generation fails
- Check `receipts/` directory is writable
- Verify TCPDF library is installed

