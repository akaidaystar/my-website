<?php
session_start();
include '../config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Current user ki email fetch karo
$user_stmt = mysqli_prepare($conn, "SELECT email, username, profile_pic, role FROM users WHERE id = ?");
$uid = (string)$_SESSION['user_id'];
mysqli_stmt_bind_param($user_stmt, "s", $uid);
mysqli_stmt_execute($user_stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
$user_email = $user_data['email'];
$username = $user_data['username'];
$pfp_path = $user_data['profile_pic'];
$user_role = $user_data['role'];

// Mark all as read
mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_email='$user_email'");

// Fetch all notifications
$notif_stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_email=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($notif_stmt, "s", $user_email);
mysqli_stmt_execute($notif_stmt);
$notifs = mysqli_stmt_get_result($notif_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications - TheFoodies</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body, input, button { font-family: 'Inter', sans-serif !important; }
    .notif-container { max-width: 750px; margin: 40px auto; padding: 0 20px; }
    .notif-card {
      background: white;
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 16px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex;
      align-items: flex-start;
      gap: 16px;
      border-left: 4px solid #C84B31;
    }
    .notif-card.read {
      border-left-color: #e2e8f0;
      opacity: 0.75;
    }
    .notif-icon {
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    .notif-body h4 {
      margin: 0 0 4px 0;
      color: #333;
      font-size: 1rem;
      font-weight: 600;
    }
    .notif-body p {
      margin: 0 0 8px 0;
      color: #555;
      font-size: 0.9rem;
      line-height: 1.5;
    }
    .notif-time {
      font-size: 0.78rem;
      color: #999;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #888;
    }
    .empty-state p { font-size: 1.1rem; margin-top: 12px; }
  </style>
</head>
<body style="background-color: #ffeed9; margin: 0;">

<nav>
  <div class="nav-top">
    <div class="logo">The Foodies<span>.com</span></div>
    <div class="nav-auth" style="display:flex; align-items:center; gap:12px;">
      <a href="profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:#C84B31; font-weight:600;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#C84B31; border:2px solid #ffeed9; box-shadow:0 2px 5px rgba(0,0,0,0.1); flex-shrink:0;">
          <?php if(!empty($pfp_path)): ?>
            <img src="../<?php echo htmlspecialchars($pfp_path); ?>" style="width:100%; height:100%; object-fit:cover;" alt="PFP">
          <?php else: ?>
            <span style="color:white; font-size:0.9rem; font-weight:600; text-transform:uppercase;"><?php echo htmlspecialchars($username[0]); ?></span>
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
        $pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM recipes WHERE status='pending'"));
      ?>
        <a href="../admin.php" class="btn-login" style="color:#C84B31; font-weight:600; position:relative; text-decoration:none;">
          Admin
          <?php if($pending_count > 0): ?>
            <span style="position:absolute; top:-8px; right:-12px; background:#C84B31; color:white; border-radius:50%; width:18px; height:18px; font-size:0.65rem; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo $pending_count; ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>

      <a href="logout.php" class="btn-login">Logout</a>
    </div>
  </div>
  <div class="nav-bottom">
    <div class="search-bar" style="position:relative;">
      <input type="text" id="navbarSearchInput" name="search" autocomplete="off" placeholder="Search ingredients, recipes....">
      <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:#999; line-height:1; padding:0;">✕</button>
      <div id="searchSuggestionsBox" style="position:absolute; top:100%; left:0; width:100%; background:#ffffff; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.1); z-index:999; display:none; margin-top:5px; overflow:hidden; border:1px solid rgba(200,75,49,0.1);"></div>
    </div>
    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="recipes.php">Recipes</a></li>
      <li><a href="categories.php">Categories</a></li>
      <li><a href="saved.php">Saved</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </div>
</nav>

<main class="notif-container">
  <h2 style="color:#333; font-size:1.8rem; font-weight:600; margin-bottom:8px;">Notifications</h2>
  <p style="color:#888; font-size:0.9rem; margin-bottom:30px;">Your latest updates and replies</p>

  <?php if(mysqli_num_rows($notifs) == 0): ?>
    <div class="empty-state">
      <div style="font-size:3rem;">🔔</div>
      <p>No notifications yet!</p>
    </div>
  <?php else: ?>
    <?php while($notif = mysqli_fetch_assoc($notifs)): ?>
      <div class="notif-card <?php echo $notif['is_read'] ? 'read' : ''; ?>">
        <div class="notif-icon">📨</div>
        <div class="notif-body">
          <h4>Admin Reply</h4>
          <p><?php echo htmlspecialchars($notif['message']); ?></p>
          <span class="notif-time"><?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?></span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
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
.suggestion-item {
  padding: 12px 15px; cursor: pointer; font-size: 0.95rem; color: #333;
  transition: all 0.2s ease; border-bottom: 1px solid #f1f5f9;
  text-align: left; display: flex; align-items: center; gap: 10px;
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background-color: #ffeed9; color: #C84B31; padding-left: 20px; }
</style>

<script>
// Apna updated search JS yahan paste karo
</script>
</body>
</html>