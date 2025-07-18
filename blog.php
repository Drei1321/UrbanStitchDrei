<?php
require_once 'config.php';

// Get page info for meta tags
$page_title = "Urban Stories & Style Blog - UrbanStitch";
$page_description = "Discover the latest streetwear trends, styling tips, and urban culture stories from UrbanStitch. Your go-to source for street fashion inspiration.";
$page_keywords = "streetwear blog, urban fashion, style tips, street culture, fashion trends, UrbanStitch blog";

// Mock blog posts data (in a real app, this would come from database)
$featured_posts = [
    [
        'id' => 1,
        'title' => 'The Evolution of Streetwear: From Underground to Mainstream',
        'excerpt' => 'Explore how streetwear transformed from underground subculture to a billion-dollar industry that influences high fashion.',
        'content' => 'Streetwear has come a long way from its humble beginnings in the skateboarding and hip-hop communities of the 1980s. What started as a form of self-expression for marginalized youth has evolved into a dominant force in the fashion industry...',
        'featured_image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
        'author' => 'Alex Chen',
        'date' => '2024-03-15',
        'category' => 'Fashion History',
        'tags' => ['streetwear', 'fashion history', 'culture'],
        'read_time' => 8,
        'featured' => true
    ],
    [
        'id' => 2,
        'title' => 'Sustainable Streetwear: Making Fashion More Conscious',
        'excerpt' => 'How the streetwear industry is embracing sustainability and what it means for conscious consumers.',
        'content' => 'The fashion industry is one of the world\'s most polluting industries, but streetwear brands are leading the charge toward more sustainable practices...',
        'featured_image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
        'author' => 'Maya Rodriguez',
        'date' => '2024-03-12',
        'category' => 'Sustainability',
        'tags' => ['sustainability', 'eco-fashion', 'conscious shopping'],
        'read_time' => 6,
        'featured' => true
    ]
];

$blog_posts = [
    [
        'id' => 3,
        'title' => 'How to Style Oversized Hoodies: 5 Fresh Looks',
        'excerpt' => 'Master the art of styling oversized hoodies with these versatile outfit combinations for any occasion.',
        'featured_image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
        'author' => 'Jordan Kim',
        'date' => '2024-03-10',
        'category' => 'Style Guide',
        'tags' => ['hoodies', 'styling', 'outfit ideas'],
        'read_time' => 4
    ],
    [
        'id' => 4,
        'title' => 'Sneaker Culture: The Rise of Limited Drops',
        'excerpt' => 'Understanding the hype, psychology, and business behind limited edition sneaker releases.',
        'featured_image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2012&q=80',
        'author' => 'Chris Thompson',
        'date' => '2024-03-08',
        'category' => 'Sneaker Culture',
        'tags' => ['sneakers', 'limited edition', 'streetwear culture'],
        'read_time' => 7
    ],
    [
        'id' => 5,
        'title' => 'Building Your Capsule Streetwear Wardrobe',
        'excerpt' => 'Essential pieces every streetwear enthusiast needs for a versatile and stylish wardrobe.',
        'featured_image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80',
        'author' => 'Sam Wilson',
        'date' => '2024-03-05',
        'category' => 'Wardrobe Essentials',
        'tags' => ['capsule wardrobe', 'essentials', 'minimalism'],
        'read_time' => 5
    ],
    [
        'id' => 6,
        'title' => 'The Art of Layering: Winter Streetwear Edition',
        'excerpt' => 'Stay warm and stylish with these expert layering techniques for cold weather streetwear looks.',
        'featured_image' => 'https://images.unsplash.com/photo-1544022613-e87ca75a784a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80',
        'author' => 'Taylor Davis',
        'date' => '2024-03-02',
        'category' => 'Seasonal Style',
        'tags' => ['layering', 'winter fashion', 'outerwear'],
        'read_time' => 6
    ],
    [
        'id' => 7,
        'title' => 'From Tokyo to New York: Global Streetwear Scenes',
        'excerpt' => 'A journey through the world\'s most influential streetwear capitals and their unique style signatures.',
        'featured_image' => 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
        'author' => 'Yuki Tanaka',
        'date' => '2024-02-28',
        'category' => 'Global Fashion',
        'tags' => ['international fashion', 'street style', 'cultural trends'],
        'read_time' => 9
    ],
    [
        'id' => 8,
        'title' => 'Thrift Flipping: Creating Unique Streetwear Pieces',
        'excerpt' => 'Learn how to transform thrifted finds into one-of-a-kind streetwear pieces with DIY techniques.',
        'featured_image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
        'author' => 'Riley Martinez',
        'date' => '2024-02-25',
        'category' => 'DIY Fashion',
        'tags' => ['thrift flipping', 'DIY', 'upcycling', 'creativity'],
        'read_time' => 5
    ]
];

