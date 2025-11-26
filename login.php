<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page auth-page">
    <section class="auth-card">
        <h1>Login</h1>
        <p>Sign in to participate in auctions.</p>

        <!-- DYNAMIC: hata mesajı -->
        <!--
        <?php if(isset($error)) : ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        -->

        <form method="POST">
            <label>
                Username or Email
                <input type="text" name="username" required>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <button type="submit">Login</button>
        </form>

        <p class="auth-switch">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
