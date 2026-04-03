<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    $payload = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'membership_no' => trim($_POST['membership_no'] ?? ''),
    ];

    if ($action === 'create') {
        $statement = $pdo->prepare('INSERT INTO members (name, email, phone, address, membership_no) VALUES (:name, :email, :phone, :address, :membership_no)');
        $statement->execute($payload);
        redirect('members.php', 'Member added successfully.');
    }

    if ($action === 'update') {
        $payload['id'] = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('UPDATE members SET name = :name, email = :email, phone = :phone, address = :address, membership_no = :membership_no WHERE id = :id');
        $statement->execute($payload);
        redirect('members.php', 'Member updated successfully.');
    }
}

if (isset($_GET['delete'])) {
    $statement = $pdo->prepare('DELETE FROM members WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['delete']]);
    redirect('members.php', 'Member deleted.');
}

$editMember = null;
if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM members WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editMember = $statement->fetch();
}

$members = $pdo->query('SELECT * FROM members ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel-grid">
    <article class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Member directory</p>
                <h3><?= $editMember ? 'Edit member' : 'Add member' ?></h3>
            </div>
        </div>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editMember ? 'update' : 'create' ?>">
            <?php if ($editMember): ?>
                <input type="hidden" name="id" value="<?= (int) $editMember['id'] ?>">
            <?php endif; ?>

            <label>
                <span>Member Name</span>
                <input type="text" name="name" value="<?= e($editMember['name'] ?? '') ?>" required>
            </label>

            <label>
                <span>Membership No.</span>
                <input type="text" name="membership_no" value="<?= e($editMember['membership_no'] ?? '') ?>" required>
            </label>

            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($editMember['email'] ?? '') ?>">
            </label>

            <label>
                <span>Phone</span>
                <input type="text" name="phone" value="<?= e($editMember['phone'] ?? '') ?>">
            </label>

            <label class="full-width">
                <span>Address</span>
                <textarea name="address" rows="4"><?= e($editMember['address'] ?? '') ?></textarea>
            </label>

            <button type="submit" class="btn-primary"><?= $editMember ? 'Update Member' : 'Save Member' ?></button>
        </form>
    </article>

    <article class="panel wide">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Library users</p>
                <h3>All members</h3>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Membership No.</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?= e($member['name']) ?></td>
                            <td><?= e($member['membership_no']) ?></td>
                            <td><?= e($member['email']) ?></td>
                            <td><?= e($member['phone']) ?></td>
                            <td class="actions">
                                <a href="members.php?edit=<?= (int) $member['id'] ?>">Edit</a>
                                <a href="members.php?delete=<?= (int) $member['id'] ?>" onclick="return confirm('Delete this member?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$members): ?>
                        <tr><td colspan="5">No members found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
