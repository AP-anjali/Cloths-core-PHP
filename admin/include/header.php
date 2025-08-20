<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}
// Logout handling (add if needed)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Cloths Store</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
   <style>
.navbar .navbar-brand {
    position: relative;
    font-weight: 600;
    text-transform: capitalize;
    color: #fff !important;
    border-radius: 6px;
    padding: 0.35rem 0.75rem;
    /* Setup transition for multiple properties */
    transition: 
        box-shadow 0.3s ease, 
        transform 0.3s ease, 
        background-color 0.3s ease,
        color 0.3s ease;
    transform-origin: center center;
}

/* Hover and focus state: scale up + translate + shadow + background + color */
.navbar .navbar-brand:hover,
.navbar .navbar-brand:focus {
    color: #f8f9fa !important;
    background-color: rgba(255, 255, 255, 0.15);
    /* Translation on Y axis combined with scale */
    transform: translateY(-4px) scale(1.1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    z-index: 10;
    text-decoration: none;
}

/* Optional: add a subtle animation when losing hover/focus (smooth reverse) */
/* This is already handled by transition properties */

/* Keyframes for an optional 'entry' animation (for fade + slide up on page load) */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Apply fadeInUp animation when page loads (optional) */
.navbar .navbar-brand {
    animation: fadeInUp 0.8s ease forwards;
}

        /* Button hover */
        .btn-light {
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background-color: #f1f1f1;
            color: #0d6efd;
            box-shadow: 0 0 8px #0d6efdaa;
        }

        /* Animated stat card */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: scale(1.06);
            box-shadow: 0 8px 20px rgb(13 110 253 / 0.4);
        }
        .stat-counter {
            font-size: 3rem;
            font-weight: 800;
            color: #ffffffff;
            letter-spacing: 1.5px;
            margin-bottom: 0.5rem;
        }

        /* Animated entrance for stats */
        .stat-counter span {
            display: inline-block;
            opacity: 0;
            animation-name: counterFade;
            animation-fill-mode: forwards;
            animation-timing-function: cubic-bezier(0.65, 0, 0.35, 1);
        }
        @keyframes counterFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Navbar greeting */
        .navbar .greeting {
            font-weight: 500;
        }

        /* Animate nav links container */
        .nav-links-container {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #0d6efd transparent;
            gap: 0.6rem;
        }
        .nav-links-container::-webkit-scrollbar {
            height: 6px;
        }
        .nav-links-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .nav-links-container::-webkit-scrollbar-thumb {
            background-color: #0d6efd;
            border-radius: 3px;
        }

        /* Responsive tweaks */
        @media (max-width: 768px) {
            .nav-links-container a.navbar-brand {
                font-size: 0.9rem;
                padding: 0.3rem 0.5rem;
            }
            .stat-counter {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4 sticky-top">
    <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <a class="navbar-brand fs-4 fw-bold" href="dashboard.php">Cloths Admin Dashboard</a>
        <div class="d-flex align-items-center gap-3 flex-wrap nav-links-container">
            <span class="text-light greeting">Hi, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a class="navbar-brand" href="dashboard.php">Dashboard</a>
            <a class="navbar-brand" href="contact_messages.php">Messages</a>
            <a class="navbar-brand" href="blog.php">Blog</a>
            <a class="navbar-brand" href="coupons.php">Add Coupon</a>
            <a class="navbar-brand" href="admin_m.php">Manage Admin</a>
            <a class="navbar-brand" href="categories.php">Categories</a>
            <a class="navbar-brand" href="admin_products.php">Products</a>
            <a class="navbar-brand" href="order.php">Orders</a>
            <a class="navbar-brand" href="users.php">Users</a>
            <a class="navbar-brand" href="homepage.php">Homepage</a>
            <a class="btn btn-sm btn-light" href="dashboard.php?logout=1" onclick="return confirm('Logout?')">Logout</a>
        </div>
    </div>
</nav>



<!-- You can add bootstrap JS bundle for components -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Simple counter animation function
    function animateCounter(id, endValue, duration = 1500) {
        const el = document.getElementById(id);
        const span = el.querySelector("span");
        let start = 0;
        let range = endValue - start;
        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            let progress = timestamp - startTime;
            let current = Math.min(Math.floor(progress / duration * range), range);
            span.textContent = current + start;
            if(progress < duration) {
                requestAnimationFrame(step);
            } else {
                span.textContent = endValue;
            }
            // Fade in the number progressively
            span.style.opacity = Math.min(progress / duration, 1);
            span.style.transform = `translateY(${20 - (20 * (progress / duration))}px)`;
        }

        requestAnimationFrame(step);
    }

    // Replace with your dynamic data values from server or API
    const totalOrders = 523; 
    const totalUsers = 342;   
    const totalProducts = 123; 

    document.addEventListener("DOMContentLoaded", () => {
        animateCounter("ordersCount", totalOrders);
        animateCounter("usersCount", totalUsers);
        animateCounter("productsCount", totalProducts);
    });
</script>
</body>
</html>
