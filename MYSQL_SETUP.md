# MySQL Database Setup Guide

This application now uses MySQL instead of SQLite for better performance and scalability.

## Quick Setup

1. **Configure Database Credentials:**
   - Open `db_config.php`
   - Update the following values:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'forgedcore_receipts');
     define('DB_USER', 'root');
     define('DB_PASS', '');  // Your MySQL password
     ```

2. **Start MySQL:**
   - Open XAMPP Control Panel
   - Click "Start" next to MySQL

3. **Run Setup Script:**
   - Visit: `http://localhost/invoice/setup-database.php`
   - This will automatically:
     - Create the database if it doesn't exist
     - Create the `clients` table
     - Verify the setup

## Manual Setup (Alternative)

If you prefer to set up manually:

### Step 1: Create Database

```sql
CREATE DATABASE forgedcore_receipts 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### Step 2: Create Table

```sql
USE forgedcore_receipts;

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

## Default XAMPP MySQL Credentials

- **Host:** localhost
- **Username:** root
- **Password:** (empty by default)
- **Port:** 3306

## Security Notes

1. **Change Default Password:**
   - For production, change the default MySQL root password
   - Update `db_config.php` with secure credentials

2. **Create Dedicated User:**
   - Don't use root user in production
   - Create a dedicated database user:
     ```sql
     CREATE USER 'forgedcore_user'@'localhost' IDENTIFIED BY 'secure_password';
     GRANT ALL PRIVILEGES ON forgedcore_receipts.* TO 'forgedcore_user'@'localhost';
     FLUSH PRIVILEGES;
     ```

3. **Protect db_config.php:**
   - The `.htaccess` file already protects this file
   - Never commit real credentials to version control

## Migration from SQLite (if needed)

If you have existing data in SQLite:

1. Export data from SQLite:
   ```bash
   sqlite3 database.db ".dump" > export.sql
   ```

2. Convert SQLite syntax to MySQL syntax (manual conversion may be needed)

3. Import to MySQL:
   ```bash
   mysql -u root -p forgedcore_receipts < converted_export.sql
   ```

## Troubleshooting

### "Access denied for user"
- Check MySQL username and password in `db_config.php`
- Verify MySQL is running in XAMPP

### "Unknown database"
- Run `setup-database.php` to create the database
- Or create it manually using phpMyAdmin

### "Table doesn't exist"
- Run `setup-database.php` to create tables
- Or run the CREATE TABLE SQL manually

### Connection Refused
- Make sure MySQL is started in XAMPP Control Panel
- Check if MySQL is running on port 3306

## Using phpMyAdmin

XAMPP includes phpMyAdmin for easy database management:

1. Start MySQL in XAMPP
2. Visit: `http://localhost/phpmyadmin`
3. Create database and tables as needed
4. Or use the setup script for automatic setup

## Verification

After setup, verify everything works:

1. Visit: `http://localhost/invoice/setup-database.php`
2. Should show all green checkmarks
3. Try creating a receipt to test database writes
4. Check `clients.php` to verify data is being saved

