# 📦 RockStock — Warehouse Management System (Frontend)

<div align="center">

![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Vite](https://img.shields.io/badge/Vite-6.3-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-4.1-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![MUI](https://img.shields.io/badge/MUI-7.1-007FFF?style=for-the-badge&logo=mui&logoColor=white)
![Arabic RTL](https://img.shields.io/badge/Arabic-RTL-green?style=for-the-badge)

**A full-featured Arabic RTL warehouse management frontend — 65 routes, multi-role access, and complete document workflows.**

واجهة أمامية متكاملة لنظام إدارة المستودعات باللغة العربية — تدعم أدوار متعددة وسير عمل وثائق كاملة.

> 🔗 **Backend:** [Warehouses-Management Laravel API](https://github.com/MG22Hadi/Warehouses-Mangement)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#️-tech-stack)
- [Installation](#️-installation)
- [User Roles](#-user-roles)
- [Screen Coverage](#-screen-coverage)
- [Project Structure](#-project-structure)
- [Authors](#-authors)

---

## 🌟 Overview

RockStock is an Arabic-first, RTL warehouse management interface built for real operational use.
It connects to a Laravel REST API backend and supports three user roles with different
access levels, approval workflows, and document management capabilities.

The frontend covers **65 routes** across authentication, inventory management, warehouse
documents, supplier management, and manager approval workflows — all in a consistent
Arabic RTL Material UI theme with light/dark mode support.

---

## ✨ Features

### 🗂️ Warehouse Document Workflows
- **Entry Notes** — record incoming goods with multi-item forms
- **Exit Notes** — record outgoing goods
- **Receiving Notes** — confirm supplier deliveries linked to purchase requests
- **Scrap Notes** — document scrapped materials with manager approval
- **Purchase Requests** — procurement workflow with supplier selection
- **Installation Reports** — material consumption tracking with approval flow
- **Custody Management** — assign items to users and track returns

### 📦 Inventory Management
- Product listing, filtering, and detailed views
- Warehouse-specific product balances
- Product movement history with monthly tracking
- Storage location assignment with availability search
- Unassigned items tracking

### 👥 Multi-Role Access Control
- Role-based route protection for `warehouseKeeper`, `warehouse`, and `manager`
- Manager approval/rejection workflows for material requests and reports
- Quantity adjustment capabilities for managers

### 📊 Dashboard & Analytics
- Summary cards and Chart.js visualizations
- Interactive FullCalendar with note creation/deletion
- Notification center with mark-all-read functionality

### 🖨️ Document Printing
- Custom A4 print utility for government-style warehouse documents
- Printable entry, exit, receiving, and custody documents

### 🌙 UI & Theming
- Full Arabic RTL layout throughout
- MUI-based light/dark theme switching
- Responsive design with Tailwind CSS utilities

---

## 🛠️ Tech Stack

| Library | Version | Purpose |
|---------|---------|---------|
| React | 19.1 | UI framework |
| React Router DOM | 7.6 | Routing and protected navigation |
| Vite | 6.3 | Build tool and dev server |
| Tailwind CSS | 4.1 | Utility-first styling |
| MUI Material | 7.1 | Component library and theme system |
| Axios | 1.9 | HTTP client with auth interceptor |
| Chart.js + React Chart.js 2 | 4.4 / 5.3 | Dashboard charts |
| FullCalendar | 6.1 | Interactive calendar widget |
| Notistack | 3.0 | Snackbar notifications |
| Headless UI | 2.2 | Accessible unstyled components |
| Lucide React + Heroicons | latest | Icon libraries |

---

## ⚙️ Installation

### Prerequisites
- Node.js 18+
- The [Laravel backend](https://github.com/MG22Hadi/Warehouses-Mangement) running on `http://127.0.0.1:8000`

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/MG22Hadi/Warehouse_React.git
cd Warehouse_React

# 2. Install dependencies
npm install

# 3. Start the dev server
npm run dev
```

Open `http://localhost:5173` in your browser.

### Available Scripts

| Command | Effect |
|---------|--------|
| `npm run dev` | Dev server with hot reload |
| `npm run build` | Production bundle → `dist/` |
| `npm run preview` | Serve the built bundle locally |
| `npm run lint` | Run ESLint checks |

### Backend Configuration

The frontend connects to the Laravel API at:

```
http://127.0.0.1:8000/api
```

Authentication tokens are stored in `localStorage` and automatically attached
to every request via an Axios interceptor.

---

## 👥 User Roles

| Role | Access |
|------|--------|
| 🏭 **WarehouseKeeper** | Manage inventory, create entry/exit/receiving notes, handle purchase requests |
| 👔 **Manager** | Approve/reject material requests, installation reports, and scrap notes; adjust quantities |
| 🧑‍💼 **User** | Submit material requests, view custody items, access personal dashboard |

---

## 📱 Screen Coverage

### Authentication
- Login, Registration, Password Reset

### Dashboard
- Summary cards, revenue charts, calendar widget, notifications

### Warehouses & Locations
- Warehouse CRUD, Department management, Storage location assignment
- Unassigned items tracking, Available location search

### Products
- Product listing and filtering, Warehouse-specific inventory
- Product details and movement history, Create and delete products

### Warehouse Documents

| Document Type | List | Detail | Create | Approve |
|---------------|------|--------|--------|---------|
| Entry Notes | ✅ | ✅ | ✅ | — |
| Exit Notes | ✅ | ✅ | ✅ | — |
| Receiving Notes | ✅ | ✅ | ✅ | — |
| Scrap Notes | ✅ | ✅ | ✅ | ✅ |
| Purchase Requests | ✅ | ✅ | ✅ | ✅ |
| Installation Reports | ✅ | ✅ | ✅ | ✅ |
| Custody | ✅ | — | — | — |

### Administration
- User management (4-step workflow), Supplier CRUD
- Notifications center, Settings, Material requests

### Utilities
- Interactive calendar with notes, A4 document printing, 404 page

---

## 📁 Project Structure

```
src/
├── api/
│   └── axiosInstance.js      # Axios config with auth interceptor
├── components/               # ~37 shared components
│   ├── layout/               # Sidebar, navbar, protected routes
│   ├── dashboard/            # Charts, cards, calendar
│   └── ui/                   # Reusable UI primitives
├── pages/                    # ~76 page files across all modules
│   ├── Auth/                 # Login, register, reset
│   ├── Dashboard/            # Main dashboard
│   ├── Warehouses/           # Warehouse and department management
│   ├── Products/             # Product pages
│   ├── Notes/                # Entry, exit, receiving documents
│   ├── Manager/              # Manager workflows and approvals
│   └── ...
├── print/                    # A4 document printing utility
├── utils/                    # Global notification handling
├── data/                     # Static demo data
└── App.jsx                   # Root router with 65 routes
```

---

## 👨‍💻 Authors

### Ammar Dahhan — Frontend Developer

- 🎨 Frontend development, UI implementation, and component work

[![GitHub](https://img.shields.io/badge/GitHub-AmmarDahhan-181717?style=flat&logo=github)](https://github.com/AmmarDahhan)

### Malak Mobark — Frontend Developer

- 🎨 Frontend development, UI implementation, and component work

[![GitHub](https://img.shields.io/badge/GitHub-MalakMobark-181717?style=flat&logo=github)](https://github.com/MalakMobark)

### Hadi Al-Gafer — AI & Full-Stack Developer

- 🎓 4th Year Computer Science Student — AI Specialization
- 🎨 Contributed to frontend architecture, component design, and Arabic RTL implementation
- 🔧 Also built the complete Laravel backend for this system

[![GitHub](https://img.shields.io/badge/GitHub-MG22Hadi-181717?style=flat&logo=github)](https://github.com/MG22Hadi)

---

## 📄 License

Academic and portfolio project — developed as part of a real-world warehouse management case study.
