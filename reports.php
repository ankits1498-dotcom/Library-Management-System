<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$inventory = $pdo->query("
    SELECT COUNT(*) AS total_titles, SUM(quantity) AS total_copies, SUM(available_quantity) AS available_copies
    FROM books
")->fetch();

$memberStats = $pdo->query('SELECT COUNT(*) AS total_members FROM members')->fetch();
$issueStats = $pdo->query("
    SELECT
        COUNT(*) AS total_transactions,
        SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) AS active_loans,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) AS closed_loans
    FROM issued_books
")->fetch();

$overdues = $pdo->query("
    SELECT b.title, m.name AS member_name, ib.due_date
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN members m ON m.id = ib.member_id
    WHERE ib.status = 'issued' AND ib.due_date < CURDATE()
    ORDER BY ib.due_date ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="stats-grid">
    <article class="stat-card neutral-card">
        <p>Catalog Titles</p>
        <h3><?= (int) ($inventory['total_titles'] ?? 0) ?></h3>
        <span><?= (int) ($inventory['total_copies'] ?? 0) ?> total copies</span>
    </article>
    <article class="stat-card neutral-card">
        <p>Available Copies</p>
        <h3><?= (int) ($inventory['available_copies'] ?? 0) ?></h3>
        <span>Ready to issue</span>
    </article>
    <article class="stat-card neutral-card">
        <p>Members</p>
        <h3><?= (int) ($memberStats['total_members'] ?? 0) ?></h3>
        <span>Registered readers</span>
    </article>
    <article class="stat-card neutral-card">
        <p>Transactions</p>
        <h3><?= (int) ($issueStats['total_transactions'] ?? 0) ?></h3>
        <span><?= (int) ($issueStats['active_loans'] ?? 0) ?> active / <?= (int) ($issueStats['closed_loans'] ?? 0) ?> returned</span>
    </article>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Priority attention</p>
            <h3>Overdue books</h3>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdues as $overdue): ?>
                    <tr>
                        <td><?= e($overdue['title']) ?></td>
                        <td><?= e($overdue['member_name']) ?></td>
                        <td><?= e($overdue['due_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$overdues): ?>
                    <tr><td colspan="3">No overdue books right now.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
