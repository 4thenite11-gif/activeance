# Activeance Investment CC - Car Sales Website

A premium, interactive car sales website featuring a 3D React Three Fiber background, Liquid Glass UI, Framer Motion scroll animations, and a secure PHP/MySQL backend with an admin dashboard.

## ✨ Features
- **3D Interactive Background:** Floating elements using React Three Fiber.
- **Scroll Word Design:** Smooth animations as you scroll through the car list.
- **Liquid Glass UI:** Modern glassmorphism design using Tailwind CSS.
- **WhatsApp Direct Integration:** Customers click "Shop Now" and it opens WhatsApp with a pre-filled message directly to the owner (`+264814540060`).
- **Secure Admin Panel:** PHP/MySQL backend using PDO and secure Bcrypt password hashing.
- **Fully Mobile Responsive.**

## 🛠️ Tech Stack
- **Frontend:** React, Vite, TypeScript, Tailwind CSS, Framer Motion, React Three Fiber.
- **Backend:** PHP, MySQL.

---

## 🚀 Setup Instructions

### 1. Database Setup (MySQL / phpMyAdmin)
1. Start your local server (XAMPP, WAMP, or MAMP).
2. Open phpMyAdmin (`http://localhost/phpmyadmin`).
3. Import the `backend/database.sql` file. This creates the `activeance_db` database, the `cars` table, and the `users` table.
4. **Important:** Follow the instructions in the previous step to generate a secure Bcrypt hash for your admin password and update the `users` table.

### 2. Backend Setup (PHP)
1. Move the `backend` folder into your local server's `htdocs` directory (e.g., `C:\xampp\htdocs\activeance\backend\`).
2. Open `backend/config.php` and verify your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'activeance_db');