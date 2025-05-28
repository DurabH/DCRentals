<?php
// authentication/signup.php
require_once '../db/database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $raw_password = trim($_POST['password']);
    $password = hash('sha256', $raw_password);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = trim($_POST['role']);

    // Validate name (only letters and spaces)
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $error = "Name can only contain letters and spaces.";
    }
    // Validate phone (only digits, max 17 digits)
    elseif (!preg_match('/^[0-9]+$/', $phone)) {
        $error = "Phone number can only contain digits.";
    }
    elseif (strlen($phone) > 17) {
        $error = "Phone number cannot exceed 17 digits.";
    }
    // Validate password length (min 6, max 20)
    elseif (strlen($raw_password) < 6 || strlen($raw_password) > 20) {
        $error = "Password must be between 6 and 20 characters.";
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } 
    // Restrict to specific domains
    elseif (!preg_match('/@(gmail\.com|students\.riphah\.edu\.pk|yahoo\.com|hotmail\.com|outlook\.com)$/i', $email)) {
        $error = "Email must be from @gmail.com, @students.riphah.edu.pk, @yahoo.com, @hotmail.com, or @outlook.com.";
    }
    // Validate role
    elseif (!in_array($role, ['user', 'provider'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists!";
            } else {
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $password, $phone, $address, $role]);
                
                // Get the new user's ID
                $user_id = $pdo->lastInsertId();
                
                // Set session variables for automatic login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;
                
                // Redirect to role-specific home page
                if ($role === 'user') {
                    header('Location: ../user/dashboard.php');
                } elseif ($role === 'provider') {
                    header('Location: ../provider/dashboard.php');
                }
                exit();
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Car Rental</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Sign Up</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form action="" method="POST" class="auth-form">
        <input type="text" name="name" placeholder="Full Name" required class="form-input">
        <input type="email" name="email" placeholder="Email" required class="form-input">
        <input type="password" name="password" placeholder="Password" required class="form-input">
        <input type="text" name="phone" placeholder="Phone Number" class="form-input">
        <textarea name="address" placeholder="Address" class="form-input" rows="3"></textarea>
        <select name="role" required class="form-input">
            <option value="">Select Role</option>
            <option value="user">User</option>
            <option value="provider">Provider</option>
        </select>
        <button type="submit" class="btn-primary">Sign Up</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
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

.auth-container {
    max-width: 400px;
    margin: 60px auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    text-align: center;
    backdrop-filter: blur(10px);
}

.auth-container h2 {
    color: #1e3a8a;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-input {
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    background: #f9fafb;
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
    padding: 12px;
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

.error {
    color: #e74c3c;
    text-align: center;
    font-size: 16px;
    margin-bottom: 15px;
    background: rgba(231, 76, 60, 0.1);
    padding: 10px;
    border-radius: 6px;
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

p {
    text-align: center;
    margin-top: 20px;
    color: #1e3a8a;
    font-size: 16px;
}

p a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease-in-out;
}

p a:hover {
    color: #60a5fa;
    text-decoration: underline;
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