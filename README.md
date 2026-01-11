# Tropicart

**Short Description**

Tropicart is a e-commerce platform with the core mission of sharing authentic tropical flavors with the world, primarily by using a curated selection of beverages and drinks as their initial main product line, thereby ensuring that people anywhere can easily order and savor products sourced directly from tropical islands. It features Dashboard, Products Management, Sales Management and Reports to have an outlook for the decision making in the future.

**Technologies Used**

*Frontend: HTML, Tailwind and Vanilla CSS, JavaScript
*Backend: PHP (OOP + MySQLi Prepared Statements)
*Database: XAMMP Mysql

**Features**
* Two factor Authentication (Signup)
* Homepage
* Product Catalog
* Add to cart
* Checkout and Order Processing
* Administrator Dashboard
* Inventory Management (Crud)
* Sales Management (Crud)
* Shipment Management
* User Management (Crud)
* Report Management (Daily, Weekly, Monthy) 

**Installation Instructions**

1) Clone the repository: git clone https://github.com/Mamaw321/Tropicart
2) Import SQL file: Open phpMyAdmin, create a new database, and import the file found in "/database/Tropicart_db.sql"
3) Configure Database: Update "db.php" or your database connection file with your local credentials (DB Name, Username, Password).
4) Start Local Server: Move the folder to "htdocs" (XAMPP) and access it via "localhost/Tropicart"

Admin Login
*Can be access on Tropicart/Files/db/adminacc.txt



**Project Structure**
* /db-Database 

* /Documentation- Word and Pdf project Documentation

* /Vendor-For 2FA authentication
  
* /uploads - Images


**User_Side**

* register.html - Registration for users

* login.html - For verifying the users identity
  
* user_product_page.html - For browsing the Products Catalog
  
* user_checkout_page.html - User_side page for the final step of buying products
  
* user_view_order.html. When the checkout process is complete the user can see their orders on this page

**Admin Side**
  
* admin_login.html - For verifying the Admin identity
  
* admin_dashboard.html - One of the mainpages for the Administrator, This page shows the summary of the flow
  
* admin_addproduct.html - One of the mainpages for the Administrator, In this page the Administrator can add, edit, delete and search products. 
  
* admin_orderlist.html - In this page the Administrator can see the Orders coming from the User Side. The Administrator can update the status of the order (Pending & Processing).
  
* admin_shipment.html - In this page, The Administrator can see the movement of delivery of the product, once the product reach its destination both on the user and admin side will update simultaneously.
  
* admin_reports.html - This is the last main process of being an Administrator. In this page, The Administrator can see the Overview of Sales, Additional Sales and Detailed Product. Each with its unique Graph and Numerical Values to back up the decision making.

**Developer Information**

Members
Group Name - Qwerty
1) Elaine Mae Sandigan - Role (Front-end Developer and Backend Developer) 
2) Jeremiah Roilo -  Role (Documentation) 
3) Mikylla Nicole Jereza - Role (Documentation) 
4) Christine Juban - Role (Documentation) 
5) Lyca Mondejar - Role (Documentation)
6) Trixie Nicole Jastillana - Role (Documentation)
7) Mary Rose Arguelles - Role (Documentation)



 
