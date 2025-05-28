<?php
// user/queries.php
require_once '../db/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../authentication/login.php');
    exit();
}

$success = '';
$error = '';

// Handle query submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_query'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, name, email, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $name, $email, $message]);
            $success = "Your query has been sent successfully!";
        } catch (PDOException $e) {
            $error = "Error submitting query: " . $e->getMessage();
        }
    }
}

try {
    // Fetch user's queries
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.message, cm.created_at, 
               (SELECT COUNT(*) FROM message_responses mr WHERE mr.message_id = cm.id) AS response_count
        FROM contact_messages cm 
        WHERE cm.user_id = ? 
        ORDER BY cm.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // View specific query
    $selected_query = null;
    if (isset($_GET['view_id'])) {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.name AS user_name 
            FROM contact_messages cm 
            LEFT JOIN users u ON cm.user_id = u.id 
            WHERE cm.id = ? AND cm.user_id = ?
        ");
        $stmt->execute([$_GET['view_id'], $_SESSION['user_id']]);
        $selected_query = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_query) {
            // Fetch responses
            $stmt = $pdo->prepare("
                SELECT mr.*, u.name AS admin_name 
                FROM message_responses mr 
                JOIN users u ON mr.admin_id = u.id 
                WHERE mr.message_id = ?
            ");
            $stmt->execute([$selected_query['id']]);
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Query not found or unauthorized.";
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $queries = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Queries - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<nav class="user-nav">
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="my_bookings.php">My Bookings</a></li>
        <li><a href="queries.php" class="active">Queries</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="../authentication/logout.php">Logout</a></li>
    </ul>
</nav>

<div class="dashboard-container">
    <h2>My Queries</h2>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Query Submission Form -->
    <div class="query-form">
        <h3>Submit a New Query</h3>
        <form action="" method="POST">
            <input type="text" name="name" placeholder="Your Name" value="<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" required class="form-input">
            <input type="email" name="email" placeholder="Your Email" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required class="form-input">
            <textarea name="message" rows="5" placeholder="Your Message" required class="form-input"></textarea>
            <button type="submit" name="submit_query" class="btn-primary">Send Query</button>
        </form>
    </div>

    <?php if ($selected_query): ?>
        <div class="query-details">
            <h3>Query Details</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($selected_query['id']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_query['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($selected_query['email']); ?></p>
            <p><strong>Message:</strong> <?php echo htmlspecialchars($selected_query['message']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($selected_query['created_at']); ?></p>
            
            <?php if ($responses): ?>
                <h4>Admin Responses</h4>
                <?php foreach ($responses as $response): ?>
                    <p><strong><?php echo htmlspecialchars($response['admin_name']); ?> (<?php echo htmlspecialchars($response['created_at']); ?>):</strong> <?php echo htmlspecialchars($response['response_text']); ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No responses yet.</p>
            <?php endif; ?>
            <a href="queries.php" class="btn-secondary">Back to Queries</a>
        </div>
    <?php else: ?>
        <?php if ($queries): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Responses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queries as $query): ?>
                    <tr>
                        <td><a href="?view_id=<?php echo $query['id']; ?>"><?php echo htmlspecialchars($query['id']); ?></a></td>
                        <td><?php echo htmlspecialchars(substr($query['message'], 0, 50)); ?>...</td>
                        <td><?php echo $query['response_count'] > 0 ? 'Responded' : 'Pending'; ?></td>
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

.user-nav {
    background: linear-gradient(to right, #111827, #1f2937);
    padding: 20px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.user-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    gap: 30px;
}

.user-nav li {
    margin: 0;
}

.user-nav a {
    color: #e2e8f0;
    text-decoration: none;
    font-size: 18px;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
}

.user-nav a:hover, .user-nav a.active {
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

.query-form {
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
    font-size: 16px;
    margin-bottom: 15px;
    background: rgba(46, 204, 113, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.error {
    color: #e74c3c;
    text-align: center;
    font-size: 16px;
    margin-bottom: 15px;
    background: rgba(231, 76, 60, 0.1);
    padding: 10px;
    border-radius: 6px;
}

.site-footer {
    background: linear-gradient(to right, #111827, #1f2937);
    color: #e2e8f0;
    padding: 20px 0;
    text-align: center;
    margin-top: auto;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
}

.footer-container p {
    margin: 5px 0;
    font-size: 14px;
}

.footer-container a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.3s ease-in-out;
}

.footer-container a:hover {
    color: #60a5fa;
    text-decoration: underline;
}
</style>
</body>
</html>