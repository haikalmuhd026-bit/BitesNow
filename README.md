# BitesNow
Food Delivery System
# BitesNow — Online Food Delivery Web Application

BitesNow is a full-stack PHP and MySQL food delivery web application built for a Klang Valley comfort-food kitchen. Customers can browse dishes, manage their cart, place orders with multiple payment options, track live kitchen progress, and leave verified dish ratings, while administrators manage incoming orders through a real-time Kitchen Queue.

---

## Key Features

### Customer Experience
* **Menu & Chef Recommendations:** Browse dishes (`food.php`) and top-rated chef picks (`recom.php`) displaying live average star ratings and verified diner feedback.
* **Persistent Shopping Cart:** Client-side cart powered by `localStorage` (`cart.php`) with real-time quantity adjustments, subtotals, and automatic cleanup upon checkout or logout.
* **Multi-Method Checkout:** Secure order placement (`checkout.php`) supporting Cash on Delivery (COD), DuitNow / TnG QR, Credit/Debit Card, and Online Banking (FPX).
* **Live Order Tracking & History:** 4-stage visual progress stepper (`track_order.php`) with automatic status polling (*Order Placed → In Kitchen → Out for Delivery → Delivered*) and full receipt history (`my_orders.php`).
* **Ratings & Store Locator:** Interactive 5-star dish review form (`product_review.php`) and embedded store map with direct inquiry messaging (`location.php`).

### Admin & Security
* **Kitchen Order Queue:** Role-protected dashboard (`admin_orders.php`) to advance order statuses, clear completed/cancelled tickets, and view customer inquiries.
* **Security Best Practices:** Prepared SQL statements (`mysqli`) across all queries, `password_hash()` / `password_verify()` authentication, CSRF token verification on all forms, and session regeneration.

---

## Tech Stack
* **Frontend:** HTML5, CSS3 (Custom Dark Theme), Vanilla JavaScript (`localStorage`)
* **Backend:** PHP 8+
* **Database:** MySQL / MariaDB

---

## Local Setup Instructions (XAMPP)

1. **Download or Clone the Repository:**
   Place the project folder inside your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\BitesNow`).
2. **Start Local Servers:**
   Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. **Import the Database:**
   * Open `http://localhost/phpmyadmin` in your browser.
   * Create a new database named **`bitesnow`** (collation: `utf8mb4_general_ci`).
   * Select the `bitesnow` database, click the **Import** tab, choose **`bitesnow.sql`** from the project folder, and click **Import**.
4. **Configure Database Connection (if needed):**
   The default configuration connects using `Host: localhost`, `User: root`, `Password: ""` (blank), and `Database: bitesnow`.
5. **Launch the Application:**
   Visit `http://localhost/BitesNow/index.php` in your browser.

---

## Demo Account Credentials

Use these accounts to test both customer and admin workflows (or create a new customer account via **Log in → Create an account**):

| Role | Username | Password | Access |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Kitchen Queue (`admin_orders.php`) & Storefront |
| **Customer** | `customer1` | `password123` | Ordering, Live Tracking, Order History & Reviews |

---

## Folder Overview
* `index.php` — Landing page & active delivery banner
* `food.php` / `recom.php` — Full menu & recommended dishes with live ratings
* `cart.php` / `checkout.php` — Cart management & order submission
* `track_order.php` / `my_orders.php` — Live order stepper & customer order history
* `admin_orders.php` — Admin Kitchen Queue & inquiry viewer
* `product_review.php` / `submit_review.php` — Dish rating form & handler
* `location.php` / `contact.php` / `AboutUs.php` — Store info, map & contact handler
* `login.php` / `validation.php` / `registration.php` / `logout.php` — Authentication flow
* `styles.css` — Master dark-theme stylesheet
* `bitesnow.sql` — MySQL database export
