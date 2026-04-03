<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$stats = [
    'books' => (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
    'members' => (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn(),
    'issued' => (int) $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status = 'issued'")->fetchColumn(),
    'overdue' => (int) $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status = 'issued' AND due_date < CURDATE()") ->fetchColumn(),
];

$recentIssues = $pdo->query("
    SELECT ib.id, b.title, m.name AS member_name, ib.issue_date, ib.due_date,
           CASE
               WHEN ib.status = 'returned' THEN 'returned'
               WHEN ib.due_date < CURDATE() THEN 'overdue'
               ELSE 'issued'
           END AS display_status
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN members m ON m.id = ib.member_id
    ORDER BY ib.id DESC
    LIMIT 6
")->fetchAll();

$popularBooks = $pdo->query("
    SELECT b.title, COUNT(ib.id) AS total_issues
    FROM books b
    LEFT JOIN issued_books ib ON ib.book_id = b.id
    GROUP BY b.id
    ORDER BY total_issues DESC, b.title ASC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="stats-grid">
    <article class="stat-card gradient-one">
        <p>Total Books</p>
        <h3><?= $stats['books'] ?></h3>
        <span>Books available in catalog</span>
    </article>
    <article class="stat-card gradient-two">
        <p>Registered Members</p>
        <h3><?= $stats['members'] ?></h3>
        <span>Active member records</span>
    </article>
    <article class="stat-card gradient-three">
        <p>Books Issued</p>
        <h3><?= $stats['issued'] ?></h3>
        <span>Currently checked out</span>
    </article>
    <article class="stat-card gradient-four">
        <p>Overdue Returns</p>
        <h3><?= $stats['overdue'] ?></h3>
        <span>Need attention today</span>
    </article>
</section>

<section class="panel-grid">
    <article class="panel wide">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Recent activity</p>
                <h3>Latest circulation records</h3>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentIssues as $issue): ?>
                        <tr>
                            <td><?= e($issue['title']) ?></td>
                            <td><?= e($issue['member_name']) ?></td>
                            <td><?= e($issue['issue_date']) ?></td>
                            <td><?= e($issue['due_date']) ?></td>
                            <td><span class="<?= status_badge($issue['display_status']) ?>"><?= e(ucfirst($issue['display_status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentIssues): ?>
                        <tr><td colspan="5">No circulation activity yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Top books</p>
                <h3>Most issued titles</h3>
            </div>
        </div>
        <div class="stack-list">
            <?php foreach ($popularBooks as $book): ?>
                <div class="stack-item">
                    <strong><?= e($book['title']) ?></strong>
                    <span><?= (int) $book['total_issues'] ?> issue(s)</span>
                </div>
            <?php endforeach; ?>
            <?php if (!$popularBooks): ?>
                <p>No book data available yet.</p>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
