<?php
require_once 'include/config.php'; // Adjust path if your config file is elsewhere
require_once 'include/header.php'; // Your header containing navbar & CSS

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper: Show alert messages
function showAlert($message, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

// Initialize variables
$errors = [];
$success = '';
$editing_post = null;

// Handle Delete blog post & related images
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $postId = (int)$_GET['delete'];

    // Delete images from folder and database
    $stmt = $conn->prepare("SELECT image_path FROM blog_images WHERE blog_id = ?");
    $stmt->execute([$postId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $img) {
        $file = __DIR__ . '/../uploads/blog_images/' . basename($img['image_path']);
        if (file_exists($file)) unlink($file);
    }

    $conn->beginTransaction();
    $del_img_stmt = $conn->prepare("DELETE FROM blog_images WHERE blog_id = ?");
    $del_img_stmt->execute([$postId]);

    $del_post_stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $del_post_stmt->execute([$postId]);
    $conn->commit();

    $success = "Blog post #$postId and its images deleted successfully.";
}

// Handle Edit: Fetch post data and images
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$edit_id]);
    $editing_post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editing_post) {
        $img_stmt = $conn->prepare("SELECT * FROM blog_images WHERE blog_id = ?");
        $img_stmt->execute([$editing_post['id']]);
        $editing_post['images'] = $img_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $errors[] = "Blog post not found.";
    }
}

// Handle POST for Create or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;

    // Validate inputs
    if (!$title) $errors[] = "Title is required.";
    if (!$content) $errors[] = "Content is required.";

    // Handle main featured image upload (optional)
    $featured_image_path = $editing_post['image'] ?? null;
    if (!empty($_FILES['featured_image']['name'])) {
        $file = $_FILES['featured_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if ($file['error'] === 0 && in_array($file['type'], $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('feature_', true) . '.' . $ext;
            $target = __DIR__ . '/../uploads/blog_images/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // Delete old featured image if updating
                if ($featured_image_path) {
                    $oldFile = __DIR__ . '/../uploads/blog_images/' . basename($featured_image_path);
                    if (file_exists($oldFile)) unlink($oldFile);
                }
                $featured_image_path = 'uploads/blog_images/' . $filename;
            } else {
                $errors[] = "Failed to upload featured image.";
            }
        } else {
            $errors[] = "Invalid featured image type.";
        }
    }

    if (!$errors) {
        try {
            if ($post_id) {
                // Update existing post
                $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, content = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $content, $featured_image_path, $post_id]);
                $success = "Post #$post_id updated.";
            } else {
                // Insert new post
                $stmt = $conn->prepare("INSERT INTO blog_posts (title, content, image) VALUES (?, ?, ?)");
                $stmt->execute([$title, $content, $featured_image_path]);
                $post_id = $conn->lastInsertId();
                $success = "New post created.";
            }

            // Handle multiple images upload (optional)
            if (!empty($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $upload_dir = __DIR__ . '/../uploads/blog_images/';
                for ($i=0; $i < count($_FILES['images']['name']); $i++) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $type = $_FILES['images']['type'][$i];
                    $error = $_FILES['images']['error'][$i];
                    if ($error === 0 && in_array($type, $allowed_types)) {
                        $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                        $filename = uniqid("img_{$post_id}_", true) . '.' . $ext;
                        $target = $upload_dir . $filename;
                        if (move_uploaded_file($tmp_name, $target)) {
                            $imgstmt = $conn->prepare("INSERT INTO blog_images (blog_id, image_path) VALUES (?, ?)");
                            $imgstmt->execute([$post_id, 'uploads/blog_images/' . $filename]);
                        }
                    }
                }
            }

            // Refresh data if editing
            if ($post_id) {
                header("Location: admin_blog.php?edit=$post_id&success=" . urlencode($success));
                exit;
            }
        } catch(Exception $e) {
            $errors[] = "DB Error: " . $e->getMessage();
        }
    }
}

