<?php
session_start();
include('db.php');

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['customer_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['date_of_birth'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if ($password !== $confirm) {
        $error_msg = "Passwords do not match.";
    } elseif (!empty($name) && !empty($email) && !empty($password)) {
        $customer_id = rand(100, 999);

        $check = oci_parse($conn, "SELECT COUNT(*) as C FROM CUSTOMER WHERE LOWER(EMAIL) = LOWER(:email)");
        oci_bind_by_name($check, ':email', $email);
        oci_execute($check);
        $exists = oci_fetch_array($check, OCI_ASSOC)['C'];
        oci_free_statement($check);

        if ($exists > 0) {
            $error_msg = "An account with this email already exists.";
        } else {
            $ins = oci_parse($conn, "INSERT INTO CUSTOMER (customer_id, customer_name, gender, date_of_birth, email, phone_number, password) VALUES (:id, :name, :gender, TO_DATE(:dob, 'YYYY-MM-DD'), :email, :phone, :pass)");
            oci_bind_by_name($ins, ':id', $customer_id);
            oci_bind_by_name($ins, ':name', $name);
            oci_bind_by_name($ins, ':gender', $gender);
            oci_bind_by_name($ins, ':dob', $dob);
            oci_bind_by_name($ins, ':email', $email);
            oci_bind_by_name($ins, ':phone', $phone);
            oci_bind_by_name($ins, ':pass', $password);

            if (oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
                $success_msg = "Registration successful! You can now log in.";
            } else {
                $e = oci_error($ins);
                $error_msg = "Registration failed: " . htmlentities($e['message']);
            }
            oci_free_statement($ins);
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Terminal 17</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #1e293b;
            display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px 20px;
        }
        .card {
            background: #fff; border-radius: 12px; padding: 36px; width: 100%; max-width: 480px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        h1 { font-size: 22px; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #64748b; font-size: 13px; margin-bottom: 24px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
        .form-group input, .form-group select {
            width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 13px; font-family: inherit; background: #fff; color: #1e293b;
            transition: border 0.15s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 20px; border: none; border-radius: 8px; width: 100%;
            font-size: 13px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all 0.15s; margin-top: 6px;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; text-align: center; }
        .msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .msg-success { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .footer-text { text-align: center; font-size: 13px; color: #64748b; margin-top: 16px; }
        .footer-text a { color: #2563eb; font-weight: 600; }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Terminal 17</h1>
        <p class="subtitle">Create your customer account</p>

        <?php if ($error_msg) { echo "<div class=\"msg msg-error\">" . htmlspecialchars($error_msg) . "</div>"; } ?>
        <?php if ($success_msg) { echo "<div class=\"msg msg-success\">" . htmlspecialchars($success_msg) . "</div>"; } ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="customer_name" placeholder="e.g. Jeremy Dawat" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="e.g. jeremy@uitm.edu.my" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" placeholder="e.g. 012-3456789" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Choose a password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Create Account</button>
        </form>

        <p class="footer-text">Already have an account? <a href="login.php">Sign in here</a></p>
    </div>
</body>
</html>
