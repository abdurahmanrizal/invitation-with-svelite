# Deploy to Shared Hosting (PHP + MySQL)

This guide will help you deploy your Umrah Gathering QR Code app to shared hosting with PHP and MySQL support.

## Prerequisites
- Shared hosting with PHP 7.4 or higher
- MySQL database
- Access to PHPMyAdmin or MySQL console
- FTP or file manager access

## Step 1: Build the Project

1. Install dependencies:
```bash
bun install
```

2. Build the static export:
```bash
bun run build
```

This will create a `build/` directory with all static files.

## Step 2: Set Up the Database

1. Create a new MySQL database via your hosting control panel
2. Open PHPMyAdmin and import the SQL file:
   - Go to `php-api/database.sql`
   - Copy the contents
   - Paste in PHPMyAdmin SQL tab and run

Or you can create the table manually with the SQL provided.

## Step 3: Configure Database Connection

1. Edit `php-api/config.php`:
```php
<?php
return [
    'db_host' => 'localhost',  // Usually localhost, check your hosting
    'db_user' => 'your_database_username',  // Your MySQL username
    'db_pass' => 'your_database_password',  // Your MySQL password
    'db_name' => 'your_database_name'  // Your database name
];
?>
```

## Step 4: Upload Files

Upload the following files/folders to your hosting:

### Public HTML directory (usually `public_html` or `www`):
```
build/
├── _app/
├── index.html
└── (all other build files)
```

### API directory:
Create an `api/` folder in your public directory and upload:
```
api/
├── .htaccess
├── config.php
└── reservation.php
```

Your structure should look like:
```
public_html/
├── api/
│   ├── .htaccess
│   ├── config.php
│   └── reservation.php
├── _app/
├── index.html
└── (other build files)
```

## Step 5: Test the Deployment

1. Visit your domain
2. Try accessing: `https://yourdomain.com/api/reservation/UMR-ARIF-001`
   - Should return JSON with reservation details
3. Test the scan page: `https://yourdomain.com/scan`

## Troubleshooting

### API returns 404
- Check that `.htaccess` is uploaded to the `api/` folder
- Verify mod_rewrite is enabled on your hosting

### Database connection error
- Verify database credentials in `config.php`
- Check that MySQL database exists and user has permissions
- Try 'localhost' or '127.0.0.1' for db_host

### Scan page not working
- Ensure HTTPS is enabled (camera requires HTTPS)
- Check browser console for errors
- Verify qr-scanner files are included in build

## File Permissions

After uploading, set proper permissions:
- Folders: 755
- PHP files: 644
- `.htaccess`: 644

## Adding New Reservations

You can add reservations via PHPMyAdmin:

```sql
INSERT INTO `reservations` (`reservationCode`, `guestName`, `seatLabel`, `allowedGuests`, `phone`, `status`)
VALUES ('UMR-NEW001', 'Guest Name', 'Table X · Seat Y', 2, '+62...', 'confirmed');
```

## Security Tips

1. Change database password regularly
2. Don't upload `node_modules` or source files
3. Keep config.php outside web root if possible
4. Use strong passwords for database
