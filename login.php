<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        redirect('dashboard.php', 'Welcome back, ' . $user['name'] . '!');
    }

    redirect('login.php', 'Invalid email or password.', 'error');
}

$flashMessage = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LibraryFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <section class="auth-layout">
        <div class="auth-hero">
            <div class="hero-card">
                <p class="eyebrow">Modern library workspace</p>
                <h1>Keep your collection, members, and circulation in perfect flow.</h1>
                <p class="hero-copy">A bold, clean dashboard for librarians who want fast operations, easy record keeping, and a polished experience.</p>
                <div class="hero-points">
                    <span>Catalog management</span>
                    <span>Member tracking</span>
                    <span>Issue and return workflow</span>
                </div>
            </div>
        </div>

        <div class="auth-panel">
            <form class="auth-card" method="POST">
                <div>
                    <p class="eyebrow">Welcome back</p>
                    <h2>Login to your account</h2>
                </div>

                <?php if ($flashMessage): ?>
                    <div class="alert <?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="librarian@example.com" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" required>
                </label>

                <button type="submit" class="btn-primary">Login</button>
                <p class="auth-link">No account yet? <a href="register.php">Create one</a></p>
            </form>
        </div>
    </section>
</body>
</html>
