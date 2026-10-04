<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

function log_action(string $action, ?string $type = null, ?int $id = null, ?string $details = null): void
{
    $statement = db()->prepare('INSERT INTO audit_logs(user_id, action, entity_type, entity_id, details) VALUES(?,?,?,?,?)');
    $statement->execute([user()['id'] ?? null, $action, $type, $id, $details]);
}

function page(string $title, string $content, bool $auth = true): void
{
    global $page;

    if ($auth) {
        require_login();
    }

    $user = user();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · LMS</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <?php if ($auth): ?>
            <div class="app">
                <aside class="sidebar">
                    <div class="brand">
                        <div class="brand-mark">LMS</div>
                        <div>
                            <strong>Marine Biology</strong>
                            <small>Laboratory Management</small>
                        </div>
                    </div>

                    <nav class="nav">
                        <div class="nav-section">Main</div>
                        <a class="<?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">▦ Dashboard</a>

                        <div class="nav-section">Transactions</div>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a class="<?= $page === 'reservations' ? 'active' : '' ?>" href="index.php?page=reservations">◷ Reservations</a>
                            <a class="<?= $page === 'borrowed' ? 'active' : '' ?>" href="index.php?page=borrowed">↗ Borrowed Items</a>
                            <a class="<?= $page === 'returned' ? 'active' : '' ?>" href="index.php?page=returned">↩ Returned Items</a>
                            <a class="<?= $page === 'borrowers' ? 'active' : '' ?>" href="index.php?page=borrowers">♙ Borrower</a>
                        <?php else: ?>
                            <a class="<?= $page === 'new' ? 'active' : '' ?>" href="index.php?page=new">＋ New</a>
                            <a class="<?= $page === 'my_reservations' ? 'active' : '' ?>" href="index.php?page=my_reservations">◷ Reservation</a>
                            <a class="<?= $page === 'my_borrowed' ? 'active' : '' ?>" href="index.php?page=my_borrowed">↗ Borrowed Items</a>
                            <a class="<?= $page === 'my_returned' ? 'active' : '' ?>" href="index.php?page=my_returned">↩ Returned Items</a>
                        <?php endif; ?>

                        <div class="nav-section">Records</div>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a class="<?= $page === 'inventory' ? 'active' : '' ?>" href="index.php?page=inventory">▤ Inventory</a>
                            <a class="<?= $page === 'users' ? 'active' : '' ?>" href="index.php?page=users">♟ Users</a>
                        <?php else: ?>
                            <a class="<?= $page === 'items' ? 'active' : '' ?>" href="index.php?page=items">▤ Items</a>
                        <?php endif; ?>
                        <a class="<?= $page === 'history' ? 'active' : '' ?>" href="index.php?page=history">◫ History</a>

                        <div class="nav-section">Account</div>
                        <a href="index.php?page=password">⚿ Change Password</a>
                    </nav>
                </aside>

                <main class="main">
                    <header class="topbar">
                        <div class="mobile-brand">LMS · Marine Biology</div>
                        <div class="muted">Welcome, <strong><?= e($user['name']) ?></strong></div>
                        <a class="logout" href="index.php?page=logout">Log out</a>
                    </header>
        <?php endif; ?>

                    <section class="content">
                        <?php foreach (flashes() as [$type, $message]): ?>
                            <div class="alert <?= e($type) ?>"><?= e($message) ?></div>
                        <?php endforeach; ?>
                        <?= $content ?>
                    </section>

        <?php if ($auth): ?>
                    <nav class="mobile-nav">
                        <a href="index.php?page=dashboard"><span>▦</span>Home</a>
                        <a href="index.php?page=<?= $user['role'] === 'admin' ? 'inventory' : 'items' ?>"><span>▤</span>Items</a>
                        <a href="index.php?page=<?= $user['role'] === 'admin' ? 'reservations' : 'my_reservations' ?>"><span>◷</span>Loans</a>
                        <a href="index.php?page=history"><span>◫</span>History</a>
                    </nav>
                </main>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

$page = $_GET['page'] ?? (user() ? 'dashboard' : 'login');

if ($page === 'logout') {
    logout_user();
    redirect('index.php?page=login');
}

if ($page === 'login') {
    if (user()) {
        redirect('index.php?page=dashboard');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $statement = db()->prepare('SELECT * FROM users WHERE email=?');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            session_regenerate_id(true);
            redirect('index.php?page=dashboard');
        }

        flash('error', 'Invalid email or password.');
    }

    ob_start();
    ?>
    <div class="login-page">
        <div class="login-box">
            <div class="login-brand">
                <div class="brand-mark">LMS</div>
                <div>
                    <h1>Laboratory Management System</h1>
                    <p>Marine Biology Department</p>
                </div>
            </div>

            <?php foreach (flashes() as [$type, $message]): ?>
                <div class="alert <?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>

            <form class="form" method="post">
                <?= csrf_field() ?>
                <div>
                    <label>Email</label>
                    <input name="email" type="email" required>
                </div>
                <div>
                    <label>Password</label>
                    <input name="password" type="password" required>
                </div>
                <button class="btn" type="submit">Sign in</button>
            </form>

            <p class="muted" style="margin-top:18px;text-align:center">
                New borrower?
                <a href="index.php?page=register" style="color:var(--navy);font-weight:700">Create an account</a>
            </p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    page('Login', $content, false);
    exit;
}