// Handle delete single blog image (AJAX or GET param for simplicity)
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image'])) {
    $imgid = (int)$_GET['delete_image'];
    $stmt = $conn->prepare("SELECT image_path, blog_id FROM blog_images WHERE id = ?");
    $stmt->execute([$imgid]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($img) {
        $filePath = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($filePath)) unlink($filePath);
        $del = $conn->prepare("DELETE FROM blog_images WHERE id = ?");
        $del->execute([$imgid]);
        header("Location: admin_blog.php?edit=" . $img['blog_id'] . "&success=" . urlencode("Image deleted."));
        exit;
    }
}

// Fetch all blog posts for list (with count of images)
$posts_stmt = $conn->query("
    SELECT bp.*, 
        (SELECT COUNT(*) FROM blog_images WHERE blog_id = bp.id) AS images_count
    FROM blog_posts bp ORDER BY bp.created_at DESC
");
$blog_posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Show success from GET param after redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

?>

<div class="container">
    <h1 class="mb-4">Manage Blog Posts</h1>

    <?php
    if ($errors) {
        foreach($errors as $err) {
            showAlert(htmlspecialchars($err), 'danger');
        }
    }

    if ($success) {
        showAlert($success, 'success');
    }
    ?>

    <!-- Blog Post Form (Create / Edit) -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header">
            <strong><?php echo $editing_post ? "Edit Blog Post #{$editing_post['id']}" : "Add New Blog Post"; ?></strong>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="post_id" value="<?php echo $editing_post['id'] ?? ''; ?>">
                <div class="col-md-12">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required
                        value="<?php echo htmlspecialchars($editing_post['title'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label for="content" class="form-label">Content *</label>
                    <textarea id="content" name="content" rows="6" class="form-control" required><?php echo htmlspecialchars($editing_post['content'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Featured Image<?php echo $editing_post && $editing_post['image'] ? ' (Current below)' : ''; ?></label>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <?php if ($editing_post && $editing_post['image']): ?>
                        <div class="mt-2">
                            <img src="../<?php echo htmlspecialchars($editing_post['image']); ?>" alt="Featured" class="img-thumbnail" style="max-width:200px;">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Additional Images (You can upload multiple)</label>
                    <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                    <?php if ($editing_post && !empty($editing_post['images'])): ?>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php foreach ($editing_post['images'] as $img): ?>
                                <div class="position-relative" style="width:120px;">
                                    <img src="../<?php echo htmlspecialchars($img['image_path']); ?>"
                                         class="img-thumbnail" style="width:100%;height:auto;">
                                    <a href="?delete_image=<?php echo $img['id']; ?>" 
                                       onclick="return confirm('Delete this image?');"
                                       class="btn btn-danger btn-sm position-absolute top-0 end-0"
                                       style="font-size:12px; line-height:12px; padding:2px 5px;">
                                       &times;
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 text-end">
                    <?php if ($editing_post): ?>
                        <a href="admin_blog.php" class="btn btn-secondary me-2">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing_post ? 'Update Post' : 'Create Post'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- List all blog posts -->
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            Blog Posts (<?php echo count($blog_posts); ?>)
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Created At</th>
                        <th>Featured Image</th>
                        <th>Images Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$blog_posts): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No blog posts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blog_posts as $post): ?>
                        <tr>
                            <td><?php echo (int)$post['id']; ?></td>
                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                            <td><?php echo date("d/M/Y H:i", strtotime($post['created_at'])); ?></td>
                            <td>
                                <?php if ($post['image']): ?>
                                    <img src="../<?php echo htmlspecialchars($post['image']); ?>" alt="Featured" style="width:60px; height:auto; border-radius:4px;">
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int)$post['images_count']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $post['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $post['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete Blog Post #<?php echo $post['id']; ?>? This will also delete all its images.');"
                                   class="btn btn-sm btn-danger" title="Delete">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<?php require_once 'include/footer.php'; // Your footer with JS and closing tags ?>
