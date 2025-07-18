<?php
require_once 'config.php';

// Page meta information
$page_title = "About UrbanStitch - Our Story & Mission";
$page_description = "Discover the story behind UrbanStitch - from street culture passion to premium streetwear brand. Learn about our mission, values, and commitment to authentic urban fashion.";
$page_keywords = "UrbanStitch story, streetwear brand, urban fashion, company mission, brand values, street culture";

// Team members data
$team_members = [
    [
        'name' => 'Marcus Rivera',
        'position' => 'Founder & Creative Director',
        'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
        'bio' => 'Started UrbanStitch from his bedroom in Brooklyn, combining his love for street art and fashion. 10+ years in the streetwear scene.',
        'social' => [
            'instagram' => '#',
            'twitter' => '#',
            'linkedin' => '#'
        ]
    ],
    [
        'name' => 'Sofia Chen',
        'position' => 'Head of Design',
        'image' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
        'bio' => 'Fashion school graduate turned streetwear designer. Brings technical expertise and fresh perspectives to every collection.',
        'social' => [
            'instagram' => '#',
            'twitter' => '#',
            'linkedin' => '#'
        ]
    ],
    [
        'name' => 'Jamal Washington',
        'position' => 'Brand Manager',
        'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
        'bio' => 'Former sneakerhead blogger who joined to help build authentic connections with the streetwear community worldwide.',
        'social' => [
            'instagram' => '#',
            'twitter' => '#',
            'linkedin' => '#'
        ]
    ],
    [
        'name' => 'Emma Thompson',
        'position' => 'Sustainability Lead',
        'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
        'bio' => 'Environmental advocate ensuring UrbanStitch leads the way in sustainable streetwear production and ethical practices.',
        'social' => [
            'instagram' => '#',
            'twitter' => '#',
            'linkedin' => '#'
        ]
    ]
];

// Company milestones
$milestones = [
    [
        'year' => '2019',
        'title' => 'The Beginning',
        'description' => 'Marcus starts UrbanStitch in his Brooklyn apartment, selling custom designs to local skaters and artists.'
    ],
    [
        'year' => '2020',
        'title' => 'First Collection',
        'description' => 'Launched our first official collection "Street Dreams" - 500 pieces sold out in 24 hours.'
    ],
    [
        'year' => '2021',
        'title' => 'Going Digital',
        'description' => 'Launched e-commerce platform and expanded to serve streetwear enthusiasts across the country.'
    ],
    [
        'year' => '2022',
        'title' => 'Sustainability Focus',
        'description' => 'Introduced eco-friendly materials and sustainable production processes across all product lines.'
    ],
    [
        'year' => '2023',
        'title' => 'Community Growth',
        'description' => 'Reached 100K+ community members and launched collaborative collections with local artists.'
    ],
    [
        'year' => '2024',
        'title' => 'Global Expansion',
        'description' => 'Expanded internationally and opened flagship stores in major urban centers worldwide.'
    ]
];

// Core values
$values = [
    [
        'icon' => 'fas fa-street-view',
        'title' => 'Authentic Street Culture',
        'description' => 'We stay true to our roots in street culture, supporting the communities and movements that inspire us.'
    ],
    [
        'icon' => 'fas fa-leaf',
        'title' => 'Sustainable Fashion',
        'description' => 'Committed to reducing our environmental impact through responsible sourcing and production methods.'
    ],
    [
        'icon' => 'fas fa-users',
        'title' => 'Community First',
        'description' => 'Our customers are family. We listen, engage, and create products that reflect their needs and style.'
    ],
    [
        'icon' => 'fas fa-star',
        'title' => 'Premium Quality',
        'description' => 'Every piece is crafted with attention to detail, using high-quality materials that stand the test of time.'
    ],
    [
        'icon' => 'fas fa-palette',
        'title' => 'Creative Expression',
        'description' => 'Fashion is art. We encourage individual expression and provide pieces that let people tell their story.'
    ],
    [
        'icon' => 'fas fa-handshake',
        'title' => 'Ethical Practices',
        'description' => 'Fair wages, safe working conditions, and transparent supply chains are non-negotiable for us.'
    ]
];

