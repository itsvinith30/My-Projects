# Classroom Attendance Tracker

A comprehensive web application built with PHP and MySQL to manage student attendance, leave requests, and user roles in an educational environment. It provides distinct dashboards and functionalities for Admins, Teachers, Students, and Parents.

## Features

-   **Role-Based Access Control**: Secure login and distinct dashboards for four user roles: Admin, Teacher, Student, and Parent.
-   **Admin Dashboard**: Includes graphical charts for an at-a-glance overview of daily and weekly attendance statistics.
-   **Attendance Management**: Easily take, view, and update daily attendance for classes, with options for 'Present', 'Absent', and 'Late' statuses.
-   **Leave Management System**: Students can submit leave requests, which can then be approved or denied by Teachers and Admins.
-   **Reporting & Exporting**: Generate detailed attendance reports for any class within a specific date range, with an option to export the data to a CSV file.
-   **Comprehensive Management**: Admins can manage teachers, classes, students, and parent accounts from a central management area.
-   **Parental Portal**: Parents can log in to view the attendance history for their linked children.
-   **Email Notifications**: Automated email notifications are sent for student absences and for status updates on leave requests.
-   **System Auditing**: All critical actions are logged for security and accountability, including logins, user creation, and attendance changes.
-   **Dynamic Theming**: A modern user interface with support for both light and dark modes.

## Tech Stack

-   **Backend**: PHP
-   **Database**: MySQL
-   **Frontend**: HTML, CSS, JavaScript
-   **Emailing**: PHPMailer

## Installation and Setup

Follow these steps to set up the project on your local machine.

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/your-username/your-repository-name.git](https://github.com/your-username/your-repository-name.git)
    cd your-repository-name
    ```

2.  **Database Setup**
    -   Create a new database in your MySQL server (e.g., using phpMyAdmin).
    -   Import the `attendance_system.sql` file into the newly created database. This will set up all the necessary tables and seed them with some initial data.

3.  **Configure Credentials**
    -   Rename the file `db_connect.php.example` to `db_connect.php`.
    -   Open `db_connect.php` and fill in your database credentials (host, username, password, database name).
    -   Rename the file `config.php.example` to `config.php`.
    -   Open `config.php` and fill in your SMTP credentials to enable email notifications.

4.  **Run the Application**
    -   Place the entire project folder into the root directory of your local web server (e.g., `htdocs` for XAMPP or `www` for WAMP).
    -   Open your web browser and navigate to `http://localhost/your-project-folder-name`.

## Usage & Default Logins

You can use the following default credentials from the database to test the application:

-   **Admin**
    -   **Email**: `admin@gmail.com`
    -   **Password**: `adminpass`

-   **Teacher**
    -   **Email**: `teacher@example.com`
    -   **Password**: `teacherpass`

-   **Student**
    -   Logins for students are available in the `students` table. 
    -   **Email**: `your_email`
    -   **Password**: (The password you set for this user).

## Project Structure
/
├── PHPMailer/           # PHPMailer library files
├── index.php            # Main dashboard for Admin/Teacher
├── login.php            # Login page
├── db_connect.php       # Database connection
├── header.php           # Shared header and navigation
├── style.css            # All CSS styles for the application
├── manage_*.php         # Pages for admin management tasks
├── take_attendance.php  # Core page for taking attendance
├── view_reports.php     # Page for generating reports
└── attendance_system.sql # The database schema and data