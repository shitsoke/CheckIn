<?php
// login.php: Authenticate user and start session
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginUser = $_POST['loginUser'] ?? '';
    $loginPassword = $_POST['loginPassword'] ?? '';
    $adminUser = [
        'username' => 'admin',
        'email' => 'admin@checkin.com',
        'password' => 'admin123',
        'type' => 'admin'
    ];
    // Check admin
    if (($loginUser === $adminUser['username'] || $loginUser === $adminUser['email']) && $loginPassword === $adminUser['password']) {
        $_SESSION['user'] = $adminUser;
        header('Location: admin.html');
        exit;
    }
    // Check customer
    $file = 'users.json';
    if (file_exists($file)) {
        $users = json_decode(file_get_contents($file), true) ?? [];
        foreach ($users as $user) {
            if (($loginUser === $user['username'] || $loginUser === $user['email']) && password_verify($loginPassword, $user['password'])) {
                $_SESSION['user'] = $user;
                header('Location: profile.html');
                exit;
            }
        }
    }
    echo "<script>alert('Invalid credentials!'); window.history.back();</script>";
    exit;
}
?>
