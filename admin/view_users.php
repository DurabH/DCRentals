<?php
// admin/view_users.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php');
    exit();
}

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    $pdo->query("ALTER TABLE users AUTO_INCREMENT = 1");
    header('Location: view_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="view_users.php" class="active">View Users</a></li>
        <li><a href="view_cars.php">View Cars</a></li>
        <li><a href="bookings.php">Bookings</a></li>
        <li><a href="query.php">Queries</a></li>
        <li><a href="revenue.php">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>All Users</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-primary">Edit</a>
                    <a href="view_users.php?delete_id=<?php echo $user['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure to delete?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('../hf/footer.php'); ?>

<style>
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    color: #f1f5f9;
}

.admin-nav {
    background: linear-gradient(to right, #111827, #1f2937);
    padding: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.admin-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 30px;
}

.admin-nav li {
    margin: 0;
}

.admin-nav a {
    color: #e2e8f0;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.admin-nav a:hover, .admin-nav a.active {
    background-color: #3b82f6;
    color: #ffffff;
    transform: translateY(-2px);
}

.dashboard-container {
    max-width: 1000px;
    margin: 60px auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    text-align: center;
    backdrop-filter: blur(10px);
}

h2 {
    color: #1e3a8a;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.styled-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.styled-table th, .styled-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    color: #1e3a8a;
}

.styled-table th {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    font-weight: 600;
}

.styled-table tr {
    transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
}

.styled-table tr:hover {
    background-color: #f1f5f9;
    transform: translateY(-2px);
}

.btn-primary {
    padding: 10px 15px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-danger {
    padding: 10px 15px;
    background: linear-gradient(135deg, #e74c3c, #f97316);
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}
</style>
</body>
</html>