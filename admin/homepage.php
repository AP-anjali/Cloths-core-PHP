<?php

require_once 'include/config.php'; // PDO $conn connection
require_once 'include/header.php'; // Your existing header (Bootstrap, navbar, etc.)

// Protect page
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

function showAlert($msg, $type = 'success') {
    echo <<<HTML
<div class="alert alert-{$type} alert-dismissible fade show" role="alert">
    {$msg}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
HTML;
}

$errors = [];
$success = "";

// --- Image upload helper ---
function uploadImage($file, $uploadDir, $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp']){
    if ($file['error'] !== 0) {
        return ['error' => 'File upload error'];
    }
    $fileType = mime_content_type($file['tmp_name']);
    if(!in_array($fileType, $allowedTypes)) {
        return ['error' => 'Invalid file type'];
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . "." . $ext;
    $target = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['error' => 'Failed to move uploaded file'];
    }
    return ['path' => $target];
}

// ----------------------
// Handle POST Requests
// ----------------------

/*
POST actions:
 - add/edit category_banner
 - delete category_banner (GET)
 - add/edit home_slider
 - delete home_slider (GET)
 - edit site_settings values

We distinguish by presence of hidden input fields or GET parameters.
*/

// Base upload directories for images:
$bannerUploadDir = 'uploads/category_banners/';
$sliderUploadDir = 'uploads/home_sliders/';

// Ensure directories exist
if (!is_dir($bannerUploadDir)) mkdir($bannerUploadDir, 0755, true);
if (!is_dir($sliderUploadDir)) mkdir($sliderUploadDir, 0755, true);

// Handle Delete Requests (by GET)
if (isset($_GET['delete_banner']) && is_numeric($_GET['delete_banner'])) {
    $id = (int) $_GET['delete_banner'];
    // Delete DB record and related image file
    $stmt = $conn->prepare("SELECT image FROM category_banners WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if ($row['image'] && file_exists($row['image'])) unlink($row['image']);
        $stmt = $conn->prepare("DELETE FROM category_banners WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Category banner deleted successfully.";
        } else {
            $errors[] = "Failed to delete category banner.";
        }
    }
}

if (isset($_GET['delete_slider']) && is_numeric($_GET['delete_slider'])) {
    $id = (int) $_GET['delete_slider'];
    $stmt = $conn->prepare("SELECT main_image, bg_image, bottom_image FROM home_slider WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach (['main_image', 'bg_image', 'bottom_image'] as $imgField) {
            if ($row[$imgField] && file_exists($row[$imgField])) unlink($row[$imgField]);
        }
        $stmt = $conn->prepare("DELETE FROM home_slider WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Home slider deleted successfully.";
        } else {
            $errors[] = "Failed to delete home slider.";
        }
    }
}

