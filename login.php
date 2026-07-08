<?php
// login.php
session_start();
include('db.php');

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Query to match email and plaintext password for assignment testing simplicity
        $query = "SELECT CUSTOMER_ID, CUSTOMER_NAME FROM CUSTOMER WHERE LOWER(EMAIL) = LOWER(:email) AND TRIM(PASSWORD) = :password";
        
        $stmt = oci_parse($conn, $query);
        oci_bind_by_name($stmt, ':email', $email);
        oci_bind_by_name($stmt, ':password', $password);
        
        oci_execute($stmt);
        
        if ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            $_SESSION['customer_id'] = $row['CUSTOMER_ID'];
            $_SESSION['customer_name'] = $row['CUSTOMER_NAME'];

            // Check if user is an admin
            $admin_chk = oci_parse($conn, "SELECT ADMIN_ID FROM ADMIN WHERE CUSTOMER_ID = :cid");
            oci_bind_by_name($admin_chk, ':cid', $row['CUSTOMER_ID']);
            oci_execute($admin_chk);
            $admin_row = oci_fetch_array($admin_chk, OCI_ASSOC);
            oci_free_statement($admin_chk);

            if ($admin_row) {
                $_SESSION['role'] = 'admin';
                $_SESSION['admin_id'] = $admin_row['ADMIN_ID'];
                header("Location: admin_dashboard.php");
            } else {
                $_SESSION['role'] = 'customer';
                header("Location: portal.php");
            }
            exit;
        } else {
            $error_msg = "Invalid email registration credentials or password match.";
        }
        oci_free_statement($stmt);
    } else {
        $error_msg = "Please fill in all requested fields.";
    }
}
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terminal 17 Portal Login</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 50%, #0f172a 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; box-sizing: border-box; }
        h2 { margin-top: 0; color: #1e3a8a; text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; color: #4b5563; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { background: #2563eb; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 25px; }
        button:hover { background: #1d4ed8; }
        .error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; position: relative; padding-right: 40px; }
        .error .close { position: absolute; top: 8px; right: 10px; cursor: pointer; font-size: 18px; font-weight: 700; line-height: 1; opacity: .6; background: none; border: none; color: inherit; }
        .error .close:hover { opacity: 1; }
        .demo-hint { background: #e0f2fe; color: #0369a1; padding: 10px; border-radius: 4px; font-size: 12px; margin-top: 20px; border-left: 4px solid #0284c7; }
        .login-logo { display: block; margin: 0 auto 16px; max-width: 120px; height: auto; }
    </style>
</head>
<body>

    <div class="login-card">
        <img src="img/t17-removebg-preview.png" alt="Terminal 17" class="login-logo">
        <h2>Welcome to Terminal 17</h2>
        
        <?php if (!empty($error_msg)) { ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?><button class="close" onclick="this.parentElement.remove()">×</button></div>
        <?php } ?>

        <form method="POST" action="login.php">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="e.g. example@email.com" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>

            <button type="submit">Sign In</button>
        </form>

        <p style="text-align:center;margin-top:16px;font-size:13px;color:#64748b;">Don't have an account? <a href="register.php" style="color:#2563eb;font-weight:600;">Register here</a></p>
    </div>

</body>
</html>