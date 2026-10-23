<<<<<<< HEAD
# Green Garden Farm

Healthy Poultry • Better Living

PHP/MySQL storefront for Green Garden Farm, selling fresh eggs, chicks, feed and poultry equipment.
=======
# Golden Eggs & Chicks Farm

Production-oriented PHP/MySQL starter for an online farm shop selling fresh eggs, chicks, feed and poultry equipment.
>>>>>>> origin/main

## Run locally

1. Update database credentials in `config.php` if your local MySQL settings differ.
2. Start Apache and MySQL in XAMPP. On the first page request, the app will create `golden_eggs` and load `database/schema.sql` automatically when the configured MySQL user can create databases.
3. If database creation is disabled, open phpMyAdmin, import `database/schema.sql`, then reload the site.
4. Serve the project from Apache, Nginx/PHP-FPM, or PHP's local server:

	`php -S localhost:8000`

5. Visit the project URL, for example `http://localhost/poultry_farm-_e-commerce/`.

<<<<<<< HEAD
The schema includes customers, admin roles, products, categories, carts, orders, payments, reviews, contact messages, newsletter subscriptions, delivery areas, quality flags and hatch batches. Create the first admin by registering a user, then set its `role` to `admin` in MySQL.
=======
The schema includes customers, admin roles, products, categories, carts, orders, payments, reviews, contact messages and newsletter subscriptions. Create the first admin by registering a user, then set its `role` to `admin` in MySQL.
>>>>>>> origin/main

## Structure

- `index.php`, `shop.php`, `about.php`, `contact.php`: public storefront
- `register.php`, `login.php`, `account.php`, `cart.php`, `checkout.php`: customer flows
- `admin/`: protected dashboard and management views
- `includes/`: shared layout helpers
- `database/schema.sql`: schema and sample catalog data
- `database/catalog_seed.sql`: add-on catalog migration for existing installations
- `assets/`: responsive CSS and progressive enhancement JavaScript

<<<<<<< HEAD
The application uses PDO prepared statements, password hashing, CSRF tokens, escaped output, secure session cookies, role checks and validated newsletter/contact inputs. Review the farm contact details and canonical domain before deployment, and configure HTTPS in production.
=======
The application uses PDO prepared statements, password hashing, CSRF tokens, escaped output, secure session cookies, role checks and validated newsletter/contact inputs. Replace the placeholder contact details and canonical domain before deployment, and configure HTTPS in production.
>>>>>>> origin/main

### Add the expanded catalog to an existing database

Import `database/catalog_seed.sql` in phpMyAdmin after the original schema. It adds more fertilized eggs, broilers, layers, poultry feeds and equipment without duplicating products already present.
<<<<<<< HEAD

### Shop catalogue

The shop uses the existing `products` and `categories` tables. It supports database-backed category counts, keyword search, price and stock filters, sorting, page-size selection, pagination, and grid/list views. Add or edit real inventory through the admin product manager; categories without products are still shown with a zero count. The storefront does not add demo inventory or a poultry-meat category unless those records exist in the database.

Product cards and the cart enforce current stock on the server. Checkout rechecks and reserves stock inside the order transaction. The existing checkout choices are not a payment-provider integration; configure and test a payment provider before accepting online payments in production.

### Delivery, hatch batches and quality indicators

The delivery checker accepts configured Nairobi and Kiambu neighbourhoods, quotes the standard fee, and applies free delivery above KSh 5,000. Checkout revalidates the delivery area and reserves stock transactionally.

The product schema contains batch tracking and explicit quality flags. For an existing database, run `database/storefront_features_migration.sql` in phpMyAdmin (select the `golden_eggs` database first). Manage chick and pullet release batches at `admin/batches.php`; products must have batch tracking enabled to use batch stock. The customer account page offers quick reorder for products present in prior orders.

Quality badges render only when an administrator has explicitly set the corresponding product flags. The M-Pesa, Visa, Mastercard and Equity labels are payment-method references, not evidence of a live payment-provider integration. Configure and verify a payment gateway before accepting online payments.

For local verification, start Apache and MySQL in XAMPP and visit `http://localhost/poultry_farm-_e-commerce/` and `http://localhost/poultry_farm-_e-commerce/shop.php`. On the shop page, try a category, a price range, each availability option, sorting and page 2; add an in-stock item, change its quantity in the cart, then verify that a quantity above available stock is rejected. No database migration is required for the shop filters.
=======
>>>>>>> origin/main
