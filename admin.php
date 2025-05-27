<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once 'dbcon.php';



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_news':
                $title = $_POST['title'];
                $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title)));
                $content = $_POST['content'];
                $excerpt = $_POST['excerpt'];
                $category_id = $_POST['category_id'];
                $status = isset($_POST['publish_now']) ? 'published' : 'draft';
                $image_path = '';
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image_path = $upload_dir . uniqid() . '.' . $file_extension;
                    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
                }
                
                $query = "INSERT INTO articles (title, slug, content, excerpt, image_path, category_id, status, published_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $dbh->prepare($query);
                $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$title, $slug, $content, $excerpt, $image_path, $category_id, $status, $published_at]);
                $success_message = "News article added successfully!";
                break;
                
            case 'update_news':
                $article_id = $_POST['article_id'];
                $title = $_POST['title'];
                $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title)));
                $content = $_POST['content'];
                $excerpt = $_POST['excerpt'];
                $category_id = $_POST['category_id'];
                $status = isset($_POST['publish_now']) ? 'published' : 'draft';
                
                // Handle image upload
                $image_path = $_POST['existing_image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image_path = $upload_dir . uniqid() . '.' . $file_extension;
                    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
                }
                
                $query = "UPDATE articles SET title=?, slug=?, content=?, excerpt=?, image_path=?, category_id=?, status=?, published_at=?, updated_at=NOW() WHERE article_id=?";
                $stmt = $dbh->prepare($query);
                $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
                $stmt->execute([$title, $slug, $content, $excerpt, $image_path, $category_id, $status, $published_at, $article_id]);
                $success_message = "News article updated successfully!";
                break;
                
            case 'delete_news':
                $article_id = $_POST['article_id'];
                
                // Get image path to delete file
                $query = "SELECT image_path FROM articles WHERE article_id = ?";
                $stmt = $dbh->prepare($query);
                $stmt->execute([$article_id]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($article && $article['image_path'] && file_exists($article['image_path'])) {
                    unlink($article['image_path']);
                }
                
                $query = "DELETE FROM articles WHERE article_id = ?";
                $stmt = $dbh->prepare($query);
                $stmt->execute([$article_id]);
                $success_message = "News article deleted successfully!";
                break;
        }
    }
}

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM articles) as total_news,
    (SELECT COUNT(*) FROM articles WHERE status = 'published') as published_news,
    (SELECT COUNT(*) FROM articles WHERE status = 'draft') as draft_news,
    (SELECT COUNT(*) FROM categories) as total_categories";
$stats_stmt = $dbh->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get all articles with categories
$articles_query = "SELECT a.*, c.category_name 
                   FROM articles a 
                   LEFT JOIN categories c ON a.category_id = c.category_id 
                   ORDER BY a.created_at DESC";
