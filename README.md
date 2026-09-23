# Greaselogs - Premium Accounts Marketplace

Greaselogs is a high-end marketplace for buying and selling social media accounts, built with PHP and MySQL. It features an elite SaaS UI, secure wallet system, Paystack integration, and an automated delivery system.

## 🚀 Features
- **Elite SaaS UI**: Dark luxury theme with glassmorphism and smooth animations.
- **User System**: Secure registration, login, and dashboard.
- **Wallet System**: Fund wallet via Paystack and use balance for purchases.
- **Marketplace**: Advanced filtering, search, and featured listings.
- **Auto-Delivery**: Instant access to account credentials after purchase.
- **Admin Panel**: Full control over users, listings, and transactions.
- **API Integration**: Sync accounts from AcctShop.com.

## 🛠️ Installation Guide (cPanel)

1. **Upload Files**:
   - Upload the `Greaselogs.zip` file to your cPanel File Manager (usually in `public_html`).
   - Extract the contents.

2. **Create Database**:
   - Go to **MySQL Database Wizard** in cPanel.
   - Create a new database (e.g., `greaselogs_db`).
   - Create a new user and assign it to the database with all privileges.

3. **Import SQL**:
   - Open **phpMyAdmin**.
   - Select your new database.
   - Click the **Import** tab and upload the `database.sql` file.

4. **Configure Site**:
   - Open `includes/config.php`.
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_db_username');
     define('DB_PASS', 'your_db_password');
     define('DB_NAME', 'your_db_name');
     ```
   - Update the `SITE_URL` to your domain:
     ```php
     define('SITE_URL', 'https://yourdomain.com');
     ```
   - Add your **Paystack** and **AcctShop** API keys.

5. **Admin Access**:
   - URL: `https://yourdomain.com/admin/login.php`
   - Default Email: `admin@deluxesocial.com`
   - Default Password: `admin123` (Please change this immediately after login).

6. **Cron Job (Optional for API Sync)**:
   - Set up a cron job in cPanel to run every hour:
     ```bash
     php /home/username/public_html/api/acctshop.php
     ```

## 🔐 Security
- Password hashing using `password_hash()`.
- Prepared SQL statements using PDO.
- CSRF and XSS protection.
- Secure session management.

## 📄 License
This project is licensed for use as a premium marketplace solution.

---
Developer
Smart Wisdom

Frontend Developer focused on building responsive, interactive web applications and digital products.

GitHub: https://github.com/dtech3168-bit
Portfolio: https://digitechcom.netlify.app
under Smartech Fullstack Innovations 
## Visit site
https://smartech.com.ng