if ($page === 'register') {
    if (user()) {
        redirect('index.php?page=dashboard');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
        } else {
            try {
                $statement = db()->prepare('INSERT INTO users(name, id_number, email, password_hash) VALUES(?,?,?,?)');
                $statement->execute([$name, $idNumber, $email, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', 'Account created. You can now sign in.');
                redirect('index.php?page=login');
            } catch (PDOException $e) {
                flash('error', 'Email or ID number is already registered.');
            }
        }
    }

    ob_start();
    ?>
    <div class="login-page">
        <div class="login-box">
            <div class="login-brand">
                <div class="brand-mark">LMS</div>
                <div>
                    <h1>Create Account</h1>
                    <p>Marine Biology Department</p>
                </div>
            </div>

            <?php foreach (flashes() as [$type, $message]): ?>
                <div class="alert <?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>

            <form class="form" method="post">
                <?= csrf_field() ?>
                <div>
                    <label>Full Name</label>
                    <input name="name" required>
                </div>
                <div>
                    <label>ID Number</label>
                    <input name="id_number" required>
                </div>
                <div>
                    <label>Email</label>
                    <input name="email" type="email" required>
                </div>
                <div>
                    <label>Password</label>
                    <input name="password" type="password" minlength="8" required>
                </div>
                <button class="btn" type="submit">Create Account</button>
            </form>

            <p class="muted" style="margin-top:18px;text-align:center">
                <a href="index.php?page=login" style="color:var(--navy);font-weight:700">Back to sign in</a>
            </p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    page('Register', $content, false);
    exit;
}

require_login();

if ($page === 'password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $oldPassword = $_POST['old'] ?? '';
        $newPassword = $_POST['new'] ?? '';
        $statement = db()->prepare('SELECT password_hash FROM users WHERE id=?');
        $statement->execute([user()['id']]);
        $user = $statement->fetch();

        if (!password_verify($oldPassword, $user['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            $statement = db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), user()['id']]);
            log_action('Changed password', 'user', user()['id']);
            flash('success', 'Password changed successfully.');
            redirect('index.php?page=password');
        }
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Change Password</h1>
            <p class="muted">Update your LMS account password.</p>
        </div>
    </div>

    <div class="card form-card">
        <form class="form" method="post">
            <?= csrf_field() ?>
            <div>
                <label>Current Password</label>
                <input type="password" name="old" required>
            </div>
            <div>
                <label>New Password</label>
                <input type="password" name="new" minlength="8" required>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Change Password</button>
            </div>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    page('Change Password', $content);
    exit;
}

if ($page === 'dashboard') {
    $pdo = db();
    $stats = [];
    $stats['items'] = $pdo->query('SELECT COUNT(*) FROM equipment WHERE is_active=1')->fetchColumn();
    $stats['borrowed'] = $pdo->query("SELECT COALESCE(SUM(li.quantity-li.returned_quantity),0) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.status='borrowed'")->fetchColumn();
    $stats['pending'] = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

    if (user()['role'] === 'admin') {
        $recent = $pdo->query("SELECT l.id, u.name, l.room, l.status, l.created_at FROM loans l JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
    } else {
        $statement = $pdo->prepare("SELECT l.id, l.room, l.status, l.created_at FROM loans l WHERE l.user_id=? ORDER BY l.created_at DESC LIMIT 8");
        $statement->execute([user()['id']]);
        $recent = $statement->fetchAll();
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Laboratory equipment and transaction overview.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card stat">
            <div class="num"><?= e((string) $stats['items']) ?></div>
            <div class="label">Active inventory records</div>
        </div>
        <div class="card stat">
            <div class="num"><?= e((string) $stats['borrowed']) ?></div>
            <div class="label">Items currently borrowed</div>
        </div>
        <div class="card stat">
            <div class="num"><?= e((string) $stats['pending']) ?></div>
            <div class="label">Pending loan requests</div>
        </div>
        <div class="card stat">
            <div class="num"><?= e((string) $stats['users']) ?></div>
            <div class="label">Registered borrowers</div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <h3>Recent Transactions</h3>
        <div class="table-wrap" style="box-shadow:none;margin-top:12px">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= user()['role'] === 'admin' ? 'Borrower' : 'Room' ?></th>
                        <th><?= user()['role'] === 'admin' ? 'Room' : 'Status' ?></th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $record): ?>
                        <tr>
                            <td>#<?= e((string) $record['id']) ?></td>
                            <td><?= e($record[user()['role'] === 'admin' ? 'name' : 'room']) ?></td>
                            <td><?= e($record[user()['role'] === 'admin' ? 'room' : 'status']) ?></td>
                            <td>
                                <span class="badge <?= in_array($record['status'], ['approved', 'borrowed'], true) ? 'success' : ($record['status'] === 'pending' ? 'warning' : 'info') ?>">
                                    <?= e(ucfirst($record['status'])) ?>
                                </span>
                            </td>
                            <td><?= e($record['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$recent): ?>
                        <tr>
                            <td colspan="5" class="empty">No transactions yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    page('Dashboard', $content);
    exit;
}

if ($page === 'inventory') {
    require_admin();
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $variant = trim($_POST['variant'] ?? '');
            $quantity = $_POST['total_qty'] === '' ? null : max(0, (int) $_POST['total_qty']);
            $damage = max(0, (int) ($_POST['damaged_qty'] ?? 0));
            $note = trim($_POST['quantity_note'] ?? '');

            if ($id) {
                $statement = $pdo->prepare('UPDATE equipment SET name=?, category=?, variant=?, total_qty=?, damaged_qty=?, quantity_note=? WHERE id=?');
                $statement->execute([$name, $category, $variant ?: null, $quantity, $damage, $note ?: null, $id]);
                log_action('Updated equipment', 'equipment', $id, $name);
            } else {
                $statement = $pdo->prepare('INSERT INTO equipment(name, category, variant, total_qty, damaged_qty, quantity_note) VALUES(?,?,?,?,?,?)');
                $statement->execute([$name, $category, $variant ?: null, $quantity, $damage, $note ?: null]);
                log_action('Added equipment', 'equipment', (int) $pdo->lastInsertId(), $name);
            }

            flash('success', 'Inventory saved.');
            redirect('index.php?page=inventory');
        }

        if ($action === 'delete') {
            $id = (int) $_POST['id'];
            $statement = $pdo->prepare('SELECT COUNT(*) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE li.equipment_id=? AND l.status IN (\'pending\',\'approved\',\'borrowed\')');
            $statement->execute([$id]);

            if ((int) $statement->fetchColumn() > 0) {
                flash('error', 'Cannot delete an item involved in an active transaction.');
            } else {
                $statement = $pdo->prepare('UPDATE equipment SET is_active=0 WHERE id=?');
                $statement->execute([$id]);
                log_action('Deleted equipment', 'equipment', $id);
                flash('success', 'Equipment removed from active inventory.');
            }

            redirect('index.php?page=inventory');
        }
    }

    $query = trim($_GET['q'] ?? '');
    $sql = "SELECT e.*, COALESCE((SELECT SUM(li.quantity-li.returned_quantity) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE li.equipment_id=e.id AND l.status='borrowed'),0) borrowed FROM equipment e WHERE e.is_active=1";
    $args = [];

    if ($query !== '') {
        $sql .= ' AND (e.name LIKE ? OR e.category LIKE ? OR e.variant LIKE ?)';
        $like = '%' . $query . '%';
        $args = [$like, $like, $like];
    }

    $sql .= ' ORDER BY e.category, e.name, e.variant';
    $statement = $pdo->prepare($sql);
    $statement->execute($args);
    $rows = $statement->fetchAll();

    $edit = null;
    if (isset($_GET['edit'])) {
        $statement = $pdo->prepare('SELECT * FROM equipment WHERE id=?');
        $statement->execute([(int) $_GET['edit']]);
        $edit = $statement->fetch();
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Inventory</h1>
            <p class="muted">Only administrators can add, edit, or remove equipment.</p>
        </div>
        <a class="btn yellow" href="index.php?page=inventory&add=1">＋ Add Equipment</a>
    </div>

    <?php if (isset($_GET['add']) || $edit): ?>
        <div class="card form-card" style="margin-bottom:18px">
            <h3><?= $edit ? 'Edit Equipment' : 'Add Equipment' ?></h3>
            <form class="form" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e((string) ($edit['id'] ?? 0)) ?>">
                <div class="form-grid">
                    <div class="field">
                        <label>Equipment Name</label>
                        <input name="name" required value="<?= e($edit['name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Category</label>
                        <input name="category" required value="<?= e($edit['category'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Size / Variant</label>
                        <input name="variant" value="<?= e($edit['variant'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Total Quantity</label>
                        <input type="number" min="0" name="total_qty" value="<?= e(isset($edit['total_qty']) && $edit['total_qty'] !== null ? (string) $edit['total_qty'] : '') ?>">
                        <span class="muted">Leave blank when source quantity is unknown.</span>
                    </div>
                    <div class="field">
                        <label>Damage Quantity</label>
                        <input type="number" min="0" name="damaged_qty" value="<?= e((string) ($edit['damaged_qty'] ?? 0)) ?>">
                    </div>
                    <div class="field">
                        <label>Quantity Note</label>
                        <input name="quantity_note" value="<?= e($edit['quantity_note'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn secondary" href="index.php?page=inventory">Cancel</a>
                    <button class="btn" type="submit">Save Equipment</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <form method="get">
            <input type="hidden" name="page" value="inventory">
            <input name="q" value="<?= e($query) ?>" placeholder="Search equipment, category, or variant...">
            <button class="btn" type="submit">Search</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Size/Variant</th>
                    <th>Borrowed</th>
                    <th>Damage</th>
                    <th>Available</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $available = $row['total_qty'] === null ? null : max(0, (int) $row['total_qty'] - (int) $row['borrowed'] - (int) $row['damaged_qty']); ?>
                    <tr>
                        <td>
                            <strong><?= e($row['name']) ?></strong>
                            <?php if ($row['quantity_note']): ?>
                                <div class="muted"><?= e($row['quantity_note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['category']) ?></td>
                        <td><?= e($row['variant'] ?? '—') ?></td>
                        <td><?= e((string) $row['borrowed']) ?></td>
                        <td><?= e((string) $row['damaged_qty']) ?></td>
                        <td><?= $available === null ? '—' : e((string) $available) ?></td>
                        <td><?= $row['total_qty'] === null ? '—' : e((string) $row['total_qty']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="index.php?page=inventory&edit=<?= $row['id'] ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Remove this equipment from active inventory?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="empty">No equipment found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $content = ob_get_clean();
    page('Inventory', $content);
    exit;
}

if (in_array($page, ['items', 'new'], true)) {
    $pdo = db();
    $query = trim($_GET['q'] ?? '');
    $sql = "SELECT e.*, COALESCE((SELECT SUM(li.quantity-li.returned_quantity) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE li.equipment_id=e.id AND l.status='borrowed'),0) borrowed FROM equipment e WHERE e.is_active=1";
    $args = [];

    if ($query !== '') {
        $sql .= ' AND (e.name LIKE ? OR e.category LIKE ? OR e.variant LIKE ?)';
        $like = '%' . $query . '%';
        $args = [$like, $like, $like];
    }

    $sql .= ' ORDER BY e.name, e.variant';
    $statement = $pdo->prepare($sql);
    $statement->execute($args);
    $rows = $statement->fetchAll();

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Items</h1>
            <p class="muted">Browse equipment available for laboratory use.</p>
        </div>
    </div>

    <div class="toolbar">
        <form method="get">
            <input type="hidden" name="page" value="<?= $page ?>">
            <input name="q" value="<?= e($query) ?>" placeholder="Search equipment...">
            <button class="btn" type="submit">Search</button>
        </form>
    </div>

    <div class="item-grid">
        <?php foreach ($rows as $row): ?>
            <?php $available = $row['total_qty'] === null ? null : max(0, (int) $row['total_qty'] - (int) $row['borrowed'] - (int) $row['damaged_qty']); ?>
            <div class="item-card">
                <img src="uploads/<?= e($row['image']) ?>" alt="<?= e($row['name']) ?>">
                <div class="item-body">
                    <h3><?= e($row['name']) ?></h3>
                    <p><?= e($row['category']) ?> · <?= e($row['variant'] ?? 'No variant') ?></p>
                    <div class="qty-row">
                        <span class="qty-pill">Borrowed: <?= e((string) $row['borrowed']) ?></span>
                        <span class="qty-pill">Damage: <?= e((string) $row['damaged_qty']) ?></span>
                        <span class="qty-pill">Available: <?= $available === null ? '—' : e((string) $available) ?></span>
                    </div>

                    <?php if ($page === 'new' && $available !== 0): ?>
                        <div style="margin-top:12px">
                            <a class="btn" href="index.php?page=borrow_request&equipment=<?= $row['id'] ?>">Request</a>
                            <a class="btn secondary" href="index.php?page=reservation&equipment=<?= $row['id'] ?>">Reserve</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
            <div class="empty">No equipment found.</div>
        <?php endif; ?>
    </div>
    <?php
    $content = ob_get_clean();
    page('Items', $content);
    exit;
}

if ($page === 'borrow_request') {
    $pdo = db();
    $equipmentId = (int) ($_GET['equipment'] ?? 0);
    $statement = $pdo->prepare('SELECT * FROM equipment WHERE id=? AND is_active=1');
    $statement->execute([$equipmentId]);
    $equipment = $statement->fetch();

    if (!$equipment) {
        flash('error', 'Equipment not found.');
        redirect('index.php?page=new');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $quantity = max(1, (int) $_POST['quantity']);
        $room = trim($_POST['room']);
        $purpose = trim($_POST['purpose']);

        $statement = $pdo->prepare("SELECT COALESCE(SUM(li.quantity-li.returned_quantity),0) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE li.equipment_id=? AND l.status='borrowed'");
        $statement->execute([$equipmentId]);
        $borrowed = (int) $statement->fetchColumn();

        $available = $equipment['total_qty'] === null ? null : (int) $equipment['total_qty'] - $borrowed - (int) $equipment['damaged_qty'];

        if ($available !== null && $quantity > $available) {
            flash('error', 'Not enough equipment available for the requested quantity.');
        } else {
            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('INSERT INTO loans(user_id, room, purpose, status) VALUES(?,?,?,\'pending\')');
                $statement->execute([user()['id'], $room, $purpose ?: null]);
                $loanId = (int) $pdo->lastInsertId();

                $statement = $pdo->prepare('INSERT INTO loan_items(loan_id, equipment_id, quantity) VALUES(?,?,?)');
                $statement->execute([$loanId, $equipmentId, $quantity]);

                log_action('Created loan request', 'loan', $loanId, $equipment['name']);
                $pdo->commit();
                flash('success', 'Borrow request submitted.');
                redirect('index.php?page=my_borrowed');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>New Borrow Request</h1>
            <p class="muted">Request equipment from the Marine Biology laboratory.</p>
        </div>
    </div>

    <div class="card form-card">
        <h3><?= e($equipment['name']) ?> <span class="muted">· <?= e($equipment['variant'] ?? '') ?></span></h3>
        <form class="form" method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" value="1" required>
                </div>
                <div class="field">
                    <label>Room</label>
                    <input name="room" required placeholder="Laboratory room / activity room">
                </div>
                <div class="field" style="flex-basis:100%">
                    <label>Purpose / Activity</label>
                    <textarea name="purpose" rows="3"></textarea>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn secondary" href="index.php?page=new">Cancel</a>
                <button class="btn" type="submit">Submit Request</button>
            </div>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    page('Borrow Request', $content);
    exit;
}

if ($page === 'reservation') {
    $pdo = db();
    $equipmentId = (int) ($_GET['equipment'] ?? 0);
    $statement = $pdo->prepare('SELECT * FROM equipment WHERE id=? AND is_active=1');
    $statement->execute([$equipmentId]);
    $equipment = $statement->fetch();

    if (!$equipment) {
        flash('error', 'Equipment not found.');
        redirect('index.php?page=new');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $quantity = max(1, (int) $_POST['quantity']);
        $room = trim($_POST['room']);
        $purpose = trim($_POST['purpose']);
        $from = date('Y-m-d H:i:s', strtotime($_POST['reserved_from']));
        $until = date('Y-m-d H:i:s', strtotime($_POST['reserved_until']));

        if (strtotime($until) <= strtotime($from)) {
            flash('error', 'End time must be after start time.');
        } else {
            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('INSERT INTO reservations(user_id, room, purpose, reserved_from, reserved_until) VALUES(?,?,?,?,?)');
                $statement->execute([user()['id'], $room, $purpose ?: null, $from, $until]);
                $reservationId = (int) $pdo->lastInsertId();

                $statement = $pdo->prepare('INSERT INTO reservation_items(reservation_id, equipment_id, quantity) VALUES(?,?,?)');
                $statement->execute([$reservationId, $equipmentId, $quantity]);

                log_action('Created reservation', 'reservation', $reservationId, $equipment['name']);
                $pdo->commit();
                flash('success', 'Reservation submitted.');
                redirect('index.php?page=my_reservations');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Reserve Equipment</h1>
            <p class="muted"><?= e($equipment['name']) ?> · <?= e($equipment['variant'] ?? '') ?></p>
        </div>
    </div>

    <div class="card form-card">
        <form class="form" method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" value="1" required>
                </div>
                <div class="field">
                    <label>Room</label>
                    <input name="room" required>
                </div>
                <div class="field">
                    <label>Start</label>
                    <input type="datetime-local" name="reserved_from" required>
                </div>
                <div class="field">
                    <label>End</label>
                    <input type="datetime-local" name="reserved_until" required>
                </div>
                <div class="field" style="flex-basis:100%">
                    <label>Purpose</label>
                    <textarea name="purpose" rows="3"></textarea>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn secondary" href="index.php?page=new">Cancel</a>
                <button class="btn" type="submit">Submit Reservation</button>
            </div>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    page('Reservation', $content);
    exit;
}

if ($page === 'reservations' || $page === 'my_reservations') {
    if ($page === 'reservations') {
        require_admin();
    }

    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $reservationId = (int) $_POST['id'];
        $status = $_POST['status'];

        if ($page === 'reservations' && in_array($status, ['approved', 'rejected', 'cancelled', 'completed'], true)) {
            $statement = $pdo->prepare('UPDATE reservations SET status=? WHERE id=?');
            $statement->execute([$status, $reservationId]);
            log_action('Updated reservation', 'reservation', $reservationId, $status);
            flash('success', 'Reservation updated.');
            redirect('index.php?page=reservations');
        }
    }

    if ($page === 'reservations') {
        $rows = $pdo->query("SELECT r.*, u.name borrower, GROUP_CONCAT(CONCAT(e.name,' × ',ri.quantity) SEPARATOR ', ') items FROM reservations r JOIN users u ON u.id=r.user_id JOIN reservation_items ri ON ri.reservation_id=r.id JOIN equipment e ON e.id=ri.equipment_id GROUP BY r.id ORDER BY r.reserved_from DESC")->fetchAll();
    } else {
        $statement = $pdo->prepare("SELECT r.*, GROUP_CONCAT(CONCAT(e.name,' × ',ri.quantity) SEPARATOR ', ') items FROM reservations r JOIN reservation_items ri ON ri.reservation_id=r.id JOIN equipment e ON e.id=ri.equipment_id WHERE r.user_id=? GROUP BY r.id ORDER BY r.reserved_from DESC");
        $statement->execute([user()['id']]);
        $rows = $statement->fetchAll();
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1><?= $page === 'reservations' ? 'Reservations' : 'My Reservations' ?></h1>
            <p class="muted">Track scheduled equipment use.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($page === 'reservations'): ?>
                        <th>Borrower</th>
                    <?php endif; ?>
                    <th>Items</th>
                    <th>Room</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <?php if ($page === 'reservations'): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $reservation): ?>
                    <tr>
                        <?php if ($page === 'reservations'): ?>
                            <td><?= e($reservation['borrower']) ?></td>
                        <?php endif; ?>
                        <td><?= e($reservation['items']) ?></td>
                        <td><?= e($reservation['room']) ?></td>
                        <td><?= e($reservation['reserved_from']) ?> → <?= e($reservation['reserved_until']) ?></td>
                        <td>
                            <span class="badge <?= $reservation['status'] === 'approved' ? 'success' : ($reservation['status'] === 'pending' ? 'warning' : 'info') ?>">
                                <?= e(ucfirst($reservation['status'])) ?>
                            </span>
                        </td>
                        <?php if ($page === 'reservations'): ?>
                            <td>
                                <?php if ($reservation['status'] === 'pending'): ?>
                                    <div class="actions">
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $reservation['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn success">Approve</button>
                                        </form>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $reservation['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn danger">Reject</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="empty">No reservations.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $content = ob_get_clean();
    page('Reservations', $content);
    exit;
}

if (in_array($page, ['borrowed', 'returned', 'my_borrowed', 'my_returned'], true)) {
    $admin = in_array($page, ['borrowed', 'returned'], true);

    if ($admin) {
        require_admin();
    }

    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $action = $_POST['action'] ?? '';
        $loanId = (int) ($_POST['loan_id'] ?? 0);

        if ($admin && $action === 'approve') {
            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('SELECT * FROM loans WHERE id=? FOR UPDATE');
                $statement->execute([$loanId]);
                $loan = $statement->fetch();

                if (!$loan || $loan['status'] !== 'pending') {
                    throw new RuntimeException('Invalid loan.');
                }

                $statement = $pdo->prepare('SELECT li.*, e.total_qty, e.damaged_qty, e.name FROM loan_items li JOIN equipment e ON e.id=li.equipment_id WHERE li.loan_id=? FOR UPDATE');
                $statement->execute([$loanId]);
                $items = $statement->fetchAll();

                foreach ($items as $item) {
                    $statement = $pdo->prepare("SELECT COALESCE(SUM(li2.quantity-li2.returned_quantity),0) FROM loan_items li2 JOIN loans l2 ON l2.id=li2.loan_id WHERE li2.equipment_id=? AND l2.status='borrowed' FOR UPDATE");
                    $statement->execute([$item['equipment_id']]);
                    $borrowed = (int) $statement->fetchColumn();

                    if ($item['total_qty'] !== null && (int) $item['quantity'] > $item['total_qty'] - $borrowed - (int) $item['damaged_qty']) {
                        throw new RuntimeException('Not enough available quantity for ' . $item['name']);
                    }
                }

                $statement = $pdo->prepare("UPDATE loans SET status='borrowed', borrowed_at=NOW() WHERE id=?");
                $statement->execute([$loanId]);
                $pdo->commit();
                log_action('Approved loan', 'loan', $loanId);
                flash('success', 'Loan approved and marked borrowed.');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                flash('error', $exception->getMessage());
            }

            redirect('index.php?page=borrowed');
        }

        if ($admin && $action === 'reject') {
            $statement = $pdo->prepare("UPDATE loans SET status='rejected' WHERE id=? AND status='pending'");
            $statement->execute([$loanId]);
            log_action('Rejected loan', 'loan', $loanId);
            flash('success', 'Loan request rejected.');
            redirect('index.php?page=borrowed');
        }

        if ($action === 'return') {
            $statement = $pdo->prepare('SELECT * FROM loans WHERE id=? AND status=\'borrowed\'' . ($admin ? '' : ' AND user_id=?'));
            $statement->execute($admin ? [$loanId] : [$loanId, user()['id']]);
            $loan = $statement->fetch();

            if (!$loan) {
                flash('error', 'Loan is not currently borrowed.');
            } else {
                $damages = $_POST['damage'] ?? [];
                $hasDamage = false;
                $pdo->beginTransaction();

                try {
                    $statement = $pdo->prepare('SELECT li.*, e.name FROM loan_items li JOIN equipment e ON e.id=li.equipment_id WHERE li.loan_id=? FOR UPDATE');
                    $statement->execute([$loanId]);
                    $items = $statement->fetchAll();

                    foreach ($items as $item) {
                        $damageQuantity = min((int) $item['quantity'], max(0, (int) ($damages[$item['id']] ?? 0)));
                        $statement = $pdo->prepare('UPDATE loan_items SET returned_quantity=quantity, damaged_quantity=? WHERE id=?');
                        $statement->execute([$damageQuantity, $item['id']]);

                        if ($damageQuantity > 0) {
                            $hasDamage = true;
                            $statement = $pdo->prepare('UPDATE equipment SET damaged_qty=damaged_qty+? WHERE id=?');
                            $statement->execute([$damageQuantity, $item['equipment_id']]);
                            $statement = $pdo->prepare('INSERT INTO damage_reports(loan_id, equipment_id, reported_by, quantity, description) VALUES(?,?,?,?,?)');
                            $statement->execute([$loanId, $item['equipment_id'], user()['id'], $damageQuantity, trim($_POST['description'][$item['id']] ?? 'Damage reported during return.')]);
                        }
                    }

                    $statement = $pdo->prepare("UPDATE loans SET status='returned', returned_at=NOW() WHERE id=?");
                    $statement->execute([$loanId]);
                    $pdo->commit();
                    log_action($hasDamage ? 'Returned loan with damage report' : 'Returned loan', 'loan', $loanId);
                    flash('success', $hasDamage
                        ? 'Return recorded and damage quantities updated.'
                        : 'Return recorded.');
                } catch (Throwable $exception) {
                    $pdo->rollBack();
                    flash('error', $exception->getMessage());
                }
            }

            redirect('index.php?page=' . ($admin ? 'borrowed' : 'my_borrowed'));
        }
    }

    $status = $page === 'borrowed' || $page === 'my_borrowed' ? 'borrowed' : 'returned';

    if ($admin) {
        $statement = $pdo->query("SELECT l.*, u.name borrower, GROUP_CONCAT(CONCAT(e.name,' × ',li.quantity,' (damaged: ',li.damaged_quantity,')') SEPARATOR ', ') items FROM loans l JOIN users u ON u.id=l.user_id JOIN loan_items li ON li.loan_id=l.id JOIN equipment e ON e.id=li.equipment_id WHERE l.status='$status' GROUP BY l.id ORDER BY COALESCE(l.borrowed_at,l.created_at) DESC");
        $rows = $statement->fetchAll();
    } else {
        $statement = $pdo->prepare("SELECT l.*, GROUP_CONCAT(CONCAT(e.name,' × ',li.quantity) SEPARATOR ', ') items FROM loans l JOIN loan_items li ON li.loan_id=l.id JOIN equipment e ON e.id=li.equipment_id WHERE l.user_id=? AND l.status=? GROUP BY l.id ORDER BY COALESCE(l.borrowed_at,l.created_at) DESC");
        $statement->execute([user()['id'], $status]);
        $rows = $statement->fetchAll();
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1><?= $status === 'borrowed' ? 'Borrowed Items' : 'Returned Items' ?></h1>
            <p class="muted"><?= $admin ? 'Manage laboratory transactions.' : 'Your equipment transaction records.' ?></p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($admin): ?>
                        <th>Borrower</th>
                    <?php endif; ?>
                    <th>Items</th>
                    <th>Room</th>
                    <th>Borrowed Date</th>
                    <th>Returned Date</th>
                    <th>Status</th>
                    <?php if ($status === 'borrowed'): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $loan): ?>
                    <tr>
                        <?php if ($admin): ?>
                            <td><?= e($loan['borrower']) ?></td>
                        <?php endif; ?>
                        <td><?= e($loan['items']) ?></td>
                        <td><?= e($loan['room']) ?></td>
                        <td><?= e($loan['borrowed_at'] ?? '—') ?></td>
                        <td><?= e($loan['returned_at'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $status === 'borrowed' ? 'success' : 'info' ?>"><?= e(ucfirst($loan['status'])) ?></span>
                        </td>
                        <?php if ($status === 'borrowed'): ?>
                            <td>
                                <?php if ($loan['status'] === 'borrowed'): ?>
                                    <form method="post" class="form" style="gap:6px;min-width:180px">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                        <button class="btn success">Mark Returned</button>
                                        <a class="btn secondary" href="index.php?page=return_form&loan=<?= $loan['id'] ?>">Report Damage / Return</a>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="<?= $admin ? 7 : ($status === 'borrowed' ? 6 : 5) ?>" class="empty">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($admin && $status === 'borrowed'): ?>
        <div class="card" style="margin-top:18px">
            <h3>Pending Requests</h3>
            <p class="muted">Pending requests are listed separately so approval does not get mixed with active loans.</p>
            <?php $pending = $pdo->query("SELECT l.id, u.name, l.room, l.created_at, GROUP_CONCAT(CONCAT(e.name,' × ',li.quantity) SEPARATOR ', ') items FROM loans l JOIN users u ON u.id=l.user_id JOIN loan_items li ON li.loan_id=l.id JOIN equipment e ON e.id=li.equipment_id WHERE l.status='pending' GROUP BY l.id ORDER BY l.created_at")->fetchAll(); ?>
            <div class="table-wrap" style="box-shadow:none">
                <table>
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Items</th>
                            <th>Room</th>
                            <th>Requested</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $request): ?>
                            <tr>
                                <td><?= e($request['name']) ?></td>
                                <td><?= e($request['items']) ?></td>
                                <td><?= e($request['room']) ?></td>
                                <td><?= e($request['created_at']) ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="loan_id" value="<?= $request['id'] ?>">
                                            <button class="btn success">Approve</button>
                                        </form>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="loan_id" value="<?= $request['id'] ?>">
                                            <button class="btn danger">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$pending): ?>
                            <tr>
                                <td colspan="5" class="empty">No pending requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    <?php
    $content = ob_get_clean();
    page(ucfirst($status), $content);
    exit;
}

if ($page === 'return_form') {
    require_login();
    $admin = user()['role'] === 'admin';
    $pdo = db();
    $loanId = (int) ($_GET['loan'] ?? 0);

    $statement = $pdo->prepare("SELECT l.*, u.name borrower FROM loans l JOIN users u ON u.id=l.user_id WHERE l.id=? AND l.status='borrowed'" . ($admin ? '' : ' AND l.user_id=?'));
    $statement->execute($admin ? [$loanId] : [$loanId, user()['id']]);
    $loan = $statement->fetch();

    if (!$loan) {
        flash('error', 'Active loan not found.');
        redirect('index.php?page=' . ($admin ? 'borrowed' : 'my_borrowed'));
    }

    $statement = $pdo->prepare('SELECT li.*, e.name, e.variant FROM loan_items li JOIN equipment e ON e.id=li.equipment_id WHERE li.loan_id=?');
    $statement->execute([$loanId]);
    $items = $statement->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $hasDamage = false;
        $pdo->beginTransaction();

        try {
            foreach ($items as $item) {
                $damage = min((int) $item['quantity'], max(0, (int) ($_POST['damage'][$item['id']] ?? 0)));
                $statement = $pdo->prepare('UPDATE loan_items SET returned_quantity=quantity, damaged_quantity=? WHERE id=?');
                $statement->execute([$damage, $item['id']]);

                if ($damage > 0) {
                    $hasDamage = true;
                    $statement = $pdo->prepare('UPDATE equipment SET damaged_qty=damaged_qty+? WHERE id=?');
                    $statement->execute([$damage, $item['equipment_id']]);
                    $statement = $pdo->prepare('INSERT INTO damage_reports(loan_id, equipment_id, reported_by, quantity, description) VALUES(?,?,?,?,?)');
                    $statement->execute([$loanId, $item['equipment_id'], user()['id'], $damage, trim($_POST['description'][$item['id']] ?? 'Damage reported during return.')]);
                }
            }

            $statement = $pdo->prepare("UPDATE loans SET status='returned', returned_at=NOW() WHERE id=?");
            $statement->execute([$loanId]);
            $pdo->commit();
            log_action($hasDamage ? 'Returned loan with damage report' : 'Returned loan', 'loan', $loanId);
            flash('success', $hasDamage
                ? 'Return recorded and damage quantities updated.'
                : 'Return recorded.');
            redirect('index.php?page=' . ($admin ? 'borrowed' : 'my_borrowed'));
        } catch (Throwable $exception) {
            $pdo->rollBack();
            flash('error', $exception->getMessage());
        }
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Process Return</h1>
            <p class="muted"><?= $admin ? 'Borrower: ' . e($loan['borrower']) . ' · ' : '' ?>Room: <?= e($loan['room']) ?></p>
        </div>
    </div>

    <div class="card">
        <form class="form" method="post">
            <?= csrf_field() ?>
            <?php foreach ($items as $item): ?>
                <div class="card" style="box-shadow:none;background:#f8fafc">
                    <strong><?= e($item['name']) ?></strong>
                    <span class="muted">· <?= e($item['variant'] ?? '') ?></span>
                    <div class="form-grid" style="margin-top:10px">
                        <div class="field">
                            <label>Damaged Quantity (max <?= e((string) $item['quantity']) ?>)</label>
                            <input type="number" min="0" max="<?= e((string) $item['quantity']) ?>" name="damage[<?= $item['id'] ?>]" value="0">
                        </div>
                        <div class="field">
                            <label>Damage Description</label>
                            <input name="description[<?= $item['id'] ?>]" placeholder="Optional description">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <a class="btn secondary" href="index.php?page=<?= $admin ? 'borrowed' : 'my_borrowed' ?>">Cancel</a>
                <button class="btn success">Complete Return</button>
            </div>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    page('Process Return', $content);
    exit;
}

if ($page === 'borrowers') {
    require_admin();
    $pdo = db();
    $rows = $pdo->query("SELECT u.name, MAX(l.borrowed_at) borrowed_date, GROUP_CONCAT(DISTINCT CONCAT(e.name,' × ',li.quantity) SEPARATOR ', ') items, GROUP_CONCAT(DISTINCT l.room SEPARATOR ', ') rooms FROM users u LEFT JOIN loans l ON l.user_id=u.id AND l.status='borrowed' LEFT JOIN loan_items li ON li.loan_id=l.id LEFT JOIN equipment e ON e.id=li.equipment_id WHERE u.role='user' GROUP BY u.id ORDER BY u.name")->fetchAll();

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Borrower</h1>
            <p class="muted">Current borrower overview.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Borrower Name</th>
                    <th>Borrowed Date</th>
                    <th>Items Borrowed</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['borrowed_date'] ?? '—') ?></td>
                        <td><?= e($row['items'] ?? '—') ?></td>
                        <td><?= e($row['rooms'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="4" class="empty">No borrowers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $content = ob_get_clean();
    page('Borrowers', $content);
    exit;
}

if ($page === 'users') {
    require_admin();
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $action = $_POST['action'] ?? '';
        $userId = (int) ($_POST['id'] ?? 0);

        if ($action === 'role' && $userId !== user()['id']) {
            $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
            $statement = $pdo->prepare('UPDATE users SET role=? WHERE id=?');
            $statement->execute([$role, $userId]);
            log_action('Changed user role', 'user', $userId, $role);
            flash('success', 'User role updated.');
        }

        if ($action === 'delete' && $userId !== user()['id']) {
            $statement = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE user_id=?');
            $statement->execute([$userId]);

            if ((int) $statement->fetchColumn() > 0) {
                flash('error', 'Users with transaction history cannot be deleted.');
            } else {
                $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
                log_action('Deleted user', 'user', $userId);
                flash('success', 'User deleted.');
            }
        }
    }

    $rows = $pdo->query("SELECT u.id, u.name, u.id_number, u.role, u.created_at, COALESCE((SELECT COUNT(*) FROM loan_items li JOIN loans l ON l.id=li.loan_id WHERE l.user_id=u.id AND l.status='borrowed'),0) borrowed_items FROM users u ORDER BY u.created_at DESC")->fetchAll();

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>Users</h1>
            <p class="muted">Manage borrower accounts and administrator roles.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Borrowed Items</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['id_number']) ?></td>
                        <td><span class="badge <?= $row['role'] === 'admin' ? 'info' : '' ?>"><?= e(ucfirst($row['role'])) ?></span></td>
                        <td><?= e($row['created_at']) ?></td>
                        <td><?= e((string) $row['borrowed_items']) ?></td>
                        <td>
                            <?php if ($row['id'] !== user()['id']): ?>
                                <div class="actions">
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="role">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="role" value="<?= $row['role'] === 'admin' ? 'user' : 'admin' ?>">
                                        <button class="btn secondary">Make <?= $row['role'] === 'admin' ? 'User' : 'Admin' ?></button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this account?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button class="btn danger">Delete</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted">Current account</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $content = ob_get_clean();
    page('Users', $content);
    exit;
}

if ($page === 'history') {
    $pdo = db();
    $admin = user()['role'] === 'admin';

    if ($admin) {
        $rows = $pdo->query("SELECT u.name borrower, e.name equipment, e.variant, li.quantity, l.room, l.borrowed_at, l.returned_at FROM loan_items li JOIN loans l ON l.id=li.loan_id JOIN users u ON u.id=l.user_id JOIN equipment e ON e.id=li.equipment_id ORDER BY l.created_at DESC")->fetchAll();
    } else {
        $statement = $pdo->prepare("SELECT u.name borrower, e.name equipment, e.variant, li.quantity, l.room, l.borrowed_at, l.returned_at FROM loan_items li JOIN loans l ON l.id=li.loan_id JOIN users u ON u.id=l.user_id JOIN equipment e ON e.id=li.equipment_id WHERE l.user_id=? ORDER BY l.created_at DESC");
        $statement->execute([user()['id']]);
        $rows = $statement->fetchAll();
    }

    ob_start();
    ?>
    <div class="page-title">
        <div>
            <h1>History</h1>
            <p class="muted">Complete borrowing and return history.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Room</th>
                    <th>Borrowed Date</th>
                    <th>Returned Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['borrower']) ?></td>
                        <td>
                            <?= e($row['equipment']) ?>
                            <?php if ($row['variant']): ?>
                                <div class="muted"><?= e($row['variant']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) $row['quantity']) ?></td>
                        <td><?= e($row['room']) ?></td>
                        <td><?= e($row['borrowed_at'] ?? '—') ?></td>
                        <td><?= e($row['returned_at'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="empty">No history yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $content = ob_get_clean();
    page('History', $content);
    exit;
}

http_response_code(404);
ob_start();
?>
<div class="card">
    <h1>404</h1>
    <p>The requested page was not found.</p>
    <a class="btn" href="index.php?page=dashboard">Back to Dashboard</a>
</div>
<?php
$content = ob_get_clean();
page('Not Found', $content);
exit;
