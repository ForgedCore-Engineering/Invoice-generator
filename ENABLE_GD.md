# How to Enable GD Extension in XAMPP

The GD extension is required for TCPDF to handle PNG images with alpha channel (transparency).

## Quick Steps:

1. **Open php.ini file:**
   - Location: `D:\xampp\php\php.ini`
   - You can also access it from XAMPP Control Panel → Apache → Config → PHP (php.ini)

2. **Find the GD extension line:**
   - Press `Ctrl+F` and search for: `extension=gd`
   - You'll likely find it commented out like this:
     ```ini
     ;extension=gd
     ```

3. **Enable the extension:**
   - Remove the semicolon (`;`) at the beginning:
     ```ini
     extension=gd
     ```

4. **Save the file**

5. **Restart Apache:**
   - Open XAMPP Control Panel
   - Click "Stop" next to Apache
   - Click "Start" next to Apache

6. **Verify it's enabled:**
   - Visit: `http://localhost/invoice/check-extensions.php`
   - Or run: `php -r "echo extension_loaded('gd') ? 'GD Enabled' : 'GD Not Enabled';"`

## Alternative: If extension=gd line doesn't exist

If you can't find the line, add it manually in the "Dynamic Extensions" section:

```ini
; Dynamic Extensions
extension=gd
```

## Troubleshooting

- **Still not working?** Make sure you edited the correct php.ini file. Check which one PHP is using:
  ```bash
  php --ini
  ```

- **Apache won't start?** Check the Apache error log in XAMPP Control Panel

- **Extension still not loaded?** Make sure the GD DLL file exists in `D:\xampp\php\ext\` directory

