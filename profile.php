<?php
session_start();
include '../config.php';

// Agar user logged in nahi hai toh login page par bhejo
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 🌟 Profile Picture Upload Handler
if(isset($_FILES['pfp_file']) && $_FILES['pfp_file']['error'] == 0) {
    $file_name = $_FILES['pfp_file']['name'];
    $file_tmp = $_FILES['pfp_file']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = array("png", "jpg", "jpeg", "webp");

    if(in_array($file_ext, $allowed_ext)) {
        $new_pfp_name = 'pfp_' . $user_id . '_' . time() . '.' . $file_ext;
        $upload_destination = '../UPLOADS/' . $new_pfp_name;

        if(move_uploaded_file($file_tmp, $upload_destination)) {
            $db_path = 'UPLOADS/' . $new_pfp_name;
            
            // Prepared Statement for Update PFP
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE id = ?");
            $update_user_id = (string)$user_id; 
            mysqli_stmt_bind_param($update_stmt, "ss", $db_path, $update_user_id);
            mysqli_stmt_execute($update_stmt);
            
            $success = "Profile picture updated successfully!";
        } else {
            $error = "Failed to save uploaded image.";
        }
    } else {
        $error = "Invalid format!";
    }
}

// 🌟 USER DATA FETCH
$user_stmt = mysqli_prepare($conn, "SELECT username, email, profile_pic, role, bio FROM users WHERE id = ?");
$fetch_user_id = (string)$user_id;
mysqli_stmt_bind_param($user_stmt, "s", $fetch_user_id);
mysqli_stmt_execute($user_stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

$username = $user_data['username'];
$email = $user_data['email'];
$pfp_path = $user_data['profile_pic'];
$user_role = isset($user_data['role']) ? $user_data['role'] : '';

$clean_username = trim($username);

// 1. Count Shared Recipes
$recipes_count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM recipes WHERE LOWER(TRIM(credit_name)) = LOWER(?) AND status = 'approved'");
mysqli_stmt_bind_param($recipes_count_stmt, "s", $clean_username);
mysqli_stmt_execute($recipes_count_stmt);
$recipes_count_row = mysqli_fetch_assoc(mysqli_stmt_get_result($recipes_count_stmt));
$shared_count = $recipes_count_row['total'];

// 2. Count Saved Recipes
$saved_count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM saved_recipes WHERE user_id = ?");
$count_user_id = (string)$user_id;
mysqli_stmt_bind_param($saved_count_stmt, "s", $count_user_id);
mysqli_stmt_execute($saved_count_stmt);
$saved_count_row = mysqli_fetch_assoc(mysqli_stmt_get_result($saved_count_stmt));
$saved_count = $saved_count_row['total'];

// 3. FETCH SHARED RECIPES DATA
$shared_recipes_stmt = mysqli_prepare($conn, "SELECT * FROM recipes WHERE LOWER(TRIM(credit_name)) = LOWER(?) AND status = 'approved' ORDER BY id DESC");
mysqli_stmt_bind_param($shared_recipes_stmt, "s", $clean_username);
mysqli_stmt_execute($shared_recipes_stmt);
$shared_recipes_result = mysqli_stmt_get_result($shared_recipes_stmt);

// 4. FETCH SAVED RECIPES DATA VIA JOIN
$saved_recipes_stmt = mysqli_prepare($conn, "
    SELECT r.* FROM recipes r 
    INNER JOIN saved_recipes s ON r.id = s.recipe_id 
    WHERE s.user_id = ? 
    ORDER BY s.id DESC
");
$join_user_id = (string)$user_id;
mysqli_stmt_bind_param($saved_recipes_stmt, "s", $join_user_id);
mysqli_stmt_execute($saved_recipes_stmt);
$saved_recipes_result = mysqli_stmt_get_result($saved_recipes_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button {
      font-family: 'Inter', sans-serif !important;
    }
    
    .profile-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 30px;
      display: flex;
      align-items: center;
      gap: 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.04);
      margin-bottom: 40px;
    }

    .pfp-container {
      position: relative;
      width: 110px;
      height: 110px;
    }

    .pfp-avatar {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.8rem;
      font-weight: 600;
      color: #ffffff;
      background: #C84B31;
      border: 3px solid #ffeed9;
      box-shadow: 0 4px 10px rgba(200, 75, 49, 0.2);
    }

    .pfp-edit-btn {
      position: absolute;
      bottom: 2px;
      right: 2px;
      background: #C84B31;
      border: 2px solid #ffffff;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      transition: all 0.2s ease;
    }

    .pfp-edit-btn:hover {
      transform: scale(1.1);
      background: #b03f28;
    }

    .pfp-edit-btn svg {
      width: 14px;
      height: 14px;
      fill: #ffeed9;
    }

    .hidden-file-input {
      display: none;
    }

    .badge-container {
      display: flex;
      gap: 12px;
      margin-top: 10px;
    }

    .stat-badge {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .badge-saved { background: #fff5f5; color: #c53030; }
    .badge-recipes { background: #f0fff4; color: #2f855a; }

    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 20px;
    }
    
    .recipe-link-card {
      text-decoration: none;
      color: inherit;
      display: block;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .recipe-link-card:hover {
      transform: translateY(-4px);
    }
    
    .recipe-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
      height: 100%;
    }
    .recipe-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .recipe-card-content {
      padding: 15px;
    }
    .recipe-card-content h4 {
      margin: 0 0 8px 0;
      color: #333;
      font-size: 1.1rem;
    }
  </style>
</head>
<body style="background-color: #ffeed9; margin: 0;">

  <nav>
    <div class="nav-top">
      <div class="logo">The Foodies<span>.com</span></div>
      
      <div class="nav-auth" style="display: flex; align-items: center; gap: 12px;">
        
        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #C84B31; font-weight: 600;">
          <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #C84B31; border: 2px solid #ffeed9; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex-shrink: 0;">
            <?php if(!empty($pfp_path)): ?>
                <img src="../<?php echo htmlspecialchars($pfp_path); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="User PFP">
            <?php else: ?>
                <span style="color: white; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">
                  <?php echo htmlspecialchars($username[0]); ?>
                </span>
            <?php endif; ?>
          </div>
          <span>Hi, <?php echo htmlspecialchars($username); ?></span>
        </a>

          <?php
// Unread count fetch karo
$unread_count = 0;
if(isset($_SESSION['user_id'])) {
    $bell_email_stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE id=?");
    $bell_uid = (string)$_SESSION['user_id'];
    mysqli_stmt_bind_param($bell_email_stmt, "s", $bell_uid);
    mysqli_stmt_execute($bell_email_stmt);
    $bell_email = mysqli_fetch_assoc(mysqli_stmt_get_result($bell_email_stmt))['email'];
    
    $unread_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_email=? AND is_read=0");
    mysqli_stmt_bind_param($unread_stmt, "s", $bell_email);
    mysqli_stmt_execute($unread_stmt);
    $unread_count = mysqli_fetch_assoc(mysqli_stmt_get_result($unread_stmt))['cnt'];
}
?>
<a href="notifications.php" style="position:relative; text-decoration:none; font-size:1.2rem;" title="Notifications">
  🔔
  <?php if($unread_count > 0): ?>
    <span style="position:absolute; top:-8px; right:-10px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.6rem; display:flex; align-items:center; justify-content:center; font-weight:700;">
      <?php echo $unread_count; ?>
    </span>
  <?php endif; ?>
</a>

        <?php if($user_role == 'admin'): 
            $pending_count_res = mysqli_query($conn, "SELECT id FROM recipes WHERE status='pending'");
            $pending_count = mysqli_num_rows($pending_count_res);
        ?>
          <a href="../admin.php" class="btn-login" style="color:#C84B31; font-weight:600; position:relative; text-decoration:none; margin-left: 5px; margin-right: 5px;">
            Admin
            <?php if($pending_count > 0): ?>
              <span style="position:absolute; top:-8px; right:-12px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.65rem; display:flex; align-items:center; justify-content:center; font-weight:700;">
                <?php echo $pending_count; ?>
              </span>
            <?php endif; ?>
          </a>
        <?php endif; ?>

        <a href="logout.php" class="btn-login" style="margin-left: 5px;">Logout</a>
      </div>
    </div>
    
    <div class="nav-bottom">
      <form method="GET" action="recipes.php">
        <div class="search-bar" style="position: relative;">
  <input type="text" id="navbarSearchInput" name="search" autocomplete="off" placeholder="Search ingredients, recipes....">
  
  <!-- YE NAYA LINE ADD KARO -->
  <button id="clearSearchBtn" onclick="clearSearch()" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:#999; line-height:1; padding:0;">✕</button>
  
  <div id="searchSuggestionsBox" style="position: absolute; top: 100%; ..."></div>
</div>
      </form>
      <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
      <ul>
        <li><a href="../index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="recipes.php" class="<?php echo ($current_page == 'recipes.php' || $current_page == 'recipe-detail.php') ? 'active' : ''; ?>">Recipes</a></li>
        <li><a href="categories.php" class="<?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">Categories</a></li>
        <li><a href="saved.php" class="<?php echo ($current_page == 'saved.php') ? 'active' : ''; ?>">Saved</a></li>
        <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
      </ul>
    </div>
  </nav>

  <main style="max-width: 950px; margin: 40px auto; padding: 0 20px;">
    
    <?php if($success) echo "<p style='color: #2f855a; background: #f0fff4; padding: 12px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; text-align: center;'>✅ " . htmlspecialchars($success) . "</p>"; ?>
    <?php if($error) echo "<p style='color: #c53030; background: #fff5f5; padding: 12px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; text-align: center;'>❌ " . htmlspecialchars($error) . "</p>"; ?>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
      <p style="background:#f0fff4; color:#2f855a; padding:12px 16px; border-radius:8px; font-weight:500; margin-bottom:20px; text-align:center;">✅ Profile updated successfully!</p>
    <?php endif; ?>

    <div class="profile-card">
  <div class="pfp-container">
    <form id="pfpForm" method="POST" enctype="multipart/form-data" action="">
      <input type="file" id="pfpInput" name="pfp_file" class="hidden-file-input" accept="image/*" onchange="submitPfpForm()">
    </form>
    
    <?php if(!empty($pfp_path)): ?>
      <img src="../<?php echo htmlspecialchars($pfp_path); ?>" class="pfp-avatar" alt="Profile Picture">
    <?php else: ?>
      <div class="pfp-avatar"><?php echo strtoupper(htmlspecialchars($username[0])); ?></div>
    <?php endif; ?>

    <a href="edit-profile.php" class="pfp-edit-btn" title="Edit Profile" style="display:flex; align-items:center; justify-content:center;">
      <svg viewBox="0 0 24 24">
        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
      </svg>
    </a>
  </div>

  <!-- Right: Info -->
<div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
  
  <!-- Top row: name + badges side by side -->
  <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px;">
    <div>
      <h2 style="margin:0 0 4px 0; color:#333; font-size:1.8rem; font-weight:600;"><?php echo htmlspecialchars($username); ?></h2>
      <?php if(!empty($user_data['bio'])): ?>
        <p style="margin:0; color:#666; font-size:0.92rem; line-height:1.5; max-width:380px;"><?php echo htmlspecialchars($user_data['bio']); ?></p>
      <?php else: ?>
        <p style="margin:0; color:#aaa; font-size:0.85rem; font-style:italic;">No bio yet. <a href="edit-profile.php" style="color:#C84B31;">Add one!</a></p>
      <?php endif; ?>
    </div>

    <!-- Badges right side -->
    <div style="display:flex; flex-direction:row; gap:10px; align-items:flex-end; flex-shrink:0;">
      <span class="stat-badge badge-saved">🔖 <?php echo (int)$saved_count; ?> Saved</span>
      <span class="stat-badge badge-recipes">🍳 <?php echo (int)$shared_count; ?> Recipes</span>
    </div>
  </div>

</div>
</div>

    <div style="margin-bottom: 40px;">
      <h3 style="color: #C84B31; font-size: 1.4rem; border-bottom: 2px solid #ffffff; padding-bottom: 8px; margin-bottom: 20px;">My Saved Recipes</h3>
      
      <?php if(mysqli_num_rows($saved_recipes_result) > 0): ?>
          <div class="recipe-grid">
              <?php while($saved_recipe = mysqli_fetch_assoc($saved_recipes_result)): ?>
                  <a href="recipe-detail.php?id=<?php echo (int)$saved_recipe['id']; ?>" class="recipe-link-card">
                      <div class="recipe-card">
                          <?php 
                            $saved_img = (strpos($saved_recipe['image'], 'http') === 0) ? $saved_recipe['image'] : '../' . $saved_recipe['image'];
                          ?>
                          <img src="<?php echo htmlspecialchars($saved_img); ?>" alt="<?php echo htmlspecialchars($saved_recipe['title']); ?>">
                          <div class="recipe-card-content">
                              <h4><?php echo htmlspecialchars($saved_recipe['title']); ?></h4>
                              <p style="color: #666; font-size: 0.85rem; margin: 0;">By: <?php echo htmlspecialchars($saved_recipe['credit_name']); ?></p>
                          </div>
                      </div>
                  </a>
              <?php endwhile; ?>
          </div>
      <?php else: ?>
          <p style="color: #777; font-style: italic;">You haven't saved any recipes yet!</p>
      <?php endif; ?>
    </div>

    <div style="margin-bottom: 40px;">
      <h3 style="color: #C84B31; font-size: 1.4rem; border-bottom: 2px solid #ffffff; padding-bottom: 8px; margin-bottom: 20px;">My Shared Recipes</h3>
      
      <?php if(mysqli_num_rows($shared_recipes_result) > 0): ?>
          <div class="recipe-grid">
              <?php while($recipe = mysqli_fetch_assoc($shared_recipes_result)): ?>
                  <a href="recipe-detail.php?id=<?php echo (int)$recipe['id']; ?>" class="recipe-link-card">
                      <div class="recipe-card">
                          <?php 
                            $img_src = (strpos($recipe['image'], 'http') === 0) ? $recipe['image'] : '../' . $recipe['image'];
                          ?>
                          <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                          <div class="recipe-card-content">
                              <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                              <p style="color: #C84B31; font-size: 0.85rem; margin: 0;">⭐ Approved</p>
                          </div>
                      </div>
                  </a>
              <?php endwhile; ?>
          </div>
      <?php else: ?>
          <p style="color: #777; font-style: italic;">You haven't shared any recipes yet!</p>
      <?php endif; ?>
    </div>

  </main>

  <?php include 'cursor.php'; ?>

  <script>
    function submitPfpForm() {
        const fileInput = document.getElementById('pfpInput');
        if (fileInput.files.length > 0) {
            document.getElementById('pfpForm').submit();
        }
    }
  </script>

  <style>
  /* Suggestions item custom interactive UI design */
  .suggestion-item {
    padding: 12px 15px;
    cursor: pointer;
    font-size: 0.95rem;
    color: #333;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .suggestion-item:last-child {
    border-bottom: none;
  }
  .suggestion-item:hover {
    background-color: #ffeed9;
    color: #C84B31;
    padding-left: 20px;
  }
</style>

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
</body>
</html>