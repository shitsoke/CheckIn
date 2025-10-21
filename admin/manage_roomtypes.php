<?php
session_start();
require_once "../db_connect.php";
include "admin_sidebar.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

$result = $conn->query("SELECT * FROM room_types ORDER BY id ASC");
?>

<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Room Types | Admin - CheckIn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 250px;
      --brand-red: #c62828;
      --brand-red-dark: #b71c1c;
    }

```
body {
  background-color: #f8f9fa;
  min-height: 100vh;
}

/* Layout wrapper to respect sidebar */
.page-wrapper {
  padding: 1.25rem;
}

@media (min-width: 992px) {
  .page-wrapper { margin-left: var(--sidebar-width); }
  .page-inner { max-width: 1200px; margin: 0 auto; }
}

@media (max-width: 991.98px) {
  .page-wrapper { margin-left: 0; padding-top: 0.5rem; }
  .page-inner { padding-left: .5rem; padding-right: .5rem; }
}

/* Table styling */
.table-responsive {
  border-radius: 10px;
  overflow-x: auto;
  background-color: #fff;
}
.table thead th {
  background-color: var(--brand-red) !important;
  color: white !important;
}
.table td, .table th {
  vertical-align: middle;
}

.btn-danger {
  background-color: var(--brand-red) !important;
  border-color: var(--brand-red-dark) !important;
}
.btn-danger:hover {
  background-color: var(--brand-red-dark) !important;
}
```

  </style>
</head>

<body>
  <div class="page-wrapper">
    <div class="page-inner">

```
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="fw-bold text-danger mb-0">Manage Room Types</h3>
    </div>

    <div class="table-responsive shadow-sm">
      <table class="table table-bordered table-hover text-center align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Hourly Rate (₱)</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="<?= $row['id'] % 2 == 0 ? 'table-light' : '' ?>">
              <td><?= htmlspecialchars($row['id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= number_format($row['hourly_rate'], 2) ?></td>
              <td>
                <a href="edit_roomtype.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Edit</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div> <!-- /.page-inner -->
```

  </div> <!-- /.page-wrapper -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
