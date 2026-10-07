# 🌾 Smart Farm Management

A web-based **Smart Farm Management System** developed using **Core PHP and MySQL**.  
The system is designed to manage farm operations, managers, workers, attendance, inventory, financial records, products, customers, orders, pre-orders, reporting, and other farm-related activities from a centralized platform.

This project was developed as part of the **Database Management Systems Laboratory (CSE 3522)** course.

---

## 📌 Project Information

- **Project Title:** Smart Farm Management
- **Course Title:** Database Management Systems Laboratory
- **Course Code:** CSE 3522
- **Section:** J
- **Group No:** 2
- **Instructor:** Miftahul Sheikh

---

## 👥 Submitted By

| Student ID | Name |
|---|---|
| 0112330461 | Md. Nafiz Nizam |
| 0112510165 | Sabjana Mehejabin Prapty |
| 0112420247 | Nishat Tarannum |
| 0112330755 | Shakib Chowdhury |

---

## 🎯 Project Overview

Smart Farm Management provides a centralized web platform for managing different farm-related activities.

The system allows the **Owner** to manage farms, managers, financial information, inventory, workforce, products, reports, and permissions.

Each farm can have an assigned **Manager** who manages the operational activities of that farm.

Farm workers are maintained as workforce records. They do not require individual system accounts.

Customers can browse products, place orders, use pre-order/pickup facilities where available, and communicate with support.

The system also includes an **optional delivery module** that can be used when delivery functionality is required.

---

## ✨ Main Features

### 👑 Owner Management

- Owner dashboard
- Farm management
- Create and manage Managers
- Assign Managers to farms
- Manager permission control
- View workforce information
- View worker attendance
- Financial monitoring
- Income and expense management
- Inventory monitoring
- Purchase management
- Product management
- Reports and summaries
- Seller application review
- Notifications

### 👨‍💼 Manager Management

Managers operate only within their assigned farm.

Managers can manage:

- Farm workers
- Worker information
- Worker attendance
- Daily Leave
- Worker payroll
- Inventory
- Stores
- Purchases
- Products
- Stock
- Feed and medicine records
- Pre-order capacity
- Farm operational records

Manager access can be controlled by the Owner through permissions.

---

## 👷 Workforce Management

Workers are maintained as farm records instead of website users.

Worker information can include:

- Name
- Mobile number
- NID information
- Address
- Worker photo
- Join date
- Payment type
- Payment rate
- Employment status

Supported workforce operations include:

- Add worker
- Update worker information
- Attendance management
- Present / Absent
- Daily Leave
- Attendance history
- Payroll records
- End Employment

---

## 📦 Inventory & Store Management

The system supports farm inventory and store operations including:

- Multiple stores
- Inventory items
- Stock quantities
- Purchase records
- Feed stock
- Medicine records
- Farm-related supplies
- Stock monitoring

---

## 💰 Financial Management

The system maintains financial records for farm operations.

Features include:

- Income records
- Expense records
- Purchases
- Worker salary/payment records
- Manager salary records
- Account transactions
- Cash records
- Financial reports

---

## 🛒 Customer & Shop System

Customers can use the public marketplace to:

- Register an account
- Login
- Browse products
- Search products
- Add products to cart
- Checkout
- View orders
- View order status
- Use support features

---

## 📅 Pre-order & Pickup

The system supports farm product pre-orders.

Managers can configure:

- Expected daily quantity
- Pre-order limit
- Product availability
- Pickup time
- Product unit

This helps prevent orders from exceeding expected farm production.

Customers can place eligible pre-orders and collect products using the configured pickup process.

---

## 🚚 Optional Delivery Module

Delivery is an **optional feature** of Smart Farm Management.

The core system can operate without delivery functionality.

When enabled, the delivery module can support:

- Delivery assignments
- Delivery personnel
- Customer-delivery communication
- Delivery collections
- Delivery settlements
- Delivery earnings
- Delivery-related order operations

Normal farm management, customer ordering, and pickup/pre-order operations do not depend on this module.

---

## 🧑‍🌾 External Seller / Marketplace

External farmers or sellers can apply through the platform.

The application process can include:

- Seller information
- Address
- Product information
- Supporting information/documents

The Owner can review seller applications before marketplace participation.

---

## 🔔 Notifications & Support

The system includes:

- User notifications
- Notification bell
- Unread notification indicator
- Customer support
- Support conversations
- Profile settings
- UI theme preferences

---

## 🛠️ Technologies Used

### Backend

- Core PHP

### Database

- MySQL

### Frontend

- HTML5
- CSS3
- Bootstrap
- JavaScript

### Development Environment

- XAMPP
- Apache
- PHP
- MySQL
- phpMyAdmin
- Visual Studio Code

---

## 🗄️ Database

The complete database structure is available inside:

```text
database/smart_farm.sql
```

The project uses a relational MySQL database for storing: