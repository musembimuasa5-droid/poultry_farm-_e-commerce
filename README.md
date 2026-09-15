# Golden Eggs & Chicks Farm

Production-oriented PHP/MySQL starter for an online farm shop selling fresh eggs, chicks, feed and poultry equipment.

## Run locally

1. Update database credentials in `config.php` if your local MySQL settings differ.
2. Start Apache and MySQL in XAMPP. On the first page request, the app will create `golden_eggs` and load `database/schema.sql` automatically when the configured MySQL user can create databases.
3. If database creation is disabled, open phpMyAdmin, import `database/schema.sql`, then reload the site.
4. Serve the project from Apache, Nginx/PHP-FPM, or PHP's local server:

	`php -S localhost:8000`

5. Visit the project URL, for example `http://localhost/poultry_farm-_e-commerce/`.

The schema includes customers, admin roles, products, categories, carts, orders, payments, reviews, contact messages and newsletter subscriptions. Create the first admin by registering a user, then set its `role` to `admin` in MySQL.

## Structure

- `index.php`, `shop.php`, `about.php`, `contact.php`: public storefront
- `register.php`, `login.php`, `account.php`, `cart.php`, `checkout.php`: customer flows
- `admin/`: protected dashboard and management views
- `includes/`: shared layout helpers
- `database/schema.sql`: schema and sample catalog data
- `assets/`: responsive CSS and progressive enhancement JavaScript

The application uses PDO prepared statements, password hashing, CSRF tokens, escaped output, secure session cookies, role checks and validated newsletter/contact inputs. Replace the placeholder contact details and canonical domain before deployment, and configure HTTPS in production.
