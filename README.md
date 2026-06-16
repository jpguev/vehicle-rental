# vehicle-rental

A PHP-based vehicle rental application.

## Project structure

- `public/` - web root for the application pages
  - `index.php` - redirects to `login.php`
  - `login.php`, `signup.php`, `selection.php`, `vehicle_rental.php`, `vehicle_rental_no_driver.php`, `images_gallery.php`, `logout.php`
  - `assets/css/style.css` - application stylesheet
  - `assets/images/` - vehicle, driver, and UI images
  - `uploads/licenses/` - user-uploaded license files
- `src/` - reusable PHP backend code
  - `db.php` - database connection and helper functions
- `sql/` - database schema scripts
  - `database_schema.sql`
- `docs/` - project documentation
  - `DATABASE_GUIDE.md`
  - `SYSTEM_DESIGN.md`
- `legacy/` - legacy or helper files moved out of the public app root
  - `works.php`, `myinclude.inc.php`, `parseinput.html`, `parseoutput.php`, `sample_validators.php`

## Notes

- Set the web server document root to the `public/` folder.
- `public/login.php` and `public/signup.php` now include `../src/db.php`.
- Uploaded licenses are stored in `public/uploads/licenses/`.

## Quick start

1. Point your web server to `public/`.
2. Ensure PHP has write access to `public/uploads/licenses/`.
3. Create the database using `sql/database_schema.sql` or allow the app to initialize it via `src/db.php`.

