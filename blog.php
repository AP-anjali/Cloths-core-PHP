<?php
include('include/header.php');
require_once 'include/config.php';

$result = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Fashion Blog</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- CSS -->
    <style>
      

        h1 {
            text-align: center;
            font-size: 2.7em;
            margin: 40px 0 20px;
            animation: fadeInDown 1s ease;
            color: #E91E63;
        }

        .blog-container {
            max-width: 1200px;
            margin: auto;
            padding: 0 20px 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .blog-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeUp 0.8s ease forwards;
            opacity: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .blog-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 18px 35px rgba(0,0,0,0.15);
        }

        .blog-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .blog-card:hover img {
            transform: scale(1.03);
        }

        .blog-card h2 {
            padding: 20px 15px 10px;
            font-size: 1.4em;
            color: #E91E63;
        }

        .blog-card p {
            padding: 0 15px 15px;
            color: #444;
            font-size: 0.95em;
            line-height: 1.6;
        }

        .blog-card a {
            margin: 15px;
            padding: 12px;
            background: linear-gradient(135deg, #E91E63, #FF4081);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .blog-card a:hover {
            background: linear-gradient(135deg, #c2185b, #d81b60);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive fixes */
        @media (max-width: 500px) {
            .blog-card h2 {
                font-size: 1.2em;
            }
            .blog-card p {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <h1>🧥 Our Fashion Blog</h1>

    <div class="blog-container">
        <?php while($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="blog-card">
                <?php if (!empty($row['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                <?php endif; ?>
                <h2><?= htmlspecialchars($row['title']) ?></h2>
                <p><?= substr(htmlspecialchars($row['content']), 0, 150) ?>...</p>
                <a href="view_blog.php?id=<?= $row['id'] ?>">Read More</a>
            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>
