# 🏭 Warehouses Management System — نظام إدارة المستودعات

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Laravel_Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)

**A comprehensive RESTful API backend for managing warehouses, inventory, and logistics operations.**

نظام متكامل لإدارة المستودعات، المخزون، وعمليات الخدمات اللوجستية — مبني بـ Laravel REST API.

</div>

---

## 📋 Table of Contents | الفهرس

- [Overview](#-overview)
- [Features](#-features)
- [System Architecture](#-system-architecture)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [API Endpoints](#-api-endpoints)
- [User Roles](#-user-roles)
- [Database Schema](#-database-schema)
- [Author](#-author)

---

## 🌟 Overview

This system was designed based on a **real-world warehouse management use case**, covering the full lifecycle of inventory — from purchase requests and receiving notes, to storage locations, material requests, custody tracking, and scrapping.

صُمم هذا النظام بناءً على **حالة واقعية لإدارة مستودعات**، ويغطي دورة حياة المخزون الكاملة — من طلبات الشراء وسندات الاستلام، إلى مواقع التخزين، طلبات المواد، سجلات العهدة، وملاحظات الهدر.

---

## ✨ Features

### 📦 Inventory & Stock Management
- Track products across multiple warehouses and storage locations
- Real-time stock levels and monthly balance reports
- Product movement history and audit trail

### 📋 Document Management
- **Entry Notes** — record incoming goods
- **Exit Notes** — record outgoing goods
- **Receiving Notes** — confirm supplier deliveries

### 🔄 Request Workflows
- **Material Requests** — department users request materials
- **Purchase Requests** — warehouse keepers request procurement
- Multi-step approval flow: Pending → Approved / Rejected

### 🗂️ Custody & Asset Tracking
- Assign items to users and specific rooms
- Track custody returns with full history
- Bulk room assignment for assets

### 🏗️ Installation & Scrap
- Installation reports with material consumption tracking
- Scrap notes with manager approval workflow

### 🔔 Notifications & Calendar
- Real-time notifications via Laravel's polymorphic notification system
- Personal calendar notes per user

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Client (React / Mobile)               │
└─────────────────────┬───────────────────────────────────┘
                      │ HTTP Requests
┌─────────────────────▼───────────────────────────────────┐
│              Laravel REST API (Backend)                  │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │   Auth      │  │  Controllers │  │   Middleware   │  │
│  │  (Sanctum)  │  │  (30+ APIs)  │  │  (Role-based)  │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
└─────────────────────┬───────────────────────────────────┘
                      │ Eloquent ORM
┌─────────────────────▼───────────────────────────────────┐
│                    MySQL Database                        │
│           30+ Tables | Complex Relationships             │
└─────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| Language | PHP 8.2 |
| Database | MySQL 8 |
| Authentication | Laravel Sanctum (Token-based) |
| API Style | RESTful JSON API |

---

## ⚙️ Installation

### Prerequisites
- PHP >= 8.2
- Composer
- MySQL 8+
- Node.js (optional, for assets)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/MG22Hadi/Warehouses-Mangement.git
cd Warehouses-Mangement

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Generate app key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Start the server
php artisan serve
```

---

## 🔌 API Endpoints

All endpoints are prefixed with `/api` and protected by `auth:sanctum` unless noted.

### 🔐 Authentication (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Login and get token |
| POST | `/register` | Register new user |
| POST | `/logout` | Logout (protected) |

### 📦 Products
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | List all products |
| POST | `/products/store` | Create product |
| GET | `/products/{id}/details` | Product details |
| GET | `/products/search` | Search products |
| POST | `/products/update/{id}` | Update product |
| DELETE | `/products/delete/{id}` | Delete product |

### 🏭 Warehouses & Locations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/warehouses` | List / Create warehouses |
| GET/POST | `/locations` | List / Create locations |
| GET | `/locations/product-locations` | Products per location |
| POST | `/assign-location` | Assign product to location |
| GET | `/unassigned-items` | Items without location |

### 📋 Material & Purchase Requests
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/MRequest` | Submit material request |
| GET | `/pendingRequestMaterial` | Pending requests |
| PUT | `/materialRequests/{id}/approve` | Approve request |
| PUT | `/materialRequests/{id}/reject` | Reject request |
| GET/POST | `/purchase-requests` | Purchase requests CRUD |

### 📑 Notes (Entry / Exit / Receiving)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/entryNote/store` | Record incoming goods |
| POST | `/exitNote/store` | Record outgoing goods |
| POST | `/receivingNote/store` | Confirm supplier delivery |
| GET | `/allReceivingNote` | All receiving notes |

### 🗂️ Custody Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/custody/store` | Create custody record |
| GET | `/custody/showAll` | All custodies |
| POST | `/custody-returns` | Submit return request |
| GET | `/custody-returns/pending` | Pending returns |

### 📊 Reports & Analytics
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/product-movements/{id}` | Product movement history |
| GET | `/products/monthlyBalances` | Monthly stock report |
| GET | `/warehouses/{id}/product-balances` | Warehouse balances |
| GET | `/installation-reports/dashboard-stats` | Dashboard statistics |

> 📬 **Full Postman Collection:** *(coming soon)*

---

## 👥 User Roles

The system supports **3 distinct roles**, each with different permissions:

| Role | Description |
|------|-------------|
| 🧑‍💼 **User** | Department employee — can submit material requests, manage custody |
| 👔 **Manager** | Approves/rejects requests, signs off on installation and scrap reports |
| 🏭 **WarehouseKeeper** | Manages inventory, processes notes, handles purchase requests |

---

## 🗄️ Database Schema

The system includes **30+ database tables** covering:

```
Users & Roles          → users, managers, warehouse_keepers
Locations              → warehouses, buildings, rooms, locations, departments
Products & Stock       → products, stocks, product_locations, product_movements
Suppliers & Purchasing → suppliers, purchase_requests, purchase_request_items
Material Flow          → material_requests, entry_notes, exit_notes, receiving_notes
Custody & Assets       → custodies, custody_items, custody_returns
Operations             → installation_reports, scrap_notes, calendar_notes
System                 → notifications, personal_access_tokens, sessions
```

---

## 👨‍💻 Author

**Hadi Al-Gafer** — Backend Developer & System Designer

- 🎓 4th Year Computer Science Student — AI Specialization
- 🔧 Designed and led the backend architecture for this project
- 💼 Skilled in Laravel, RESTful API design, and database modeling

[![GitHub](https://img.shields.io/badge/GitHub-MG22Hadi-181717?style=flat&logo=github)](https://github.com/MG22Hadi)

**Nour Haies** — Backend Developer & System Designer

- 🎓 4th Year Computer Science Student — AI Specialization
- 🔧 Designed and led the backend architecture for this project
- 💼 Skilled in Laravel, RESTful API design, and database modeling

[![GitHub](https://img.shields.io/badge/GitHub-nourahaies-181717?style=flat&logo=github)](https://github.com/nourahaies)
---

## 📄 License

This project is for educational and portfolio purposes.
