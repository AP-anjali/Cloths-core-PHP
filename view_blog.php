<?php
include('include/header.php');
require_once 'include/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch blog data
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$blog = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch multiple images
$imgStmt = $conn->prepare("SELECT image_path FROM blog_images WHERE blog_id = :id");
$imgStmt->bindParam(':id', $id, PDO::PARAM_INT);
$imgStmt->execute();
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($blog['title']) ?> | Blog</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&family=Roboto&display=swap" rel="stylesheet">

    <style>
   

        .blog-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .blog-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            color: #E91E63;
            text-align: center;
            margin-bottom: 25px;
        }

        .blog-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 25px;
        }

        .blog-gallery img {
            width: 100%;
            max-width: 250px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .blog-gallery img:hover {
            transform: scale(1.05);
        }

        .blog-content {
            font-size: 1.1em;
            line-height: 1.8;
            text-align: justify;
        }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            background: #E91E63;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .back-btn:hover {
            background: #c2185b;
        }

        @media (max-width: 768px) {
            .blog-title {
                font-size: 2em;
            }

            .blog-gallery {
                flex-direction: column;
                align-items: center;
            }

            .blog-gallery img {
                max-width: 90%;
                height: auto;
            }
        }
    </style>
</head>
<body>

<div class="blog-container">
    <h1 class="blog-title"><?= htmlspecialchars($blog['title']) ?></h1>

    <?php if (!empty($images)): ?>
    <div class="blog-gallery">
        <?php foreach ($images as $img): ?>
            <img src="uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="Blog Image">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="blog-content">
        <?= nl2br(htmlspecialchars($blog['content'])) ?>
    </div>

    <a href="blog.php" class="back-btn">← Back to Blog</a>
</div>

</body>
</html>
