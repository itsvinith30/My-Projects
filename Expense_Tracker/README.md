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

MySQL database.

Git (for version control).

2. Clone the Repository
git clone [https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git](https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git)
cd YOUR_REPOSITORY_NAME

3. Database Setup
Start your Apache and MySQL services in XAMPP.

Open your database management tool (like phpMyAdmin).

Create a new database named expense_tracker.

Import the setup.sql file provided in this repository into the expense_tracker database. This will create all the necessary tables.

4. Configure Database Connection
Open the db_connect.php file.

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