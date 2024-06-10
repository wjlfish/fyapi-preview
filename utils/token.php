<?php

function generateToken($openid, $salt) {
    global $pdo;
    $timestamp = time();
    $expiry = $timestamp + 3600; 
    $tokenString = $openid . $salt . $timestamp;
    $token = hash('sha256', $tokenString);
    $stmt = $pdo->prepare("UPDATE fy_users SET access_token = ?, token_expiry = ? WHERE openid = ?");
    $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $openid]);
    return ['token' => $token, 'expiry' => $expiry];
}

function verifyToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM fy_users WHERE access_token = ?");
    $stmt->execute([$token]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        $status = "user_not_found";
        return $status;
    } elseif ($userData && strtotime($userData['token_expiry']) > time()) {
        // 检查是否为管理员
        $openid = $userData['openid'];
        $stmtAdmin = $pdo->prepare("SELECT * FROM fy_admins WHERE openid = ?");
        $stmtAdmin->execute([$openid]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if ($adminData) {
            $userData['is_admin'] = true;
        } else {
            $userData['is_admin'] = false;
        }

        return $userData;
    } elseif (strtotime($userData['token_expiry']) <= time()) {
        $status = "token_expired";
        return $status;
    }
}