// Handle add/edit category_banner
if (isset($_POST['save_banner'])) {
    $id = isset($_POST['banner_id']) && is_numeric($_POST['banner_id']) ? (int)$_POST['banner_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $link = trim($_POST['link'] ?? 'shop-sidebar.html');
    $position = $_POST['position'] ?? 'left';

    // Validate
    $validPositions = ['left','top-left','top-right','bottom'];
    if (!in_array($position, $validPositions)) {
        $errors[] = "Invalid banner position.";
    }

    // Handle optional image upload
    $uploadedImagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $res = uploadImage($_FILES['image'], $bannerUploadDir);
        if (isset($res['error'])) {
            $errors[] = "Banner image upload error: " . $res['error'];
        } else {
            $uploadedImagePath = $res['path'];
        }
    }

    if (!$errors) {
        if ($id) {
            // fetch old image if new uploaded to delete
            if ($uploadedImagePath) {
                $stmt = $conn->prepare("SELECT image FROM category_banners WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($old && $old['image'] && file_exists($old['image'])) unlink($old['image']);
            }
            $imageToSave = $uploadedImagePath ?? null;
            if (!$uploadedImagePath) {
                $stmt = $conn->prepare("SELECT image FROM category_banners WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $imageToSave = $row['image'] ?? null;
            }
            $stmt = $conn->prepare("UPDATE category_banners SET title = ?, subtitle = ?, link = ?, position = ?, image = ? WHERE id = ?");
            $stmt->execute([$title ?: null, $subtitle ?: null, $link ?: 'shop-sidebar.html', $position, $imageToSave, $id]);
            $success = "Category banner updated successfully.";
        } else {
            if (!$uploadedImagePath) {
                $errors[] = "Banner image is required for new banner.";
            } else {
                $stmt = $conn->prepare("INSERT INTO category_banners (title, subtitle, link, position, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title ?: null, $subtitle ?: null, $link ?: 'shop-sidebar.html', $position, $uploadedImagePath]);
                $success = "New category banner added successfully.";
            }
        }
    }
}

// Handle add/edit home_slider
if (isset($_POST['save_slider'])) {
    $id = isset($_POST['slider_id']) && is_numeric($_POST['slider_id']) ? (int)$_POST['slider_id'] : null;
    $sub_title = trim($_POST['sub_title'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '');

    $imagesToSave = ['main_image' => null, 'bg_image' => null, 'bottom_image' => null];
    $existingImages = ['main_image' => null, 'bg_image' => null, 'bottom_image' => null];

    if ($id) {
        // Fetch existing images to delete if replaced
        $stmt = $conn->prepare("SELECT main_image, bg_image, bottom_image FROM home_slider WHERE id = ?");
        $stmt->execute([$id]);
        $existingImages = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $imageFields = ['main_image', 'bg_image', 'bottom_image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $uploadErrors = [];

    foreach($imageFields as $field) {
        if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) {
            $res = uploadImage($_FILES[$field], $sliderUploadDir, $allowedTypes);
            if (isset($res['error'])) {
                $uploadErrors[] = "$field upload error: " . $res['error'];
            } else {
                // Delete old image if exists and new uploaded
                if ($id && !empty($existingImages[$field]) && file_exists($existingImages[$field])) {
                    unlink($existingImages[$field]);
                }
                $imagesToSave[$field] = $res['path'];
            }
        } elseif ($id) {
            // keep old image if no new upload
            $imagesToSave[$field] = $existingImages[$field] ?? null;
        }
    }

    if ($uploadErrors) {
        $errors = array_merge($errors, $uploadErrors);
    }

    if (!$errors) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE home_slider SET main_image = ?, bg_image = ?, bottom_image = ?, sub_title = ?, title = ?, description = ?, button_link = ?, button_text = ? WHERE id = ?");
            $stmt->execute([
                $imagesToSave['main_image'], $imagesToSave['bg_image'], $imagesToSave['bottom_image'],
                $sub_title ?: null, $title ?: null, $description ?: null,
                $button_link ?: null, $button_text ?: null, $id
            ]);
            $success = "Home slider updated successfully.";
        } else {
            // For new slider, all 3 images are mandatory
            if (!$imagesToSave['main_image'] || !$imagesToSave['bg_image'] || !$imagesToSave['bottom_image']) {
                $errors[] = "All 3 slider images (main, bg, bottom) are required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO home_slider (main_image, bg_image, bottom_image, sub_title, title, description, button_link, button_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $imagesToSave['main_image'], $imagesToSave['bg_image'], $imagesToSave['bottom_image'],
                    $sub_title ?: null, $title ?: null, $description ?: null,
                    $button_link ?: null, $button_text ?: null
                ]);
                $success = "New home slider added successfully.";
            }
        }
    }
}

// Handle site settings update
if (isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([trim($value), $key]);
    }
    $success = "Site settings updated successfully.";
}

// -- Fetch data for display --

