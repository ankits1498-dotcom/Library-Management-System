<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    $payload = [
        'title' => trim($_POST['title'] ?? ''),
        'author' => trim($_POST['author'] ?? ''),
        'isbn' => trim($_POST['isbn'] ?? ''),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'publisher' => trim($_POST['publisher'] ?? ''),
        'published_year' => trim($_POST['published_year'] ?? ''),
        'quantity' => (int) ($_POST['quantity'] ?? 0),
        'available_quantity' => (int) ($_POST['available_quantity'] ?? 0),
        'shelf_location' => trim($_POST['shelf_location'] ?? ''),
    ];

    if ($action === 'create') {
        $statement = $pdo->prepare('
            INSERT INTO books (title, author, isbn, category_id, publisher, published_year, quantity, available_quantity, shelf_location)
            VALUES (:title, :author, :isbn, :category_id, :publisher, :published_year, :quantity, :available_quantity, :shelf_location)
        ');
        $statement->execute($payload);
        redirect('books.php', 'Book added successfully.');
    }

    if ($action === 'update') {
        $payload['id'] = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('
            UPDATE books
            SET title = :title, author = :author, isbn = :isbn, category_id = :category_id, publisher = :publisher,
                published_year = :published_year, quantity = :quantity, available_quantity = :available_quantity, shelf_location = :shelf_location
            WHERE id = :id
        ');
        $statement->execute($payload);
        redirect('books.php', 'Book updated successfully.');
    }
}

if (isset($_GET['delete'])) {
    $statement = $pdo->prepare('DELETE FROM books WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['delete']]);
    redirect('books.php', 'Book deleted.');
}

$editBook = null;
if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM books WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editBook = $statement->fetch();
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$books = $pdo->query("
    SELECT b.*, c.name AS category_name
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    ORDER BY b.id DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel-grid">
    <article class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Catalog management</p>
                <h3><?= $editBook ? 'Edit book' : 'Add new book' ?></h3>
            </div>
        </div>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $editBook ? 'update' : 'create' ?>">
            <?php if ($editBook): ?>
                <input type="hidden" name="id" value="<?= (int) $editBook['id'] ?>">
            <?php endif; ?>

            <label class="full-width">
                <span>Book Title</span>
                <input type="text" name="title" value="<?= e($editBook['title'] ?? '') ?>" required>
            </label>

            <label>
                <span>Author</span>
                <input type="text" name="author" value="<?= e($editBook['author'] ?? '') ?>" required>
            </label>

            <label>
                <span>ISBN</span>
                <input type="text" name="isbn" value="<?= e($editBook['isbn'] ?? '') ?>" required>
            </label>

            <label>
                <span>Category</span>
                <select name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= ((int) ($editBook['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Publisher</span>
                <input type="text" name="publisher" value="<?= e($editBook['publisher'] ?? '') ?>">
            </label>

            <label>
                <span>Published Year</span>
                <input type="number" name="published_year" value="<?= e((string) ($editBook['published_year'] ?? '')) ?>">
            </label>

            <label>
                <span>Total Quantity</span>
                <input type="number" name="quantity" min="1" value="<?= e((string) ($editBook['quantity'] ?? '1')) ?>" required>
            </label>

            <label>
                <span>Available Quantity</span>
                <input type="number" name="available_quantity" min="0" value="<?= e((string) ($editBook['available_quantity'] ?? '1')) ?>" required>
            </label>

            <label class="full-width">
                <span>Shelf Location</span>
                <input type="text" name="shelf_location" value="<?= e($editBook['shelf_location'] ?? '') ?>" placeholder="Example: Row B / Rack 3">
            </label>

            <button type="submit" class="btn-primary"><?= $editBook ? 'Update Book' : 'Save Book' ?></button>
        </form>
    </article>

    <article class="panel wide">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Catalog table</p>
                <h3>All books</h3>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Available</th>
                        <th>Shelf</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= e($book['title']) ?></td>
                            <td><?= e($book['author']) ?></td>
                            <td><?= e($book['category_name']) ?></td>
                            <td><?= e($book['isbn']) ?></td>
                            <td><?= (int) $book['available_quantity'] ?> / <?= (int) $book['quantity'] ?></td>
                            <td><?= e($book['shelf_location']) ?></td>
                            <td class="actions">
                                <a href="books.php?edit=<?= (int) $book['id'] ?>">Edit</a>
                                <a href="books.php?delete=<?= (int) $book['id'] ?>" onclick="return confirm('Delete this book?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$books): ?>
                        <tr><td colspan="7">No books found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
