<div class="form-container">
    <h2>Create a New Account</h2>
    <form action="actions/register_action.php" method="POST">
        <!-- PHP LOGIC IN register_action.php:
            1. Validate all inputs.
            2. Check if the email already exists in the `users` table.
            3. Hash the password using password_hash().
            4. Insert the new user into the `users` table.
            5. If user_role is 'counselor', also create a default profile in `counselor_profiles`.
            6. Automatically log them in by setting session variables.
            7. Redirect to the dashboard.
        -->
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="user_role">I am a...</label>
            <select id="user_role" name="user_role" required>
                <option value="patient">Patient</option>
                <option value="counselor">Counselor</option>
            </select>
        </div>
        <button type="submit" class="btn">Register</button>
        <p class="form-switch">Already have an account? <a href="index.php?page=login">Login here</a></p>
    </form>
</div>
