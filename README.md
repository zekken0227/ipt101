# Inventory Management System

A web-based inventory management system built with PHP and MySQL. This system allows users to manage inventory items, track stock levels, and handle item requests.

## Features

- User authentication and role-based access control
- Inventory management (CRUD operations)
- Stock level monitoring
- Request management system
- Dashboard with key metrics
- Responsive design using Bootstrap 5

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP (recommended for local development)

## Installation

1. Clone or download this repository to your web server's root directory
2. Create a new MySQL database named `inventory_system`
3. Import the `database.sql` file to set up the database structure
4. Configure the database connection in `config/database.php`
5. Access the system through your web browser

## Default Login Credentials

- Username: admin
- Password: admin123

## Directory Structure

```
inventory_system/
├── actions/
│   ├── add_item.php
│   └── delete_item.php
├── auth/
│   ├── login.php
│   └── logout.php
├── config/
│   └── database.php
├── includes/
│   └── navbar.php
├── database.sql
├── dashboard.php
├── index.php
├── inventory.php
└── README.md
```

## Security Considerations

- Change the default admin password after first login
- Keep the config directory outside the web root if possible
- Use HTTPS in production
- Regularly backup the database

## License

This project is licensed under the MIT License. 