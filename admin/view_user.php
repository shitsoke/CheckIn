<?php
session_start();
require_once "../db_connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die('Invalid user id');

$stmt = $conn->prepare("SELECT u.first_name,u.middle_name,u.last_name,u.email,u.created_at,u.is_banned,p.phone,p.address,p.avatar,p.display_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
if (!$user) die('User not found');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View User | Admin - CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-dark">
  <div class="container py-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-danger">👤 User Profile</h3>
      <a href="manage_users.php" class="btn btn-outline-danger fw-semibold">
        ← Back to Users
      </a>
    </div>

    <!-- Profile Card -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-danger text-white fw-bold">
        User Information
      </div>

      <div class="card-body p-4">
        <div class="row g-4 align-items-start">
          
          <!-- Avatar Section -->
          <div class="col-md-4 text-center">
            <?php if (!empty($user['avatar'])): ?>
              <img src="<?= htmlspecialchars('../'.$user['avatar']) ?>" 
                   class="img-fluid rounded-circle border border-3 border-danger" 
                   style="width: 180px; height: 180px; object-fit: cover;" 
                   alt="Avatar">
            <?php else: ?>
              <div class="d-flex flex-column justify-content-center align-items-center border border-2 border-danger rounded-circle" 
                   style="width: 180px; height: 180px;">
                <span class="text-muted">No Avatar</span>
              </div>
            <?php endif; ?>
            <div class="mt-3">
              <?= $user['is_banned'] 
                ? '<span class="badge bg-danger px-3 py-2">Banned</span>' 
                : '<span class="badge bg-success px-3 py-2">Active</span>' ?>
            </div>
          </div>

          <!-- Details Section -->
          <div class="col-md-8">
            <div class="table-responsive">
              <table class="table table-borderless">
                <tbody>
                  <tr>
                    <th class="text-danger w-25">Full Name:</th>
                    <td class="fw-semibold"><?= htmlspecialchars(trim($user['first_name'].' '.($user['middle_name']?:'').' '.$user['last_name'])) ?></td>
                  </tr>
                  <tr>
                    <th class="text-danger">Display Name:</th>
                    <td><?= htmlspecialchars($user['display_name'] ?? '') ?></td>
                  </tr>
                  <tr>
                    <th class="text-danger">Email:</th>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                  </tr>
                  <tr>
                    <th class="text-danger">Phone:</th>
                    <td><?= htmlspecialchars($user['phone'] ?? '') ?></td>
                  </tr>
                  <tr>
                    <th class="text-danger">Address:</th>
                    <td><?= nl2br(htmlspecialchars($user['address'] ?? '')) ?></td>
                  </tr>
                  <tr>
                    <th class="text-danger">Registered:</th>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</body>
</html>
