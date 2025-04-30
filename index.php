<?php

/**
 * Typoria Blog Platform
 * Enhanced Home/Index Page with improved UI and responsive design
 * OPTIMIZED FOR MOBILE RESPONSIVENESS & VISUAL APPEAL
 */

// Include required files
require_once 'includes/functions.php';
require_once 'includes/theme.php';

// Get authentication details
$auth = check_auth();
$isLoggedIn = $auth['isLoggedIn'];
$user_id = $auth['user_id'];
$username = $auth['username'];

// Initialize database connection
$conn = get_db_connection();

// Get featured/trending posts
$trending_posts = get_trending_posts(6, 7);

// Get categories
$categories_sql = "SELECT c.*, COUNT(p.post_id) as post_count 
                   FROM categories c
                   LEFT JOIN posts p ON c.category_id = p.category_id AND p.status = 'approved'
                   GROUP BY c.category_id
                   ORDER BY post_count DESC
                   LIMIT 6";
$categories_result = $conn->query($categories_sql);

// Get total posts count
$total_posts_sql = "SELECT COUNT(*) as total FROM posts WHERE status = 'approved'";
$total_posts_result = $conn->query($total_posts_sql);
$total_posts = $total_posts_result->fetch_assoc()['total'];

// Get total users count
$total_users_sql = "SELECT COUNT(*) as total FROM users";
$total_users_result = $conn->query($total_users_sql);
$total_users = $total_users_result->fetch_assoc()['total'];

