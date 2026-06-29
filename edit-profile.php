<?php
session_start();
include '../config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission
if(isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_bio = trim($_POST['bio']);
    $new_mobile = trim($_POST['mobile']);

    // PFP upload handle
    $pfp_update_sql = "";
    if(isset($_FILES['pfp_file']) && $_FILES['pfp_file']['error'] == 0) {
        $file_ext = strtolower(pathinfo($_FILES['pfp_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','webp'];
        if(in_array($file_ext, $allowed)) {
            $new_name = 'pfp_' . $user_id . '_' . time() . '.' . $file_ext;
            if(move_uploaded_file($_FILES['pfp_file']['tmp_name'], '../UPLOADS/' . $new_name)) {
                $pfp_update_sql = ", profile_pic='UPLOADS/$new_name'";
            }
        } else {
            $error = "Invalid image format!";
        }
    }

    if(empty($error)) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET username=?, email=?, bio=?, mobile=? $pfp_update_sql WHERE id=?");
        $uid = (string)$user_id;
        mysqli_stmt_bind_param($stmt, "sssss", $new_username, $new_email, $new_bio, $new_mobile, $uid);
        if(mysqli_stmt_execute($stmt)) {
          $_SESSION['username'] = $new_username;
          header("Location: profile.php?msg=updated");
          exit;
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// Fetch current data
$stmt = mysqli_prepare($conn, "SELECT username, email, profile_pic, bio, mobile, role FROM users WHERE id=?");
$uid = (string)$user_id;
mysqli_stmt_bind_param($stmt, "s", $uid);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button, textarea { font-family: 'Inter', sans-serif !important; }

    .edit-container {
      max-width: 600px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .edit-card {
      background: white;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .edit-card h2 {
      color: #333;
      font-size: 1.6rem;
      font-weight: 600;
      margin: 0 0 30px 0;
    }

    /* PFP Preview Section */
    .pfp-edit-section {
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 32px;
      padding-bottom: 28px;
      border-bottom: 1.5px solid #f1f5f9;
    }

    .pfp-preview-wrap {
      position: relative;
      width: 90px;
      height: 90px;
      flex-shrink: 0;
    }

    .pfp-preview {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #C84B31;
    }

    .pfp-placeholder {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: #C84B31;
      color: white;
      font-size: 2.2rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      text-transform: uppercase;
      border: 3px solid #ffeed9;
    }

    .pfp-change-label {
      background: #C84B31;
      color: white;
      border: none;
      padding: 8px 18px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }

    .pfp-change-label:hover { background: #a83828; }

    /* Form Fields */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      font-size: 0.88rem;
      color: #555;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.95rem;
      color: #333;
      outline: none;
      transition: border-color 0.2s;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #C84B31;
    }

    .form-group textarea {
      height: 90px;
      resize: vertical;
    }

    .save-btn {
      width: 100%;
      background: #C84B31;
      color: white;
      border: none;
      padding: 14px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s;
    }

    .save-btn:hover { background: #a83828; }

    .cancel-link {
      display: block;
      text-align: center;
      margin-top: 14px;
      color: #888;
      font-size: 0.9rem;
      text-decoration: none;
    }

    .cancel-link:hover { color: #C84B31; }

    .success-msg {
      background: #f0fff4;
      color: #2f855a;
      padding: 12px 16px;
      border-radius: 8px;
      font-weight: 500;
      margin-bottom: 24px;
      text-align: center;
    }

    .error-msg {
      background: #fff5f5;
      color: #c53030;
      padding: 12px 16px;
      border-radius: 8px;
      font-weight: 500;
      margin-bottom: 24px;
      text-align: center;
    }
  </style>
</head>
<body style="background-color: #ffeed9; margin: 0;">

<nav>
  <div class="nav-top">
    <div class="logo">The Foodies<span>.com</span></div>
    <div class="nav-auth" style="display:flex; align-items:center; gap:12px;">
      <a href="profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:#C84B31; font-weight:600;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#C84B31; border:2px solid #ffeed9; box-shadow:0 2px 5px rgba(0,0,0,0.1); flex-shrink:0;">
          <?php if(!empty($user['profile_pic'])): ?>
            <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <span style="color:white; font-size:0.9rem; font-weight:600; text-transform:uppercase;"><?php echo htmlspecialchars($user['username'][0]); ?></span>
          <?php endif; ?>
        </div>
        <span>Hi, <?php echo htmlspecialchars($user['username']); ?></span>
      </a>

      <?php if($user['role'] == 'admin'):
        $pc = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM recipes WHERE status='pending'"));
      ?>
        <a href="../admin.php" class="btn-login" style="color:#C84B31; font-weight:600; position:relative; text-decoration:none;">
          Admin
          <?php if($pc > 0): ?>
            <span style="position:absolute; top:-8px; right:-12px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.65rem; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo $pc; ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>

      <?php
        $bell_unread_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_email=? AND is_read=0");
        mysqli_stmt_bind_param($bell_unread_stmt, "s", $user['email']);
        mysqli_stmt_execute($bell_unread_stmt);
        $bell_unread = mysqli_fetch_assoc(mysqli_stmt_get_result($bell_unread_stmt))['cnt'];
      ?>
      <a href="notifications.php" style="position:relative; text-decoration:none; font-size:1.2rem;" title="Notifications">
        🔔
        <?php if($bell_unread > 0): ?>
          <span style="position:absolute; top:-8px; right:-10px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.6rem; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo $bell_unread; ?></span>
        <?php endif; ?>
      </a>

      <a href="logout.php" class="btn-login">Logout</a>
    </div>
  </div>
  <div class="nav-bottom">
    <div class="search-bar" style="position:relative;">
      <input type="text" id="navbarSearchInput" name="search" autocomplete="off" placeholder="Search ingredients, recipes....">
      <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:#999; line-height:1; padding:0;">✕</button>
      <div id="searchSuggestionsBox" style="position:absolute; top:100%; left:0; width:100%; background:#ffffff; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.1); z-index:999; display:none; margin-top:5px; overflow:hidden; border:1px solid rgba(200,75,49,0.1);"></div>
    </div>
    <ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="recipes.php">Recipes</a></li>
      <li><a href="categories.php">Categories</a></li>
      <li><a href="saved.php">Saved</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </div>
</nav>

<main class="edit-container">
  <div class="edit-card">
    <h2>Edit Profile</h2>

    <?php if($success): ?>
      <div class="success-msg">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <!-- PFP Section -->
      <div class="pfp-edit-section">
        <div class="pfp-preview-wrap">
          <?php if(!empty($user['profile_pic'])): ?>
            <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" class="pfp-preview" id="pfpPreview" alt="PFP">
          <?php else: ?>
            <div class="pfp-placeholder" id="pfpPreview"><?php echo strtoupper($user['username'][0]); ?></div>
          <?php endif; ?>
        </div>
        <div>
          <p style="margin:0 0 8px 0; font-weight:500; color:#333;">Profile Picture</p>
          <p style="margin:0 0 12px 0; color:#888; font-size:0.85rem;">JPG, PNG, WEBP supported</p>
          <label for="pfpInput" class="pfp-change-label">Change Photo</label>
          <input type="file" id="pfpInput" name="pfp_file" accept="image/*" style="display:none;" onchange="previewPfp(this)">
        </div>
      </div>

      <!-- Fields -->
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
      </div>

      <div class="form-group">
        <label>Mobile Number</label>
        <input type="text" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" placeholder="+91 XXXXX XXXXX">
      </div>

      <div class="form-group">
        <label>Bio</label>
        <textarea name="bio" placeholder="Tell something about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
      </div>

      <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
      <a href="profile.php" class="cancel-link">← Cancel, go back to profile</a>
    </form>
  </div>
</main>

<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-logo">The Foodies<span>.com</span></div>
      <p class="footer-desc">Discover and share delicious recipes from around the world. Your kitchen, our community.</p>
    </div>
    <div>
      <p class="footer-heading">Quick Links</p>
      <ul class="footer-links">
        <li><a href="../index.php">Home</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="categories.php">Categories</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </div>
    <div>
      <p class="footer-heading">Connect</p>
      <ul class="footer-links">
        <li><a href="#">Instagram</a></li>
        <li><a href="#">YouTube</a></li>
        <li><a href="#">Pinterest</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    © 2026 TheFoodies.com — Made with ❤️ and hunger
  </div>
</footer>

<?php include 'cursor.php'; ?>

<script>
// PFP live preview
function previewPfp(input) {
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('pfpPreview');
            // Agar placeholder div hai toh img se replace karo
            if(preview.tagName === 'DIV') {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'pfp-preview';
                img.id = 'pfpPreview';
                preview.replaceWith(img);
            } else {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Apna updated search JS yahan paste karo
</script>

</body>
</html>