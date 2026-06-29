<?php
session_start();
include '../config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

// 🌟 BACKEND FETCH: Pehle se exist karne wale saare unique categories/keywords nikalna
$existing_keywords = [];
$kw_query = mysqli_query($conn, "SELECT category FROM recipes WHERE category IS NOT NULL AND category != ''");
while($kw_row = mysqli_fetch_assoc($kw_query)) {
    // Agar row me comma-separated keywords hain, toh unhe tod kar array me daalenge
    $exploded = explode(',', $kw_row['category']);
    foreach($exploded as $tag) {
        $trimmed = strtolower(trim($tag));
        if(!empty($trimmed) && !in_array($trimmed, $existing_keywords)) {
            $existing_keywords[] = $trimmed;
        }
    }
}
// Keywords ko alphabetically sort kar dete hain
sort($existing_keywords);

if(isset($_POST['submit_recipe'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $steps = trim($_POST['steps']);
    
    // Keywords filter karna (extra commas aur spaces saaf karna)
    $category_raw = $_POST['category'];
    $tags_array = explode(',', $category_raw);
    $cleaned_tags = [];
    foreach($tags_array as $t) {
        $t_trim = strtolower(trim($t));
        if(!empty($t_trim)) {
            $cleaned_tags[] = $t_trim;
        }
    }
    $category = implode(', ', $cleaned_tags);

    $calories = trim($_POST['calories']);
    $protein = trim($_POST['protein']);
    $carbs = trim($_POST['carbs']);
    $fat = trim($_POST['fat']);
    $credit_name = !empty(trim($_POST['credit_name'])) ? trim($_POST['credit_name']) : $_SESSION['username'];
    
    $image_path = '';

    // File Upload Handler
    if(isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $file_name = $_FILES['image_file']['name'];
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array("png", "jpg", "jpeg", "webp");

        if(in_array($file_ext, $allowed_ext)) {
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            // Ensure target directory exists safely
            $upload_destination = '../UPLOADS/' . $new_file_name;

            if(move_uploaded_file($file_tmp, $upload_destination)) {
                $image_path = 'UPLOADS/' . $new_file_name;
            } else {
                $error = "Failed to save uploaded image to server folder.";
            }
        } else {
            $error = "Invalid format! Only JPG, JPEG, PNG, and WEBP are allowed.";
        }
    } 
    
    if(empty($image_path) && !empty($_POST['image_url'])) {
        $image_path = trim($_POST['image_url']);
    }

    // Insert database via Prepared Statements
    if(empty($error)) {
        // Admin role check
$role_stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id=?");
$rid = (string)$_SESSION['user_id'];
mysqli_stmt_bind_param($role_stmt, "s", $rid);
mysqli_stmt_execute($role_stmt);
$role_data = mysqli_fetch_assoc(mysqli_stmt_get_result($role_stmt));
$insert_status = ($role_data['role'] == 'admin') ? 'approved' : 'pending';

$insert_stmt = mysqli_prepare($conn, "INSERT INTO recipes (title, description, ingredients, steps, category, image, calories, protein, carbs, fat, credit_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insert_stmt, "ssssssssssss", $title, $description, $ingredients, $steps, $category, $image_path, $calories, $protein, $carbs, $fat, $credit_name, $insert_status);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            $success = "Recipe submitted successfully! Waiting for admin approval.";
            header("Refresh:2");
        } else {
            $error = "Database error processing request.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Recipe - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button, textarea {
      font-family: 'Inter', sans-serif !important;
    }
    .custom-input:focus, .custom-textarea:focus {
      border-color: #C84B31 !important;
      outline: none;
    }
    .keyword-suggestions-container {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
      margin-bottom: 4px;
    }
    .keyword-badge {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      cursor: pointer;
      user-select: none;
      transition: all 0.2s ease;
    }
    .keyword-badge:hover {
      background: #e2e8f0;
      border-color: #94a3b8;
    }
    .keyword-badge.active {
      background: #ffeed9 !important;
      color: #C84B31 !important;
      border-color: #C84B31 !important;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(200, 75, 49, 0.15);
    }
    .active-nav {
      color: #C84B31 !important;
      font-weight: 600;
    }
  </style>
</head>
<body style="background-color: #ffeed9; margin: 0;">

  <nav>
    <div class="nav-top">
      <div class="logo">The Foodies<span>.com</span></div>
      
      <div class="nav-auth" style="display: flex; align-items: center; gap: 12px;">
        <?php if(isset($_SESSION['user_id'])): 
            $nav_user_id = $_SESSION['user_id'];
            $nav_stmt = mysqli_prepare($conn, "SELECT username, profile_pic, role FROM users WHERE id = ?");
            mysqli_stmt_bind_param($nav_stmt, "s", $nav_user_id);
            mysqli_stmt_execute($nav_stmt);
            $nav_data = mysqli_fetch_assoc(mysqli_stmt_get_result($nav_stmt));
            
            $nav_username = $nav_data['username'];
            $nav_pfp = $nav_data['profile_pic'];
            $user_role = isset($nav_data['role']) ? $nav_data['role'] : '';
        ?>

          <a href="profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #C84B31; font-weight: 600;">
            <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #C84B31; border: 2px solid #ffeed9; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex-shrink: 0;">
              <?php if(!empty($nav_pfp)): ?>
                  <img src="../<?php echo htmlspecialchars($nav_pfp); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="User PFP">
              <?php else: ?>
                  <span style="color: white; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">
                    <?php echo htmlspecialchars($nav_username[0]); ?>
                  </span>
              <?php endif; ?>
            </div>
            <span>Hi, <?php echo htmlspecialchars($nav_username); ?></span>
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
        <?php endif; ?>
      </div>
    </div>
    
    <div class="nav-bottom">
      <form method="GET" action="recipes.php">
        <div class="search-bar" style="position: relative;">
  <input type="text" id="navbarSearchInput" name="search" autocomplete="off" placeholder="Search ingredients, recipes....">
  
  <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:#999; line-height:1; padding:0;">✕</button>
  
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

  <main style="max-width: 650px; margin: 50px auto; padding: 30px; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
    <h2 style="color: #C84B31; font-size: 2rem; font-weight: 600; margin-bottom: 8px; text-align: center;">Submit a Recipe</h2>
    <p style="color: #666; font-size: 0.95rem; text-align: center; margin-bottom: 30px;">Share your delicious creation with the world!</p>
    
    <?php if($success) echo "<p style='color: #2f855a; background: #f0fff4; padding: 12px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; text-align: center;'>✅ " . htmlspecialchars($success) . "</p>"; ?>
    <?php if($error) echo "<p style='color: #c53030; background: #fff5f5; padding: 12px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; text-align: center;'>❌ " . htmlspecialchars($error) . "</p>"; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      
      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Recipe Title</label>
        <input type="text" name="title" class="custom-input" placeholder="e.g. Rasgulla" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;" required>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Description</label>
        <textarea name="description" class="custom-textarea" placeholder="Short description..." style="width: 100%; height: 80px; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; resize: vertical; box-sizing: border-box;"></textarea>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Ingredients</label>
        <textarea name="ingredients" class="custom-textarea" placeholder="1 Liter Milk..." style="width: 100%; height: 100px; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; resize: vertical; box-sizing: border-box;"></textarea>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Steps</label>
        <textarea name="steps" class="custom-textarea" placeholder="Step 1..." style="width: 100%; height: 100px; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; resize: vertical; box-sizing: border-box;"></textarea>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Categories / Keywords</label>
        <input type="text" id="categoryInput" name="category" class="custom-input" placeholder="indian, sweet dish, dessert" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;" required>
        <p style="font-size: 0.8rem; color: #888; margin: 4px 0 8px 0;">Separate choices with commas. You can also type custom keywords!</p>
        
        <div class="keyword-suggestions-container">
          <?php foreach($existing_keywords as $keyword): ?>
            <span class="keyword-badge" onclick="toggleKeyword(this, '<?php echo htmlspecialchars(addslashes($keyword)); ?>')">
              # <?php echo htmlspecialchars($keyword); ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="margin-bottom: 20px; padding: 18px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px;">
        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 12px; font-size: 0.95rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">📸 Recipe Image</label>
        
        <div style="margin-bottom: 14px;">
          <label style="display: block; font-size: 0.85rem; font-weight: 500; color: #4a5568; margin-bottom: 6px;">Option A: Upload file from device</label>
          <input type="file" name="image_file" style="font-size: 0.9rem;">
        </div>

        <div style="text-align: center; margin: 10px 0; color: #a0aec0; font-size: 0.85rem; font-weight: 600;">— OR —</div>

        <div>
          <label style="display: block; font-size: 0.85rem; font-weight: 500; color: #4a5568; margin-bottom: 6px;">Option B: Paste Web Image URL Link</label>
          <input type="text" name="image_url" class="custom-input" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px; border: 1.5px solid #cbd5e0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;">
        </div>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="display: block; font-weight: 500; color: #333; margin-bottom: 6px;">Nutritional Info (Optional)</label>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
          <input type="text" name="calories" class="custom-input" placeholder="Calories" style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; box-sizing: border-box;">
          <input type="text" name="protein" class="custom-input" placeholder="Protein" style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; box-sizing: border-box;">
          <input type="text" name="carbs" class="custom-input" placeholder="Carbs" style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; box-sizing: border-box;">
          <input type="text" name="fat" class="custom-input" placeholder="Fat" style="width: 100%; padding: 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; box-sizing: border-box;">
        </div>
      </div>
      
      <div style="margin-bottom: 30px; padding: 20px; background: #fffcf8; border: 1.5px dashed #C84B31; border-radius: 12px;">
        <label style="color: #C84B31; font-weight: 600; display: block; margin-bottom: 4px;">✨ Recipe Credit</label>
        <p style="font-size: 0.8rem; color: #666; margin-bottom: 12px;">By default aapka naam rahega, change karna chahein toh edit kar sakte hain.</p>
        <input type="text" name="credit_name" class="custom-input" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
      </div>

      <button type="submit" name="submit_recipe" style="background: #C84B31; color: #ffeed9; padding: 14px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%;">Submit Recipe</button>
    </form>
  </main>

  <?php include 'cursor.php'; ?>

  <script>
    const categoryInput = document.getElementById('categoryInput');

    function toggleKeyword(element, keyword) {
        let currentValues = categoryInput.value.split(',')
            .map(item => item.trim().toLowerCase())
            .filter(item => item !== "");

        if (element.classList.contains('active')) {
            element.classList.remove('active');
            currentValues = currentValues.filter(val => val !== keyword);
        } else {
            element.classList.add('active');
            if (!currentValues.includes(keyword)) {
                currentValues.push(keyword);
            }
        }
        categoryInput.value = currentValues.join(', ');
    }

    categoryInput.addEventListener('input', function() {
        let currentValues = categoryInput.value.split(',')
            .map(item => item.trim().toLowerCase());
            
        const badges = document.querySelectorAll('.keyword-badge');
        badges.forEach(badge => {
            const badgeText = badge.textContent.replace('#', '').trim().toLowerCase();
            if (currentValues.includes(badgeText)) {
                badge.classList.add('active');
            } else {
                badge.classList.remove('active');
            }
        });
    });
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