// Enhanced CSS for the homepage - OPTIMIZED FOR MOBILE & BEAUTY
$custom_css = "
    /* Variables */
    :root {
        --primary: " . $TYPORIA_COLORS['primary'] . ";
        --secondary: " . $TYPORIA_COLORS['secondary'] . ";
        --accent: " . $TYPORIA_COLORS['accent'] . ";
        --dark: #1f2937;
        --light: #f9fafb;
        --primary-rgb: " . hex_to_rgb($TYPORIA_COLORS['primary']) . ";
        --secondary-rgb: " . hex_to_rgb($TYPORIA_COLORS['secondary']) . ";
        --accent-rgb: " . hex_to_rgb($TYPORIA_COLORS['accent']) . ";
    }
    
    /* Global Style Enhancements */
    body {
        overflow-x: hidden;
        background-color: #f8f9fa;
        color: #333;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    
    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    /* Enhanced Animation */
    @keyframes shimmer {
        0% {
            background-position: -100% 0;
        }
        100% {
            background-position: 100% 0;
        }
    }
    
    @keyframes fadeUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    /* Improved Hero Section */
    .hero-section {
        position: relative;
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: -5rem;
    }
    
    .hero-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('assets/images/hero-bg.jpg');
        background-size: cover;
        background-position: center;
        filter: brightness(0.6) saturate(1.2) blur(2px);
        z-index: 0;
        transform: scale(1.05);
        transition: transform 15s ease-out, filter 1s ease;
    }
    
    .hero-section:hover .hero-background {
        transform: scale(1.1);
        filter: brightness(0.65) saturate(1.3) blur(2px);
    }
    
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.7), rgba(var(--secondary-rgb), 0.6));
        z-index: 1;
        opacity: 0.85;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        text-align: center;
        padding: 1.5rem;
        animation: fadeUp 1.2s ease-out;
    }
    
    .hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 1rem;
        line-height: 1.1;
        background: linear-gradient(to right, #ffffff, #f3f4f6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    
    .hero-title::after {
        content: '';
        position: absolute;
        bottom: -0.5rem;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(to right, rgba(var(--primary-rgb), 0.8), rgba(var(--secondary-rgb), 0.8));
        border-radius: 3px;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 1.75rem;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.5;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .hero-buttons {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .hero-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 9999px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        z-index: 1;
        transform: translateZ(0);
        width: 100%;
        max-width: 170px;
        backdrop-filter: blur(5px);
    }
    
    .hero-button.primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .hero-button.secondary {
        background: rgba(255, 255, 255, 0.9);
        color: var(--primary);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .hero-button:hover {
        transform: translateY(-5px) translateZ(0);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }
    
    .hero-button::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 200%;
        height: 100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.7s ease;
        z-index: -1;
    }
    
    .hero-button:hover::after {
        transform: translateX(50%);
    }
    
    /* Enhanced Stats Section */
    .stats-section {
        position: relative;
        z-index: 10;
        padding-top: 6rem;
        margin-bottom: 3rem;
    }
    
    .stats-container {
        backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.7);
    }
    
    .stats-card {
        background-color: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid rgba(243, 244, 246, 0.8);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 1rem;
        transform: translateY(0);
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(var(--primary-rgb), 0.1);
        border-color: rgba(var(--primary-rgb), 0.3);
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        width: 200%;
        height: 200%;
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.06) 0%, rgba(var(--secondary-rgb), 0.06) 100%);
        top: -50%;
        left: -50%;
        transform: rotate(-15deg);
        z-index: 0;
        transition: all 0.5s ease;
    }
    
    .stats-card:hover::before {
        transform: rotate(-5deg);
    }
    
    .stats-icon {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        margin-bottom: 1rem;
        box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
        transition: all 0.3s ease;
        float: left;
        margin-right: 1rem;
    }
    
    .stats-card:hover .stats-icon {
        transform: scale(1.05);
    }
    
    .stats-number {
        position: relative;
        z-index: 1;
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.25rem;
        display: inline-block;
    }
    
    .stats-label {
        position: relative;
        z-index: 1;
        font-size: 1rem;
        color: #4b5563;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .stats-description {
        font-size: 0.9rem;
        color: #6b7280;
        margin-top: 0.25rem;
        line-height: 1.4;
    }
    
    /* Enhanced Category Section */
    .category-section {
        padding: 2rem 0;
        position: relative;
        margin-bottom: 1rem;
    }
    
    .section-title {
        font-size: 1.75rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 2rem;
        color: #1f2937;
        position: relative;
        padding-bottom: 1rem;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        border-radius: 2px;
    }

    /* Optimized category grid for mobile with visual enhancements */
    .category-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .category-card {
        background-color: white;
        border-radius: 1rem;
        padding: 1.25rem 1rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid #f3f4f6;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        transform: translateY(0);
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(var(--primary-rgb), 0.1);
        border-color: rgba(var(--primary-rgb), 0.3);
    }
    
    .category-card::before {
        content: '';
        position: absolute;
        width: 300%;
        height: 300%;
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.05) 0%, rgba(var(--secondary-rgb), 0.02) 50%, transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 0;
        opacity: 0;
        transition: opacity 0.5s ease;
    }
    
    .category-card:hover::before {
        opacity: 1;
    }
    
    .category-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border-radius: 15px;
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));
        margin: 0 auto 1rem;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    .category-card:hover .category-icon {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.2), rgba(var(--secondary-rgb), 0.2));
        transform: scale(1.1) rotate(5deg);
    }
    
    .category-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        position: relative;
        z-index: 1;
    }
    
    .category-card:hover .category-title {
        color: var(--primary);
    }
    
    .category-count {
        font-size: 0.8rem;
        color: #6b7280;
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background-color: #f3f4f6;
        border-radius: 9999px;
        transition: all 0.3s ease;
        margin-top: auto;
        position: relative;
        z-index: 1;
    }
    
    .category-card:hover .category-count {
        background-color: rgba(var(--primary-rgb), 0.1);
        color: var(--primary);
    }
    
    .view-more {
        display: inline-flex;
        align-items: center;
        font-weight: 600;
        color: var(--primary);
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        background-color: rgba(var(--primary-rgb), 0.05);
        margin-top: 1.5rem;
    }
    
    .view-more svg {
        margin-left: 0.5rem;
        transition: transform 0.3s ease;
    }
    
    .view-more:hover {
        color: var(--secondary);
        background-color: rgba(var(--primary-rgb), 0.1);
    }
    
    .view-more:hover svg {
        transform: translateX(5px);
    }
    
    /* Enhanced Post Cards */
    .posts-section {
        position: relative;
        padding: 2rem 0;
        background-color: #f8f9fa;
    }
    
    .posts-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(circle at 20% 30%, rgba(var(--primary-rgb), 0.03) 0%, transparent 25%), 
                          radial-gradient(circle at 80% 70%, rgba(var(--secondary-rgb), 0.03) 0%, transparent 25%);
        z-index: 0;
    }
    
    .posts-container {
        position: relative;
        z-index: 1;
    }
    
    .post-card {
        background-color: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #f3f4f6;
        margin-bottom: 1.5rem;
        transform: translateY(0);
    }
    
    .post-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(var(--primary-rgb), 0.1);
        border-color: rgba(var(--primary-rgb), 0.2);
    }
    
    .post-image-container {
        position: relative;
        overflow: hidden;
        height: 180px;
    }
    
    .post-image {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .post-card:hover .post-image {
        transform: scale(1.05);
    }
    
    .post-category {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background-color: rgba(var(--primary-rgb), 0.95);
        color: white;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 1;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }
    
    .post-card:hover .post-category {
        background-color: var(--secondary);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .post-content {
        padding: 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .post-content::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: radial-gradient(circle at 90% 10%, rgba(var(--primary-rgb), 0.03) 0%, transparent 50%);
        z-index: 0;
        opacity: 0;
        transition: opacity 0.5s ease;
    }
    
    .post-card:hover .post-content::before {
        opacity: 1;
    }
    
    .post-date {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    
    .post-date svg {
        width: 0.8rem;
        height: 0.8rem;
        margin-right: 0.3rem;
    }
    
    .post-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.3s ease;
        flex-grow: 0;
        position: relative;
        z-index: 1;
    }
    
    .post-card:hover .post-title {
        color: var(--primary);
    }
    
    .post-excerpt {
        color: #6b7280;
        margin-bottom: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
        font-size: 0.9rem;
        flex-grow: 1;
        position: relative;
        z-index: 1;
    }
    
    .post-meta {
        font-size: 0.85rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid #f3f4f6;
        position: relative;
        z-index: 1;
    }
    
    .post-author {
        display: flex;
        align-items: center;
    }
    
    .post-avatar, .post-avatar-fallback {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 0.5rem;
        border: 2px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .post-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .post-avatar-fallback {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .post-author-name {
        font-weight: 600;
        color: #4b5563;
        transition: color 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }
    
    .post-card:hover .post-author-name {
        color: var(--primary);
    }
    
    .post-stats {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .post-stat {
        display: flex;
        align-items: center;
        color: #6b7280;
        transition: all 0.3s ease;
        font-size: 0.8rem;
    }
    
    .post-card:hover .post-stat {
        color: #4b5563;
    }
    
    .post-stat svg {
        width: 0.9rem;
        height: 0.9rem;
        margin-right: 0.3rem;
    }
    
    .post-stat.likes svg {
        color: #ef4444;
    }
    
    .post-stat.comments svg {
        color: #3b82f6;
    }
    
    /* View All Button - Enhanced */
    .view-all-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 600;
        border-radius: 9999px;
        box-shadow: 0 10px 25px rgba(var(--primary-rgb), 0.25);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        z-index: 1;
        margin-top: 1rem;
    }
    
    .view-all-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(var(--primary-rgb), 0.35);
    }
    
    .view-all-btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 200%;
        height: 100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.7s ease;
        z-index: -1;
    }
    
    .view-all-btn:hover::after {
        transform: translateX(50%);
    }
    
    /* Enhanced Features Section */
    .features-section {
        padding: 3rem 0;
        position: relative;
        background-color: white;
    }
    
    .features-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(circle at 10% 10%, rgba(var(--primary-rgb), 0.03) 0%, transparent 30%), 
                          radial-gradient(circle at 90% 90%, rgba(var(--secondary-rgb), 0.03) 0%, transparent 30%);
        z-index: 0;
    }
    
    .feature-card {
        background-color: white;
        border-radius: 1rem;
        padding: 2rem 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
        border: 1px solid #f3f4f6;
        display: flex;
        flex-direction: column;
        align-items: center;
        transform: translateY(0);
        z-index: 1;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(var(--primary-rgb), 0.1);
        border-color: rgba(var(--primary-rgb), 0.2);
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        width: 300%;
        height: 300%;
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.05) 0%, rgba(var(--secondary-rgb), 0.02) 50%, transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.5s ease;
    }
    
    .feature-card:hover::before {
        opacity: 1;
    }
    
    .feature-icon-wrapper {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 70px;
        height: 70px;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.1));
        margin-bottom: 1.5rem;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .feature-card:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.15), rgba(var(--secondary-rgb), 0.15));
    }
    
    .feature-icon {
        width: 35px;
        height: 35px;
        color: var(--primary);
        transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.1);
        color: var(--secondary);
    }
    
    .feature-title {
        position: relative;
        z-index: 1;
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
    }
    
    .feature-description {
        position: relative;
        z-index: 1;
        color: #6b7280;
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    /* Enhanced CTA Section */
    .cta-section {
        padding: 3rem 0;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        margin: 2rem 0;
    }
    
    .cta-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.2) 0%, transparent 20%),
        radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.2) 0%, transparent 20%);
    opacity: 0.3;
    z-index: 0;
}


    .cta-content {
        position: relative;
        z-index: 1;
        text-align: center;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }
    
    .cta-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: white;
        margin-bottom: 1rem;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .cta-description {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    .cta-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        background-color: white;
        color: var(--primary);
        font-weight: 600;
        border-radius: 9999px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        font-size: 1rem;
        position: relative;
        overflow: hidden;
        z-index: 1;
        width: 100%;
        max-width: 250px;
    }
    
    .cta-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }
    
    .cta-button::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 200%;
        height: 100%;
        background: linear-gradient(90deg, rgba(var(--primary-rgb), 0) 0%, rgba(var(--primary-rgb), 0.1) 50%, rgba(var(--primary-rgb), 0) 100%);
        transform: translateX(-100%);
        transition: transform 0.7s ease;
        z-index: -1;
    }
    
    .cta-button:hover::after {
        transform: translateX(50%);
    }
       
    /* Responsive Styles */
    @media (min-width: 640px) {
        .hero-title {
            font-size: 3rem;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .hero-button {
            width: auto;
            max-width: none;
        }
        
        .stats-card {
            margin-bottom: 0;
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
        }
        
        .stats-number {
            font-size: 2.5rem;
        }
        
        .stats-label {
            font-size: 1.1rem;
        }
        
        .section-title {
            font-size: 2rem;
            margin-bottom: 2.5rem;
        }
        
        .category-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .category-title {
            font-size: 1.1rem;
        }
        
        .post-title {
            font-size: 1.2rem;
        }
        
        .post-author-name {
            max-width: 120px;
        }
        
        .cta-button {
            width: auto;
            max-width: none;
        }
        
        .newsletter-form {
            flex-direction: row;
            border-radius: 9999px;
        }
        
        .newsletter-input {
            border-radius: 9999px 0 0 9999px;
        }
        
        .newsletter-button {
            width: auto;
            border-radius: 0 9999px 9999px 0;
        }
    }
    
    @media (min-width: 768px) {
        .hero-section {
            min-height: 75vh;
        }
        
        .hero-title {
            font-size: 3.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }
        
        .hero-button {
            padding: 0.9rem 2rem;
            font-size: 1.1rem;
        }
        
        .stats-section {
            margin-top: -4rem;
        }
        
        .category-grid {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
        }
        
        .category-title {
            font-size: 1.2rem;
        }
        
        .post-image-container {
            height: 200px;
        }
        
        .post-card {
            margin-bottom: 0;
        }
        
        .post-title {
            font-size: 1.25rem;
        }
        
        .post-excerpt {
            -webkit-line-clamp: 3;
        }
        
        .post-author-name {
            max-width: none;
        }
        
        .cta-title, .newsletter-title {
            font-size: 2.25rem;
        }
        
        .cta-description, .newsletter-description {
            font-size: 1.15rem;
        }
    }
    
    @media (min-width: 1024px) {
        .hero-section {
            min-height: 80vh;
        }
        
        .hero-title {
            font-size: 4rem;
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
        }
        
        .stats-section {
            margin-top: -5rem;
        }
        
        .section-title {
            font-size: 2.5rem;
        }
        
        .category-grid {
            grid-template-columns: repeat(6, 1fr);
        }
        
        .post-image-container {
            height: 220px;
        }
        
        .cta-section, .newsletter-section {
            padding: 5rem 0;
            margin: 4rem 0;
        }
        
        .cta-title, .newsletter-title {
            font-size: 2.5rem;
        }
    }
    
    @media (min-width: 1280px) {
        .hero-title {
            font-size: 4.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
        }
    }
";

// Add RGB variables for CSS
function hex_to_rgb($hex)
{
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

// Get some statistics for the stats section
$recent_posts_sql = "SELECT COUNT(*) as count FROM posts WHERE status = 'approved' AND date_time > DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_posts_result = $conn->query($recent_posts_sql);
$recent_posts_count = $recent_posts_result->fetch_assoc()['count'];

$engagement_sql = "SELECT COUNT(*) as count FROM (
                    SELECT like_id FROM likes 
                    UNION ALL 
                    SELECT comment_id FROM comments
                    ) as engagements";
$engagement_result = $conn->query($engagement_sql);
$engagement_count = $engagement_result->fetch_assoc()['count'];

// Generate HTML header
typoria_header("Home", $custom_css);
?>

<!-- Start of body content -->
<?php include 'navbar.php'; ?>

<!-- Enhanced Hero Section -->
<section class="hero-section">
    <div class="hero-background"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title">Welcome to Typoria</h1>
        <p class="hero-subtitle">A community where ideas flourish, stories come alive, and connections are made through the power of words.</p>

        <div class="hero-buttons">
            <?php if (!$isLoggedIn) : ?>
                <a href="register.php" class="hero-button primary">
                    Join Typoria
                </a>
                <a href="login.php" class="hero-button secondary">
                    Log In
                </a>
            <?php else : ?>
                <a href="create_post.php" class="hero-button primary">
                    Write a Post
                </a>
                <a href="search.php" class="hero-button secondary">
                    Explore
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Enhanced Stats Section - OPTIMIZED & BEAUTIFUL -->
<section class="stats-section">
    <div class="container mx-auto px-4">
        <div class="stats-container">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <div>
                        <div class="stats-number"><?php echo $total_posts; ?></div>
                        <div class="stats-label">Total Articles</div>
                        <p class="stats-description">Discover creative articles from our community.</p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="stats-number"><?php echo $total_users; ?></div>
                        <div class="stats-label">Active Members</div>
                        <p class="stats-description">Join our diverse community of writers and readers.</p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                    </div>
                    <div>
                        <div class="stats-number"><?php echo $engagement_count; ?></div>
                        <div class="stats-label">Interactions</div>
                        <p class="stats-description">Engage with content through likes and comments.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Categories - ENHANCED & BEAUTIFUL -->
<section class="category-section">
    <div class="container mx-auto px-4">
        <h2 class="section-title">Explore Categories</h2>

        <div class="category-grid">
            <?php
            // Reset result pointer
            if ($categories_result && $categories_result->num_rows > 0) {
                $categories_result->data_seek(0); // Reset pointer to the beginning
                $icons = ['book-open', 'code', 'camera', 'music', 'heart', 'globe', 'map', 'coffee'];
                $i = 0;

                while ($category = $categories_result->fetch_assoc()) {
                    $icon = $icons[$i % count($icons)];
                    $i++;

                    echo '
                    <a href="category.php?category_id=' . $category['category_id'] . '" class="category-card">
                        <div class="category-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . get_icon_path($icon) . '" />
                            </svg>
                        </div>
                        <h3 class="category-title">' . htmlspecialchars($category['category']) . '</h3>
                        <span class="category-count">' . $category['post_count'] . ' posts</span>
                    </a>';
                }
            }

            // Helper function to get SVG path for icons
            function get_icon_path($icon)
            {
                switch ($icon) {
                    case 'book-open':
                        return "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253";
                    case 'code':
                        return "M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4";
                    case 'camera':
                        return "M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z";
                    case 'music':
                        return "M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3";
                    case 'heart':
                        return "M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z";
                    case 'globe':
                        return "M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z";
                    case 'map':
                        return "M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7";
                    case 'coffee':
                        return "M3 3h18M3 10h18M3 17h12";
                    default:
                        return "M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z";
                }
            }
            ?>
        </div>

        <div class="text-center mt-4">
            <a href="categories.php" class="view-more">
                View All Categories
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Trending Posts - ENHANCED & BEAUTIFUL -->
<section class="posts-section py-10">
    <div class="container mx-auto px-4 posts-container">
        <h2 class="section-title">Trending Posts</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            if (count($trending_posts) > 0) {
                foreach ($trending_posts as $post) {
                    $post_image = !empty($post['image']) ? 'uploads/' . $post['image'] : 'assets/images/default-post.jpg';
                    
                    // Get author profile image
                    $author_image_sql = "SELECT profile_image FROM users WHERE user_id = ?";
                    $author_stmt = $conn->prepare($author_image_sql);
                    $author_stmt->bind_param("i", $post['user_id']);
                    $author_stmt->execute();
                    $author_result = $author_stmt->get_result();
                    $author_image = 'default.png';
                    
                    if ($author_result->num_rows > 0) {
                        $author_data = $author_result->fetch_assoc();
                        if (!empty($author_data['profile_image'])) {
                            $author_image = $author_data['profile_image'];
                        }
                    }
                    
                    $post_author_initial = strtoupper(substr($post['user_name'] ?? 'A', 0, 1));
                    $date_formatted = format_date($post['date_time'], false);

                    $excerpt = strip_tags($post['details']);
                    if (strlen($excerpt) > 120) { // Reduced excerpt length for mobile
                        $excerpt = substr($excerpt, 0, 120) . '...';
                    }

                    echo '
                    <a href="post_view.php?post_id=' . $post['post_id'] . '" class="post-card">
                        <div class="post-image-container">
                            <img src="' . $post_image . '" alt="' . htmlspecialchars($post['title']) . '" class="post-image">
                            <span class="post-category">' . htmlspecialchars($post['category']) . '</span>
                        </div>
                        <div class="post-content">
                            <div class="post-date">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                ' . $date_formatted . '
                            </div>
                            <h3 class="post-title">' . htmlspecialchars($post['title']) . '</h3>
                            <p class="post-excerpt">' . $excerpt . '</p>
                            
                            <div class="post-meta">
                                <div class="post-author">';
                                
                    // Display actual profile image or fallback to initial
                    if ($author_image != 'default.png') {
                        echo '<div class="post-avatar">
                                <img src="uploads/profiles/' . htmlspecialchars($author_image) . '" alt="' . htmlspecialchars($post['user_name']) . '">
                              </div>';
                    } else {
                        echo '<div class="post-avatar-fallback">' . $post_author_initial . '</div>';
                    }
                    
                    echo '      <span class="post-author-name">' . htmlspecialchars($post['user_name']) . '</span>
                                </div>
                                <div class="post-stats">
                                    <div class="post-stat likes">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                                        </svg>
                                        ' . $post['like_count'] . '
                                    </div>
                                    <div class="post-stat comments">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
                                        </svg>
                                        ' . $post['comment_count'] . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            } else {
                echo '<div class="col-span-3 p-6 bg-white rounded-xl shadow text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <p class="text-gray-600">No trending posts found at the moment.</p>
                    </div>';
            }
            ?>
        </div>

        <div class="text-center mt-6">
            <a href="search.php" class="view-all-btn">
                Explore All Posts
            </a>
        </div>
    </div>
</section>

<!-- Features Section - ENHANCED & BEAUTIFUL -->
<section class="features-section">
    <div class="container mx-auto px-4">
        <h2 class="section-title">Why Choose Typoria?</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" class="feature-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <h3 class="feature-title">Elegant Reading Experience</h3>
                <p class="feature-description">Enjoy a distraction-free reading experience with our clean, modern interface designed for maximum focus and engagement.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" class="feature-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="feature-title">Vibrant Community</h3>
                <p class="feature-description">Connect with writers and readers through comments, likes, and discussions. Build your audience and discover new perspectives.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" class="feature-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                </div>
                <h3 class="feature-title">Content Discovery</h3>
                <p class="feature-description">Find exactly what interests you with our smart categorization system. Explore trending topics or dive deep into niche subjects.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action - ENHANCED & BEAUTIFUL -->
<section class="cta-section">
    <div class="container mx-auto px-4">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Share Your Story?</h2>
            <p class="cta-description">Join our community of writers and readers today. Start sharing your thoughts, stories, and ideas with the world.</p>
            <?php if (!$isLoggedIn) : ?>
                <a href="register.php" class="cta-button">
                    Create Your Account
                </a>
            <?php else : ?>
                <a href="create_post.php" class="cta-button">
                    Write Your First Post
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Latest Posts - ENHANCED & BEAUTIFUL -->
<section class="posts-section py-10">
    <div class="container mx-auto px-4 posts-container">
        <h2 class="section-title">Latest Publications</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            // Get latest posts
            $latest_posts_sql = "SELECT p.*, u.name AS user_name, u.profile_image AS author_image, c.category, 
                                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS like_count,
                                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS comment_count
                                FROM posts p
                                JOIN users u ON p.user_id = u.user_id
                                JOIN categories c ON p.category_id = c.category_id
                                WHERE p.status = 'approved'
                                ORDER BY p.date_time DESC
                                LIMIT 6";

            $latest_posts_result = $conn->query($latest_posts_sql);

            if ($latest_posts_result && $latest_posts_result->num_rows > 0) {
                while ($post = $latest_posts_result->fetch_assoc()) {
                    $post_image = !empty($post['image']) ? 'uploads/' . $post['image'] : 'assets/images/default-post.jpg';
                    $post_author_initial = strtoupper(substr($post['user_name'] ?? 'A', 0, 1));
                    $date_formatted = format_date($post['date_time'], false);

                    // Truncate excerpt to shorter length for mobile
                    $excerpt = strip_tags($post['details']);
                    if (strlen($excerpt) > 120) {
                        $excerpt = substr($excerpt, 0, 120) . '...';
                    }

                    echo '
                    <a href="post_view.php?post_id=' . $post['post_id'] . '" class="post-card">
                        <div class="post-image-container">
                            <img src="' . $post_image . '" alt="' . htmlspecialchars($post['title']) . '" class="post-image">
                            <span class="post-category">' . htmlspecialchars($post['category']) . '</span>
                        </div>
                        <div class="post-content">
                            <div class="post-date">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                ' . $date_formatted . '
                            </div>
                            <h3 class="post-title">' . htmlspecialchars($post['title']) . '</h3>
                            <p class="post-excerpt">' . $excerpt . '</p>
                            
                            <div class="post-meta">
                                <div class="post-author">';
                            
                    // Display actual profile image or fallback to initial
                    if (!empty($post['author_image']) && $post['author_image'] != 'default.png') {
                        echo '<div class="post-avatar">
                                <img src="uploads/profiles/' . htmlspecialchars($post['author_image']) . '" alt="' . htmlspecialchars($post['user_name']) . '">
                              </div>';
                    } else {
                        echo '<div class="post-avatar-fallback">' . $post_author_initial . '</div>';
                    }
                    
                    echo '      <span class="post-author-name">' . htmlspecialchars($post['user_name']) . '</span>
                                </div>
                                <div class="post-stats">
                                    <div class="post-stat likes">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                                        </svg>
                                        ' . $post['like_count'] . '
                                    </div>
                                    <div class="post-stat comments">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
                                        </svg>
                                        ' . $post['comment_count'] . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>';
                }
            } else {
                echo '<div class="col-span-3 p-6 bg-white rounded-xl shadow text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <p class="text-gray-600">No posts found yet. Be the first to create content!</p>
                    </div>';
            }
            ?>
        </div>

        <div class="text-center mt-6">
            <a href="search.php" class="view-more">
                View More Posts
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>
</section>

<?php
// Generate footer
typoria_footer();
?>