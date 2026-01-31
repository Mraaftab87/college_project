# Setup Instructions - Smart Inventory System

## Quick Setup Guide

### 1. Database Configuration

Copy the example database configuration file:
```bash
cp db.example.php db.php
```

Then edit `db.php` with your database credentials:
```php
$servername = "localhost";
$username = "your_mysql_username";
$password = "your_mysql_password";
$dbname = "inventory";
```

### 2. Import Database

Import the database schema:
```bash
mysql -u root -p < database.sql
```

Or use phpMyAdmin:
1. Open phpMyAdmin
2. Create a new database named `inventory`
3. Import the `database.sql` file

### 3. File Permissions

Ensure the images directory is writable:
```bash
chmod 755 images/
chmod 755 images/products/
chmod 755 images/products/thumbs/
```

### 4. Apache Configuration

Make sure `.htaccess` is enabled in your Apache configuration:
```apache
<Directory "/path/to/your/project">
    AllowOverride All
</Directory>
```

Restart Apache after changes:
```bash
# Linux/Mac
sudo service apache2 restart

# Windows (XAMPP)
# Restart Apache from XAMPP Control Panel
```

### 5. Default Login Credentials

- **Admin**: username: `admin`, password: `admin123`
- **Manager**: username: `manager`, password: `manager123`
- **User**: username: `user`, password: `user123`

**⚠️ IMPORTANT**: Change these passwords after first login!

### 6. Git Setup (Optional)

Initialize Git repository:
```bash
git init
git add .
git commit -m "Initial commit"
```

### 7. Production Deployment

For production deployment:

1. Enable HTTPS redirect in `.htaccess` (uncomment lines 48-50)
2. Update `db.php` with production credentials
3. Ensure `display_errors` is Off in PHP configuration
4. Set proper file permissions (644 for files, 755 for directories)
5. Change all default passwords

## Troubleshooting

### PHP files not executing
- Check if Apache mod_rewrite is enabled
- Verify `.htaccess` is being read (check Apache config)
- Ensure PHP is installed and configured

### Database connection error
- Verify MySQL is running
- Check credentials in `db.php`
- Ensure database `inventory` exists

### Image upload not working
- Check directory permissions (755 for directories)
- Verify GD extension is installed: `php -m | grep gd`
- Check `upload_max_filesize` in php.ini

### Session issues
- Ensure session directory is writable
- Check session configuration in php.ini

## Support

For issues or questions, check the main README.md file.
