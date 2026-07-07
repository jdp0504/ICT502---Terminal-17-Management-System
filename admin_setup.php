<?php
include('db.php');
$msg = '';

// Create ADMIN table if not exists
$create = "BEGIN EXECUTE IMMEDIATE 'CREATE TABLE ADMIN (ADMIN_ID NUMBER PRIMARY KEY, CUSTOMER_ID NUMBER UNIQUE, CREATED_AT DATE DEFAULT SYSDATE)'; EXCEPTION WHEN OTHERS THEN NULL; END;";
$stmt = oci_parse($conn, $create);
oci_execute($stmt);
oci_free_statement($stmt);

// Ensure the CUSTOMER admin@uitm.edu.my exists
$find = oci_parse($conn, "SELECT CUSTOMER_ID, CUSTOMER_NAME FROM CUSTOMER WHERE LOWER(EMAIL) = 'admin@uitm.edu.my'");
oci_execute($find);
$cust = oci_fetch_array($find, OCI_ASSOC);
oci_free_statement($find);

if ($cust) {
    $cust_id = $cust['CUSTOMER_ID'];
    $msg .= "Found existing admin customer (ID #$cust_id). ";
} else {
    // Check if jeremy@uitm.edu.my exists from previous setup — update to admin@uitm.edu.my
    $old = oci_parse($conn, "SELECT CUSTOMER_ID FROM CUSTOMER WHERE LOWER(EMAIL) = 'jeremy@uitm.edu.my'");
    oci_execute($old);
    $old_cust = oci_fetch_array($old, OCI_ASSOC);
    oci_free_statement($old);
    if ($old_cust) {
        $cust_id = $old_cust['CUSTOMER_ID'];
        $upd = oci_parse($conn, "UPDATE CUSTOMER SET EMAIL = 'admin@uitm.edu.my', CUSTOMER_NAME = 'Admin Terminal' WHERE CUSTOMER_ID = :cid");
        oci_bind_by_name($upd, ':cid', $cust_id);
        oci_execute($upd, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($upd);
        $msg .= "Updated existing customer (ID #$cust_id) to admin@uitm.edu.my. ";
    } else {
        // Insert new admin customer with dynamic ID
        $nid = oci_parse($conn, "SELECT NVL(MAX(CUSTOMER_ID), 0) + 1 FROM CUSTOMER");
        oci_execute($nid);
        $cust_id = oci_fetch_array($nid, OCI_NUM)[0];
        oci_free_statement($nid);
        $ins = oci_parse($conn, "INSERT INTO CUSTOMER (customer_id, customer_name, gender, date_of_birth, email, phone_number, password) VALUES (:cid, 'Admin Terminal', 'Male', TO_DATE('1990-01-01', 'YYYY-MM-DD'), 'admin@uitm.edu.my', '010-0000000', 'password123')");
        oci_bind_by_name($ins, ':cid', $cust_id);
        oci_execute($ins, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($ins);
        $msg .= "Created default admin customer (ID #$cust_id, admin@uitm.edu.my / password123). ";
    }
}

// Insert into ADMIN table
$check_admin = oci_parse($conn, "SELECT COUNT(*) as C FROM ADMIN WHERE CUSTOMER_ID = :cid");
oci_bind_by_name($check_admin, ':cid', $cust_id);
oci_execute($check_admin);
$already = oci_fetch_array($check_admin, OCI_ASSOC)['C'];
oci_free_statement($check_admin);

if ($already == 0) {
    $admin_id = rand(10, 99);
    $ins_ad = oci_parse($conn, "INSERT INTO ADMIN (admin_id, customer_id, created_at) VALUES (:aid, :cid, SYSDATE)");
    oci_bind_by_name($ins_ad, ':aid', $admin_id);
    oci_bind_by_name($ins_ad, ':cid', $cust_id);
    oci_execute($ins_ad, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($ins_ad);
    $msg .= "Admin account created (ID #$admin_id). ";
} else {
    $msg .= "Admin account already exists. ";
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Admin Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
<style>
body { font-family: Inter, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
.card { background: #fff; border-radius: 12px; padding: 36px; max-width: 500px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align: center; }
h1 { font-size: 20px; color: #0f172a; }
p { color: #475569; font-size: 14px; margin: 16px 0; line-height: 1.6; }
.btn { display: inline-block; padding: 10px 24px; border-radius: 8px; background: #2563eb; color: #fff; text-decoration: none; font-weight: 600; font-size: 13px; }
.success { background: #ecfdf5; color: #166534; padding: 10px; border-radius: 8px; font-size: 13px; }
</style>
</head>
<body>
<div class="card">
    <h1>🔧 Admin Setup Complete</h1>
    <div class="success"><?php echo htmlspecialchars($msg); ?></div>
    <p><strong>Admin Login Credentials:</strong><br>
    Email: <code>admin@uitm.edu.my</code><br>
    Password: <code>password123</code></p>
    <p>Existing users will still go to the Customer Portal.<br>
    Admin users will be redirected to the Admin Dashboard after login.</p>
    <a href="login.php" class="btn">Go to Login →</a>
</div>
</body>
</html>
