<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $statement = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
    $statement->execute([
        'role' => $_POST['role'] ?? 'librarian',
        'id' => (int) ($_POST['id'] ?? 0),
    ]);
    redirect('users.php', 'User role updated successfully.');
}

$users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Team access</p>
            <h3>User management</h3>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $userRow): ?>
                    <tr>
                        <td><?= e($userRow['name']) ?></td>
                        <td><?= e($userRow['email']) ?></td>
                        <td><?= e(ucfirst($userRow['role'])) ?></td>
                        <td><?= e($userRow['created_at']) ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $userRow['id'] ?>">
                                <select name="role">
                                    <option value="admin" <?= $userRow['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="librarian" <?= $userRow['role'] === 'librarian' ? 'selected' : '' ?>>Librarian</option>
                                </select>
                                <button type="submit" class="btn-secondary small-btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
