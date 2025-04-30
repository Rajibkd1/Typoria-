<div align="center">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" width="200px" height="auto" style="margin-bottom: 20px;">
    <circle cx="50" cy="50" r="48" fill="#7c3aed"/>
    <path d="M65 25l-25 25M60 20l2 4L66 28 70 30l-8 8-20-20l8-8 3 4L57 20" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M40 45L30 55l-8 3 3-8 10-10M30 55l12 12" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>

  # Typoria

  <p>
    <img src="https://img.shields.io/badge/version-1.0.0-blue.svg?style=flat-square" alt="Version">
    <img src="https://img.shields.io/badge/license-MIT-green.svg?style=flat-square" alt="License">
    <img src="https://img.shields.io/badge/php-7.4+-8892BF.svg?style=flat-square" alt="PHP Version">
    <img src="https://img.shields.io/badge/MySQL-5.7+-orange.svg?style=flat-square" alt="MySQL Version">
  </p>

  <h3>A modern, elegant blogging platform where words come to life</h3>
</div>

<hr>

## ✨ Features

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin: 30px 0;">
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">📝 Rich Content Creation</h3>
    <p>Create beautiful articles with a powerful editor supporting images, formatting, and more.</p>
  </div>
  
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">👥 User Management</h3>
    <p>Secure authentication, profile customization, and user role management.</p>
  </div>
  
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">🔔 Notification System</h3>
    <p>Keep users engaged with real-time notifications for likes, comments, and mentions.</p>
  </div>
  
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">📊 Admin Dashboard</h3>
    <p>Comprehensive admin panel for content moderation and platform management.</p>
  </div>
  
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">🔍 Search & Categories</h3>
    <p>Powerful search functionality and organized content categories.</p>
  </div>
  
  <div style="padding: 18px; background-color: #f8fafc; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h3 style="margin-top: 0; color: #3B82F6;">❤️ Social Features</h3>
    <p>Likes, comments, bookmarks, and sharing capabilities to foster community.</p>
  </div>
</div>

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Step-by-Step Installation

1. **Clone the repository**

```bash
git clone https://github.com/Rajibkd1/typoria.git
cd typoria
```

2. **Create and configure the database**

```bash
# Create a new MySQL database
mysql -u root -p
```

```sql
CREATE DATABASE typoria;
USE typoria;
SOURCE database/schema.sql;
```

3. **Update the database configuration**

Edit `includes/functions.php` and update the database connection details:

```php
function get_db_connection() {
    $host = "localhost";      // Your database host
    $username = "your_user";  // Your database username
    $password = "your_pass";  // Your database password
    $database = "typoria";    // Your database name
    
    // ...
}
```

4. **Set up the web server**

Configure your web server to point to the project's root directory, ensuring that the document root is set correctly.

5. **Set proper permissions**

```bash
chmod -R 755 .
chmod -R 777 uploads/
```

6. **Access your site**

Open your web browser and navigate to your configured URL (e.g., `http://localhost/typoria` or your domain).

## 🌟 Usage

### User Roles

Typoria supports multiple user roles with different capabilities:

<div style="overflow-x: auto;">
  <table style="width: 100%; border-collapse: collapse; margin: 25px 0;">
    <tr style="background-color: #3B82F6; color: white;">
      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #ddd;">Role</th>
      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #ddd;">Capabilities</th>
    </tr>
    <tr style="background-color: #f8f8f8;">
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;"><strong>Visitor</strong></td>
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;">Read posts, view profiles</td>
    </tr>
    <tr>
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;"><strong>User</strong></td>
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;">Create posts, comment, like, bookmark, follow other users</td>
    </tr>
    <tr style="background-color: #f8f8f8;">
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;"><strong>Admin</strong></td>
      <td style="padding: 12px 15px; border-bottom: 1px solid #ddd;">Manage all content, approve/reject posts, manage users</td>
    </tr>
  </table>
</div>

### Key Features Guide

#### Creating a New Post

1. Log in to your account
2. Click on "Write" in the navigation menu
3. Enter title, content, select category, and add tags
4. Upload a feature image (optional)
5. Save as draft or submit for approval

#### Managing Your Profile

1. Navigate to your profile by clicking your username
2. Click "Edit Profile" to update your personal information
3. Upload a profile picture
4. Add social media links and a bio
5. Save changes

#### Admin Functions

1. Access the admin dashboard at `/admin`
2. Manage posts, categories, and users
3. Review and moderate pending content
4. Monitor platform statistics and activity

## 🔧 Customization

Typoria is designed to be easily customizable to fit your needs and branding.

### Theme Configuration

Edit the theme settings in `includes/theme.php` to customize colors, fonts, and layout elements:

```php
// Color Scheme
$TYPORIA_COLORS = [
    'primary' => '#3B82F6',      // Blue-500
    'secondary' => '#8B5CF6',    // Violet-500
    'accent' => '#10B981',       // Emerald-500
    // ...
];
```

### Site Configuration

Update general site settings in the same file:

```php
// Site Configuration
$TYPORIA_CONFIG = [
    'site_name' => 'Your Site Name',
    'site_tagline' => 'Your Custom Tagline',
    'site_description' => 'Your site description here',
    // ...
];
```

## 📁 Project Structure

```
typoria/
├── admin/              # Admin area files
├── assets/             # Static assets (CSS, JS, images)
├── database/           # Database schema and migrations
├── includes/           # Core functionality files
│   ├── functions.php   # Helper functions
│   └── theme.php       # Theme configuration
├── uploads/            # User uploaded content
├── index.php           # Main entry point
├── login.php           # Authentication
├── register.php        # User registration
└── README.md           # Project documentation
```

## 🔄 Database Schema

<div style="text-align: center; margin: 30px 0;">
  <img src="https://i.imgur.com/YF3Wzzr.png" alt="Database Schema" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
</div>

## 🤝 Contributing

Contributions are welcome! To contribute to Typoria:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add some amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

Please follow our [Code of Conduct](CODE_OF_CONDUCT.md) and [Contribution Guidelines](CONTRIBUTING.md).

## 📄 License

Typoria is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/) - For the UI components and styling
- [MySQL](https://www.mysql.com/) - Database system
- [PHP](https://www.php.net/) - Core programming language
- [Font Awesome](https://fontawesome.com/) - Icons used throughout the platform

---

<div style="text-align: center; margin-top: 50px; color: #6B7280;">
  <p>Built with ❤️ by Rajib Kumar</p>
  <a href="https://github.com/Rajibkd1" style="text-decoration: none; color: #3B82F6;">GitHub</a> •
  <a href="mailto:rrajibkd@gmail.com" style="text-decoration: none; color: #3B82F6;">Contact</a>
</div>
