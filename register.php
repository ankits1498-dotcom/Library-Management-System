<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        redirect('register.php', 'Passwords do not match.', 'error');
    }

    $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existing->execute(['email' => $email]);

    if ($existing->fetch()) {
        redirect('register.php', 'That email is already registered.', 'error');
    }

    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $role = $totalUsers === 0 ? 'admin' : 'librarian';

    $statement = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    redirect('login.php', 'Registration successful. You can now log in.');
}

$flashMessage = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LibraryFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <section class="auth-layout">
        <div class="auth-hero register-hero">
            <div class="hero-card">
                <p class="eyebrow">Set up your library team</p>
                <h1>Create your workspace and start managing books with style.</h1>
                <p class="hero-copy">The first registered account becomes the administrator automatically, so you can begin configuring the system right away.</p>
            </div>
        </div>

        <div class="auth-panel">
            <form class="auth-card" method="POST">
                <div>
                    <p class="eyebrow">Create account</p>
                    <h2>Register a new user</h2>
                </div>

                <?php if ($flashMessage): ?>
                    <div class="alert <?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

                <label>
                    <span>Full Name</span>
                    <input type="text" name="name" placeholder="Your full name" required>
                </label>

                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="admin@example.com" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Create a password" required minlength="6">
                </label>

                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat your password" required minlength="6">
                </label>

                <button type="submit" class="btn-primary">Register</button>
                <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </section>
</body>
</html>
