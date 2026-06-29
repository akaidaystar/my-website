<?php
session_start();
include 'config.php';

// Only admin access
if(!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit;
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='{$_SESSION['user_id']}'"));
if($user['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Feature recipe handler
if(isset($_POST['feature_recipe'])) {
    $fid = (int)$_POST['recipe_id'];
    mysqli_query($conn, "UPDATE recipes SET is_featured=0"); // pehle sab unfeature
    mysqli_query($conn, "UPDATE recipes SET is_featured=1 WHERE id='$fid'");
    header("Location: admin.php?msg=featured");
    exit;
}

// Fetch featured recipe
$featured = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM recipes WHERE is_featured=1 LIMIT 1"));

// Fetch all approved for dropdown
$all_recipes = mysqli_query($conn, "SELECT id, title FROM recipes WHERE status='approved' ORDER BY title ASC");

if(isset($_POST['add_recipe'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $ingredients = $_POST['ingredients'];
    $steps = $_POST['steps'];
    $category = $_POST['category'];
    $image = '';
if(isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
    $file_ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','webp'];
    if(in_array($file_ext, $allowed)) {
        $new_name = time() . '_' . uniqid() . '.' . $file_ext;
        if(move_uploaded_file($_FILES['image_file']['tmp_name'], 'UPLOADS/' . $new_name)) {
            $image = 'UPLOADS/' . $new_name;
        }
    }
}
if(empty($image) && !empty($_POST['image_url'])) {
    $image = trim($_POST['image_url']);
}
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];
    $carbs = $_POST['carbs'];
    $fat = $_POST['fat'];

    $stmt = mysqli_prepare($conn, "INSERT INTO recipes (title, description, ingredients, steps, category, image, calories, protein, carbs, fat, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
    mysqli_stmt_bind_param($stmt, "ssssssssss", $title, $description, $ingredients, $steps, $category, $image, $calories, $protein, $carbs, $fat);

  if(mysqli_stmt_execute($stmt)) {
        $success = "Recipe added successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

if(isset($_POST['approve_recipe'])) {
    $rid = $_POST['recipe_id'];
    mysqli_query($conn, "UPDATE recipes SET status='approved' WHERE id='$rid'");
    $success = "Recipe approved!";
}

if(isset($_POST['delete_recipe'])) {
    $rid = $_POST['recipe_id'];
    mysqli_query($conn, "DELETE FROM recipes WHERE id='$rid'");
    $success = "Recipe deleted!";
}

// Toggle Role
if(isset($_POST['toggle_role'])) {
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['current_role'] == 'admin' ? 'user' : 'admin';
    mysqli_query($conn, "UPDATE users SET role='$new_role' WHERE id='$uid'");
    header("Location: admin.php?msg=role_updated#users");
    exit;
}

// Delete User
if(isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$uid'");
    header("Location: admin.php?msg=user_deleted#users");
    exit;
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");

$recipes = mysqli_query($conn, "SELECT * FROM recipes WHERE status='approved'");
$pending = mysqli_query($conn, "SELECT * FROM recipes WHERE status='pending'");
// Contacts fetch
$contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY id DESC");
// Reply handler
if(isset($_POST['reply_contact'])) {
    $contact_id = (int)$_POST['contact_id'];
    $reply = mysqli_real_escape_string($conn, $_POST['reply_message']);
    
    // Contact fetch karo email ke liye
    $contact_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, name FROM contacts WHERE id='$contact_id'"));
    
    mysqli_query($conn, "UPDATE contacts SET reply='$reply', replied_at=NOW() WHERE id='$contact_id'");
    
    // Notification insert
    $notif_msg = "Your message has been replied to by admin.";
    mysqli_query($conn, "INSERT INTO notifications (user_email, message) VALUES ('{$contact_row['email']}', '$notif_msg')");
    
    $success = "Reply sent!";
    $contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY id DESC");
}

// Delete contact
if(isset($_POST['delete_contact'])) {
    $contact_id = (int)$_POST['contact_id'];
    mysqli_query($conn, "DELETE FROM contacts WHERE id='$contact_id'");
    $success = "Message deleted!";
    $contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - TheFoodies</title>
  <link rel="stylesheet" href="CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    .admin-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    .admin-form { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 40px; }
    .admin-form h2 { margin-bottom: 24px; color: #C84B31; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.9rem; outline: none; }
    .form-group textarea { height: 100px; resize: vertical; }
    .form-group input:focus, .form-group textarea:focus { border-color: #C84B31; }
    .submit-btn { background: #C84B31; color: #ffeed9; padding: 12px 32px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; width: 100%; }
    .submit-btn:hover { background: #a83828; }
    .success { color: green; margin-bottom: 16px; font-weight: 500; }
    .error { color: red; margin-bottom: 16px; }
    .recipe-list { margin-top: 20px; }
    .recipe-item { background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
    .recipe-item h4 { color: #333; margin-bottom: 4px; }
    .recipe-item span { font-size: 0.8rem; color: #888; }
    .hint { font-size: 0.78rem; color: #999; margin-top: 4px; }
    .approve-btn { background: #2d8a4e; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin-right: 8px; }
    .approve-btn:hover { background: #1f6b3a; }
    .delete-btn { background: #e53e3e; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
    .delete-btn:hover { background: #c53030; }
    .pending-section { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 40px; }
    .pending-section h2 { color: #C84B31; margin-bottom: 20px; }
    .pending-badge { background: #fff3cd; color: #856404; font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; margin-left: 8px; }
  </style>
</head>
<body>

<nav>
  <div class="nav-top">
    <div class="logo">The Foodies<span>.com</span></div>
    
    <div class="nav-auth" style="display: flex; align-items: center; gap: 12px;">
      <a href="pages/profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #C84B31; font-weight: 600;">
        <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #C84B31; border: 2px solid #ffeed9; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex-shrink: 0;">
          <?php if(!empty($user['profile_pic'])): ?>
              <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="User PFP">
          <?php else: ?>
              <span style="color: white; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">
                <?php echo htmlspecialchars($user['username'][0]); ?>
              </span>
          <?php endif; ?>
        </div>
        <span>Hi, <?php echo htmlspecialchars($user['username']); ?></span>
      </a>

      <?php
      $bell_unread = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_email='" . mysqli_real_escape_string($conn, $user['email']) . "' AND is_read=0"))['cnt'];
      ?>
      <a href="pages/notifications.php" style="position:relative; text-decoration:none; font-size:1.2rem;" title="Notifications">
        🔔
        <?php if($bell_unread > 0): ?>
          <span style="position:absolute; top:-8px; right:-10px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.6rem; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo $bell_unread; ?></span>
        <?php endif; ?>
      </a>

      <a href="admin.php" class="btn-login active" style="color:#C84B31; font-weight:600;">Admin</a>
      <a href="pages/logout.php" class="btn-login" style="margin-left: 5px;">Logout</a>
    </div>
  </div>
  
  <div class="nav-bottom">
    <div class="search-bar" style="position: relative;">
      <input type="text" id="navbarSearchInput" name="search" autocomplete="off" placeholder="Search ingredients, recipes....">
      <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:#999; line-height:1; padding:0;">✕</button>
      <div id="searchSuggestionsBox" style="position: absolute; top: 100%; left: 0; width: 100%; background: #ffffff; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); z-index: 999; display: none; margin-top: 5px; overflow: hidden; border: 1px solid rgba(200, 75, 49, 0.1);"></div>
    </div>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="pages/recipes.php">Recipes</a></li>
      <li><a href="pages/categories.php">Categories</a></li>
      <li><a href="pages/saved.php">Saved</a></li>
      <li><a href="pages/contact.php">Contact</a></li>
    </ul>
  </div>
</nav>

<div class="admin-container">

<!-- FEATURED RECIPE SECTION -->
<div class="pending-section" style="margin-bottom:40px;">
  <h2>⭐ Dish of the Month
    <?php if($featured): ?>
      <span class="pending-badge" style="background:#fff3cd; color:#856404;">
        Currently: <?php echo htmlspecialchars($featured['title']); ?>
      </span>
    <?php else: ?>
      <span class="pending-badge" style="background:#ffe0e0; color:#c53030;">None selected</span>
    <?php endif; ?>
  </h2>

  <?php if(isset($_GET['msg']) && $_GET['msg'] == 'featured'): ?>
    <p class="success">✅ Featured recipe updated!</p>
  <?php endif; ?>

  <!-- Current featured preview -->
  <?php if($featured): ?>
  <div class="recipe-item" style="margin-bottom:20px; border-left:4px solid #C84B31;">
    <div style="display:flex; align-items:center; gap:16px;">
      <?php if(!empty($featured['image'])): ?>
        <img src="<?php echo htmlspecialchars($featured['image']); ?>" style="width:80px; height:60px; object-fit:cover; border-radius:8px;">
      <?php endif; ?>
      <div>
        <h4 style="margin:0 0 4px 0;"><?php echo htmlspecialchars($featured['title']); ?></h4>
        <p style="margin:0; color:#666; font-size:0.85rem;"><?php echo htmlspecialchars(substr($featured['description'], 0, 100)); ?>...</p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Dropdown to select -->
  <form method="POST">
    <div style="display:flex; gap:12px; align-items:center;">
      <select name="recipe_id" style="flex:1; padding:10px 14px; border:1.5px solid #ddd; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; outline:none;">
        <option value="">-- Select a recipe --</option>
        <?php while($r = mysqli_fetch_assoc($all_recipes)): ?>
          <option value="<?php echo $r['id']; ?>" <?php echo ($featured && $featured['id'] == $r['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($r['title']); ?>
          </option>
        <?php endwhile; ?>
      </select>
      <button type="submit" name="feature_recipe" class="approve-btn" style="padding:10px 24px; white-space:nowrap;">
        ⭐ Set as Featured
      </button>
    </div>
  </form>
</div>

<!-- CONTACTS SECTION -->
<div class="pending-section" style="margin-bottom: 40px;">
  <h2>
    Contact Messages
    <span class="pending-badge" style="background:#e8f4fd; color:#2b6cb0;">
      <?php 
        $total_contacts = mysqli_num_rows($contacts);
        $unreplied = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM contacts WHERE reply IS NULL"));
        echo $unreplied . " unreplied";
      ?>
    </span>
  </h2>

  <?php if($total_contacts == 0): ?>
    <p style="color:#888">No messages yet!</p>
  <?php endif; ?>

  <?php 
  // Reset pointer
  mysqli_data_seek($contacts, 0);
  while($msg = mysqli_fetch_assoc($contacts)): 
    $is_replied = !empty($msg['reply']);
  ?>
  <div class="recipe-item" style="flex-direction: column; align-items: flex-start; gap: 12px; padding: 20px; border-left: 4px solid <?php echo $is_replied ? '#2d8a4e' : '#C84B31'; ?>;">
    
    <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
      <div>
        <h4 style="margin:0 0 4px 0;"><?php echo htmlspecialchars($msg['name']); ?> 
          <span style="font-weight:400; color:#888; font-size:0.85rem;">&lt;<?php echo htmlspecialchars($msg['email']); ?>&gt;</span>
        </h4>
        <p style="margin:0; color:#555; font-size:0.9rem;"><?php echo htmlspecialchars($msg['message']); ?></p>
        <?php if($is_replied): ?>
          <p style="margin:6px 0 0 0; color:#2d8a4e; font-size:0.82rem;">
            ✅ Replied on <?php echo date('d M Y', strtotime($msg['replied_at'])); ?>
          </p>
        <?php endif; ?>
      </div>
      
      <form method="POST" style="display:inline; margin-left:16px;">
        <input type="hidden" name="contact_id" value="<?php echo $msg['id']; ?>">
        <button type="submit" name="delete_contact" class="delete-btn">🗑️</button>
      </form>
    </div>

    <?php if($is_replied): ?>
      <div style="background:#f0fff4; border-radius:8px; padding:12px 16px; width:100%; box-sizing:border-box;">
        <p style="margin:0; font-size:0.85rem; color:#2d8a4e; font-weight:600;">Your reply:</p>
        <p style="margin:4px 0 0 0; color:#444; font-size:0.9rem;"><?php echo htmlspecialchars($msg['reply']); ?></p>
      </div>
    <?php endif; ?>

    <form method="POST" style="width:100%;">
      <input type="hidden" name="contact_id" value="<?php echo $msg['id']; ?>">
      <textarea name="reply_message" placeholder="<?php echo $is_replied ? 'Edit reply...' : 'Write a reply...'; ?>" 
        style="width:100%; padding:10px; border:1.5px solid #ddd; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; resize:vertical; min-height:70px; box-sizing:border-box;"><?php echo $is_replied ? htmlspecialchars($msg['reply']) : ''; ?></textarea>
      <button type="submit" name="reply_contact" class="approve-btn" style="margin-top:8px;">
        <?php echo $is_replied ? '✏️ Update Reply' : '📨 Send Reply'; ?>
      </button>
    </form>

  </div>
  <?php endwhile; ?>
</div>

  <!-- Pending Recipes -->
  <div class="pending-section">
    <h2>Pending Recipes <span class="pending-badge"><?php echo mysqli_num_rows($pending); ?> pending</span></h2>
    <?php if(mysqli_num_rows($pending) == 0): ?>
      <p style="color:#888">No pending recipes!</p>
    <?php endif; ?>
    <?php while($row = mysqli_fetch_assoc($pending)): ?>
    <div class="recipe-item">
  <div>
    <h4><?php echo $row['title']; ?></h4>
    <span><?php echo $row['category']; ?></span>
    <br>
    <small style="color:#666"><?php echo substr($row['description'], 0, 80); ?>...</small>
  </div>
  <div style="display:flex; gap:8px; align-items:center;">
    <a href="pages/recipe-detail.php?id=<?php echo $row['id']; ?>" target="_blank" 
       style="background:#666; color:white; border:none; padding:8px 16px; border-radius:6px; font-size:0.85rem; text-decoration:none;">
       Preview
    </a>
    <form method="POST" action="" style="display:inline">
      <input type="hidden" name="recipe_id" value="<?php echo $row['id']; ?>">
      <button type="submit" name="approve_recipe" class="approve-btn">Approve</button>
    </form>
    <form method="POST" action="" style="display:inline">
      <input type="hidden" name="recipe_id" value="<?php echo $row['id']; ?>">
      <button type="submit" name="delete_recipe" class="delete-btn">Delete</button>
    </form>
  </div>
</div>
    <?php endwhile; ?>
  </div>

  <!-- Add Recipe Form -->
<div class="admin-form">
  <h2>Add New Recipe</h2>
  <?php if(isset($success) && !isset($user_success)) echo "<p class='success'>✅ $success</p>"; ?>
  <?php if(isset($error)) echo "<p class='error'>❌ $error</p>"; ?>

  <?php
  // Existing keywords fetch
  $existing_keywords = [];
  $kw_query = mysqli_query($conn, "SELECT category FROM recipes WHERE category IS NOT NULL AND category != ''");
  while($kw_row = mysqli_fetch_assoc($kw_query)) {
      $exploded = explode(',', $kw_row['category']);
      foreach($exploded as $tag) {
          $trimmed = strtolower(trim($tag));
          if(!empty($trimmed) && !in_array($trimmed, $existing_keywords)) {
              $existing_keywords[] = $trimmed;
          }
      }
  }
  sort($existing_keywords);
  ?>

  <style>
    .keyword-suggestions-container {
      display: flex; flex-wrap: wrap; gap: 8px;
      margin-top: 8px; margin-bottom: 4px;
    }
    .keyword-badge {
      background: #f1f5f9; color: #475569;
      border: 1px solid #cbd5e1; padding: 6px 14px;
      border-radius: 20px; font-size: 0.85rem;
      font-weight: 500; cursor: pointer;
      user-select: none; transition: all 0.2s ease;
    }
    .keyword-badge:hover { background: #e2e8f0; border-color: #94a3b8; }
    .keyword-badge.active {
      background: #ffeed9 !important; color: #C84B31 !important;
      border-color: #C84B31 !important; font-weight: 600;
    }
  </style>

  <form method="POST" action="" enctype="multipart/form-data">
    <div class="form-group">
      <label>Recipe Name</label>
      <input type="text" name="title" placeholder="e.g. Rasgulla" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" placeholder="Short description..."></textarea>
    </div>
    <div class="form-group">
      <label>Ingredients</label>
      <textarea name="ingredients" placeholder="1 cup sugar, 2 cups water..."></textarea>
    </div>
    <div class="form-group">
      <label>Steps</label>
      <textarea name="steps" placeholder="Step 1: ..."></textarea>
    </div>

    <div class="form-group">
      <label>Categories / Keywords</label>
      <input type="text" id="adminCategoryInput" name="category" placeholder="indian, sweet dish, dessert" required>
      <p class="hint">Separate with commas. Custom keywords bhi type kar sakte ho!</p>
      <div class="keyword-suggestions-container">
        <?php foreach($existing_keywords as $keyword): ?>
          <span class="keyword-badge" onclick="toggleAdminKeyword(this, '<?php echo htmlspecialchars(addslashes($keyword)); ?>')">
            # <?php echo htmlspecialchars($keyword); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group" style="padding:18px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px;">
      <label style="font-weight:600; border-bottom:1px solid #e2e8f0; padding-bottom:6px; display:block; margin-bottom:12px;">📸 Recipe Image</label>
      <div style="margin-bottom:14px;">
        <label style="font-size:0.85rem; font-weight:500; color:#4a5568; display:block; margin-bottom:6px;">Option A: Upload file</label>
        <input type="file" name="image_file" accept="image/*">
      </div>
      <div style="text-align:center; margin:10px 0; color:#a0aec0; font-size:0.85rem; font-weight:600;">— OR —</div>
      <div>
        <label style="font-size:0.85rem; font-weight:500; color:#4a5568; display:block; margin-bottom:6px;">Option B: Image URL</label>
        <input type="text" name="image_url" placeholder="https://example.com/image.jpg" style="width:100%; padding:10px; border:1.5px solid #cbd5e0; border-radius:6px; font-size:0.9rem; box-sizing:border-box;">
      </div>
    </div>

    <div class="form-group">
      <label>Nutritional Info (Optional)</label>
      <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px;">
        <input type="text" name="calories" placeholder="Calories">
        <input type="text" name="protein" placeholder="Protein">
        <input type="text" name="carbs" placeholder="Carbs">
        <input type="text" name="fat" placeholder="Fat">
      </div>
    </div>

    <button type="submit" name="add_recipe" class="submit-btn">Add Recipe</button>
  </form>
</div>

  <!-- Added Recipes List -->
  <div class="admin-form">
    <h2>Added Recipes</h2>
    <div class="recipe-list">
      <?php while($row = mysqli_fetch_assoc($recipes)): ?>
      <div class="recipe-item">
        <div>
          <h4><?php echo $row['title']; ?></h4>
          <span><?php echo $row['category']; ?></span>
        </div>
        <form method="POST" action="">
          <input type="hidden" name="recipe_id" value="<?php echo $row['id']; ?>">
          <button type="submit" name="delete_recipe" class="delete-btn">🗑️ Delete</button>
        </form>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- MANAGE USERS SECTION -->
  <div class="admin-form" id="users">
  <h2>Manage Users</h2>
  <?php 
    $user_success = '';
      if(isset($_GET['msg'])) {
      if($_GET['msg'] == 'role_updated') $user_success = "Role updated!";
      if($_GET['msg'] == 'user_deleted') $user_success = "User deleted!";
    }
  ?>

  <div class="recipe-list">
    <?php while($u = mysqli_fetch_assoc($users)): ?>
    <div class="recipe-item" style="align-items:center;">
      <div style="display:flex; align-items:center; gap:12px;">
        
        <!-- Avatar -->
        <div style="width:38px; height:38px; border-radius:50%; overflow:hidden; background:#C84B31; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
          <?php if(!empty($u['profile_pic'])): ?>
            <img src="<?php echo htmlspecialchars($u['profile_pic']); ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <span style="color:white; font-weight:600; font-size:0.9rem; text-transform:uppercase;"><?php echo htmlspecialchars($u['username'][0]); ?></span>
          <?php endif; ?>
        </div>

        <div>
          <h4 style="margin:0 0 2px 0;"><?php echo htmlspecialchars($u['username']); ?></h4>
          <span style="font-size:0.8rem; color:#888;"><?php echo htmlspecialchars($u['email']); ?></span>
          <br>
          <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; font-weight:600;
            <?php echo $u['role'] == 'admin' ? 'background:#fff3cd; color:#856404;' : 'background:#e8f4fd; color:#2b6cb0;'; ?>">
            <?php echo ucfirst($u['role'] ?? 'user'); ?>
          </span>
        </div>
      </div>

      <div style="display:flex; gap:8px;">
        <!-- Toggle Role -->
        <form method="POST" style="display:inline;">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <input type="hidden" name="current_role" value="<?php echo $u['role'] ?? 'user'; ?>">
          <button type="submit" name="toggle_role" 
            style="background:<?php echo ($u['role'] == 'admin') ? '#856404' : '#2b6cb0'; ?>; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-size:0.82rem;">
            <?php echo ($u['role'] == 'admin') ? '⬇️ Make User' : '⬆️ Make Admin'; ?>
          </button>
        </form>

        <!-- Delete — apne aap ko delete nahi kar sakta -->
        <?php if($u['id'] != $_SESSION['user_id']): ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?php echo htmlspecialchars($u['username']); ?>? This cannot be undone!')">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <button type="submit" name="delete_user" class="delete-btn">🗑️ Delete</button>
        </form>
        <?php else: ?>
          <span style="font-size:0.8rem; color:#aaa; padding:8px 14px;">(You)</span>
        <?php endif; ?>
        </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

</div>
<?php include 'pages/cursor.php'; ?>

<script>
const adminCatInput = document.getElementById('adminCategoryInput');

function toggleAdminKeyword(element, keyword) {
    let currentValues = adminCatInput.value.split(',')
        .map(item => item.trim().toLowerCase())
        .filter(item => item !== "");

    if (element.classList.contains('active')) {
        element.classList.remove('active');
        currentValues = currentValues.filter(val => val !== keyword);
    } else {
        element.classList.add('active');
        if (!currentValues.includes(keyword)) currentValues.push(keyword);
    }
    adminCatInput.value = currentValues.join(', ');
}

adminCatInput.addEventListener('input', function() {
    let currentValues = adminCatInput.value.split(',')
        .map(item => item.trim().toLowerCase());
    document.querySelectorAll('.keyword-badge').forEach(badge => {
        const badgeText = badge.textContent.replace('#', '').trim().toLowerCase();
        badge.classList.toggle('active', currentValues.includes(badgeText));
    });
});
</script>

<!-- search and search suggestions -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('navbarSearchInput');
    const suggestionsBox = document.getElementById('searchSuggestionsBox');
    const clearBtn = document.getElementById('clearSearchBtn');
    
    if(!searchInput || !suggestionsBox) return;

    let debounceTimer;

    // Clear button visibility toggle
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        
        if(clearBtn) clearBtn.style.display = this.value.length > 0 ? 'block' : 'none';
        
        const query = this.value.trim();

        if (query.length < 2) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            const isSubFolder = window.location.pathname.includes('/pages/');
            const fetchUrl = isSubFolder ? 'search-suggestions.php?q=' : 'pages/search-suggestions.php?q=';

            fetch(fetchUrl + encodeURIComponent(query))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            
                            if (item.type === 'user') {
                                let imgHtml = '';
                                if (item.pfp && item.pfp.trim() !== '') {
                                    let pfpSrc = isSubFolder ? '../' + item.pfp : item.pfp;
                                    imgHtml = `<img src="${pfpSrc}" style="width:24px; height:24px; border-radius:50%; object-fit:cover; border:1px solid #C84B31; flex-shrink:0;">`;
                                } else {
                                    let firstLetter = item.title.charAt(0).toUpperCase();
                                    imgHtml = `<div style="width:24px; height:24px; border-radius:50%; background:#C84B31; color:white; font-size:0.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; text-transform:uppercase; flex-shrink:0;">${firstLetter}</div>`;
                                }
                                let chefBadge = item.is_chef ? ` <small style="color:#666; font-weight:400; font-style:italic;">(Chef)</small>` : '';
                                div.innerHTML = `<div style="display:flex; align-items:center; gap:10px; width:100%;">${imgHtml}<span style="font-weight:600; color:#C84B31;">${item.title}${chefBadge}</span></div>`;
                            } else {
                                div.innerHTML = `🔍 <span style="font-weight:500; color:#333;">${item.title}</span>`;
                            }
                            
                            div.addEventListener('click', function() {
                                if (item.type === 'user') {
                                    window.location.href = (isSubFolder ? 'user-profile.php?id=' : 'pages/user-profile.php?id=') + item.id;
                                } else {
                                    window.location.href = (isSubFolder ? 'recipe-detail.php?id=' : 'pages/recipe-detail.php?id=') + item.id;
                                }
                            });
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                })
                .catch(err => console.error('Suggestion Error:', err));
        }, 250);
    });

    // Clear button click handler
    window.clearSearch = function() {
        searchInput.value = '';
        if(clearBtn) clearBtn.style.display = 'none';
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        searchInput.focus();
    };

    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && e.target !== suggestionsBox && e.target !== clearBtn) {
            suggestionsBox.style.display = 'none';
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recentRecipes = JSON.parse(localStorage.getItem('recentRecipes') || '[]');
    const section = document.getElementById('recentSection');
    const grid = document.getElementById('recentGrid');

    if(recentRecipes.length === 0) return; // kuch nahi toh section nahi dikhega

    section.style.display = 'block';

    const isSubFolder = window.location.pathname.includes('/pages/');

    recentRecipes.forEach(recipe => {
        const card = document.createElement('a');
        card.href = 'pages/recipe-detail.php?id=' + recipe.id;
        card.className = 'recipe-link-card';
        card.innerHTML = `
            <div class="recipe-card">
                ${recipe.image ? `<img src="${recipe.image}" alt="${recipe.title}">` : ''}
                <div class="card-body">
                    <h3>${recipe.title}</h3>
                    <span class="card-category">${recipe.category}</span>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
});

function clearRecent() {
    localStorage.removeItem('recentRecipes');
    document.getElementById('recentSection').style.display = 'none';
}
</script>

</body>
</html>
