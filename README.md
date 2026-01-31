# Smart Inventory System

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

A web-based inventory management system built with PHP and MySQL. Manage products, track inventory, handle customer data, and generate reports - all with role-based access control.

## Screenshots

> Screenshots coming soon

## Features

**Product Management**
- Add, edit, and delete products
- Organize with categories and subcategories
- Track stock levels with low stock alerts
- Upload product images with automatic thumbnails
- SKU and barcode support

**Customer Management**
- Maintain customer database
- Track customer types (Regular, Wholesale, VIP, Corporate)
- View customer transaction history
- Manage credit limits and GST numbers

**Transactions & Reports**
- Record buy/sell transactions
- Generate monthly sales and purchase reports
- Calculate profit/loss
- Export data to CSV
- View transaction history

**User Management**
- Three user roles: Admin, Manager, User
- Role-based permissions
- Secure login with password recovery
- Track user login activity

**Additional Features**
- Barcode scanning
- Bulk import/export via CSV
- Advanced search and filtering
- Responsive design for mobile
- Security features (CSRF protection, SQL injection prevention)

## Tech Stack

- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- Apache with mod_rewrite
- Font Awesome icons

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- GD extension (for image processing)
- mod_rewrite enabled

## Installation

1. Clone or download this repository
2. Create a MySQL database named `inventory`
3. Import `database.sql` into your database
4. Copy `db.example.php` to `db.php` and update with your database credentials
5. Make sure `images/` directory is writable
6. Access via browser: `http://localhost/smart-inventory-system/`

**Default Login:**
- Admin: `admin` / `admin123`
- Manager: `manager` / `manager123`
- User: `user` / `user123`

Change these passwords after first login!

See [SETUP.md](SETUP.md) for detailed instructions.

## Project Structure

```
inventory_system/
├── index.php              # Main dashboard
├── login.php              # User authentication
├── register.php           # User registration
├── functions.php          # Core functions and utilities
├── db.php                 # Database connection
├── style.css              # Main stylesheet
├── database.sql           # Database schema and sample data
├── README.md              # This file
├── LICENSE                # MIT License
├── images/                # Product images directory
│   └── .htaccess          # Security for images
├── Product Management/
│   ├── add_product.php    # Add new products
│   ├── view_products.php  # View product list
│   ├── edit_product.php  # Edit existing products
│   └── delete_product.php # Delete products
├── Customer Management/
│   ├── customers.php      # Customer management
│   └── process_customer.php # Customer operations
├── Transaction Management/
│   ├── transactions.php   # Transaction history
│   └── export_data.php   # Data export functionality
├── Reporting/
│   └── reports.php        # Inventory reports
├── Bulk Operations/
│   ├── bulk_operations.php    # Bulk import/export interface
│   ├── process_import.php     # CSV import processing
│   └── download_template.php  # Download CSV template
├── Barcode System/
│   └── barcode_scanner.php   # Barcode scanning interface
└── AJAX Handlers/
    ├── get_product_details.php    # Product details API
    ├── get_subcategories.php      # Subcategory loading
    ├── get_product_items.php      # Product items loading
    ├── get_companies.php         # Company data API
    └── get_type_item_category.php # Category data API
```

## Security

- Password hashing with bcrypt
- Prepared statements for SQL queries
- CSRF token protection
- XSS prevention through output sanitization
- Secure session management
- File upload validation
- Role-based access control

## User Interface

The system features a clean, responsive design that works on desktop, tablet, and mobile devices. Stock levels are color-coded for quick identification, and the interface includes advanced search and filtering options.

## User Roles

**User**
- View products
- Manage customers
- Basic dashboard access

**Manager**
- Everything User can do
- View transactions
- Access reports
- Add transactions

**Admin**
- Everything Manager can do
- Add/edit/delete products
- Manage users
- Full system access

## Configuration

Edit `db.php` with your database credentials:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";
```

Make sure the `images/` directory has write permissions.

## Troubleshooting

**Database connection error?** Check your credentials in `db.php`

**Image upload not working?** Verify GD extension is installed and `images/` directory is writable

**Session issues?** Check PHP session configuration in php.ini

## Contributing

Found a bug or want to add a feature? Contributions are welcome!

1. Fork the repo
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Author

Update this section with your information:
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

## Future Plans

- REST API
- Email notifications
- PDF reports
- Multi-warehouse support
- Advanced analytics

---

Built as a learning project to demonstrate PHP, MySQL, and web development skills. 