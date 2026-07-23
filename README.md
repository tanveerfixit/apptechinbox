# TechInbox POS & Workspace Utility

TechInbox is a premium, lightweight, and responsive Point of Sale (POS) and workspace management tool designed for repair shops and retail environments. 

---

## 🛠️ Technology Stack

TechInbox is built as a highly optimized, hybrid application combining structured PHP backend logic with modern, reactive client-side libraries.

### 1. Core Architecture
- **Backend Language**: PHP (7.4+ / 8.x compatible)
- **Database Layer**: MySQL / MariaDB via **PDO** (configured with persistent connections `PDO::ATTR_PERSISTENT` to maximize performance and minimize connection limit issues on shared hosting servers).
- **Routing & Rendering**: Hybrid structure supporting both standalone optimized PHP scripts and integrated **Laravel Livewire** reactive components.

### 2. Frontend & Styling
- **Styling System**: **Tailwind CSS** integrated with custom, premium styling tokens matching the **Microsoft Fluent Light Theme** guidelines:
  - Clean light backgrounds (`#f3f3f3`)
  - Crisp container boundaries (`#e0e0e0`)
  - Distinct accent brand colors (Teal/Green/Blue/Orange)
  - Responsive layout handling across mobile, tablet, and desktop screens.
- **Interactions & State Management**: **Alpine.js** for reactive UI states, dynamic updates, and validation flows.
- **Bundling / Tooling**: Node.js with **Vite** config.

### 3. Key Assets & Integrations
- **QRious**: Client-side QR generation engine for generating stateless, timestamped intake sessions.
- **Audio Context API**: Synthesized notification chime audio cues on desktop when customer submissions are pulled.

---

## ✨ Core Features & Modules

### 📲 Mobile Customer Intake & QR Engine
A stateless, real-time booking utility:
- **Intake Flow**: The merchant launches the booking form, generating a dynamic QR code containing a secure Session ID, created timestamp, and business name.
- **Customer Form**: Customers scan the code on their mobile device and enter their details (Name, Phone required; Email, Device optional) directly on their browser.
- **Dynamic 3-Minute Expiration**: Form sessions expire after 180 seconds to secure connections. If expired, the QR code on the merchant's screen disappears and displays a refresh button, while the customer is notified.
- **Zero-Poll / Manual Trigger**: Features a manual pull check option to fetch data instantly, operating alongside a slow background pull routine to stay within hosting connection guidelines.

### 💨 Vape Order Management (`vape.php`)
- Interactive, filterable dashboard for managing active retail vape product orders.
- Features real-time status updates and order workflows.

### 📅 Daily Closer Accountant (`daily-closer.php`)
- Day-end balance audit tools.
- Tracks cash drawer balances, card totals, and calculated discrepancies to ensure bookkeeping transparency.

---

## 🚀 Setup & Local Development

### 1. Prerequisites
- **Web Server**: Apache / Nginx or PHP local built-in server.
- **Database**: MySQL/MariaDB database.
- **Composer & NPM**: For installing dependencies.

### 2. Installation Steps
1. Clone the repository to your local webroot:
   ```bash
   git clone https://github.com/tanveerfixit/apptechinbox.git
   ```
2. Configure Environment Variables:
   - Duplicate `.env.example` as `.env`.
   - Update database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. Run Database Migrations:
   - Database schemas are designed to auto-seed and alter table structures dynamically on first database boot (`db.php`).
4. Boot Local Server:
   - Run via PHP built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Or access via your configured local virtual host path.

---

## 📄 License
This project is proprietary and built for exclusive use in TechInbox Workspaces. All rights reserved.