$categories = [
    'Fashion History',
    'Sustainability', 
    'Style Guide',
    'Sneaker Culture',
    'Wardrobe Essentials',
    'Seasonal Style',
    'Global Fashion',
    'DIY Fashion'
];

$popular_tags = [
    'streetwear', 'sustainability', 'sneakers', 'styling', 'fashion history',
    'hoodies', 'layering', 'capsule wardrobe', 'thrift flipping', 'DIY'
];

// Newsletter signup handling
$newsletter_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    $email = filter_var($_POST['newsletter_email'], FILTER_VALIDATE_EMAIL);
    if ($email) {
        // In a real application, you would save this to a database
        $newsletter_message = 'success';
    } else {
        $newsletter_message = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>/blog.php">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="<?php echo $_SERVER['HTTP_HOST']; ?>/assets/blog-og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>/blog.php">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">
    <meta property="twitter:image" content="<?php echo $_SERVER['HTTP_HOST']; ?>/assets/blog-og-image.jpg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <style>
        /* Blog-specific styles */
        .blog-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 80px 0;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .blog-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50" cy="50" r="50"><stop offset="0" stop-color="%2300ff00" stop-opacity="0.1"/><stop offset="1" stop-color="%2300ff00" stop-opacity="0"/></radialGradient></defs><circle cx="10" cy="10" r="3" fill="url(%23a)"/><circle cx="30" cy="15" r="2" fill="url(%23a)"/><circle cx="70" cy="8" r="4" fill="url(%23a)"/><circle cx="90" cy="12" r="2" fill="url(%23a)"/></svg>') repeat;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100px); }
        }

        .blog-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .blog-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .blog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .blog-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }

        .featured-posts {
            margin-bottom: 60px;
        }

        .featured-posts h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5rem;
            position: relative;
        }

        .featured-posts h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #00ff00, #0066cc);
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .featured-post {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .featured-post:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .featured-post img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .featured-post:hover img {
            transform: scale(1.05);
        }

        .featured-post-content {
            padding: 25px;
        }

        .featured-post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }

        .category-badge {
            background: linear-gradient(135deg, #00ff00, #0066cc);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .featured-post h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.4;
        }

        .featured-post h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .featured-post h3 a:hover {
            color: #00ff00;
        }

        .featured-post p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .read-more {
            color: #00ff00;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .read-more:hover {
            color: #0066cc;
        }

        .blog-posts-grid {
            display: grid;
            gap: 30px;
        }

        .blog-post {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
        }

        .blog-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .blog-post img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .blog-post-content {
            padding: 20px 20px 20px 0;
        }

        .blog-post-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #666;
        }

        .blog-post h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: #333;
        }

        .blog-post h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .blog-post h3 a:hover {
            color: #00ff00;
        }

        .blog-post p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .sidebar-widget {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .sidebar-widget h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 10px;
        }

        .sidebar-widget h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(135deg, #00ff00, #0066cc);
        }

        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .newsletter-form input {
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .newsletter-form input:focus {
            outline: none;
            border-color: #00ff00;
        }

        .newsletter-form button {
            background: linear-gradient(135deg, #00ff00, #0066cc);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .newsletter-form button:hover {
            transform: translateY(-2px);
        }

        .categories-list, .tags-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .categories-list a, .tags-list a {
            color: #666;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .categories-list a:hover, .tags-list a:hover {
            background: #f8f9fa;
            color: #00ff00;
            transform: translateX(5px);
        }

        .tags-list {
            flex-direction: row;
            flex-wrap: wrap;
        }

        .tags-list a {
            background: #f8f9fa;
            border: 1px solid #eee;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .tags-list a:hover {
            background: #00ff00;
            color: white;
            border-color: #00ff00;
            transform: translateY(-2px);
        }

        .newsletter-success {
            color: #28a745;
            font-size: 14px;
            margin-top: 10px;
        }

        .newsletter-error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 10px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #eee;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #00ff00;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .blog-hero h1 {
                font-size: 2.5rem;
            }

            .blog-main {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .featured-post, .blog-post {
                grid-template-columns: 1fr;
            }

            .blog-post img {
                height: 200px;
            }

            .blog-post-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Include your existing header -->
 <!-- Header -->
    <header class="header">
        <div class="top-banner">
            <span class="animate-pulse-neon">FREE SHIPPING THIS WEEK ORDER OVER • ₱2,500</span>
            <div style="float: right; margin-right: 16px;">
                <select style="background: transparent; color: white; border: none; margin-right: 8px;">
                    <option>PHP ₱</option>
                </select>
                <select style="background: transparent; color: white; border: none;">
                    <option>ENGLISH</option>
                    <option>SPANISH</option>
                </select>
            </div>
        </div>
        
        <div class="main-header">
            <a href="index.php" class="logo">Urban<span>Stitch</span></a>
            
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="text" class="search-input" name="search" placeholder="Search street fashion..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                <div class="user-menu" style="position: relative; display: inline-block;">
                    <button class="action-btn user-menu-btn" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user"></i>
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php">
                            <i class="fas fa-user" style="margin-right: 8px;"></i>Profile
                        </a>
                        <a href="orders.php">
                            <i class="fas fa-box" style="margin-right: 8px;"></i>Orders
                        </a>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="adminDashboard.php">
                            <i class="fas fa-cog" style="margin-right: 8px;"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="action-btn" style="text-decoration: none; color: inherit;">
                    <i class="fas fa-user"></i>
                </a>
                <?php endif; ?>
                
                
                
            </div>
        </div>
        
        <nav class="nav">
            <div class="nav-container">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link">HOME</a></li>
                    <li><a href="categories.php" class="nav-link">CATEGORIES</a></li>
                    <li><a href="index.php?category=streetwear" class="nav-link">STREETWEAR</a></li>
                    <li><a href="index.php?category=footwear" class="nav-link">FOOTWEAR</a></li>
                    <li><a href="index.php?category=winter-wear" class="nav-link">WINTER WEAR</a></li>
                    <li><a href="blog.php" class="nav-link">BLOG</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Blog Hero Section -->
    <section class="blog-hero">
        <div class="blog-container">
            <h1>Urban Stories & Style</h1>
            <p>Dive into the world of streetwear culture, fashion trends, and style inspiration. Discover stories that shape urban fashion and get the latest insights from the streets.</p>
        </div>
    </section>

    <!-- Main Blog Content -->
    <div class="blog-container">
        <!-- Featured Posts Section -->
        <section class="featured-posts">
            <h2><i class="fas fa-star"></i> Featured Stories</h2>
            <div class="featured-grid">
                <?php foreach ($featured_posts as $post): ?>
                    <article class="featured-post">
                        <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <div class="featured-post-content">
                            <div class="featured-post-meta">
                                <span class="category-badge"><?php echo $post['category']; ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($post['date'])); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo $post['read_time']; ?> min read</span>
                            </div>
                            <h3><a href="blog-post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                            <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="read-more">Read Full Story <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Main Blog Grid -->
        <div class="blog-main">
            <!-- Blog Posts -->
            <main class="blog-content">
                <h2><i class="fas fa-newspaper"></i> Latest Articles</h2>
                <div class="blog-posts-grid">
                    <?php foreach ($blog_posts as $post): ?>
                        <article class="blog-post">
                            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <div class="blog-post-content">
                                <div class="blog-post-meta">
                                    <span class="category-badge"><?php echo $post['category']; ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo $post['author']; ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($post['date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo $post['read_time']; ?>m</span>
                                </div>
                                <h3><a href="blog-post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Search Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-search"></i> Search Articles</h3>
                    <div class="search-box">
                        <input type="text" placeholder="Search for articles, styles, trends..." id="blogSearch">
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <!-- Newsletter Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-envelope"></i> Stay Updated</h3>
                    <p style="margin-bottom: 20px; color: #666; font-size: 14px;">Get the latest streetwear trends and style tips delivered to your inbox.</p>
                    
                    <?php if ($newsletter_message === 'success'): ?>
                        <div class="newsletter-success">
                            <i class="fas fa-check-circle"></i> Thanks for subscribing! Welcome to the UrbanStitch community.
                        </div>
                    <?php elseif ($newsletter_message === 'error'): ?>
                        <div class="newsletter-error">
                            <i class="fas fa-exclamation-circle"></i> Please enter a valid email address.
                        </div>
                    <?php else: ?>
                        <form class="newsletter-form" method="POST">
                            <input type="email" name="newsletter_email" placeholder="Enter your email" required>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i> Subscribe Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-folder"></i> Categories</h3>
                    <div class="categories-list">
                        <?php foreach ($categories as $category): ?>
                            <a href="blog.php?category=<?php echo urlencode($category); ?>">
                                <i class="fas fa-chevron-right"></i> <?php echo $category; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Popular Tags Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-tags"></i> Popular Tags</h3>
                    <div class="tags-list">
                        <?php foreach ($popular_tags as $tag): ?>
                            <a href="blog.php?tag=<?php echo urlencode($tag); ?>">#<?php echo $tag; ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Social Follow Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-heart"></i> Follow Us</h3>
                    <p style="margin-bottom: 20px; color: #666; font-size: 14px;">Stay connected for daily style inspiration</p>
                    <div style="display: flex; gap: 10px;">
                        <a href="#" style="background: #1da1f2; color: white; padding: 10px; border-radius: 50%; text-decoration: none; transition: transform 0.3s ease;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="background: #e4405f; color: white; padding: 10px; border-radius: 50%; text-decoration: none; transition: transform 0.3s ease;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="background: #1877f2; color: white; padding: 10px; border-radius: 50%; text-decoration: none; transition: transform 0.3s ease;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="background: #ff0000; color: white; padding: 10px; border-radius: 50%; text-decoration: none; transition: transform 0.3s ease;">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Recent Posts Widget -->
                <div class="sidebar-widget">
                    <h3><i class="fas fa-clock"></i> Recent Posts</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php 
                        $recent_posts = array_slice($blog_posts, 0, 3);
                        foreach ($recent_posts as $post): 
                        ?>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <img src="<?php echo $post['featured_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <h4 style="margin: 0 0 5px 0; font-size: 14px; line-height: 1.3;">
                                        <a href="blog-post.php?id=<?php echo $post['id']; ?>" 
                                           style="color: #333; text-decoration: none; transition: color 0.3s ease;">
                                           <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h4>
                                    <div style="font-size: 12px; color: #666;">
                                        <i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($post['date'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Call-to-Action Section -->
        <section style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); 
                        padding: 60px 40px; border-radius: 20px; text-align: center; 
                        margin: 60px 0; color: white; position: relative; overflow: hidden;">
            <div style="position: relative; z-index: 1;">
                <h2 style="font-size: 2.5rem; margin-bottom: 20px; color: white;">
                    Ready to Elevate Your Style?
                </h2>
                <p style="font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
                    From reading about trends to wearing them. Explore our curated collection of premium streetwear pieces.
                </p>
                <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                    <a href="product.php" class="btn" style="background: linear-gradient(135deg, #00ff00, #0066cc); 
                       color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; 
                       font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shopping-bag"></i> Shop Collection
                    </a>
                    <a href="about.php" class="btn" style="border: 2px solid #00ff00; color: #00ff00; 
                       padding: 13px 30px; text-decoration: none; border-radius: 25px; 
                       font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px;"
                       onmouseover="this.style.background='#00ff00'; this.style.color='white';"
                       onmouseout="this.style.background='transparent'; this.style.color='#00ff00';">
                        <i class="fas fa-info-circle"></i> Our Story
                    </a>
                </div>
            </div>
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
                        background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 20\"><defs><radialGradient id=\"b\" cx=\"50\" cy=\"50\" r=\"50\"><stop offset=\"0\" stop-color=\"%2300ff00\" stop-opacity=\"0.1\"/><stop offset=\"1\" stop-color=\"%2300ff00\" stop-opacity=\"0\"/></radialGradient></defs><circle cx=\"20\" cy=\"10\" r=\"2\" fill=\"url(%23b)\"/><circle cx=\"50\" cy=\"15\" r=\"3\" fill=\"url(%23b)\"/><circle cx=\"80\" cy=\"8\" r=\"2\" fill=\"url(%23b)\"/></svg></div>
        </section>
    </div>

    <!-- Include your existing footer -->

    <script>
        // Blog search functionality
        document.getElementById('blogSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const blogPosts = document.querySelectorAll('.blog-post');
            
            blogPosts.forEach(post => {
                const title = post.querySelector('h3').textContent.toLowerCase();
                const excerpt = post.querySelector('p').textContent.toLowerCase();
                const category = post.querySelector('.category-badge').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || excerpt.includes(searchTerm) || category.includes(searchTerm)) {
                    post.style.display = 'grid';
                } else {
                    post.style.display = 'none';
                }
            });
        });

        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Social media hover effects
        document.querySelectorAll('.sidebar-widget a[style*="border-radius: 50%"]').forEach(socialLink => {
            socialLink.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1) rotate(5deg)';
            });
            
            socialLink.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Image lazy loading and error handling
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                this.alt = 'UrbanStitch Blog Image';
            });
            
            // Add loading animation
            img.addEventListener('load', function() {
                this.style.opacity = '0';
                this.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 100);
            });
        });

        // Newsletter form enhancement
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                const button = this.querySelector('button');
                const originalText = button.innerHTML;
                
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
                button.disabled = true;
                
                // Re-enable after form submission
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            });
        }

        // Reading progress indicator for longer posts
        function createReadingProgress() {
            const progressBar = document.createElement('div');
            progressBar.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 0%;
                height: 3px;
                background: linear-gradient(135deg, #00ff00, #0066cc);
                z-index: 9999;
                transition: width 0.1s ease;
            `;
            document.body.appendChild(progressBar);

            window.addEventListener('scroll', () => {
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight - windowHeight;
                const scrolled = window.scrollY;
                const progress = (scrolled / documentHeight) * 100;
                
                progressBar.style.width = Math.min(progress, 100) + '%';
            });
        }

        // Initialize reading progress
        createReadingProgress();

        // Category and tag filtering
        function initializeFiltering() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            const tag = urlParams.get('tag');
            
            if (category || tag) {
                const posts = document.querySelectorAll('.blog-post, .featured-post');
                posts.forEach(post => {
                    const postCategory = post.querySelector('.category-badge').textContent;
                    const postContent = post.textContent.toLowerCase();
                    
                    let shouldShow = true;
                    
                    if (category && postCategory !== category) {
                        shouldShow = false;
                    }
                    
                    if (tag && !postContent.includes(tag.toLowerCase())) {
                        shouldShow = false;
                    }
                    
                    post.style.display = shouldShow ? 'grid' : 'none';
                });
                
                // Update page title
                const heroTitle = document.querySelector('.blog-hero h1');
                if (category) {
                    heroTitle.textContent = `${category} Stories`;
                } else if (tag) {
                    heroTitle.textContent = `#${tag} Articles`;
                }
            }
        }

        // Initialize filtering on page load
        initializeFiltering();

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all blog posts for scroll animations
        document.querySelectorAll('.blog-post, .featured-post, .sidebar-widget').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Recent posts hover effects
        document.querySelectorAll('.sidebar-widget h4 a').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.color = '#00ff00';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.color = '#333';
            });
        });

        console.log('🎨 UrbanStitch Blog: Where street culture meets digital storytelling');
    </script>
</body>
</html>