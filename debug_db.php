<?php
// debug_db.php
include('db.php');

/** @var resource $conn */
if (!isset($conn)) {
    die("<h2 style='color:red;'>✗ Database connection variable not initialized. Check db.php!</h2>");
}

echo "<h1>Terminal 17 Connection Diagnostics</h1>";

// 1. Check what database instance and service container PHP is currently using
$sys_query = "SELECT sys_context('USERENV', 'DB_NAME') as DB, 
                     sys_context('USERENV', 'CURRENT_SCHEMA') as SCHEMA,
                     sys_context('USERENV', 'SERVICE_NAME') as SERVICE 
              FROM dual";

$stmt = oci_parse($conn, $sys_query);
if (oci_execute($stmt)) {
    $env = oci_fetch_array($stmt, OCI_ASSOC);
    echo "<h3>PHP Connection Environment Context:</h3>";
    echo "<ul>";
    echo "<li><strong>Database Instance Name:</strong> " . htmlspecialchars($env['DB']) . "</li>";
    echo "<li><strong>Schema Username:</strong> " . htmlspecialchars($env['SCHEMA']) . "</li>";
    echo "<li><strong>Network Service Container Name:</strong> " . htmlspecialchars($env['SERVICE']) . "</li>";
    echo "</ul>";
} else {
    $e = oci_error($stmt);
    echo "<p style='color:red;'>System context query failed: " . htmlentities($e['message']) . "</p>";
}
oci_free_statement($stmt);

// 2. Count rows in the CUSTOMER table from PHP's perspective
$count_query = "SELECT COUNT(*) as TOTAL FROM CUSTOMER";
$stmt2 = oci_parse($conn, $count_query);
if (oci_execute($stmt2)) {
    $row = oci_fetch_array($stmt2, OCI_ASSOC);
    echo "<h3>Table Check:</h3>";
    echo "<p>Total rows seen in CUSTOMER table by PHP: <strong style='font-size:20px; color:blue;'>" . $row['TOTAL'] . "</strong></p>";
} else {
    $e = oci_error($stmt2);
    echo "<p style='color:red;'>Customer table check failed: " . htmlentities($e['message']) . "</p>";
}
oci_free_statement($stmt2);

oci_close($conn);
?>