<?php
session_start();
include '../config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// 🌟 Secure Queries via Prepared Statements (Sirf Approved status wali recipes hi bahar dikhengi)
if ($search != '') {
    $search_param = "%" . $search . "%";
    $stmt = mysqli_prepare($conn, "SELECT * FROM recipes WHERE status='approved' AND (title LIKE ? OR category LIKE ? OR description LIKE ?) ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
} else if ($filter != '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM recipes WHERE status='approved' AND category LIKE ? ORDER BY id DESC");
    $filter_param = "%" . $filter . "%";
    mysqli_stmt_bind_param($stmt, "s", $filter_param);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM recipes WHERE status='approved' ORDER BY id DESC");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recipes - TheFoodies</title>
  <link class="style-sheet-link" rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button {
      font-family: 'Inter', sans-serif !important;
    }
    .recipes-container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .section-header h2 {
      color: #333;
      font-size: 1.8rem;
      margin: 0;
      font-weight: 600;
    }
    .recipes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 30px;
    }
    .recipe-link-card {
      text-decoration: none;
      color: inherit;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .recipe-link-card:hover {
      transform: translateY(-5px);
    }
    .recipe-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .recipe-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .card-body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .card-body h3 {
      margin: 0 0 10px 0;
      font-size: 1.25rem;
      color: #333;
      font-weight: 600;
    }
    .card-body p {
      margin: 0 0 20px 0;
      color: #666;
      font-size: 0.9rem;
      line-height: 1.5;
      flex: 1;
    }
    .card-category {
      align-self: flex-start;
      background: #ffeed9;
      color: #C84B31;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
    }
    .active {
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

  <main class="recipes-container">
    <div class="section-header">
      <h2>All Recipes</h2>
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="submit-recipe.php" style="border-radius:8px; padding:10px 20px; text-decoration:none; color:#ffeed9; background:#C84B31; font-weight:500; font-size:0.9rem; box-shadow: 0 4px 10px rgba(200,75,49,0.15);">+ Add Recipe</a>
      <?php endif; ?>
    </div>

    <div class="recipes-grid">
      <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
          <a href="recipe-detail.php?id=<?php echo (int)$row['id']; ?>" class="recipe-link-card">
            <div class="recipe-card">
              <?php if(!empty($row['image'])): 
                  $img_src = (strpos($row['image'], 'http') === 0) ? $row['image'] : '../' . $row['image'];
              ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
              <?php endif; ?>
              <div class="card-body">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p><?php echo htmlspecialchars(substr($row['description'], 0, 110)) . (strlen($row['description']) > 110 ? '...' : ''); ?></p>
                <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #666; font-style: italic; grid-column: 1 / -1; font-size: 1rem;">No recipes found matching your criteria!</p>
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