$articles_stmt = $dbh->prepare($articles_query);
$articles_stmt->execute();
$articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_stmt = $dbh->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get article for editing if edit ID is provided
$edit_article = null;
if (isset($_GET['edit'])) {
    $edit_query = "SELECT * FROM articles WHERE article_id = ?";
    $edit_stmt = $dbh->prepare($edit_query);
    $edit_stmt->execute([$_GET['edit']]);
    $edit_article = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Admin Panel</title>
    <link rel="icon" type="image/png" href="./imgs/pfp.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.5);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .news-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3" style="width: 250px;">
            <div class="text-center mb-4">
                <h4 class="text-white"><i class="fas fa-newspaper me-2"></i>News Admin</h4>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#manage-news" onclick="showSection('manage-news')">
                        <i class="fas fa-newspaper me-2"></i> Manage News
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add-news" onclick="showSection('add-news')">
                        <i class="fas fa-plus-circle me-2"></i> Add News
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php" target="_blank">
                        <i class="fas fa-eye me-2"></i> View Website
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <button class="btn btn-primary" onclick="showSection('add-news')">
                        <i class="fas fa-plus me-2"></i> Add News
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total News</h5>
                                <h2 class="card-text"><?php echo $stats['total_news']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Published</h5>
                                <h2 class="card-text"><?php echo $stats['published_news']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Drafts</h5>
                                <h2 class="card-text"><?php echo $stats['draft_news']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Categories</h5>
                                <h2 class="card-text"><?php echo $stats['total_categories']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent News -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent News</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($articles, 0, 5) as $article): ?>
                                    <tr>
                                        <td>
                                            <?php if ($article['image_path'] && file_exists($article['image_path'])): ?>
                                                <img src="<?php echo $article['image_path']; ?>" class="news-image rounded">
                                            <?php else: ?>
                                                <div class="news-image bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($article['title'], 0, 50)) . (strlen($article['title']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($article['category_name'] ?? 'No Category'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $article['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($article['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1" onclick="editNews(<?php echo $article['article_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteNews(<?php echo $article['article_id']; ?>, '<?php echo htmlspecialchars($article['title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manage News Section -->
            <div id="manage-news" class="content-section" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage News</h2>
                    <button class="btn btn-primary" onclick="showSection('add-news')">
                        <i class="fas fa-plus me-2"></i> Add News
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td>
                                            <?php if ($article['image_path'] && file_exists($article['image_path'])): ?>
                                                <img src="<?php echo $article['image_path']; ?>" class="news-image rounded">
                                            <?php else: ?>
                                                <div class="news-image bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($article['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($article['excerpt'] ?: $article['content'], 0, 80)) . '...'; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($article['category_name'] ?? 'No Category'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $article['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($article['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                                        <td><?php echo $article['published_at'] ? date('M d, Y', strtotime($article['published_at'])) : '-'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1" onclick="editNews(<?php echo $article['article_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteNews(<?php echo $article['article_id']; ?>, '<?php echo htmlspecialchars($article['title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit News Section -->
            <div id="add-news" class="content-section" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $edit_article ? 'Edit News' : 'Add New News'; ?></h2>
                    <button class="btn btn-secondary" onclick="showSection('manage-news')">
                        <i class="fas fa-arrow-left me-2"></i> Back to List
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_article ? 'update_news' : 'add_news'; ?>">
                            <?php if ($edit_article): ?>
                                <input type="hidden" name="article_id" value="<?php echo $edit_article['article_id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo $edit_article['image_path']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo $edit_article ? htmlspecialchars($edit_article['title']) : ''; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="excerpt" class="form-label">Excerpt</label>
                                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3" 
                                                  placeholder="Brief summary of the article"><?php echo $edit_article ? htmlspecialchars($edit_article['excerpt']) : ''; ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="content" class="form-label">Content *</label>
                                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo $edit_article ? htmlspecialchars($edit_article['content']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                        <?php echo ($edit_article && $edit_article['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">Image</label>
                                        <input class="form-control" type="file" id="image" name="image" accept="image/*">
                                        <?php if ($edit_article && $edit_article['image_path']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Current image:</small><br>
                                                <img src="<?php echo $edit_article['image_path']; ?>" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="publish_now" name="publish_now" 
                                               <?php echo ($edit_article && $edit_article['status'] === 'published') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="publish_now">
                                            Publish immediately
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $edit_article ? 'Update News' : 'Save News'; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this news article?</p>
                    <p class="fw-bold" id="deleteArticleTitle"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_news">
                        <input type="hidden" name="article_id" id="deleteArticleId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
            
            // Update active nav link
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function editNews(articleId) {
            window.location.href = '?edit=' + articleId;
        }

        function deleteNews(articleId, title) {
            document.getElementById('deleteArticleId').value = articleId;
            document.getElementById('deleteArticleTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Show edit form if edit parameter is present
        <?php if (isset($_GET['edit'])): ?>
            showSection('add-news');
        <?php endif; ?>
    </script>
</body>
</html>