$categoryBanners = $conn->query("SELECT * FROM category_banners ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$homeSliders = $conn->query("SELECT * FROM home_slider ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$siteSettings = $conn->query("SELECT * FROM site_settings ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// For editing banners and sliders (GET param)
$editingBanner = null;
$editingSlider = null;

if (isset($_GET['edit_banner']) && is_numeric($_GET['edit_banner'])) {
    $id = (int)$_GET['edit_banner'];
    $stmt = $conn->prepare("SELECT * FROM category_banners WHERE id = ?");
    $stmt->execute([$id]);
    $editingBanner = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['edit_slider']) && is_numeric($_GET['edit_slider'])) {
    $id = (int)$_GET['edit_slider'];
    $stmt = $conn->prepare("SELECT * FROM home_slider WHERE id = ?");
    $stmt->execute([$id]);
    $editingSlider = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<div class="container">
    <h1 class="mb-3">Home Page Management</h1>

    <?php
    if ($errors) {
        foreach ($errors as $e) {
            showAlert(htmlspecialchars($e), 'danger');
        }
    }
    if ($success) showAlert(htmlspecialchars($success), 'success');
    ?>

    <ul class="nav nav-tabs mb-4" id="homeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bannersTab" type="button" role="tab" aria-controls="bannersTab" aria-selected="true">Category Banners</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#slidersTab" type="button" role="tab" aria-controls="slidersTab" aria-selected="false">Home Sliders</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settingsTab" type="button" role="tab" aria-controls="settingsTab" aria-selected="false">Site Settings</button>
        </li>
    </ul>

    <div class="tab-content" id="homeTabsContent">
        <!-- Category Banners -->
        <div class="tab-pane fade show active" id="bannersTab" role="tabpanel" aria-labelledby="banners-tab">

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <?= $editingBanner ? "Edit Banner ID #{$editingBanner['id']}" : "Add New Category Banner" ?>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="banner_id" value="<?= $editingBanner['id'] ?? '' ?>">

                        <div class="col-md-6">
                            <label for="banner_image" class="form-label">Banner Image <?= $editingBanner ? '(Upload to replace; optional)' : '(Required)' ?></label>
                            <input type="file" name="image" class="form-control" <?= !$editingBanner ? 'required' : '' ?> accept="image/*">
                            <?php if ($editingBanner && !empty($editingBanner['image']) && file_exists($editingBanner['image'])): ?>
                                <img src="<?= htmlspecialchars($editingBanner['image']) ?>" alt="Banner Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editingBanner['title'] ?? '') ?>" maxlength="100">
                        </div>

                        <div class="col-md-3">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($editingBanner['subtitle'] ?? '') ?>" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="link" class="form-label">Link</label>
                            <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($editingBanner['link'] ?? 'shop-sidebar.html') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="position" class="form-label">Position</label>
                            <select name="position" class="form-select" required>
                                <?php
                                $positions = ['left'=>'Left','top-left'=>'Top Left','top-right'=>'Top Right','bottom'=>'Bottom'];
                                foreach ($positions as $key => $label):
                                    $sel = (isset($editingBanner['position']) && $editingBanner['position'] == $key) ? 'selected' : '';
                                ?>
                                <option value="<?= $key ?>" <?= $sel ?>><?= $label ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <?php if ($editingBanner): ?>
                                <a href="admin_homepage.php#bannersTab" class="btn btn-secondary me-2">Cancel Edit</a>
                            <?php endif; ?>

                            <button type="submit" name="save_banner" class="btn btn-primary">
                                <?= $editingBanner ? "Update Banner" : "Add Banner" ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Category Banners List (<?= count($categoryBanners) ?>)</div>
                <div class="card-body table-responsive p-0" style="max-height: 450px; overflow-y:auto;">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-secondary text-center">
                            <tr>
                                <th>ID</th><th>Image</th><th>Title</th><th>Subtitle</th><th>Link</th><th>Position</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$categoryBanners): ?>
                            <tr><td colspan="7" class="text-center text-muted">No banners found.</td></tr>
                        <?php else: foreach ($categoryBanners as $banner): ?>
                            <tr class="text-center align-middle">
                                <td><?= $banner['id'] ?></td>
                                <td>
                                    <?php if ($banner['image'] && file_exists($banner['image'])): ?>
                                        <img src="<?= htmlspecialchars($banner['image']) ?>" alt="Banner" style="width:120px;object-fit:contain;">
                                    <?php else: ?>
                                        <small class="text-muted">No image</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($banner['title'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($banner['subtitle'] ?? '-') ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($banner['link']) ?>" target="_blank"><?= htmlspecialchars($banner['link']) ?></a>
                                </td>
                                <td><?= ucfirst(str_replace('-', ' ', $banner['position'])) ?></td>
                                <td>
                                    <a href="?edit_banner=<?= $banner['id'] ?>#bannersTab" class="btn btn-sm btn-warning me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="?delete_banner=<?= $banner['id'] ?>" onclick="return confirm('Delete banner #<?= $banner['id'] ?>?')" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Home Sliders -->
        <div class="tab-pane fade" id="slidersTab" role="tabpanel" aria-labelledby="sliders-tab">

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <?= $editingSlider ? "Edit Home Slider ID #{$editingSlider['id']}" : "Add New Home Slider" ?>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="slider_id" value="<?= $editingSlider['id'] ?? '' ?>">

                        <?php 
                        $imageFields = ['main_image'=>'Main Image', 'bg_image'=>'Background Image', 'bottom_image'=>'Bottom Image'];
                        foreach ($imageFields as $field => $label):
                        ?>
                        <div class="col-md-4">
                            <label for="<?= $field ?>" class="form-label"><?= $label ?> <?= $editingSlider ? '(Upload to replace; optional)' : '(Required)' ?></label>
                            <input type="file" name="<?= $field ?>" class="form-control" <?= !$editingSlider ? 'required' : '' ?> accept="image/*">
                            <?php if ($editingSlider && !empty($editingSlider[$field]) && file_exists($editingSlider[$field])): ?>
                                <img src="<?= htmlspecialchars($editingSlider[$field]) ?>" alt="<?= $label ?>" class="img-thumbnail mt-2" style="max-width: 200px;">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="col-md-6">
                            <label for="sub_title" class="form-label">Sub Title</label>
                            <input type="text" name="sub_title" class="form-control" value="<?= htmlspecialchars($editingSlider['sub_title'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editingSlider['title'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($editingSlider['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="button_link" class="form-label">Button Link</label>
                            <input type="text" name="button_link" class="form-control" value="<?= htmlspecialchars($editingSlider['button_link'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="button_text" class="form-label">Button Text</label>
                            <input type="text" name="button_text" class="form-control" value="<?= htmlspecialchars($editingSlider['button_text'] ?? '') ?>" maxlength="50">
                        </div>

                        <div class="col-12 text-end mt-3">
                            <?php if ($editingSlider): ?>
                                <a href="admin_homepage.php#slidersTab" class="btn btn-secondary me-2">Cancel Edit</a>
                            <?php endif; ?>

                            <button type="submit" name="save_slider" class="btn btn-primary">
                                <?= $editingSlider ? "Update Slider" : "Add Slider" ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Home Sliders List (<?= count($homeSliders) ?>)</div>
                <div class="card-body table-responsive p-0" style="max-height: 450px; overflow-y:auto;">
                    <table class="table table-striped table-hover align-middle mb-0 text-center">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Main Image</th>
                                <th>Bg Image</th>
                                <th>Bottom Image</th>
                                <th>Sub Title</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Button Link</th>
                                <th>Button Text</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$homeSliders): ?>
                            <tr><td colspan="10" class="text-center text-muted">No sliders found.</td></tr>
                        <?php else: foreach ($homeSliders as $slider): ?>
                            <tr class="align-middle">
                                <td><?= $slider['id'] ?></td>
                                <?php foreach ($imageFields as $field => $label): ?>
                                    <td>
                                        <?php if ($slider[$field] && file_exists($slider[$field])): ?>
                                            <img src="<?= htmlspecialchars($slider[$field]) ?>" alt="<?= $label ?>" style="width:100px;object-fit:contain;">
                                        <?php else: ?>
                                            <small class="text-muted">No image</small>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td><?= htmlspecialchars($slider['sub_title'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($slider['title'] ?? '-') ?></td>
                                <td style="max-width: 140px; white-space: pre-wrap;"><?= htmlspecialchars($slider['description'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($slider['button_link'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($slider['button_text'] ?? '-') ?></td>
                                <td>
                                    <a href="?edit_slider=<?= $slider['id'] ?>#slidersTab" class="btn btn-sm btn-warning me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="?delete_slider=<?= $slider['id'] ?>" onclick="return confirm('Delete slider #<?= $slider['id'] ?>?')" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Site Settings -->
        <div class="tab-pane fade" id="settingsTab" role="tabpanel" aria-labelledby="settings-tab">
            
            <form method="post" class="mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light fw-bold">Site Settings</div>
                    <div class="card-body">
                        <?php if (!$siteSettings): ?>
                            <p>No site settings found.</p>
                        <?php else: ?>
                            <?php foreach ($siteSettings as $setting): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="setting_<?= htmlspecialchars($setting['setting_key']); ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))); ?></label>
                                    <textarea class="form-control" id="setting_<?= htmlspecialchars($setting['setting_key']); ?>" name="settings[<?= htmlspecialchars($setting['setting_key']); ?>]" rows="2"><?= htmlspecialchars($setting['setting_value']); ?></textarea>
                                    <small class="text-muted">You may use HTML tags here.</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" name="save_settings" class="btn btn-primary">Update Settings</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>

<!-- Bootstrap Icons CDN for icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

<?php require_once 'include/footer.php'; ?>
