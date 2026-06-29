<?php
session_start();
include '../config.php';

// Save Recipe Handler
if(isset($_POST['save_recipe'])) {
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $recipe_id = $_POST['recipe_id'];
        
        // Prepared statement for checking saved status
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM saved_recipes WHERE user_id=? AND recipe_id=?");
        $check_user_id = (string)$user_id;
        $check_recipe_id = (string)$recipe_id;
        mysqli_stmt_bind_param($check_stmt, "ss", $check_user_id, $check_recipe_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if(mysqli_stmt_num_rows($check_stmt) == 0) {
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "ss", $check_user_id, $check_recipe_id);
            mysqli_stmt_execute($insert_stmt);
            $save_msg = "Recipe saved!";
        } else {
            $save_msg = "Already saved!";
        }
    } else {
        header("Location: login.php");
        exit;
    }
}

// Unsave Recipe Handler
if(isset($_POST['unsave_recipe'])) {
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $recipe_id = $_POST['recipe_id'];
        
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM saved_recipes WHERE user_id=? AND recipe_id=?");
        $del_user_id = (string)$user_id;
        $del_recipe_id = (string)$recipe_id;
        mysqli_stmt_bind_param($delete_stmt, "ss", $del_user_id, $del_recipe_id);
        mysqli_stmt_execute($delete_stmt);
        $save_msg = "Recipe removed!";
    }
}

// Main Recipe Fetching Logic via ID
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $sql_stmt = mysqli_prepare($conn, "SELECT * FROM recipes WHERE id=?");
    $fetch_id = (string)$id;
    mysqli_stmt_bind_param($sql_stmt, "s", $fetch_id);
    mysqli_stmt_execute($sql_stmt);
    $recipe = mysqli_fetch_assoc(mysqli_stmt_get_result($sql_stmt));
    
    if(!$recipe) {
        echo "<h2 style='text-align:center; margin-top:50px; font-family:sans-serif; color:#C84B31;'>Recipe not found!</h2>";
        exit;
    }
} else {
    header("Location: recipes.php");
    exit;
}

