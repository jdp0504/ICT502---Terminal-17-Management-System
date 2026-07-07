<?php
// db.php
$username = "terminalsystem";
$password = "Password123";
$database = "localhost/FREEPDB1";

$conn = oci_connect($username, $password, $database);

if (!$conn) {
    $e = oci_error();
    die("Terminal 17 Database Engine Connection Failed: " . htmlentities($e['message'], ENT_QUOTES));
}

$schema_stmt = oci_parse($conn, "ALTER SESSION SET CURRENT_SCHEMA = TERMINALSYSTEM");
oci_execute($schema_stmt);
oci_free_statement($schema_stmt);

?>