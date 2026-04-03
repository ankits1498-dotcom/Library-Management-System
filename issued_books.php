<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'issue') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
        $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));

        $bookStatement = $pdo->prepare('SELECT available_quantity FROM books WHERE id = :id LIMIT 1');
        $bookStatement->execute(['id' => $bookId]);
        $book = $bookStatement->fetch();

        if (!$book || (int) $book['available_quantity'] < 1) {
            redirect('issued_books.php', 'That book is not available for issue.', 'error');
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('
                INSERT INTO issued_books (book_id, member_id, issue_date, due_date, status)
                VALUES (:book_id, :member_id, :issue_date, :due_date, :status)
            ');
            $statement->execute([
                'book_id' => $bookId,
                'member_id' => $memberId,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'issued',
            ]);

            $updateBook = $pdo->prepare('UPDATE books SET available_quantity = available_quantity - 1 WHERE id = :id');
            $updateBook->execute(['id' => $bookId]);
            $pdo->commit();
            redirect('issued_books.php', 'Book issued successfully.');
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            redirect('issued_books.php', 'Could not issue the book.', 'error');
        }
    }

    if ($action === 'return') {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        $statement = $pdo->prepare('SELECT * FROM issued_books WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $issueId]);
        $issue = $statement->fetch();

        if (!$issue || $issue['status'] === 'returned') {
            redirect('issued_books.php', 'That issue record is already closed.', 'error');
        }

        $pdo->beginTransaction();
        try {
            $updateIssue = $pdo->prepare('UPDATE issued_books SET status = :status, return_date = :return_date WHERE id = :id');
            $updateIssue->execute([
                'status' => 'returned',
                'return_date' => date('Y-m-d'),
                'id' => $issueId,
            ]);

            $updateBook = $pdo->prepare('UPDATE books SET available_quantity = available_quantity + 1 WHERE id = :id');
            $updateBook->execute(['id' => (int) $issue['book_id']]);
            $pdo->commit();
            redirect('issued_books.php', 'Book returned successfully.');
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            redirect('issued_books.php', 'Could not return the book.', 'error');
        }
    }
}

$books = $pdo->query('SELECT id, title, available_quantity FROM books WHERE available_quantity > 0 ORDER BY title')->fetchAll();
$members = $pdo->query('SELECT id, name, membership_no FROM members ORDER BY name')->fetchAll();
$issuedBooks = $pdo->query("
    SELECT ib.*, b.title, m.name AS member_name, m.membership_no,
           CASE
               WHEN ib.status = 'returned' THEN 'returned'
               WHEN ib.due_date < CURDATE() THEN 'overdue'
               ELSE 'issued'
           END AS display_status
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN members m ON m.id = ib.member_id
    ORDER BY ib.id DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel-grid">
    <article class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Circulation desk</p>
                <h3>Issue a book</h3>
            </div>
        </div>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="issue">

            <label>
                <span>Select Book</span>
                <select name="book_id" required>
                    <option value="">Choose a book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= (int) $book['id'] ?>">
                            <?= e($book['title']) ?> (<?= (int) $book['available_quantity'] ?> left)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Select Member</span>
                <select name="member_id" required>
                    <option value="">Choose a member</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= (int) $member['id'] ?>">
                            <?= e($member['name']) ?> (<?= e($member['membership_no']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Issue Date</span>
                <input type="date" name="issue_date" value="<?= date('Y-m-d') ?>" required>
            </label>

            <label>
                <span>Due Date</span>
                <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
            </label>

            <button type="submit" class="btn-primary">Issue Book</button>
        </form>
    </article>

    <article class="panel wide">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Circulation log</p>
                <h3>Issued and returned books</h3>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issuedBooks as $issue): ?>
                        <tr>
                            <td><?= e($issue['title']) ?></td>
                            <td><?= e($issue['member_name']) ?> (<?= e($issue['membership_no']) ?>)</td>
                            <td><?= e($issue['issue_date']) ?></td>
                            <td><?= e($issue['due_date']) ?></td>
                            <td><?= e($issue['return_date']) ?></td>
                            <td><span class="<?= status_badge($issue['display_status']) ?>"><?= e(ucfirst($issue['display_status'])) ?></span></td>
                            <td>
                                <?php if ($issue['status'] !== 'returned'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="issue_id" value="<?= (int) $issue['id'] ?>">
                                        <button type="submit" class="btn-secondary small-btn">Mark Returned</button>
                                    </form>
                                <?php else: ?>
                                    Closed
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$issuedBooks): ?>
                        <tr><td colspan="7">No issue records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
