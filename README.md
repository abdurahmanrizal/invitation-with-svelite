# Umrah Gathering Invitation

A full-featured SvelteKit application for managing Umrah gathering invitations with personalized QR codes, guest check-in scanning, and admin management panel.

## Features

### 🎨 **Frontend**
- **SvelteKit** with Vite for lightning-fast development
- **TypeScript** for type-safe development
- **Tailwind CSS v4** via `@tailwindcss/vite` for modern styling
- **shadcn-style** local UI components
- **Responsive design** with mobile-first approach

### 👤 **Guest Experience**
- **Personalized invitation pages** for each guest (e.g., `/invitation/UMR-ARIF-001`)
- **Event details section** with date, time, venue, and map integration
- **Agenda/timeline section** showing the program flow
- **Gallery section** for event atmosphere preview
- **Dynamic QR code generation** for each reservation
- **Guest quota display** showing allowed guests
- **Seat label information** for venue seating

### 📱 **Check-in System**
- **QR code scanner** at `/scan` for real-time guest check-in
- **Camera integration** using `qr-scanner` library
- **Real-time validation** of reservation codes
- **Check-in status tracking** (pending → confirmed → checked_in)
- **Timestamp recording** for check-in times
- **Error handling** with clear feedback for invalid/expired codes

### 🎛️ **Admin Panel**
- **Full CRUD operations** for reservations at `/admin`
- **Search functionality** by guest name, code, or phone number
- **Status management** (pending, confirmed, checked_in)
- **One-click invitation URL copying** for sharing
- **Real-time data updates** via PHP API backend
- **Responsive table view** for all reservations

### 🔧 **Backend**
- **PHP API** with MySQL database
- **RESTful endpoints** for reservation management
- **CORS-enabled** for cross-origin requests
- **Pre-flight request handling**
- **Error handling** with proper HTTP status codes

## Stack

### Frontend
- **SvelteKit** - Modern web framework
- **Vite** - Build tool and dev server
- **Bun** - Fast JavaScript runtime & package manager
- **TypeScript** - Type safety
- **Tailwind CSS v4** - Utility-first CSS
- **lucide-svelte** - Icon library
- `qrcode` - QR code generation
- `qr-scanner` - QR code scanning
- `class-variance-authority` - Component variant management
- `clsx` & `tailwind-merge` - Conditional class utilities

### Backend
- **PHP** - Server-side API
- **MySQL** - Database
- **RESTful API** - Standard HTTP methods

## Installation

1. **Clone and install dependencies:**
```bash
bun install
```

2. **Set up environment variables:**
```bash
cp .env.example .env
```

Edit `.env` and set your PHP API URL:
```env
PUBLIC_PHP_API_URL=http://localhost:8001
```

3. **Set up the PHP backend:**
```bash
cd php-api
# Import database.sql into your MySQL database
# Configure config.php with your database credentials
# Start PHP server
php -S localhost:8001
```

4. **Run the development server:**
```bash
bun run dev
```

## Deployment

See [deploy-shared-hosting.md](deploy-shared-hosting.md) for detailed deployment instructions.

### Quick deployment steps:

1. **Build the SvelteKit app:**
```bash
bun run build
```

2. **Upload files:**
- Upload contents of `build/` directory to your hosting `public_html/`
- Upload `php-api/` directory to your hosting
- Configure `php-api/config.php` with your production database credentials

3. **Update environment:**
- Set `PUBLIC_PHP_API_URL` to your production PHP API endpoint

## Routes

| Route | Description |
|-------|-------------|
| `/` | Main landing page with event details |
| `/invitation/[code]` | Personalized guest invitation page |
| `/scan` | QR code scanner for guest check-in |
| `/admin` | Admin panel for managing reservations |

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/reservations.php` | GET | Get all reservations |
| `/reservations.php` | POST | Create new reservation |
| `/reservation-id.php` | PUT | Update reservation by ID |
| `/reservation.php` | GET | Get reservation by code |
| `/reservation.php` | POST | Check in guest |
| `/reservations/[id]` | DELETE | Delete reservation |

## Database Schema

```sql
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservationCode` varchar(50) NOT NULL UNIQUE,
  `guestName` varchar(255) NOT NULL,
  `seatLabel` varchar(100) DEFAULT NULL,
  `allowedGuests` int(11) NOT NULL DEFAULT 1,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('confirmed','pending','checked_in') NOT NULL DEFAULT 'pending',
  `checkedInAt` datetime DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservation_code` (`reservationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Project Structure

```
├── src/
│   ├── lib/
│   │   ├── components/
│   │   │   ├── sections/      # Page sections (hero, agenda, gallery, etc.)
│   │   │   └── ui/            # Reusable UI components
│   │   ├── data.ts            # Static event data
│   │   ├── types.ts           # TypeScript type definitions
│   │   └── reservations.ts    # Reservation data types
│   └── routes/
│       ├── +layout.svelte     # Root layout
│       ├── +page.svelte       # Home page
│       ├── admin/             # Admin panel
│       ├── scan/              # QR scanner page
│       └── invitation/[code]/ # Personalized invitation pages
├── php-api/
│   ├── config.php             # Database configuration
│   ├── database.sql           # Database schema & sample data
│   ├── reservations.php       # CRUD operations
│   ├── reservation.php        # Single reservation & check-in
│   └── reservation-id.php     # Update by ID
├── static/                    # Static assets
└── public/                    # Public build output
```

## Usage

### Creating Reservations

1. **Via Admin Panel:**
   - Navigate to `/admin`
   - Click "Add Reservation"
   - Fill in guest details and create

2. **Via Database:**
   ```sql
   INSERT INTO reservations (reservationCode, guestName, seatLabel, allowedGuests, phone, status)
   VALUES ('UMR-XXX-001', 'Guest Name', 'Table A · Seat 01', 2, '+62...', 'confirmed');
   ```

### Guest Check-in Flow

1. Guest receives personalized invitation link (e.g., `yourdomain.com/invitation/UMR-XXX-001`)
2. Guest views their QR code on the invitation page
3. At the venue, staff scans the QR code using the `/scan` page
4. System validates and checks in the guest
5. Check-in timestamp is recorded

### Managing Reservations

- **Search** by name, code, or phone in the admin panel
- **Edit** reservation details including status
- **Delete** unwanted reservations
- **Copy** invitation URLs for sharing

## Configuration

### Event Details

Edit event information in [src/lib/data.ts](src/lib/data.ts):

```typescript
export const invitation = {
  date: "Saturday, 12 April 2026",
  time: "18:30 – 21:00 WIB",
  venue: "Grand Ballroom, Hotel Indonesia Kempinski",
  address: "Jl. M.H. Thamrin Kav. 1, Jakarta",
  mapUrl: "https://maps.google.com/..."
};
```

### Agenda

Update the program flow in [src/lib/data.ts](src/lib/data.ts):

```typescript
export const agenda = [
  { title: "Guest Arrival & Registration", time: "18:30" },
  { title: "Opening Remarks", time: "19:00" },
  // ...
];
```

## Scripts

```bash
bun run dev      # Start development server
bun run build    # Build for production
bun run preview  # Preview production build
bun run check    # Run type checking
```

## License

MIT
