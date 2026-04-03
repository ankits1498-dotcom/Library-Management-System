<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $statement = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
        $statement->execute([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ]);
        redirect('categories.php', 'Category added successfully.');
    }

    if ($action === 'update') {
        $statement = $pdo->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
        $statement->execute([
            'id' => (int) ($_POST['id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ]);
        redirect('categories.php', 'Category updated successfully.');
    }
}

if (isset($_GET['delete'])) {
    $statement = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['delete']]);
    redirect('categories.php', 'Category deleted.');
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editCategory = $statement->fetch();
}

$categories = $pdo->query('SELECT c.*, COUNT(b.id) AS total_books FROM categories c LEFT JOIN books b ON b.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel-grid">
    <article class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Collection structure</p>
                <h3><?= $editCategory ? 'Edit category' : 'Add category' ?></h3>
            </div>
        </div>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>">
            <?php endif; ?>

            <label>
                <span>Category Name</span>
                <input type="text" name="name" value="<?= e($editCategory['name'] ?? '') ?>" required>
            </label>

            <label class="full-width">
                <span>Description</span>
                <textarea name="description" rows="4" placeholder="Short note about this category"><?= e($editCategory['description'] ?? '') ?></textarea>
            </label>

            <button type="submit" class="btn-primary"><?= $editCategory ? 'Update Category' : 'Save Category' ?></button>
        </form>
    </article>

    <article class="panel wide">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Browse categories</p>
                <h3>Existing groups</h3>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Total Books</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= e($category['name']) ?></td>
                            <td><?= e($category['description']) ?></td>
                            <td><?= (int) $category['total_books'] ?></td>
                            <td class="actions">
                                <a href="categories.php?edit=<?= (int) $category['id'] ?>">Edit</a>
                                <a href="categories.php?delete=<?= (int) $category['id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$categories): ?>
                        <tr><td colspan="4">No categories found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
