<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Register</h1>
        <p>Create an account as a buyer or seller.</p>

        <!-- DYNAMIC: hata / başarı -->
        <!--
        <?php if(isset($error)) : ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        -->

        <form method="POST">
            <label>
                Full Name
                <input type="text" name="full_name" required>
            </label>

            <label>
                Email
                <input type="email" name="email" required>
            </label>

            <label>
                Username
                <input type="text" name="username" required>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <label>
                Confirm Password
                <input type="password" name="confirm_password" required>
            </label>

            <label>
                Role
                <select name="role" required>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
            </label>

            <button type="submit">Create Account</button>
        </form>

        <p class="auth-switch">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
