<?php
// register.php: Save registered user data to users.json
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'] ?? '';
    $middleName = $_POST['middleName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $type = 'customer';

    if ($firstName && $lastName && $email && $username && $password) {
        $user = [
            'firstName' => $firstName,
            'middleName' => $middleName,
            'lastName' => $lastName,
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'type' => $type
        ];
        $file = 'users.json';
        $users = [];
        if (file_exists($file)) {
            $users = json_decode(file_get_contents($file), true) ?? [];
        }
        $users[] = $user;
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
        header('Location: login.html');
        exit;
    } else {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit;
    }
}
?>
