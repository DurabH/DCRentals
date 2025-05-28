<?php
// admin/query.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
$error = '';

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_response'])) {
    $message_id = $_POST['message_id'];
    $response_text = trim($_POST['response_text']);

    if (empty($response_text)) {
        $error = "Response text cannot be empty.";
    } else {
        try {
            // Validate message_id exists in contact_messages
            $stmt = $pdo->prepare("SELECT id FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            if (!$stmt->fetch()) {
                $error = "Invalid query ID.";
            } else {
                // Insert response
                $stmt = $pdo->prepare("INSERT INTO message_responses (message_id, admin_id, response_text) VALUES (?, ?, ?)");
                $stmt->execute([$message_id, $_SESSION['user_id'], $response_text]);
                $success = "Response submitted successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error submitting response: " . $e->getMessage();
        }
    }
}

try {
    // Fetch all queries
    $stmt = $pdo->prepare("
        SELECT 
            cm.id, 
            cm.name, 
            cm.email, 
            cm.message, 
            cm.created_at,
            u.name AS user_name,
            (SELECT COUNT(*) FROM message_responses mr WHERE mr.message_id = cm.id) AS response_count
        FROM contact_messages cm 
        LEFT JOIN users u ON cm.user_id = u.id 
        ORDER BY cm.created_at DESC
    ");
    $stmt->execute();
    $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // View specific query
    $selected_query = null;
    $responses = [];
    if (isset($_GET['view_id'])) {
        $stmt = $pdo->prepare("
            SELECT 
                cm.*, 
                u.name AS user_name 
            FROM contact_messages cm 
            LEFT JOIN users u ON cm.user_id = u.id 
            WHERE cm.id = ?
        ");
        $stmt->execute([$_GET['view_id']]);
        $selected_query = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_query) {
            // Fetch responses
            $stmt = $pdo->prepare("
                SELECT 
                    mr.*, 
                    u.name AS admin_name 
                FROM message_responses mr 
                JOIN users u ON mr.admin_id = u.id 
                WHERE mr.message_id = ?
                ORDER BY mr.created_at ASC
            ");
            $stmt->execute([$_GET['view_id']]);
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Query not found.";
        }
    }
} catch (PDOException $e) {
    $error = "Error fetching queries: " . $e->getMessage();
    $queries = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Queries - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="admin-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="view_users.php">View Users</a></li>
        <li><a href="view_cars.php">View Cars</a></li>
        <li><a href="bookings.php">Bookings</a></li>
        <li><a href="query.php" class="active">Queries</a></li>
        <li><a href="revenue.php">Revenue</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>Manage Queries</h2>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($selected_query): ?>
        <div class="query-details">
            <h3>Query Details</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($selected_query['id']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_query['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($selected_query['email']); ?></p>
            <p><strong>Message:</strong> <?php echo htmlspecialchars($selected_query['message']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($selected_query['created_at']); ?></p>

            <?php if ($responses): ?>
                <h4>Previous Responses</h4>
                <?php foreach ($responses as $response): ?>
                    <p><strong><?php echo htmlspecialchars($response['admin_name']); ?> (<?php echo htmlspecialchars($response['created_at']); ?>):</strong> <?php echo htmlspecialchars($response['response_text']); ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No responses yet.</p>
            <?php endif; ?>

            <h4>Submit Response</h4>
            <form action="" method="POST">
                <input type="hidden" name="message_id" value="<?php echo htmlspecialchars($selected_query['id']); ?>">
                <textarea name="response_text" rows="5" class="form-input" required placeholder="Enter your response"></textarea>
                <button type="submit" name="submit_response" class="btn-primary">Submit Response</button>
                <a href="query.php" class="btn-secondary">Back to Queries</a>
            </form>
        </div>
    <?php else: ?>
        <?php if ($queries): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Responses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queries as $query): ?>
                    <tr>
                        <td><a href="?view_id=<?php echo $query['id']; ?>"><?php echo htmlspecialchars($query['id']); ?></a></td>
                        <td><?php echo htmlspecialchars($query['name']); ?></td>
                        <td><?php echo htmlspecialchars($query['email']); ?></td>
                        <td><?php echo htmlspecialchars(substr($query['message'], 0, 50)); ?>...</td>
                        <td><?php echo htmlspecialchars($query['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($query['response_count']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No queries found.</p>
        <?php endif; ?>
    <?php endif; ?>
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

h2, h3, h4 {
    color: #1e3a8a;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

h2 {
    font-size: 32px;
    margin-bottom: 20px;
}

h3 {
    font-size: 24px;
    margin-bottom: 15px;
}

h4 {
    font-size: 20px;
    margin-bottom: 10px;
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

.query-details {
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.query-details p {
    margin: 10px 0;
    color: #1e3a8a;
    font-size: 16px;
}

.form-input {
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    background: #f9fafb;
    width: calc(100% - 24px);
    transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
}

textarea.form-input {
    resize: vertical;
}

.btn-primary {
    padding: 10px 15px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: #ffffff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    display: inline-block;
    padding: 10px 15px;
    background: linear-gradient(135deg, #6b7280, #9ca3af);
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    margin-left: 10px;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
}

.success {
    color: #2ecc71;
    text-align: center;
    font-size: 18px;
    margin-bottom: 20px;
    background: rgba(46, 204, 113, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.error {
    color: #e74c3c;
    text-align: center;
    font-size: 18px;
    margin-bottom: 20px;
    background: rgba(231, 76, 60, 0.1);
    padding: 10px;
    border-radius: 6px;
}
</style>
</body>
</html>