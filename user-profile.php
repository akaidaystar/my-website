<?php
session_start();
include '../config.php';

// Agar ID na ho toh recipes page pr bhej do
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: recipes.php");
    exit;
}

$profile_user_id = $_GET['id'];

// 1. Fetch User Data
$user_stmt = mysqli_prepare($conn, "SELECT username, profile_pic, bio FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "s", $profile_user_id);
mysqli_stmt_execute($user_stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

if(!$user_data) {
    echo "<h2 style='text-align:center; margin-top:50px;'>User not found!</h2>";
    exit;
}

// User ka actual username nikal rahe hain taaki recipes table se match kar sakein
$profile_username = $user_data['username'];

// 2. Fetch User's Approved Recipes 
// 🌟 FIX: 'user_id' column ki jagah 'credit_name' use kiya h database structure ke mutabik
$recipes_query = mysqli_prepare($conn, "SELECT id, title, image, category FROM recipes WHERE credit_name = ? AND status = 'approved' ORDER BY id DESC");
mysqli_stmt_bind_param($recipes_query, "s", $profile_username);
mysqli_stmt_execute($recipes_query);
$recipes_result = mysqli_stmt_get_result($recipes_query);
$recipe_count = mysqli_num_rows($recipes_result);

$user_stmt = mysqli_prepare($conn, "SELECT username, profile_pic, bio FROM users WHERE id = ?");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($user_data['username']); ?>'s Profile - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #ffeed9; margin:0; }
    .profile-card {
      max-width: 800px; background: white; margin: 40px auto; padding: 30px;
      border-radius: 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      border: 1px solid rgba(200, 75, 49, 0.08);
    }
    .profile-avatar {
      width: 120px; height: 120px; border-radius: 50%; object-fit: cover;
      border: 4px solid #C84B31; margin-bottom: 15px; background: #C84B31;
    }
    .profile-avatar-placeholder {
      width: 120px; height: 120px; border-radius: 50%; background: #C84B31;
      color: white; font-size: 3.5rem; display: flex; align-items: center;
      justify-content: center; margin: 0 auto 15px auto; font-weight: 600; text-transform: uppercase;
    }
    .recipes-section { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    .recipes-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;
    }
    .recipe-card {
      background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
      text-decoration: none; color: inherit; transition: transform 0.2s; border: 1px solid #f1f5f9;
    }
    .recipe-card:hover { transform: translateY(-5px); }
    .recipe-img { width: 100%; height: 200px; object-fit: cover; }
    .recipe-info { padding: 15px; }
    .recipe-title { margin: 0 0 10px 0; font-size: 1.2rem; color: #333; }
    .badge-count { background: #ffeed9; color: #C84B31; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;}
  </style>
</head>
<body>

  <div class="profile-card" style="display:flex; flex-direction:column; align-items:center; text-align:center; padding:30px;">
  <?php if(!empty($user_data['profile_pic'])): ?>
    <img src="../<?php echo htmlspecialchars($user_data['profile_pic']); ?>" class="profile-avatar" alt="Avatar">
  <?php else: ?>
    <div class="profile-avatar-placeholder"><?php echo htmlspecialchars($user_data['username'][0]); ?></div>
  <?php endif; ?>
  
  <h2 style="margin:16px 0 6px 0; color:#333; font-size:1.6rem; font-weight:600;">@<?php echo htmlspecialchars($user_data['username']); ?></h2>

  <?php if(!empty($user_data['bio'])): ?>
    <p style="color:#666; font-size:0.92rem; margin:0 0 16px 0; line-height:1.6; max-width:420px;"><?php echo htmlspecialchars($user_data['bio']); ?></p>
  <?php else: ?>
    <p style="color:#aaa; font-size:0.85rem; font-style:italic; margin:0 0 16px 0;">No bio yet.</p>
  <?php endif; ?>

  <span class="badge-count">🍳 <?php echo $recipe_count; ?> Recipes Shared</span>
  </div>

  <main class="recipes-section">
    <h3 style="color:#C84B31; border-bottom: 2px solid #C84B31; padding-bottom: 10px; margin-bottom: 30px;">Shared Recipes</h3>
    
    <div class="recipes-grid">
  <?php if($recipe_count > 0): ?>
    <?php while($recipe = mysqli_fetch_assoc($recipes_result)): ?>
      <a href="recipe-detail.php?id=<?php echo $recipe['id']; ?>" class="recipe-card">
        
        <?php 
          $image_src = $recipe['image'];
          if (!preg_match('/^(\.\.\/|https?:\/\/)/', $image_src)) {
              $image_src = '../' . $image_src;
          }
        ?>
        <img src="<?php echo htmlspecialchars($image_src); ?>" class="recipe-img" alt="Recipe Image">
        
        <div class="recipe-info">
          <h4 class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></h4>
          
          <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
            <?php 
              $categories = explode(',', $recipe['category']);
              foreach($categories as $cat): 
                $trimmed_cat = trim($cat);
                if(!empty($trimmed_cat)):
            ?>
              <span style="font-size:0.75rem; background:#f1f5f9; padding:3px 8px; border-radius:5px; color:#475569; text-transform:capitalize; font-weight:500;">
                <?php echo htmlspecialchars($trimmed_cat); ?>
              </span>
            <?php 
                endif;
              endforeach; 
            ?>
          </div>

        </div>
      </a>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="grid-column: 1/-1; text-align: center; color: #666; font-style: italic;">This chef hasn't shared any approved recipes yet.</p>
  <?php endif; ?>
</div>

  </main>
        <?php include 'cursor.php'; ?>
</body>
</html>