// Checking Saved status for currently active user session
$is_saved = false;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_saved_stmt = mysqli_prepare($conn, "SELECT id FROM saved_recipes WHERE user_id=? AND recipe_id=?");
    $status_user_id = (string)$user_id;
    $status_id = (string)$id;
    mysqli_stmt_bind_param($check_saved_stmt, "ss", $status_user_id, $status_id);
    mysqli_stmt_execute($check_saved_stmt);
    mysqli_stmt_store_result($check_saved_stmt);
    if(mysqli_stmt_num_rows($check_saved_stmt) > 0) {
        $is_saved = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($recipe['title']); ?> - TheFoodies</title>
  <link class="style-sheet-link" rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button {
      font-family: 'Inter', sans-serif !important;
    }
    .save-btn {
      transition: transform 0.2s, background-color 0.2s;
    }
    .save-btn:hover {
      transform: scale(1.02);
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
            $bind_nav_id = (string)$nav_user_id;
            mysqli_stmt_bind_param($nav_stmt, "s", $bind_nav_id);
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

        <?php else: ?>
          <a href="login.php" class="btn-login">Login</a>
          <a href="signup.php" class="btn-signup">Sign up</a>
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

  <main class="recipe-detail-container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">

          <a href="javascript:history.back()" class="back-btn">← Back</a>
    
    <div class="detail-header" style="display: flex; gap: 40px; margin-bottom: 40px; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); align-items: center;">
      <?php if(!empty($recipe['image'])): 
          $img_src = (strpos($recipe['image'], 'http') === 0) ? $recipe['image'] : '../' . $recipe['image'];
      ?>
        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" style="width: 400px; height: 300px; object-fit: cover; border-radius: 12px; flex-shrink: 0;">
      <?php endif; ?>
      
      <div class="header-info" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
        <div>
          <span class="card-category" style="background: #ffeed9; color: #C84B31; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($recipe['category']); ?></span>
          <h1 style="font-size: 2.2rem; color: #333; margin: 12px 0 8px 0;"><?php echo htmlspecialchars($recipe['title']); ?></h1>
          <p style="color: #666; line-height: 1.5; font-size: 0.95rem; margin-bottom: 16px;"><?php echo htmlspecialchars($recipe['description']); ?></p>
          
          <div class="recipe-credit-display" style="display: flex; align-items: center; gap: 8px; margin-top: 10px; background: #fffcf8; padding: 10px 14px; border-radius: 8px; border-left: 4px solid #C84B31;">
            <span style="font-size: 1.1rem;">🍳</span>
            <span style="font-size: 0.9rem; color: #555;">Recipe By: <strong style="color: #C84B31; font-weight: 600;"><?php echo htmlspecialchars($recipe['credit_name'] ?: 'Anonymous Chef'); ?></strong></span>
          </div>
        </div>

        <div style="margin-top: 20px;">
            <form method="POST" action="">
              <input type="hidden" name="recipe_id" value="<?php echo (int)$recipe['id']; ?>">
              <?php if($is_saved): ?>
                <button type="submit" name="unsave_recipe" class="save-btn" style="background: #e53e3e; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">❤️ Unsave Recipe</button>
              <?php else: ?>
                <button type="submit" name="save_recipe" class="save-btn" style="background: #C84B31; color: white; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">🔖 Save Recipe</button>
              <?php endif; ?>
            </form>
            <?php if(isset($save_msg)): ?>
               <p class='save-msg' style='color: #2f855a; font-size: 0.9rem; margin-top: 10px; font-weight: 600;'>✅ <?php echo htmlspecialchars($save_msg); ?></p>
            <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="detail-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
      <div class="detail-section" style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <h2 style="color: #C84B31; margin-bottom: 16px; font-size: 1.3rem; border-bottom: 1.5px solid #ffeed9; padding-bottom: 6px;">Ingredients</h2>
        <p style="color: #444; line-height: 1.6; font-size: 0.95rem; white-space: pre-line;"><?php echo htmlspecialchars($recipe['ingredients']); ?></p>
      </div>
      
      <div class="detail-section" style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
        <h2 style="color: #C84B31; margin-bottom: 16px; font-size: 1.3rem; border-bottom: 1.5px solid #ffeed9; padding-bottom: 6px;">Steps</h2>
        <p style="color: #444; line-height: 1.6; font-size: 0.95rem; white-space: pre-line;"><?php echo htmlspecialchars($recipe['steps']); ?></p>
      </div>
    </div>

    <div class="nutrients-section" style="margin-top: 24px; background: white; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
      <h2 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: #C84B31;">Nutritional Info</h2>
      <div class="nutrients-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
        <div class="nutrient-card" style="background: #ffeed9; border-radius: 12px; padding: 20px; text-align: center; display: flex; flex-direction: column; gap: 8px;">
          <span class="nutrient-value" style="font-size: 1.4rem; font-weight: 700; color: #C84B31;"><?php echo htmlspecialchars($recipe['calories'] ?: 'N/A'); ?></span>
          <span class="nutrient-label" style="font-size: 0.85rem; color: #666;">Calories</span>
        </div>
        <div class="nutrient-card" style="background: #ffeed9; border-radius: 12px; padding: 20px; text-align: center; display: flex; flex-direction: column; gap: 8px;">
          <span class="nutrient-value" style="font-size: 1.4rem; font-weight: 700; color: #C84B31;"><?php echo htmlspecialchars($recipe['protein'] ?: 'N/A'); ?></span>
          <span class="nutrient-label" style="font-size: 0.85rem; color: #666;">Protein</span>
        </div>
        <div class="nutrient-card" style="background: #ffeed9; border-radius: 12px; padding: 20px; text-align: center; display: flex; flex-direction: column; gap: 8px;">
          <span class="nutrient-value" style="font-size: 1.4rem; font-weight: 700; color: #C84B31;"><?php echo htmlspecialchars($recipe['carbs'] ?: 'N/A'); ?></span>
          <span class="nutrient-label" style="font-size: 0.85rem; color: #666;">Carbs</span>
        </div>
        <div class="nutrient-card" style="background: #ffeed9; border-radius: 12px; padding: 20px; text-align: center; display: flex; flex-direction: column; gap: 8px;">
          <span class="nutrient-value" style="font-size: 1.4rem; font-weight: 700; color: #C84B31;"><?php echo htmlspecialchars($recipe['fat'] ?: 'N/A'); ?></span>
          <span class="nutrient-label" style="font-size: 0.85rem; color: #666;">Fat</span>
        </div>
      </div>
    </div>
  </main>

  <?php include 'cursor.php'; ?>

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

<script>
// Recent recipe save karo localStorage mein
const recipeId = <?php echo (int)$recipe['id']; ?>;
const recipeTitle = <?php echo json_encode($recipe['title']); ?>;
const recipeImage = <?php echo json_encode($recipe['image']); ?>;
const recipeCategory = <?php echo json_encode($recipe['category']); ?>;

let recentRecipes = JSON.parse(localStorage.getItem('recentRecipes') || '[]');

// Duplicate hata do
recentRecipes = recentRecipes.filter(r => r.id !== recipeId);

// Naya sabse aage add karo
recentRecipes.unshift({ id: recipeId, title: recipeTitle, image: recipeImage, category: recipeCategory });

// Max 8 tak rakhna
if(recentRecipes.length > 8) recentRecipes = recentRecipes.slice(0, 8);

localStorage.setItem('recentRecipes', JSON.stringify(recentRecipes));
</script>
</body>
</html>