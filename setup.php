<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET password_hash = :password_hash
         WHERE email = :email'
    );

    $stmt->execute([
        ':password_hash' => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
        ':email' => DEFAULT_ADMIN_EMAIL,
    ]);

    $adminExists = $stmt->rowCount() > 0;
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>LMS setup failed</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LMS Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7fb;
            color: #1f2937;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            max-width: 560px;
            width: min(90vw, 560px);
            padding: 32px;
        }

        h1 {
            margin-top: 0;
            color: #0f172a;
        }

        p {
            line-height: 1.6;
        }

        .meta {
            font-weight: 700;
            background: #eef6ff;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 18px 0;
        }

        a {
            color: #0b5ed7;
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>LMS setup complete</h1>

        <?php if ($adminExists): ?>
            <div class="meta">
                Admin: <?= htmlspecialchars(DEFAULT_ADMIN_EMAIL, ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars(DEFAULT_ADMIN_PASSWORD, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <p>Your default administrator account is ready to use.</p>
        <?php else: ?>
            <p>The admin account could not be found. Please import the database schema before running setup again.</p>
        <?php endif; ?>

        <p><a href="index.php?page=login">Go to login</a></p>
        <p>Delete setup.php after this step.</p>
    </main>
</body>
</html>
