<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Login</h1>
        <p>Sign in to participate in auctions.</p>

        <!-- DYNAMIC: hata mesajı -->
        <!--
        <?php if (isset($error)) : ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        -->

        <form method="POST" class="auth-form" autocomplete="on">
            <label>
                Username or Email
                <input type="text" name="username" required autocomplete="username">
            </label>

            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <div class="auth-extra">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me">
                    <span>Remember me</span>
                </label>

                <!-- İleride gerçek sayfa eklersen href'i güncelleyebilirsin -->
                <a href="forgot_password.php" class="auth-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php" class="auth-link-strong">Register</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
