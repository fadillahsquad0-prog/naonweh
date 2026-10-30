# Inferno Online Clothing Store

An Ecommerce Clothing Store Website

# Instructions

To get the website up and running on your machine, you will need [XAMPP](https://www.apachefriends.org/download.html). After installtion, follow the steps below.

1. Clone the repository using this command.
   ```python
   git clone https://github.com/yousaf530/Ecommerce-Clothing-Website.git
   ```
2. Copy and Paste the cloned folder in the **htdocs** folder in XAMPP folder on your machine.
3. Start Apache and MySQL from XAMPP control panel.
4. Open browser and write `localhost` in the url bar and XAMPP interface will open. Navigate to phpMyAdmin using Navbar.
5. Import the `store.sql` file in the phpMyAdmin panel in XAMPP.
6. You might need to provide the root user password in the `functions.php` and `db.php` in `mysqli_connect()` if you have already mySQL installed on your machine.
7. Type in the browser url bar
   ```python 
   localhost/Ecommerce-Clothing-Website
   ```
   Congrats! The Website is live on your machine.

## Admin Dashboard

Open `http://localhost/Ecommerce-Clothing-Website-master/admin/`. On first use, create the initial admin account; the setup page is disabled after that account exists. Sign in to manage products, categories, customers, orders, cart records, and homepage sliders. Customer passwords are never displayed in the dashboard.

Customers can update their profile from **My Account > My Details**, including their contact information, address, optional password, and profile photo. Cart items can be updated or removed before checkout. New orders save item names, image filenames, sizes, quantities, and prices so customers can view the product details in **My Orders**. Older orders may only contain their original totals because the previous schema did not save order line items; the application creates the `order_items` table automatically when needed.

## Features

1. Customer Registration
2. Customer Login
3. Search Products
4. Products Add to Cart
5. Customer Profile Page

### Front-End Development

HTML, CSS, JQuery and Bootstrap

### Back-End Development

Implemented using PHP and integrated with MySQLi database for storing data

### Sliders

owl.carousel.js used for creating products sliders

### Icons

Font Awesome and Elegent Icons
