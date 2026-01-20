<div class="form-container">
    <h2>Login to Your Account</h2>
    <form action="actions/login_action.php" method="POST">
        <!-- In a real app, an 'actions/login_action.php' file would handle this form submission -->
        <!-- PHP LOGIC IN login_action.php:
            1. Validate inputs (email format, password not empty).
            2. Check if email exists in the `users` table.
            3. If it exists, verify the password using password_verify().
            4. If password is correct, set session variables:
               $_SESSION['user_id'] = $user['user_id'];
               $_SESSION['user_role'] = $user['user_role'];
               $_SESSION['full_name'] = $user['full_name'];
            5. Redirect to the dashboard: redirect('index.php?page=dashboard');
            6. If login fails, redirect back with an error message.
        -->
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
        <p class="form-switch">Don't have an account? <a href="index.php?page=register">Register here</a></p>
    </form>
</div>
