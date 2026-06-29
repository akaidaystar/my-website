<?php
session_start();
include '../config.php';

// 🌟 BACKEND ENGINE: Database se saari unique categories aur unke recipe counts nikalna
$category_counts = [];

// Pehle saari approved recipes fetch karte hain taaki unke tags parse kar sakein
$recipe_query = mysqli_query($conn, "SELECT category FROM recipes WHERE status='approved' AND category IS NOT NULL AND category != ''");

if($recipe_query) {
    while($row = mysqli_fetch_assoc($recipe_query)) {
        // Agar dynamic keywords comma-separated hain toh unhe split karenge
        $tags = explode(',', $row['category']);
        foreach($tags as $tag) {
            $cleaned_tag = trim(strtolower($tag));
            if(!empty($cleaned_tag)) {
                if(isset($category_counts[$cleaned_tag])) {
                    $category_counts[$cleaned_tag]++;
                } else {
                    $category_counts[$cleaned_tag] = 1;
                }
            }
        }
    }
}
// Categories ko alphabetically sort kar dete hain
ksort($category_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Categories - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button {
      font-family: 'Inter', sans-serif !important;
    }
    .categories-container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
      min-height: 70px;
    }
    .section-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .section-header h2 {
      color: #333;
      font-size: 2.2rem;
      margin: 0 0 10px 0;
      font-weight: 600;
    }
    .section-header p {
      color: #666;
      font-size: 1rem;
      margin: 0;
    }
    .categories-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 25px;
      margin-bottom: 50px;
    }
    .category-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 30px 20px;
      text-align: center;
      text-decoration: none;
      color: inherit;
      box-shadow: 0 4px 15px rgba(0,0,0,0.02);
      transition: all 0.3s ease;
      border: 1px solid rgba(200, 75, 49, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(200, 75, 49, 0.1);
      border-color: #C84B31;
    }
    .category-icon {
      font-size: 2.5rem;
      margin-bottom: 15px;
      background: #ffeed9;
      width: 70px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: #C84B31;
      transition: transform 0.3s ease;
    }
    .category-card:hover .category-icon {
      transform: scale(1.1);
    }
    .category-card h3 {
      margin: 0 0 8px 0;
      font-size: 1.3rem;
      color: #333;
      font-weight: 600;
      text-transform: capitalize;
    }
    .recipe-count {
      background: #f1f5f9;
      color: #475569;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 12px;
      transition: all 0.3s ease;
    }
    .category-card:hover .recipe-count {
      background: #C84B31;
      color: #ffffff;
    }
    .active {
      color: #C84B31 !important;
      font-weight: 600;
    }

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

  <main class="categories-container">
    <div class="section-header">
      <h2>Recipe Categories</h2>
      <p>Explore your favorite dishes by tags and cuisines</p>
    </div>
    
    <div class="categories-grid">
      <?php if(!empty($category_counts)): ?>
        <?php foreach($category_counts as $category_name => $count): 
            // Cuisines/Tags ke according placeholders emojis automatic assign karne ka short array setup
            $emojis = ['indian' => '🍛', 'sweet' => '🍬', 'dessert' => '🍰', 'chicken' => '🍗', 'fast food' => '🍔', 'chinese' => '🥢', 'healthy' => '🥗', 'breakfast' => '🍳', 'snacks' => '🍿', 'beverage' => '🥤'];
            $current_emoji = '🍽️'; 
            foreach($emojis as $key => $emo) {
                if(strpos($category_name, $key) !== false) {
                    $current_emoji = $emo;
                    break;
                }
            }
        ?>
          <a href="recipes.php?search=<?php echo urlencode($category_name); ?>" class="category-card">
            <div class="category-icon">
              <?php echo $current_emoji; ?>
            </div>
            <h3><?php echo htmlspecialchars($category_name); ?></h3>
            <span class="recipe-count"><?php echo $count; ?> <?php echo ($count > 1) ? 'Recipes' : 'Recipe'; ?></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:#666; font-style:italic; grid-column:1 / -1; font-size:1.1rem; text-align:center;">No categories or tags have been mapped yet. Submit a recipe to populate!</p>
      <?php endif; ?>
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