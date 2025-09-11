Personal Expense Tracker
A comprehensive, web-based Personal Expense Tracker built with PHP, MySQL, and JavaScript. This application allows users to register, log in, and manage their daily expenses through an intuitive and interactive dashboard.

Features
User Authentication: Secure user registration and login system with password hashing.

Expense Management: Add, edit, and delete expenses with details like description, amount, category, date, and an optional receipt URL.

Smart Categorization: Automatically suggests a category based on keywords in the expense description (e.g., "coffee" -> "Food").

Interactive Dashboard:

Visualizes monthly spending by category with a doughnut chart.

Tracks spending against user-defined monthly budgets.

Provides at-a-glance insights like top spending category and largest single purchase.

Forecasts future spending based on historical data.

Budgeting: Set monthly budgets for different spending categories.

History & Reporting: View a complete history of transactions and a monthly spending breakdown bar chart.

Data Export: Export all expense data to a CSV file.

Category Management: Users can add and delete their own custom spending categories.

Tech Stack
Frontend: HTML, Tailwind CSS, Chart.js

Backend: PHP

Database: MySQL

Setup and Installation
Follow these steps to set up the project locally.

1. Prerequisites
A local server environment like XAMPP or WAMP.

MySQL database and a management tool like phpMyAdmin, the MySQL command line, or MySQL Workbench.

Git (for version control).

2. Clone the Repository
git clone [https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git](https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git)
cd YOUR_REPOSITORY_NAME

3. Database Setup
You can set up the database using a graphical tool like phpMyAdmin, MySQL Workbench, or the command line.

Option A: Using phpMyAdmin (Recommended for XAMPP)
Start Services: Open your XAMPP Control Panel and start the "Apache" and "MySQL" modules.

Open phpMyAdmin: Click the "Admin" button next to MySQL to open phpMyAdmin in your browser.

Create Database:

Click on the "Databases" tab at the top.

Under "Create database", enter expense_tracker in the input field.

Choose a collation like utf8mb4_unicode_ci (or leave the default) and click "Create".

Import SQL File:

Click on the newly created expense_tracker database in the left sidebar.

Click on the "Import" tab at the top.

Under "File to import", click "Choose File" and navigate to the project folder to select the setup.sql file.

Scroll to the bottom and click "Go". This will execute the script and create all the necessary tables.

Option B: Using the Command Line
Open Terminal: Open your command prompt or terminal.

Log in to MySQL: Connect to the MySQL server (you may be prompted for your password).

mysql -u root -p

Create the Database: Run the following command inside the MySQL prompt:

CREATE DATABASE expense_tracker;

Select the Database:

USE expense_tracker;

Import the SQL Script: Run the source command, replacing path/to/setup.sql with the actual full path to the setup.sql file on your computer.

source path/to/setup.sql;

Exit MySQL:

exit;

Option C: Using MySQL Workbench
Connect to Database: Open MySQL Workbench and connect to your local database instance (e.g., "Local instance 3306").

Open SQL Script: In the top menu, go to File -> Open SQL Script.... Navigate to your project folder and select the setup.sql file.

Execute Script: The script's content will open in a new query tab. To run the entire script, click the first lightning bolt icon (⚡) in the toolbar (the one without a cursor on it).

Verify: In the "SCHEMAS" panel on the left, click the refresh icon. You should now see the expense_tracker database with all its tables.

4. Configure Database Connection
Open the db_connect.php file in your code editor.

Update the $servername, $username, $password, and $dbname variables with your local database credentials.

<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; // Your database password, often empty by default in XAMPP
$dbname = "expense_tracker";

5. Run the Application
Place the entire project folder inside the htdocs directory of your XAMPP installation.

Open your web browser and navigate to http://localhost/YOUR_PROJECT_FOLDER_NAME/register.html to create an account or login.html to log in.

This project is for demonstration and personal use.
