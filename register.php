<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Create Account</h1>
        <p>Join our platform and start bidding or selling.</p>

        <!-- DYNAMIC: hata / başarı -->
        <!--
        <?php if(isset($error)) : ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if(isset($success)) : ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php endif; ?>
        -->

        <form method="POST" class="auth-form">

            <label>
                Full Name
                <input type="text" name="full_name" placeholder="John Doe" required>
            </label>

            <label>
                Email
                <input type="email" name="email" placeholder="user@example.com" required>
            </label>

            <label>
                Username
                <input type="text" name="username" placeholder="yourusername" required>
            </label>

            <label>
                Password
                <input type="password" name="password" placeholder="••••••••" required>
            </label>

            <label>
                Confirm Password
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </label>

            <label>
                Role
                <select name="role" required>
                    <option value="" disabled selected>Select role</option>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
            </label>

            <button type="submit" class="btn-primary">Create Account</button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php" class="auth-link-strong">Login</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
