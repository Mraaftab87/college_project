# Smart Inventory System

A comprehensive web-based inventory management system built with PHP, MySQL, and modern web technologies. This system provides role-based access control, product management, customer management, transaction tracking, and reporting capabilities.

## 🚀 Features

### Core Functionality
- **Product Management**: Add, edit, delete, and view products with detailed information
- **Category Management**: Organize products with categories and subcategories
- **Stock Management**: Track inventory levels with low stock alerts
- **Customer Management**: Maintain customer database with contact information
- **Transaction Tracking**: Record and monitor inventory transactions
- **Barcode Support**: Generate and scan product barcodes
- **Bulk Operations**: Import/export products via CSV files

### Role-Based Access Control
- **User Role**: View products and manage customers only
- **Manager Role**: View products, customers, transactions, and reports
- **Admin Role**: Full system access including user management

### Advanced Features
- **Search & Filter**: Advanced product search with multiple filters
- **Reporting**: Comprehensive inventory reports and analytics
- **Image Management**: Product image upload with thumbnail generation
- **CSRF Protection**: Security measures against cross-site request forgery
- **Responsive Design**: Mobile-friendly interface

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Icons**: Font Awesome 6.5.0
- **Server**: Apache/Nginx (XAMPP/WAMP compatible)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- GD extension for image processing
- mod_rewrite enabled (for clean URLs)

## 🚀 Installation

1. **Clone/Download** the project to your web server directory
2. **Database Setup**:
   - Create a MySQL database named `inventory`
   - Import `database.sql` to create tables and sample data
3. **Configuration**:
   - Update `db.php` with your database credentials
   - Ensure proper file permissions (755 for directories, 644 for files)
4. **Access** the system via web browser

### Default Login Credentials
- **Admin**: username: `admin`, password: `admin123`
- **Manager**: username: `manager`, password: `manager123`
- **User**: username: `user`, password: `user123`

## 📁 Project Structure

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

## 🔐 Security Features

- **Session Management**: Secure user sessions with timeout
- **CSRF Protection**: Cross-site request forgery prevention
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output sanitization
- **File Upload Security**: Validated file uploads with type checking
- **Role-Based Permissions**: Granular access control
- **Password Hashing**: Secure password storage

## 🎨 User Interface

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Modern UI**: Clean, intuitive interface with Font Awesome icons
- **Color-Coded Status**: Visual indicators for stock levels
- **Search & Filter**: Advanced filtering capabilities
- **Pagination**: Efficient data display for large datasets

## 📊 Key Features by Role

### User Role
- View product catalog
- Search and filter products
- Manage customer information
- View basic dashboard

### Manager Role
- All User permissions
- View transaction history
- Access detailed reports
- Monitor inventory analytics

### Admin Role
- All Manager permissions
- Add/edit/delete products
- Manage user accounts
- System configuration
- Full administrative access

## 🔧 Configuration

### Database Configuration (`db.php`)
```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventory';
```

### File Permissions
- Directories: 755
- Files: 644
- Images directory: 755

## 📈 Performance Features

- **Image Optimization**: Automatic thumbnail generation
- **Lazy Loading**: Images load as needed
- **Database Indexing**: Optimized queries
- **Caching**: Session-based caching
- **Compression**: CSS/JS minification ready

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**: Check credentials in `db.php`
2. **Image Upload Fails**: Verify GD extension and directory permissions
3. **Permission Denied**: Ensure proper file permissions
4. **Session Issues**: Check PHP session configuration

### Debug Mode
Enable error reporting in development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support or questions:
- Check the troubleshooting section
- Review the code comments
- Ensure all requirements are met

## 🎯 Project Goals

- Provide a complete inventory management solution
- Implement secure, role-based access control
- Create an intuitive, responsive user interface
- Support bulk operations for efficiency
- Generate comprehensive reports and analytics

---

**Smart Inventory System** - Streamlining inventory management with modern web technologies. 