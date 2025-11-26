<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>My Profile</h1>
        <p>View and update your account information.</p>
    </section>

    <section class="form-section">
        <!-- DYNAMIC: başarı / hata -->
        <!--
        <?php if(isset($success)) : ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        -->

        <form method="POST" class="profile-form">
            <h2>Profile Info</h2>

            <label>
                Full Name
                <input type="text" name="full_name" value="Example User">
            </label>

            <label>
                Email
                <input type="email" name="email" value="user@example.com">
            </label>

            <label>
                Username
                <input type="text" name="username" value="exampleuser" readonly>
            </label>

            <h2>Change Password</h2>

            <label>
                Current Password
                <input type="password" name="current_password">
            </label>

            <label>
                New Password
                <input type="password" name="new_password">
            </label>

            <label>
                Confirm New Password
                <input type="password" name="confirm_new_password">
            </label>

            <button type="submit">Save Changes</button>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
