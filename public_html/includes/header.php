<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set default page title if not defined
if (!isset($page_title)) {
    $page_title = 'EVSU-OCC Evaluation System';
}

// Set active page if not defined
if (!isset($active_page)) {
    $active_page = '';
}

// Determine if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - EVSU-OCC</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --evsu-red: #8B0000;
            --evsu-dark-red: #660000;
            --evsu-light-red: #D11111;
            --evsu-brown: #A52A2A;
            --evsu-gray: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
        }
        
        /* Header Styles */
        .evsu-top-bar {
            background-color: var(--evsu-dark-red);
            color: white;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        
        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .top-bar-links a:hover {
            color: #ffcc00;
            text-decoration: underline;
        }
        
        .evsu-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        
        .logo-container img {
            height: 70px;
            width: auto;
            margin-right: 15px;
        }
        
        .logo-text h1 {
            font-size: 1.8rem;
            color: var(--evsu-red);
            margin: 0;
            font-weight: 700;
        }
        
        .logo-text .subtitle {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        /* Navigation */
        .evsu-nav {
            background: linear-gradient(to right, var(--evsu-red), var(--evsu-brown));
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .navbar-nav .nav-link {
            color: white !important;
            padding: 15px 20px !important;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .navbar-nav .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 3px;
            background-color: white;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-item {
            padding: 10px 20px;
            color: #333;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: var(--evsu-red);
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            color: white;
            margin-left: 20px;
        }
        
        .user-info i {
            margin-right: 8px;
        }
        
        /* Responsive Navigation */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(to right, var(--evsu-light-red), var(--evsu-brown));
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        /* Content Container */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px 40px;
            flex: 1;
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: var(--evsu-red);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        /* Button Styles */
        .btn-evsu {
            background-color: var(--evsu-red);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-evsu:hover {
            background-color: var(--evsu-dark-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 0, 0, 0.2);
        }
        
        /* Alert Styles */
        .alert-evsu {
            background-color: rgba(139, 0, 0, 0.1);
            border-left: 4px solid var(--evsu-red);
            color: #333;
            border-radius: 4px;
        }
        
        /* Footer Styles */
        .evsu-footer {
            background-color: #333;
            color: white;
            padding: 40px 0 20px;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: #ccc;
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            color: #999;
            font-size: 0.9rem;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            
            .logo-container {
                margin-bottom: 15px;
            }
            
            .logo-container img {
                height: 60px;
            }
            
            .logo-text h1 {
                font-size: 1.5rem;
            }
            
            .page-header {
                padding: 40px 0;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .navbar-nav .nav-link {
                padding: 10px 15px !important;
            }
            
            .top-bar-container {
                flex-direction: column;
                text-align: center;
            }
            
            .top-bar-links {
                margin-top: 5px;
            }
            
            .top-bar-links a {
                margin: 0 10px;
            }
        }
        
        @media (max-width: 480px) {
            .logo-container img {
                height: 50px;
            }
            
            .logo-text h1 {
                font-size: 1.3rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .footer-links a {
                margin: 0 8px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="evsu-top-bar">
        <div class="top-bar-container">
            <div class="top-bar-text">
                <i class="fas fa-university"></i> Eastern Visayas State University - Ormoc City Campus
            </div>
            <div class="top-bar-links">
                <?php if ($is_logged_in): ?>
                    <span class="user-info-mobile">
                    <i class="fas fa-user"></i> Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
                    </span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="login.php?register"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="evsu-header">
        <div class="header-container">
            <a href="index.php" class="logo-container">
                <img src="images/EVSU_Official_Logo.png" alt="EVSU Logo" onerror="this.src='https://via.placeholder.com/70x70?text=EVSU'">
                <div class="logo-text">
                    <h1>EVSU-OCC</h1>
                    <div class="subtitle">Evaluation Survey System - Registrar's Office</div>
                </div>
            </a>
            
            <?php if ($is_logged_in): ?>
                <div class="user-info d-none d-md-block">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span>
                        <?php 
                        // Use fullname if available, otherwise username
                        if (!empty($_SESSION['fullname'])) {
                            echo htmlspecialchars($_SESSION['fullname']);
                        } elseif (!empty($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        } else {
                            echo 'User';
                        }
                        ?> 
                        (<?php echo htmlspecialchars(ucfirst($user_role)); ?>)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </header>


    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Page-specific header (only shown on content pages, not home) -->
        <?php if (!empty($page_title) && $active_page != 'home'): ?>
            <div class="page-header">
                <div class="container">
                    <h1><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php if (isset($page_subtitle)): ?>
                        <p><?php echo htmlspecialchars($page_subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="content-container">