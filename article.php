<?php
require_once 'dbcon.php';

// Get article slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Fetch article details
$article_query = "SELECT a.*, c.category_name 
                  FROM articles a 
                  JOIN categories c ON a.category_id = c.category_id 
                  WHERE a.slug = ? AND a.status = 'published'";
$article_stmt = $dbh->prepare($article_query);
$article_stmt->execute([$slug]);
$article = $article_stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header('Location: index.php');
    exit;
}

// Fetch related articles from same category
$related_query = "SELECT a.*, c.category_name 
                  FROM articles a 
                  JOIN categories c ON a.category_id = c.category_id 
                  WHERE a.category_id = ? AND a.article_id != ? AND a.status = 'published' 
                  ORDER BY a.published_at DESC 
                  LIMIT 3";
$related_stmt = $dbh->prepare($related_query);
$related_stmt->execute([$article['category_id'], $article['article_id']]);
$related_articles = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get category badge color
function getCategoryColor($category) {
    $colors = [
        'Technology' => 'primary',
        'Sports' => 'success',
        'Business' => 'warning',
        'Health' => 'info',
        'Politics' => 'secondary',
        'Entertainment' => 'danger'
    ];
    return $colors[$category] ?? 'dark';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Daily News</title>
    <link rel="icon" type="image/png" href="./imgs/logo.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .article-header {
            margin: 2rem 0;
        }
        
        .article-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            margin: 2rem 0;
        }
        
        .article-content p {
            margin-bottom: 1.5rem;
        }
        
        .article-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin: 2rem 0;
        }
        
        .related-article {
            transition: transform 0.3s;
            margin-bottom: 1rem;
        }
        
        .related-article:hover {
            transform: translateY(-2px);
        }
        
        .related-article img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .placeholder-img {
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .share-buttons a {
            margin-right: 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper me-2"></i>
                Daily News
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Article Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item">
                            <span class="badge bg-<?php echo getCategoryColor($article['category_name']); ?>">
                                <?php echo htmlspecialchars($article['category_name']); ?>
                            </span>
                        </li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars(substr($article['title'], 0, 50)); ?>...</li>
                    </ol>
                </nav>

                <!-- Article Header -->
                <div class="article-header">
                    <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($article['title']); ?></h1>
                    
                    <div class="article-meta">
                        <i class="far fa-clock me-1"></i>
                        Published on <?php echo date('F d, Y \a\t g:i A', strtotime($article['published_at'])); ?>
                        <span class="mx-2">•</span>
                        <span class="badge bg-<?php echo getCategoryColor($article['category_name']); ?>">
                            <?php echo htmlspecialchars($article['category_name']); ?>
                        </span>
                    </div>
                    
                    <?php if ($article['excerpt']): ?>
                    <div class="lead text-muted">
                        <?php echo htmlspecialchars($article['excerpt']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Article Image -->
                <?php if ($article['image_path'] && file_exists($article['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($article['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>" 
                         class="article-image">
                <?php endif; ?>

                <!-- Article Content -->
                <div class="article-content">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>

                <!-- Share Buttons -->
                <div class="border-top pt-4 mt-4">
                    <h5>Share this article:</h5>
                    <div class="share-buttons">
                        <a href="https://facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="btn btn-primary btn-sm">
                            <i class="fab fa-facebook-f me-1"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" 
                           target="_blank" class="btn btn-info btn-sm">
                            <i class="fab fa-twitter me-1"></i> Twitter
                        </a>
                        <a href="https://linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="btn btn-primary btn-sm">
                            <i class="fab fa-linkedin-in me-1"></i> LinkedIn
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode($article['title']); ?>&body=<?php echo urlencode('Check out this article: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-secondary btn-sm">
                            <i class="fas fa-envelope me-1"></i> Email
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Articles -->
                <?php if (!empty($related_articles)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Related Articles</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($related_articles as $related): ?>
                        <div class="related-article">
                            <div class="card border-0 shadow-sm">
                                <?php if ($related['image_path'] && file_exists($related['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($related['image_path']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-img card-img-top">
                                        <i class="fas fa-image fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="article.php?slug=<?php echo $related['slug']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($related['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('M d, Y', strtotime($related['published_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Back to Category -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h6>More from <?php echo htmlspecialchars($article['category_name']); ?></h6>
                        <a href="index.php?category=<?php echo urlencode(strtolower($article['category_name'])); ?>" 
                           class="btn btn-outline-primary btn-sm">
                            View All <?php echo htmlspecialchars($article['category_name']); ?> News
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Daily News</h5>
                    <p>Bringing you the latest and most accurate news from around the world.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="index.php" class="btn btn-outline-light btn-sm">Back to Home</a>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Daily News. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>