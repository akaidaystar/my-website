<?php
session_start();
include '../config.php'; // Path sahi check kar lena bhai

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) >= 2) {
    $search_param = "%" . $query . "%";
    $suggestions = [];

    // 1. RECIPES SEARCH
    $stmt1 = mysqli_prepare($conn, "SELECT id, title FROM recipes WHERE title LIKE ? AND status = 'approved' LIMIT 5");
    mysqli_stmt_bind_param($stmt1, "s", $search_param);
    mysqli_stmt_execute($stmt1);
    $res1 = mysqli_stmt_get_result($stmt1);
    while ($row = mysqli_fetch_assoc($res1)) {
        $suggestions[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'type' => 'recipe'
        ];
    }

    // 2. USERS SEARCH (Chef condition ke sath)
    $user_sql = "
        SELECT u.id, u.username, u.profile_pic,
               (SELECT COUNT(*) FROM recipes r WHERE r.credit_name = u.username AND r.status = 'approved') as recipe_count
        FROM users u 
        WHERE u.username LIKE ? 
        LIMIT 5
    ";
    
    $stmt2 = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($stmt2, "s", $search_param);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    
    while ($row = mysqli_fetch_assoc($res2)) {
    $is_chef = ((int)$row['recipe_count'] > 0) ? true : false;
    
    // Database path se agar shuruat me '../' laga ho toh use trim kar lo taaki JS logic maintain rahe
    $clean_pfp = $row['profile_pic'];
    if (strpos($clean_pfp, '../') === 0) {
        $clean_pfp = substr($clean_pfp, 3); // '../' hata dega taaki base path bache
    }

    $suggestions[] = [
        'id' => $row['id'],
        'title' => $row['username'],
        'pfp' => $clean_pfp, // Ekdum neat relative path root se
        'type' => 'user',
        'is_chef' => $is_chef
    ];
}

    // JSON Output generate kar rahe hain
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit;
} else {
    echo json_encode([]);
    exit;
}