// Statistics
$stats = [
    [
        'number' => '150K+',
        'label' => 'Happy Customers',
        'icon' => 'fas fa-users'
    ],
    [
        'number' => '500+',
        'label' => 'Unique Designs',
        'icon' => 'fas fa-tshirt'
    ],
    [
        'number' => '25+',
        'label' => 'Countries Served',
        'icon' => 'fas fa-globe'
    ],
    [
        'number' => '98%',
        'label' => 'Customer Satisfaction',
        'icon' => 'fas fa-heart'
    ]
];
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
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>/about.php">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="<?php echo $_SERVER['HTTP_HOST']; ?>/assets/about-og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>/about.php">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">
    <meta property="twitter:image" content="<?php echo $_SERVER['HTTP_HOST']; ?>/assets/about-og-image.jpg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <style>
        /* About page specific styles */
        .about-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 100px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
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

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #00ff00, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1.3rem;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #00ff00;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto 3rem auto;
            line-height: 1.6;
        }

        .story-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 4rem;
        }

        .story-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .story-text h3 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .story-text h3::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #00ff00, #0066cc);
        }

        .story-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .story-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .story-image:hover img {
            transform: scale(1.05);
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .value-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            border-color: #00ff00;
        }

        .value-icon {
            background: linear-gradient(135deg, #00ff00, #0066cc);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            color: white;
            font-size: 2rem;
            transition: transform 0.3s ease;
        }

        .value-card:hover .value-icon {
            transform: rotate(10deg) scale(1.1);
        }

        .value-card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .value-card p {
            color: #666;
            line-height: 1.6;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #00ff00, #0066cc);
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
        }

        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 45%;
            position: relative;
            transition: transform 0.3s ease;
        }

        .timeline-content:hover {
            transform: scale(1.02);
        }

        .timeline-year {
            background: linear-gradient(135deg, #00ff00, #0066cc);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            width: 80px;
            text-align: center;
        }

        .timeline-content h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .timeline-content p {
            color: #666;
            line-height: 1.6;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }

        .team-image {
            position: relative;
            overflow: hidden;
        }

        .team-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .team-card:hover .team-image img {
            transform: scale(1.1);
        }

        .team-social {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .team-card:hover .team-social {
            opacity: 1;
        }

        .social-link {
            background: rgba(0, 255, 0, 0.9);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: rgba(0, 102, 204, 0.9);
            transform: scale(1.1);
        }

        .team-info {
            padding: 2rem;
        }

        .team-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .team-position {
            color: #00ff00;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-bio {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .cta-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 80px 0;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="b" cx="50" cy="50" r="50"><stop offset="0" stop-color="%2300ff00" stop-opacity="0.1"/><stop offset="1" stop-color="%2300ff00" stop-opacity="0"/></radialGradient></defs><circle cx="20" cy="10" r="2" fill="url(%23b)"/><circle cx="50" cy="15" r="3" fill="url(%23b)"/><circle cx="80" cy="8" r="2" fill="url(%23b)"/></svg>') repeat;
            animation: float 25s infinite linear;
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00ff00, #0066cc);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 255, 0, 0.3);
        }

        .btn-secondary {
            border: 2px solid #00ff00;
            color: #00ff00;
            padding: 13px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: transparent;
        }

        .btn-secondary:hover {
            background: #00ff00;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .story-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item {
                flex-direction: row !important;
                margin-left: 40px;
            }

            .timeline-content {
                width: calc(100% - 60px);
            }

            .timeline-year {
                left: 20px;
                transform: translateY(-50%);
            }

            .cta-content h2 {
                font-size: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header style="background: #1a1a2e; padding: 1rem 0; position: sticky; top: 0; z-index: 1000;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #00ff00; font-size: 1.5rem; font-weight: bold;">
                <a href="index.php" style="color: inherit; text-decoration: none;">UrbanStitch</a>
            </div>
            <nav style="display: flex; gap: 2rem;">
                <a href="index.php" style="color: white; text-decoration: none; transition: color 0.3s;">Home</a>
                <a href="products.php" style="color: white; text-decoration: none; transition: color 0.3s;">Products</a>
                <a href="blog.php" style="color: white; text-decoration: none; transition: color 0.3s;">Blog</a>
                <a href="about.php" style="color: #00ff00; text-decoration: none; transition: color 0.3s;">About</a>
                <a href="contact.php" style="color: white; text-decoration: none; transition: color 0.3s;">Contact</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="about-container">
            <div class="hero-content">
                <h1>Our Story</h1>
                <p>Born from the streets, crafted with passion. UrbanStitch is more than a brand – we're a movement that celebrates authentic street culture, sustainable fashion, and the power of individual expression.</p>
                
                <div class="hero-stats">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-item">
                            <i class="<?php echo $stat['icon']; ?>" style="font-size: 2rem; color: #00ff00; margin-bottom: 1rem;"></i>
                            <span class="stat-number"><?php echo $stat['number']; ?></span>
                            <span class="stat-label"><?php echo $stat['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="section">
        <div class="about-container">
            <h2 class="section-title">How It All Started</h2>
            <p class="section-subtitle">From a single design in a Brooklyn bedroom to a global streetwear movement</p>
            
            <div class="story-content">
                <div class="story-text">
                    <h3>The Vision</h3>
                    <p>It started in 2019 when Marcus Rivera, a graphic designer and skateboard enthusiast, couldn't find streetwear that truly represented his vision of urban culture. Frustrated with mass-produced designs that lacked soul, he decided to create something different.</p>
                    
                    <p>Working late nights in his Brooklyn apartment, Marcus designed pieces that told stories – clothing that spoke to the skaters, artists, musicians, and dreamers who make up the heart of street culture.</p>
                    
                    <p>What began as custom designs for friends quickly grew into something bigger. The first UrbanStitch piece wasn't just clothing; it was a statement of authenticity in a world of fast fashion.</p>
                </div>
                <div class="story-image">
                    <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="UrbanStitch founder working on designs">
                </div>
            </div>

            <div class="story-content">
                <div class="story-image">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="UrbanStitch design process">
                </div>
                <div class="story-text">
                    <h3>The Growth</h3>
                    <p>Word spread quickly through social media and street communities. What resonated wasn't just the designs, but the story behind them – real people, real culture, real passion.</p>
                    
                    <p>As demand grew, so did our team. We brought in Sofia Chen, a talented designer who shared our vision of elevating streetwear without losing its edge. Jamal Washington joined to help us stay connected to the communities that inspire us.</p>
                    
                    <p>Today, UrbanStitch reaches streetwear enthusiasts worldwide, but we've never forgotten our roots. Every design still starts with the question: "Does this represent real street culture?"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="section" style="background: #f8f9fa;">
        <div class="about-container">
            <h2 class="section-title">What We Stand For</h2>
            <p class="section-subtitle">Our values guide every decision, from design to delivery</p>
            
            <div class="values-grid">
                <?php foreach ($values as $value): ?>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="<?php echo $value['icon']; ?>"></i>
                        </div>
                        <h3><?php echo $value['title']; ?></h3>
                        <p><?php echo $value['description']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="section">
        <div class="about-container">
            <h2 class="section-title">Our Journey</h2>
            <p class="section-subtitle">Key milestones in the UrbanStitch story</p>
            
            <div class="timeline">
                <?php foreach ($milestones as $milestone): ?>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h3><?php echo $milestone['title']; ?></h3>
                            <p><?php echo $milestone['description']; ?></p>
                        </div>
                        <div class="timeline-year"><?php echo $milestone['year']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="section" style="background: #f8f9fa;">
        <div class="about-container">
            <h2 class="section-title">Meet the Team</h2>
            <p class="section-subtitle">The creative minds behind UrbanStitch</p>
            
            <div class="team-grid">
                <?php foreach ($team_members as $member): ?>
                    <div class="team-card">
                        <div class="team-image">
                            <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                            <div class="team-social">
                                <a href="<?php echo $member['social']['instagram']; ?>" class="social-link">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="<?php echo $member['social']['twitter']; ?>" class="social-link">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="<?php echo $member['social']['linkedin']; ?>" class="social-link">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h3><?php echo $member['name']; ?></h3>
                            <div class="team-position"><?php echo $member['position']; ?></div>
                            <p class="team-bio"><?php echo $member['bio']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Mission Statement Section -->
    <section class="section">
        <div class="about-container">
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <h2 class="section-title">Our Mission</h2>
                <div style="background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative;">
                    <div style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #00ff00, #0066cc); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-quote-left" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <p style="font-size: 1.3rem; line-height: 1.8; color: #333; margin-bottom: 1.5rem; font-style: italic;">
                        "To create authentic streetwear that empowers individual expression while building a sustainable future for fashion. We believe that great style shouldn't come at the cost of our planet or our values."
                    </p>
                    <div style="color: #00ff00; font-weight: 600; font-size: 1.1rem;">
                        — The UrbanStitch Team
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sustainability Section -->
    <section class="section" style="background: #f8f9fa;">
        <div class="about-container">
            <h2 class="section-title">Sustainable Future</h2>
            <p class="section-subtitle">Leading the streetwear industry toward environmental responsibility</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-recycle" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <h3 style="color: #333; margin-bottom: 1rem;">Eco-Friendly Materials</h3>
                    <p style="color: #666; line-height: 1.6;">We use organic cotton, recycled polyester, and innovative sustainable fabrics in all our collections.</p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-industry" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <h3 style="color: #333; margin-bottom: 1rem;">Clean Production</h3>
                    <p style="color: #666; line-height: 1.6;">Our manufacturing partners use renewable energy and water-saving technologies in their production processes.</p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-shipping-fast" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <h3 style="color: #333; margin-bottom: 1rem;">Carbon-Neutral Shipping</h3>
                    <p style="color: #666; line-height: 1.6;">We offset 100% of our shipping emissions and use recyclable packaging materials.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Section -->
    <section class="section">
        <div class="about-container">
            <h2 class="section-title">Community Impact</h2>
            <p class="section-subtitle">Giving back to the communities that inspire us</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div>
                    <h3 style="color: #333; font-size: 1.8rem; margin-bottom: 1.5rem;">Supporting Local Artists</h3>
                    <p style="color: #666; line-height: 1.8; margin-bottom: 1.5rem;">
                        We partner with local street artists, musicians, and creators to feature their work in our collections. 
                        Every collaboration ensures artists receive fair compensation and creative recognition.
                    </p>
                    <p style="color: #666; line-height: 1.8; margin-bottom: 2rem;">
                        Through our "Street Stories" program, we've worked with over 50 artists across 15 cities, 
                        helping them reach new audiences while keeping street culture authentic.
                    </p>
                    <a href="#" class="btn-primary">
                        <i class="fas fa-palette"></i> Learn About Our Collaborations
                    </a>
                </div>
                <div style="position: relative;">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Street artist collaboration" 
                         style="width: 100%; height: 300px; object-fit: cover; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                </div>
            </div>
        </div>
    </section>

    <!-- Recognition Section -->
    <section class="section" style="background: #f8f9fa;">
        <div class="about-container">
            <h2 class="section-title">Recognition & Awards</h2>
            <p class="section-subtitle">Honored to be recognized by industry leaders and communities</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                        <i class="fas fa-trophy" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 0.5rem;">Best Emerging Brand 2023</h4>
                    <p style="color: #666; font-size: 0.9rem;">Streetwear Fashion Awards</p>
                </div>
                
                <div style="text-align: center; padding: 2rem;">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                        <i class="fas fa-leaf" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 0.5rem;">Sustainability Leader</h4>
                    <p style="color: #666; font-size: 0.9rem;">Green Fashion Initiative</p>
                </div>
                
                <div style="text-align: center; padding: 2rem;">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                        <i class="fas fa-users" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 0.5rem;">Community Impact Award</h4>
                    <p style="color: #666; font-size: 0.9rem;">Urban Culture Foundation</p>
                </div>
                
                <div style="text-align: center; padding: 2rem;">
                    <div style="background: linear-gradient(135deg, #00ff00, #0066cc); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                        <i class="fas fa-star" style="color: white; font-size: 2rem;"></i>
                    </div>
                    <h4 style="color: #333; margin-bottom: 0.5rem;">Customer Choice Winner</h4>
                    <p style="color: #666; font-size: 0.9rem;">Fashion E-commerce Awards</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call-to-Action Section -->
    <section class="cta-section">
        <div class="about-container">
            <div class="cta-content">
                <h2>Join the Movement</h2>
                <p>Be part of the UrbanStitch community. Follow our journey, share your style, and help us build the future of sustainable streetwear.</p>
                
                <div class="cta-buttons">
                    <a href="products.php" class="btn-primary">
                        <i class="fas fa-shopping-bag"></i> Shop Our Collections
                    </a>
                    <a href="blog.php" class="btn-secondary">
                        <i class="fas fa-newspaper"></i> Read Our Stories
                    </a>
                    <a href="contact.php" class="btn-secondary">
                        <i class="fas fa-envelope"></i> Get In Touch
                    </a>
                </div>
                
                <div style="margin-top: 3rem; display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">@urbanstitch</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">
                            <i class="fab fa-twitter"></i>
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">@urbanstitch</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">@urbanstitch</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">
                            <i class="fab fa-youtube"></i>
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">UrbanStitch TV</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #1a1a2e; color: white; padding: 3rem 0 1rem 0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: #00ff00; margin-bottom: 1rem;">UrbanStitch</h3>
                    <p style="line-height: 1.6; margin-bottom: 1rem;">Premium streetwear for the modern urban lifestyle. Express your style with confidence.</p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: #00ff00; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: #00ff00; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: #00ff00; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: #00ff00; font-size: 1.2rem;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Quick Links</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="products.php" style="color: #ccc; text-decoration: none; transition: color 0.3s;">Products</a>
                        <a href="blog.php" style="color: #ccc; text-decoration: none; transition: color 0.3s;">Blog</a>
                        <a href="about.php" style="color: #ccc; text-decoration: none; transition: color 0.3s;">About Us</a>
                        <a href="contact.php" style="color: #ccc; text-decoration: none; transition: color 0.3s;">Contact</a>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Contact Info</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; color: #ccc;">
                        <p><i class="fas fa-envelope"></i> info@urbanstitch.com</p>
                        <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                        <p><i class="fas fa-map-marker-alt"></i> 123 Street Style Ave, Fashion District</p>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid #333; padding-top: 1rem; text-align: center; color: #666;">
                <p>&copy; <?php echo date('Y'); ?> UrbanStitch. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
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

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.about-hero');
            if (parallax) {
                const speed = scrolled * 0.5;
                parallax.style.transform = `translateY(${speed}px)`;
            }
        });

        // Intersection Observer for animations
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

        // Observe elements for scroll animations
        document.querySelectorAll('.value-card, .team-card, .timeline-item, .story-content').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(el);
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                if (element.textContent.includes('+')) {
                    element.textContent = Math.floor(current) + 'K+';
                } else if (element.textContent.includes('%')) {
                    element.textContent = Math.floor(current) + '%';
                } else {
                    element.textContent = Math.floor(current) + '+';
                }
            }, 20);
        }

        // Trigger counter animations when stats come into view
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const text = stat.textContent;
                        let target = parseInt(text.replace(/\D/g, ''));
                        
                        if (text.includes('K')) target = target;
                        else if (text.includes('%')) target = target;
                        
                        animateCounter(stat, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            statsObserver.observe(heroStats);
        }

        // Social media hover effects
        document.querySelectorAll('.social-link').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.2) rotate(5deg)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Timeline items stagger animation
        const timelineItems = document.querySelectorAll('.timeline-item');
        timelineItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.2}s`;
        });

        console.log('🎨 UrbanStitch About: Our story, your style, one community');
    </script>
</body>
</html>