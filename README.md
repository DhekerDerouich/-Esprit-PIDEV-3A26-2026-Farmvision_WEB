# 🌾 FarmVision - Agriculture Intelligente

## Quick Setup

### 1. Install Dependencies
```bash
composer install
composer require dompdf/dompdf
composer require cboden/ratchet
```

### 2. Configure Database
Update `.env`:
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/farmvision_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

### 3. Create Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Start Servers

**Terminal 1 - Symfony:**
```bash
symfony server:start
```

**Terminal 2 - WebSocket (for harvest alerts):**
```bash
php bin/websocket-server.php
```

## Features

### ✅ Harvest Alerts (WebSocket)
- Real-time notifications for harvest dates
- Bell icon in navigation
- Click to mark as seen
- No repeated notifications

### ✅ PDF Export
- Export cultures list or single culture
- Export parcelles list or single parcelle
- Professional design
- No external software needed (pure PHP)

### ✅ Culture & Parcelle Management
- CRUD operations
- Calendar view
- Weather integration
- GPS mapping

## Usage

### Harvest Alerts
1. Create culture with harvest date
2. Run: `php bin/console app:check-harvest-alert`
3. See alerts in bell icon (front-end only)

### PDF Export
1. Go to Cultures or Parcelles page
2. Click "📄 Export PDF" button
3. PDF downloads automatically

## That's It!

Simple, clean, and working. 🚀
