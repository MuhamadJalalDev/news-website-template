<?php
require_once 'dbcon.php';

// Fetch featured news (latest published article)
$featured_query = "SELECT a.*, c.category_name 
                   FROM articles a 
                   JOIN categories c ON a.category_id = c.category_id 
                   WHERE a.status = 'published' 
                   ORDER BY a.published_at DESC 
                   LIMIT 1";
$featured_stmt = $dbh->prepare($featured_query);
$featured_stmt->execute();
$featured_news = $featured_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent news (excluding the featured one)
$recent_query = "SELECT a.*, c.category_name 
                 FROM articles a 
                 JOIN categories c ON a.category_id = c.category_id 
                 WHERE a.status = 'published' AND a.article_id != ? 
                 ORDER BY a.published_at DESC 
                 LIMIT 6";
$recent_stmt = $dbh->prepare($recent_query);
$recent_stmt->execute([$featured_news['article_id'] ?? 0]);
$recent_news = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for navigation
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_stmt = $dbh->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Function to truncate text
function truncateText($text, $length = 150) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./imgs/logo.png">
    <title>Daily News</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .nav-link {
            font-weight: 500;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1504711434969-e33886168f5c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            color: white;
            padding: 100px 0;
            margin-bottom: 30px;
        }
        
        .news-card {
            transition: transform 0.3s;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .news-card:hover {
            transform: translateY(-5px);
        }
        
        .news-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .news-date {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        footer {
            background-color: #343a40;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .placeholder-img {
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-newspaper me-2"></i>
                Daily News
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <?php foreach($categories as $category): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?category=<?php echo $category['category_slug']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">Stay Informed Daily</h1>
            <p class="lead">Get the latest news and updates from around the world</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Breaking News Ticker -->
        <div class="alert alert-danger mb-4">
            <div class="d-flex align-items-center">
                <span class="badge bg-danger me-2">BREAKING</span>
                <marquee behavior="scroll" direction="left">
                    <?php echo $featured_news ? htmlspecialchars($featured_news['title']) : 'Stay tuned for the latest breaking news updates.'; ?>
                </marquee>
            </div>
        </div>
        
        <!-- Featured News -->
        <div class="row mb-5">
            <?php if($featured_news): ?>
            <div class="col-md-8">
                <div class="card news-card">
                    <div class="position-relative">
                        <?php if($featured_news['image_path'] && file_exists($featured_news['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($featured_news['image_path']); ?>" class="card-img-top" alt="featured-news">
                        <?php else: ?>
                            <div class="placeholder-img card-img-top">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <span class="badge bg-<?php echo getCategoryColor($featured_news['category_name']); ?> category-badge">
                            <?php echo htmlspecialchars($featured_news['category_name']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title"><?php echo htmlspecialchars($featured_news['title']); ?></h2>
                        <p class="card-text"><?php echo htmlspecialchars(truncateText($featured_news['excerpt'] ?: $featured_news['content'])); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="news-date">
                                <i class="far fa-clock me-1"></i> 
                                <?php echo date('M d, Y', strtotime($featured_news['published_at'])); ?>
                            </small>
                            <a href="article.php?slug=<?php echo $featured_news['slug']; ?>" class="btn btn-sm btn-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php 
                $sidebar_news = array_slice($recent_news, 0, 2);
                foreach($sidebar_news as $news): 
                ?>
                <div class="card news-card <?php echo $news !== $sidebar_news[0] ? 'mt-4' : ''; ?>">
                    <div class="position-relative">
                        <?php if($news['image_path'] && file_exists($news['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($news['image_path']); ?>" class="card-img-top" alt="secondary-news">
                        <?php else: ?>
                            <div class="placeholder-img card-img-top">
                                <i class="fas fa-image fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <span class="badge bg-<?php echo getCategoryColor($news['category_name']); ?> category-badge">
                            <?php echo htmlspecialchars($news['category_name']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h4>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="news-date">
                                <i class="far fa-clock me-1"></i> 
                                <?php echo date('M d, Y', strtotime($news['published_at'])); ?>
                            </small>
                            <a href="article.php?slug=<?php echo $news['slug']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="col-12 text-center">
                <div class="card">
                    <div class="card-body">
                        <h3>No News Available</h3>
                        <p>Please check back later or visit the admin panel to add some news articles.</p>
                        <a href="admin.php" class="btn btn-primary">Go to Admin Panel</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Latest News Section -->
        <?php if(count($recent_news) > 2): ?>
        <h3 class="mb-4 pb-2 border-bottom">Latest News</h3>
        <div class="row">
            <?php 
            $latest_news = array_slice($recent_news, 2);
            foreach($latest_news as $news): 
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card news-card h-100">
                    <div class="position-relative">
                        <?php if($news['image_path'] && file_exists($news['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($news['image_path']); ?>" class="card-img-top" alt="news-image">
                        <?php else: ?>
                            <div class="placeholder-img card-img-top">
                                <i class="fas fa-image fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <span class="badge bg-<?php echo getCategoryColor($news['category_name']); ?> category-badge">
                            <?php echo htmlspecialchars($news['category_name']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h4>
                        <p class="card-text"><?php echo htmlspecialchars(truncateText($news['excerpt'] ?: $news['content'], 100)); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="news-date">
                                <i class="far fa-clock me-1"></i> 
                                <?php echo date('M d, Y', strtotime($news['published_at'])); ?>
                            </small>
                            <a href="article.php?slug=<?php echo $news['slug']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Load More Button -->
        <?php if(count($recent_news) >= 6): ?>
        <div class="text-center mt-5">
            <button class="btn btn-primary px-4" onclick="loadMoreNews()">Load More News</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Newsletter Subscription -->
    <div class="bg-light py-5 mt-5">
        <div class="container text-center">
            <h3>Subscribe to Our Newsletter</h3>
            <p class="text-muted">Get the latest news delivered to your inbox</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form class="input-group mb-3" method="POST" action="subscribe.php">
                        <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Daily News</h5>
                    <p>Bringing you the latest and most accurate news from around the world since 2005.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Home</a></li>
                        <li><a href="#" class="text-white">About Us</a></li>
                        <li><a href="#" class="text-white">Contact</a></li>
                        <li><a href="#" class="text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in fa-lg"></i></a>
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
    <script>
        function loadMoreNews() {
            // This would typically make an AJAX call to load more news
            alert('Load more functionality would be implemented here with AJAX');
        }
    </script>
</body>
</html>