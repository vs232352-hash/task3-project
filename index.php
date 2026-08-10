<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$search = trim($_GET["search"] ?? "");
$posts_per_page = 5;
$page = isset($_GET["page"]) && is_numeric($_GET["page"]) ? (int)$_GET["page"] : 1;
$offset = ($page - 1) * $posts_per_page;

if ($search !== "") {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE title LIKE ? OR content LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
}
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

if ($search !== "") {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", "%$search%", $posts_per_page, $offset]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$posts_per_page, $offset]);
}
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - CRUD Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-journal-richtext"></i> My Blog</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION["username"]) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search posts by title or content..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-muted">
            <?php if ($search): ?>
                Results for: <strong>"<?= htmlspecialchars($search) ?>"</strong> (<?= $total_posts ?> found)
            <?php else: ?>
                All Posts (<?= $total_posts ?>)
            <?php endif; ?>
        </h5>
        <a href="create.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New Post</a>
    </div>

    <?php if (count($posts) === 0): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i>
            <?= $search ? "No posts found for your search." : "No posts yet. Add your first post!" ?>
        </div>
    <?php endif; ?>

    <?php foreach ($posts as $post): ?>
        <div class="card shadow-sm mb-3 post-card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($post["title"]) ?></h5>
                <p class="card-text text-muted"><?= nl2br(htmlspecialchars(substr($post["content"], 0, 200))) ?><?= strlen($post["content"]) > 200 ? "..." : "" ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="bi bi-clock"></i> <?= date("d M Y, h:i A", strtotime($post["created_at"])) ?></small>
                    <div>
                        <a href="edit.php?id=<?= $post["id"] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i> Edit</a>
                        <a href="delete.php?id=<?= $post["id"] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this post?');"><i class="bi bi-trash"></i> Delete</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="bi bi-chevron-left"></i> Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next <i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>