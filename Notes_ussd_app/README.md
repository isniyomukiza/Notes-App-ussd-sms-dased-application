# Notes USSD App
A PHP-based USSD application for managing personal notes, with SMS notifications for key actions using Africa's Talking.

## Features
- User registration via USSD
- Add, view, update, delete, and share notes
- Change PIN functionality
- SMS notifications for:
  - Successful registration
  - Saving a note (without sharing)
  - Sharing a note
  - Deleting a note
  - Changing PIN

## Requirements
- PHP 7.2+
- Composer
- MySQL
- Africa's Talking account (for SMS)
- Web server (e.g., Apache, Nginx)

## Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd Notes_ussd_app_backup/Notes_ussd_app
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure the database**
   - Create a MySQL database named `notes_app`.
   - Import the provided `notes_app.sql` file:
     ```bash
     mysql -u root -p notes_app < notes_app.sql
     ```
   - Update database credentials in `util.php` if needed.

4. **Configure Africa's Talking**
   - Update your Africa's Talking `username` and `apiKey` in `util.php` and `sms.php` if using production credentials.
   - For sandbox testing, add your test phone numbers in the Africa's Talking dashboard.

5. **Set up your web server**
   - Point your web server's document root to the `Notes_ussd_app` directory.
   - Ensure `index.php` is accessible for USSD POST requests.

## Usage
- Access the USSD code linked to your Africa's Talking channel.
- Register, add notes, share, delete, and change PIN via the USSD menu.
- SMS notifications will be sent to your phone for key actions.

## File Structure
- `index.php` - Main USSD entry point
- `menu.php` - USSD menu logic and actions
- `sms.php` - Africa's Talking SMS integration
- `util.php` - Utility functions (DB connection, constants)
- `notes_app.sql` - Database schema
- `vendor/` - Composer dependencies

## Troubleshooting
- Check `debug.log` for POST data and SMS API responses.
- Ensure your phone number is registered as a test number in Africa's Talking sandbox.
- If SMS is not received, check for errors in `debug.log` and verify your Africa's Talking credentials.

## License
MIT

## Developers
- NIYOMUKIZA Ismael
- TUYIZERE Aimable