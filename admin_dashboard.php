<?php
session_start();
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include('db.php');

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['customer_name'];
$customer_id = $_SESSION['customer_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ─── SVG Icons ───
$i_chart = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>';
$i_file = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
$i_package = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
$i_bus = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><rect x="2" y="4" width="20" height="14" rx="2"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/><line x1="2" y1="12" x2="22" y2="12"/></svg>';
$i_user = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$i_cal = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$i_search = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
$i_route = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/><line x1="8" y1="2" x2="8" y2="22"/></svg>';
$i_hamburger = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
$i_wrench = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';

$peninsular_states = ['Johor','Kedah','Kelantan','Kuala Lumpur','Malacca','Negeri Sembilan','Pahang','Penang','Perak','Perlis','Putrajaya','Selangor','Shah Alam','Terengganu'];

$msg = '';
$msg_type = '';
$active_section = $_POST['_section'] ?? $_GET['_section'] ?? 'dashboard';

// ─── HANDLERS ───

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $msg = 'Security token mismatch. Please try again.';
        $msg_type = 'error';
    } else {
        // Update complaint status
        if (isset($_POST['update_complaint'])) {
            $cid = intval($_POST['complaint_id']);
            $status = $_POST['complaint_status'];
            $q = oci_parse($conn, "UPDATE COMPLAINT SET COMPLAINT_STATUS = :s WHERE COMPLAINT_ID = :id");
            oci_bind_by_name($q, ':s', $status);
            oci_bind_by_name($q, ':id', $cid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Complaint #$cid updated to $status.";
                $msg_type = 'success';
            }
            oci_free_statement($q);
        }
        // Delete complaint
        elseif (isset($_POST['delete_complaint'])) {
            $cid = intval($_POST['complaint_id']);
            $q = oci_parse($conn, "DELETE FROM COMPLAINT WHERE COMPLAINT_ID = :id");
            oci_bind_by_name($q, ':id', $cid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Complaint #$cid deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Edit lost item
        elseif (isset($_POST['edit_lost'])) {
            $lid = intval($_POST['item_id']);
            $name = trim($_POST['item_name']);
            $cat = trim($_POST['item_category']);
            $desc = trim($_POST['item_description']);
            $bus_id = !empty($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
            $status = $_POST['claim_status'];
            $q = oci_parse($conn, "UPDATE LOST_ITEM SET ITEM_NAME=:nm, ITEM_CATEGORY=:cat, ITEM_DESCRIPTION=:descr, BUS_ID=:bid, CLAIM_STATUS=:st WHERE LOST_ITEM_ID=:id");
            oci_bind_by_name($q, ':nm', $name);
            oci_bind_by_name($q, ':cat', $cat);
            oci_bind_by_name($q, ':descr', $desc);
            oci_bind_by_name($q, ':bid', $bus_id);
            oci_bind_by_name($q, ':st', $status);
            oci_bind_by_name($q, ':id', $lid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Item #$lid updated.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Add lost item (admin)
        elseif (isset($_POST['add_lost'])) {
            $lid = rand(1000, 9999);
            $name = trim($_POST['item_name']);
            $cat = trim($_POST['item_category']);
            $desc = trim($_POST['item_description']);
            $bus_id = !empty($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
            $q = oci_parse($conn, "INSERT INTO LOST_ITEM (lost_item_id, bus_id, item_name, item_category, item_description, lost_date, claim_status) VALUES (:id, :bid, :nm, :cat, :descr, SYSDATE, 'Unclaimed')");
            oci_bind_by_name($q, ':id', $lid);
            oci_bind_by_name($q, ':bid', $bus_id);
            oci_bind_by_name($q, ':nm', $name);
            oci_bind_by_name($q, ':cat', $cat);
            oci_bind_by_name($q, ':descr', $desc);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Lost item '$name' added.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Delete lost item
        elseif (isset($_POST['delete_lost'])) {
            $lid = intval($_POST['item_id']);
            $q = oci_parse($conn, "DELETE FROM LOST_ITEM WHERE LOST_ITEM_ID = :id");
            oci_bind_by_name($q, ':id', $lid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Item #$lid deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Add route
        elseif (isset($_POST['add_route'])) {
            $rid = rand(100, 999);
            $name = trim($_POST['route_name']);
            $dep = trim($_POST['departure_location']);
            $arr = trim($_POST['arrival_location']);
            $dist = !empty($_POST['distance_km']) ? intval($_POST['distance_km']) : null;
            $dur = trim($_POST['estimated_duration']);
            $q = oci_parse($conn, "INSERT INTO ROUTE (route_id, route_name, departure_location, arrival_location, distance_km, estimated_duration) VALUES (:id, :n, :dep, :arr, :dist, :dur)");
            oci_bind_by_name($q, ':id', $rid);
            oci_bind_by_name($q, ':n', $name);
            oci_bind_by_name($q, ':dep', $dep);
            oci_bind_by_name($q, ':arr', $arr);
            oci_bind_by_name($q, ':dist', $dist);
            oci_bind_by_name($q, ':dur', $dur);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Route '$name' ($dep → $arr) added.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Edit route
        elseif (isset($_POST['edit_route'])) {
            $rid = intval($_POST['route_id']);
            $name = trim($_POST['route_name']);
            $dep = trim($_POST['departure_location']);
            $arr = trim($_POST['arrival_location']);
            $dist = !empty($_POST['distance_km']) ? intval($_POST['distance_km']) : null;
            $dur = trim($_POST['estimated_duration']);
            $q = oci_parse($conn, "UPDATE ROUTE SET ROUTE_NAME=:n, DEPARTURE_LOCATION=:dep, ARRIVAL_LOCATION=:arr, DISTANCE_KM=:dist, ESTIMATED_DURATION=:dur WHERE ROUTE_ID=:id");
            oci_bind_by_name($q, ':id', $rid);
            oci_bind_by_name($q, ':n', $name);
            oci_bind_by_name($q, ':dep', $dep);
            oci_bind_by_name($q, ':arr', $arr);
            oci_bind_by_name($q, ':dist', $dist);
            oci_bind_by_name($q, ':dur', $dur);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Route #$rid updated.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Add bus
        elseif (isset($_POST['add_bus'])) {
            $bid = rand(1000, 9999);
            $driver_id = intval($_POST['driver_id']);
            // Validate driver is fit & active
            $dv = oci_parse($conn, "SELECT COUNT(*) as C FROM DRIVER WHERE DRIVER_ID = :did AND LOWER(HEALTH_STATUS) = 'fit' AND LOWER(EMPLOYMENT_STATUS) = 'active'");
            oci_bind_by_name($dv, ':did', $driver_id);
            oci_execute($dv);
            $dvr = oci_fetch_array($dv, OCI_ASSOC);
            oci_free_statement($dv);
            if (intval($dvr['C']) === 0) { $msg = 'Selected driver is unfit or inactive and cannot be assigned.'; $msg_type = 'error'; }
            else {
            $num = trim($_POST['bus_number']);
            $seats = intval($_POST['total_seats']);
            $status = $_POST['bus_status'];
            $q = oci_parse($conn, "INSERT INTO BUS (bus_id, driver_id, bus_number, total_seats, bus_status) VALUES (:id, :did, :num, :seats, :st)");
            oci_bind_by_name($q, ':id', $bid);
            oci_bind_by_name($q, ':did', $driver_id);
            oci_bind_by_name($q, ':num', $num);
            oci_bind_by_name($q, ':seats', $seats);
            oci_bind_by_name($q, ':st', $status);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Bus '$num' added successfully.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
            } }
        // Edit bus
        elseif (isset($_POST['edit_bus'])) {
            $bid = intval($_POST['bus_id']);
            $driver_id = intval($_POST['driver_id']);
            // Validate driver is fit & active
            $dv = oci_parse($conn, "SELECT COUNT(*) as C FROM DRIVER WHERE DRIVER_ID = :did AND LOWER(HEALTH_STATUS) = 'fit' AND LOWER(EMPLOYMENT_STATUS) = 'active'");
            oci_bind_by_name($dv, ':did', $driver_id);
            oci_execute($dv);
            $dvr = oci_fetch_array($dv, OCI_ASSOC);
            oci_free_statement($dv);
            if (intval($dvr['C']) === 0) { $msg = 'Selected driver is unfit or inactive and cannot be assigned.'; $msg_type = 'error'; }
            else {
            $num = trim($_POST['bus_number']);
            $seats = intval($_POST['total_seats']);
            $status = $_POST['bus_status'];
            $q = oci_parse($conn, "UPDATE BUS SET DRIVER_ID=:did, BUS_NUMBER=:num, TOTAL_SEATS=:seats, BUS_STATUS=:st WHERE BUS_ID=:id");
            oci_bind_by_name($q, ':id', $bid);
            oci_bind_by_name($q, ':did', $driver_id);
            oci_bind_by_name($q, ':num', $num);
            oci_bind_by_name($q, ':seats', $seats);
            oci_bind_by_name($q, ':st', $status);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Bus #$bid updated.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
            } }
        // Add driver
        elseif (isset($_POST['add_driver'])) {
            $did = rand(10, 99);
            $name = trim($_POST['driver_name']);
            $gender = $_POST['gender'];
            $dob = $_POST['date_of_birth'];
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone_number']);
            $pass = trim($_POST['password']);
            $ic = trim($_POST['ic_number']);
            $lic = trim($_POST['license_number']);
            $exp = intval($_POST['experience_years']);
            $health = $_POST['health_status'];
            $safety = trim($_POST['safety_certification']);
            $emp = $_POST['employment_status'];
            $q = oci_parse($conn, "INSERT INTO DRIVER (driver_id, driver_name, gender, date_of_birth, email, phone_number, password, ic_number, license_number, experience_years, health_status, safety_certification, employment_status) VALUES (:id, :n, :g, TO_DATE(:dob,'YYYY-MM-DD'), :e, :p, :pw, :ic, :lic, :exp, :h, :s, :emp)");
            oci_bind_by_name($q, ':id', $did);
            oci_bind_by_name($q, ':n', $name);
            oci_bind_by_name($q, ':g', $gender);
            oci_bind_by_name($q, ':dob', $dob);
            oci_bind_by_name($q, ':e', $email);
            oci_bind_by_name($q, ':p', $phone);
            oci_bind_by_name($q, ':pw', $pass);
            oci_bind_by_name($q, ':ic', $ic);
            oci_bind_by_name($q, ':lic', $lic);
            oci_bind_by_name($q, ':exp', $exp);
            oci_bind_by_name($q, ':h', $health);
            oci_bind_by_name($q, ':s', $safety);
            oci_bind_by_name($q, ':emp', $emp);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Driver '$name' added successfully.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Edit driver
        elseif (isset($_POST['edit_driver'])) {
            $did = intval($_POST['driver_id']);
            $name = trim($_POST['driver_name']);
            $gender = $_POST['gender'];
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone_number']);
            $lic = trim($_POST['license_number']);
            $exp = intval($_POST['experience_years']);
            $health = $_POST['health_status'];
            $safety = trim($_POST['safety_certification']);
            $emp = $_POST['employment_status'];
            $q = oci_parse($conn, "UPDATE DRIVER SET DRIVER_NAME=:n, GENDER=:g, EMAIL=:e, PHONE_NUMBER=:p, LICENSE_NUMBER=:lic, EXPERIENCE_YEARS=:exp, HEALTH_STATUS=:h, SAFETY_CERTIFICATION=:s, EMPLOYMENT_STATUS=:emp WHERE DRIVER_ID=:id");
            oci_bind_by_name($q, ':id', $did);
            oci_bind_by_name($q, ':n', $name);
            oci_bind_by_name($q, ':g', $gender);
            oci_bind_by_name($q, ':e', $email);
            oci_bind_by_name($q, ':p', $phone);
            oci_bind_by_name($q, ':lic', $lic);
            oci_bind_by_name($q, ':exp', $exp);
            oci_bind_by_name($q, ':h', $health);
            oci_bind_by_name($q, ':s', $safety);
            oci_bind_by_name($q, ':emp', $emp);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Driver #$did updated.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Add schedule
        elseif (isset($_POST['add_schedule'])) {
            $sid = rand(10000, 99999);
            $route_id = intval($_POST['route_id']);
            $bus_id = intval($_POST['bus_id']);
            // Look up driver from bus assignment
            $dl = oci_parse($conn, "SELECT DRIVER_ID FROM BUS WHERE BUS_ID = :bid");
            oci_bind_by_name($dl, ':bid', $bus_id);
            oci_execute($dl);
            $dr = oci_fetch_array($dl, OCI_ASSOC);
            oci_free_statement($dl);
            $driver_id = intval($dr['DRIVER_ID'] ?? 0);
            if ($driver_id === 0) { $msg = 'Selected bus has no assigned driver.'; $msg_type = 'error'; }
            else {
            // Validate driver is fit & active
            $dv = oci_parse($conn, "SELECT COUNT(*) as C FROM DRIVER WHERE DRIVER_ID = :did AND LOWER(HEALTH_STATUS) = 'fit' AND LOWER(EMPLOYMENT_STATUS) = 'active'");
            oci_bind_by_name($dv, ':did', $driver_id);
            oci_execute($dv);
            $dvr = oci_fetch_array($dv, OCI_ASSOC);
            oci_free_statement($dv);
            if (intval($dvr['C']) === 0) { $msg = 'Bus driver is unfit or inactive and cannot be assigned.'; $msg_type = 'error'; }
            else {
            $depart = str_replace('T', ' ', $_POST['departure_datetime']);
            $q = oci_parse($conn, "INSERT INTO SCHEDULE (schedule_id, route_id, bus_id, driver_id, departure_date_time) VALUES (:sid, :rid, :bid, :did, TO_DATE(:dep, 'YYYY-MM-DD HH24:MI'))");
            oci_bind_by_name($q, ':sid', $sid);
            oci_bind_by_name($q, ':rid', $route_id);
            oci_bind_by_name($q, ':bid', $bus_id);
            oci_bind_by_name($q, ':did', $driver_id);
            oci_bind_by_name($q, ':dep', $depart);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Schedule #$sid added successfully.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
            } } }
        // Edit schedule
        elseif (isset($_POST['edit_schedule'])) {
            $sid = intval($_POST['schedule_id']);
            $route_id = intval($_POST['route_id']);
            $bus_id = intval($_POST['bus_id']);
            // Look up driver from bus assignment
            $dl = oci_parse($conn, "SELECT DRIVER_ID FROM BUS WHERE BUS_ID = :bid");
            oci_bind_by_name($dl, ':bid', $bus_id);
            oci_execute($dl);
            $dr = oci_fetch_array($dl, OCI_ASSOC);
            oci_free_statement($dl);
            $driver_id = intval($dr['DRIVER_ID'] ?? 0);
            if ($driver_id === 0) { $msg = 'Selected bus has no assigned driver.'; $msg_type = 'error'; }
            else {
            // Validate driver is fit & active
            $dv = oci_parse($conn, "SELECT COUNT(*) as C FROM DRIVER WHERE DRIVER_ID = :did AND LOWER(HEALTH_STATUS) = 'fit' AND LOWER(EMPLOYMENT_STATUS) = 'active'");
            oci_bind_by_name($dv, ':did', $driver_id);
            oci_execute($dv);
            $dvr = oci_fetch_array($dv, OCI_ASSOC);
            oci_free_statement($dv);
            if (intval($dvr['C']) === 0) { $msg = 'Bus driver is unfit or inactive and cannot be assigned.'; $msg_type = 'error'; }
            else {
            $depart = str_replace('T', ' ', $_POST['departure_datetime']);
            $q = oci_parse($conn, "UPDATE SCHEDULE SET ROUTE_ID=:rid, BUS_ID=:bid, DRIVER_ID=:did, DEPARTURE_DATE_TIME=TO_DATE(:dep, 'YYYY-MM-DD HH24:MI') WHERE SCHEDULE_ID=:sid");
            oci_bind_by_name($q, ':sid', $sid);
            oci_bind_by_name($q, ':rid', $route_id);
            oci_bind_by_name($q, ':bid', $bus_id);
            oci_bind_by_name($q, ':did', $driver_id);
            oci_bind_by_name($q, ':dep', $depart);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Schedule #$sid updated.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
            } } }
        // Delete schedule
        elseif (isset($_POST['delete_schedule'])) {
            $sid = intval($_POST['schedule_id']);
            $q = oci_parse($conn, "DELETE FROM BOOKING WHERE SCHEDULE_ID = :id");
            oci_bind_by_name($q, ':id', $sid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM SCHEDULE WHERE SCHEDULE_ID = :id");
            oci_bind_by_name($q, ':id', $sid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Schedule #$sid and its bookings deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Delete route
        elseif (isset($_POST['delete_route'])) {
            $rid = intval($_POST['route_id']);
            $q = oci_parse($conn, "DELETE FROM BOOKING WHERE SCHEDULE_ID IN (SELECT SCHEDULE_ID FROM SCHEDULE WHERE ROUTE_ID = :id)");
            oci_bind_by_name($q, ':id', $rid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM SCHEDULE WHERE ROUTE_ID = :id");
            oci_bind_by_name($q, ':id', $rid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM ROUTE WHERE ROUTE_ID = :id");
            oci_bind_by_name($q, ':id', $rid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Route #$rid and its associated schedules deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Delete bus
        elseif (isset($_POST['delete_bus'])) {
            $bid = intval($_POST['bus_id']);
            $q = oci_parse($conn, "DELETE FROM BOOKING WHERE SCHEDULE_ID IN (SELECT SCHEDULE_ID FROM SCHEDULE WHERE BUS_ID = :id)");
            oci_bind_by_name($q, ':id', $bid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM SCHEDULE WHERE BUS_ID = :id");
            oci_bind_by_name($q, ':id', $bid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM LOST_ITEM WHERE BUS_ID = :id");
            oci_bind_by_name($q, ':id', $bid);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM BUS WHERE BUS_ID = :id");
            oci_bind_by_name($q, ':id', $bid);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Bus #$bid and its associated schedules deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
        // Delete driver
        elseif (isset($_POST['delete_driver'])) {
            $did = intval($_POST['driver_id']);
            $q = oci_parse($conn, "UPDATE BUS SET DRIVER_ID = NULL WHERE DRIVER_ID = :id");
            oci_bind_by_name($q, ':id', $did);
            oci_execute($q);
            oci_free_statement($q);
            $q = oci_parse($conn, "DELETE FROM DRIVER WHERE DRIVER_ID = :id");
            oci_bind_by_name($q, ':id', $did);
            if (oci_execute($q, OCI_COMMIT_ON_SUCCESS)) {
                $msg = "Driver #$did deleted.";
                $msg_type = 'success';
            } else { $e = oci_error($q); $msg = 'Error: ' . htmlentities($e['message']); $msg_type = 'error'; }
            oci_free_statement($q);
        }
    }
}

// ─── QUERIES ───

// Complaints
$comp_q = oci_parse($conn, "SELECT c.*, cu.CUSTOMER_NAME, cu.EMAIL, cu.PHONE_NUMBER, b.BUS_NUMBER FROM COMPLAINT c LEFT JOIN CUSTOMER cu ON c.CUSTOMER_ID = cu.CUSTOMER_ID LEFT JOIN BUS b ON c.BUS_ID = b.BUS_ID ORDER BY CASE c.COMPLAINT_STATUS WHEN 'Pending' THEN 1 WHEN 'In Progress' THEN 2 WHEN 'Resolved' THEN 3 ELSE 4 END, c.COMPLAINT_DATE_TIME DESC");
oci_execute($comp_q);

// Lost items — with filters
$filter_search = intval($_GET['filter_search'] ?? 0);
$filter_category = trim($_GET['filter_category'] ?? '');
$filter_bus = intval($_GET['filter_bus'] ?? 0);
$filter_date = trim($_GET['filter_date'] ?? '');
$cust_where = "l.CUSTOMER_ID IS NOT NULL";
$admin_where = "l.CUSTOMER_ID IS NULL";
if ($filter_search > 0) {
    $cust_where .= " AND l.LOST_ITEM_ID = :search";
    $admin_where .= " AND l.LOST_ITEM_ID = :search";
}
if ($filter_category !== '') {
    $cust_where .= " AND l.ITEM_CATEGORY = :cat";
    $admin_where .= " AND l.ITEM_CATEGORY = :cat";
}
if ($filter_bus > 0) {
    $cust_where .= " AND l.BUS_ID = :bus";
    $admin_where .= " AND l.BUS_ID = :bus";
}
if ($filter_date !== '') {
    if ($filter_date === 'today') $dc = " AND TRUNC(l.LOST_DATE) = TRUNC(SYSDATE)";
    elseif ($filter_date === 'week') $dc = " AND l.LOST_DATE >= TRUNC(SYSDATE, 'IW')";
    elseif ($filter_date === 'month') $dc = " AND TRUNC(l.LOST_DATE, 'MM') = TRUNC(SYSDATE, 'MM')";
    elseif ($filter_date === 'year') $dc = " AND TRUNC(l.LOST_DATE, 'YYYY') = TRUNC(SYSDATE, 'YYYY')";
    else $dc = '';
    if ($dc) { $cust_where .= $dc; $admin_where .= $dc; }
}
$cust_sql = "SELECT l.*, c.CUSTOMER_NAME, c.EMAIL, c.PHONE_NUMBER, b.BUS_NUMBER FROM LOST_ITEM l LEFT JOIN CUSTOMER c ON l.CUSTOMER_ID = c.CUSTOMER_ID LEFT JOIN BUS b ON l.BUS_ID = b.BUS_ID WHERE $cust_where ORDER BY l.LOST_ITEM_ID DESC";
$admin_sql = "SELECT l.*, b.BUS_NUMBER FROM LOST_ITEM l LEFT JOIN BUS b ON l.BUS_ID = b.BUS_ID WHERE $admin_where ORDER BY l.LOST_ITEM_ID DESC";

// Routes
$route_q = oci_parse($conn, "SELECT r.*, LISTAGG(b.BUS_NUMBER, ', ') WITHIN GROUP (ORDER BY b.BUS_NUMBER) AS ASSIGNED_BUSES FROM ROUTE r LEFT JOIN SCHEDULE s ON r.ROUTE_ID = s.ROUTE_ID LEFT JOIN BUS b ON s.BUS_ID = b.BUS_ID GROUP BY r.ROUTE_ID, r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION, r.DISTANCE_KM, r.ESTIMATED_DURATION, r.CURRENT_LOCATION, r.BUS_NUMBER ORDER BY r.ROUTE_NAME");
oci_execute($route_q);

// Buses
$bus_q = oci_parse($conn, "SELECT b.*, d.DRIVER_NAME FROM BUS b LEFT JOIN DRIVER d ON b.DRIVER_ID = d.DRIVER_ID ORDER BY b.BUS_NUMBER");
oci_execute($bus_q);

// Driver dropdown for forms (only fit & active drivers)
$driver_dd = oci_parse($conn, "SELECT DRIVER_ID, DRIVER_NAME, HEALTH_STATUS, EMPLOYMENT_STATUS FROM DRIVER WHERE LOWER(HEALTH_STATUS) = 'fit' AND LOWER(EMPLOYMENT_STATUS) = 'active' ORDER BY DRIVER_NAME");
oci_execute($driver_dd);
$driver_options = [];
while ($d = oci_fetch_array($driver_dd, OCI_ASSOC)) {
    $driver_options[] = $d;
}
oci_free_statement($driver_dd);

// Drivers
$driver_q = oci_parse($conn, "SELECT * FROM DRIVER ORDER BY DRIVER_NAME");
oci_execute($driver_q);

// Stats
$stat_comp = oci_parse($conn, "SELECT COUNT(*) as C FROM COMPLAINT WHERE LOWER(COMPLAINT_STATUS) = 'pending'");
oci_execute($stat_comp); $pc = oci_fetch_array($stat_comp, OCI_ASSOC)['C']; oci_free_statement($stat_comp);
$stat_lost = oci_parse($conn, "SELECT COUNT(*) as C FROM LOST_ITEM WHERE LOWER(CLAIM_STATUS) = 'unclaimed'");
oci_execute($stat_lost); $lc = oci_fetch_array($stat_lost, OCI_ASSOC)['C']; oci_free_statement($stat_lost);
$stat_lc = oci_parse($conn, "SELECT COUNT(*) as C FROM LOST_ITEM WHERE LOWER(CLAIM_STATUS) = 'unclaimed' AND CUSTOMER_ID IS NOT NULL");
oci_execute($stat_lc); $lc_cust = oci_fetch_array($stat_lc, OCI_ASSOC)['C']; oci_free_statement($stat_lc);
$stat_la = oci_parse($conn, "SELECT COUNT(*) as C FROM LOST_ITEM WHERE LOWER(CLAIM_STATUS) = 'unclaimed' AND CUSTOMER_ID IS NULL");
oci_execute($stat_la); $lc_admin = oci_fetch_array($stat_la, OCI_ASSOC)['C']; oci_free_statement($stat_la);
$stat_route = oci_parse($conn, "SELECT COUNT(*) as C FROM ROUTE");
oci_execute($stat_route); $rc = oci_fetch_array($stat_route, OCI_ASSOC)['C']; oci_free_statement($stat_route);
$stat_driver = oci_parse($conn, "SELECT COUNT(*) as C FROM DRIVER");
oci_execute($stat_driver); $dc = oci_fetch_array($stat_driver, OCI_ASSOC)['C']; oci_free_statement($stat_driver);
$stat_bus = oci_parse($conn, "SELECT COUNT(*) as C FROM BUS");
oci_execute($stat_bus); $bc = oci_fetch_array($stat_bus, OCI_ASSOC)['C']; oci_free_statement($stat_bus);

// Schedules
$sched_q = oci_parse($conn, "SELECT s.*, r.ROUTE_NAME, b.BUS_NUMBER, d.DRIVER_NAME, TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_FMT FROM SCHEDULE s JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID JOIN BUS b ON s.BUS_ID = b.BUS_ID JOIN DRIVER d ON s.DRIVER_ID = d.DRIVER_ID ORDER BY s.DEPARTURE_DATE_TIME DESC");
oci_execute($sched_q);

// Route dropdown for schedule forms
$route_dd = oci_parse($conn, "SELECT ROUTE_ID, ROUTE_NAME FROM ROUTE ORDER BY ROUTE_NAME");
oci_execute($route_dd);
$route_options = [];
while ($r = oci_fetch_array($route_dd, OCI_ASSOC)) { $route_options[] = $r; }
oci_free_statement($route_dd);

// Bus dropdown for schedule forms
$bus_dd = oci_parse($conn, "SELECT b.BUS_ID, b.BUS_NUMBER, b.DRIVER_ID, d.DRIVER_NAME FROM BUS b LEFT JOIN DRIVER d ON b.DRIVER_ID = d.DRIVER_ID ORDER BY b.BUS_NUMBER");
oci_execute($bus_dd);
$bus_options = [];
while ($b = oci_fetch_array($bus_dd, OCI_ASSOC)) { $bus_options[] = $b; }
oci_free_statement($bus_dd);

$stat_sched = oci_parse($conn, "SELECT COUNT(*) as C FROM SCHEDULE");
oci_execute($stat_sched); $sc = oci_fetch_array($stat_sched, OCI_ASSOC)['C']; oci_free_statement($stat_sched);

// ─── Chart data ───
$chart_bk = oci_parse($conn, "SELECT TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM') AS MONTH, COUNT(*) AS CNT FROM BOOKING b JOIN SCHEDULE s ON b.SCHEDULE_ID = s.SCHEDULE_ID GROUP BY TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM') ORDER BY MONTH");
oci_execute($chart_bk);
$bk_months = []; $bk_counts = [];
while ($r = oci_fetch_array($chart_bk, OCI_ASSOC)) { $bk_months[] = $r['MONTH']; $bk_counts[] = intval($r['CNT']); }
oci_free_statement($chart_bk);

$chart_cc = oci_parse($conn, "SELECT COMPLAINT_CATEGORY, COUNT(*) AS CNT FROM COMPLAINT GROUP BY COMPLAINT_CATEGORY ORDER BY CNT DESC");
oci_execute($chart_cc);
$cc_labels = []; $cc_counts = [];
while ($r = oci_fetch_array($chart_cc, OCI_ASSOC)) { $cc_labels[] = $r['COMPLAINT_CATEGORY']; $cc_counts[] = intval($r['CNT']); }
oci_free_statement($chart_cc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Terminal 17</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1e293b; display: flex; min-height: 100vh; }
a { text-decoration: none; color: inherit; }
.sidebar { width: 250px; background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 50%, #0f172a 100%); color: #e2e8f0; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform 0.3s; }
.sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid #1e293b; }
.sidebar-brand h1 { font-size: 18px; font-weight: 700; color: #fff; }
.sidebar-brand .badge { display: inline-block; background: #dc2626; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 4px; margin-top: 4px; }
.sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
.sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 10px 20px; font-size: 14px; color: #94a3b8; transition: all 0.15s; border-left: 3px solid transparent; }
.sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(0,0,0,0.2); color: #fff; border-left-color: #fbbf24; }
.sidebar-nav a .icon { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-nav a .icon svg { margin: 0; width: 18px; height: 18px; }
.sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 13px; }
.sidebar-footer .name { color: #e2e8f0; font-weight: 600; }
.sidebar-footer .role { color: #f87171; font-size: 11px; }
.sidebar-footer a { color: #94a3b8; display: block; margin-top: 8px; }
.sidebar-footer a:hover { color: #fff; }
.main { margin-left: 250px; flex: 1; padding: 32px 40px 60px; max-width: 1400px; }
.page-header { margin-bottom: 24px; }
.page-header h2 { font-size: 24px; font-weight: 700; color: #0f172a; }
.page-header p { color: #64748b; font-size: 14px; }
.stats { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 14px; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .stat-icon svg { width: 22px; height: 22px; margin: 0; }
.stat-icon.red { background: #fef2f2; color: #dc2626; }
.stat-icon.amber { background: #fffbeb; color: #d97706; }
.stat-icon.blue { background: #eff6ff; color: #2563eb; }
.stat-icon.green { background: #ecfdf5; color: #059669; }
.stat-info h3 { font-size: 22px; font-weight: 700; line-height: 1.2; }
.stat-info p { font-size: 12px; color: #64748b; }
.section { display: none; }
.section.active { display: block; }
.card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 20px; }
.card h3 { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; padding: 8px 8px 8px 0; border-bottom: 2px solid #f1f5f9; white-space: nowrap; }
td { padding: 10px 8px 10px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
.tag { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.tag-green { background: #dcfce7; color: #166534; }
.tag-red { background: #fee2e2; color: #991b1b; }
.tag-amber { background: #fef3c7; color: #92400e; }
.tag-blue { background: #dbeafe; color: #1e40af; }
.tag-gray { background: #f1f5f9; color: #475569; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.15s; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-primary:hover { background: #1d4ed8; }
.btn-success { background: #059669; color: #fff; }
.btn-success:hover { background: #047857; }
.btn-amber { background: #d97706; color: #fff; }
.btn-amber:hover { background: #b45309; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-danger:hover { background: #b91c1c; }
.btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #475569; }
.btn-outline:hover { background: #f8fafc; }
.btn-sm { padding: 5px 12px; font-size: 11px; }
select, input, textarea { padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-family: inherit; background: #fff; transition: border 0.15s; }
select:focus, input:focus, textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.inline-form { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.inline-form select { padding: 5px 8px; font-size: 12px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.empty { text-align: center; padding: 30px 10px; color: #94a3b8; font-size: 13px; }
.msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .msg-success { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .msg { position: relative; padding-right: 40px; }
        .msg-close { position: absolute; top: 8px; right: 10px; cursor: pointer; font-size: 18px; font-weight: 700; line-height: 1; opacity: .6; background: none; border: none; color: inherit; }
        .msg-close:hover { opacity: 1; }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; justify-content: center; align-items: center; }
.modal-overlay.open { display: flex; }
.modal { background: #fff; border-radius: 12px; padding: 28px; width: 90%; max-width: 520px; max-height: 90vh; overflow-y: auto; }
.modal h3 { margin-bottom: 16px; }
.modal .btn { width: 100%; margin-top: 8px; }
.modal-close { float: right; background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8; }
.menu-toggle { display: none; position: fixed; top: 16px; left: 16px; z-index: 200; background: #0f172a; color: #fff; border: none; border-radius: 8px; padding: 8px 12px; font-size: 20px; cursor: pointer; }
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; padding: 20px; }
    .menu-toggle { display: block; }
    .stats { grid-template-columns: repeat(3, 1fr); }
    .grid-2 { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')"><?php echo $i_hamburger; ?></button>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="img/t17-removebg-preview.png" alt="Terminal 17" style="height:36px;width:auto;">
        <div class="badge">ADMIN</div>
    </div>
    <nav class="sidebar-nav">
        <a href="#" data-section="dashboard" class="<?php echo $active_section === 'dashboard' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_chart; ?></span> Analytics</a>
        <a href="#" data-section="complaints" class="<?php echo $active_section === 'complaints' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_file; ?></span> Complaints</a>
        <a href="#" data-section="lost" class="<?php echo $active_section === 'lost' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_package; ?></span> Lost & Found</a>
        <a href="#" data-section="routes" class="<?php echo $active_section === 'routes' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_route; ?></span> Bus Routes</a>
        <a href="#" data-section="drivers" class="<?php echo $active_section === 'drivers' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_user; ?></span> Drivers</a>
        <a href="#" data-section="schedules" class="<?php echo $active_section === 'schedules' ? 'active' : ''; ?>"><span class="icon"><?php echo $i_cal; ?></span> Schedules</a>
    </nav>
    <div class="sidebar-footer">
        <div class="name"><?php echo htmlspecialchars($admin_name); ?></div>
        <div class="role">Administrator</div>
        <a href="logout.php">Sign Out</a>
    </div>
</aside>

<div class="main">

    <?php if ($msg) { echo "<div class=\"msg msg-$msg_type\">" . htmlspecialchars($msg) . "<button class=\"msg-close\" onclick=\"this.parentElement.remove()\">×</button></div>"; } ?>

    <!-- DASHBOARD -->
    <div id="section-dashboard" class="section <?php echo $active_section === 'dashboard' ? 'active' : ''; ?>">
        <div class="page-header"><h2>Analytics</h2><p>Booking trends and complaint breakdown</p></div>
        <div class="card" style="padding:20px;margin-bottom:20px;">
            <h3 style="margin-bottom:12px;"><?php echo $i_cal; ?>Monthly Bookings</h3>
            <div style="position:relative;height:260px;"><canvas id="chartBookings"></canvas></div>
        </div>
        <div class="card" style="padding:20px;">
            <h3 style="margin-bottom:12px;"><?php echo $i_file; ?>Complaints by Category</h3>
            <div style="position:relative;height:260px;"><canvas id="chartComplaints"></canvas></div>
        </div>
    </div>

    <!-- COMPLAINTS -->
    <div id="section-complaints" class="section <?php echo $active_section === 'complaints' ? 'active' : ''; ?>">
        <div class="page-header"><h2><?php echo $i_file; ?>Complaints Management</h2><p>View, filter, and respond to passenger complaints</p></div>
        <div class="card">
            <h3>All Complaints <span class="tag tag-amber" style="margin-left:auto;"><?php echo $pc; ?> pending</span></h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Customer</th><th>Contact</th><th>Subject</th><th>Category</th><th>Status</th><th>Filed</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php $has = false; while ($r = oci_fetch_array($comp_q, OCI_ASSOC)) { $has = true;
                            $s = strtolower($r['COMPLAINT_STATUS']);
                            $c = $s === 'resolved' ? 'tag-green' : ($s === 'pending' ? 'tag-amber' : 'tag-blue');
                        ?>
                        <tr>
                            <td>#<?php echo $r['COMPLAINT_ID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['CUSTOMER_NAME']); ?></strong></td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($r['EMAIL']); ?><br><?php echo htmlspecialchars($r['PHONE_NUMBER']); ?></td>
                            <td><strong title="<?php echo htmlspecialchars($r['COMPLAINT_DESCRIPTION'] ?? 'No description'); ?>"><?php echo htmlspecialchars($r['COMPLAINT_TITLE']); ?></strong></td>
                            <td><?php echo $r['COMPLAINT_CATEGORY']; ?></td>
                            <td><span class="tag <?php echo $c; ?>"><?php echo $r['COMPLAINT_STATUS']; ?></span></td>
                            <td style="font-size:12px;"><?php echo $r['COMPLAINT_DATE_TIME']; ?></td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="_section" value="complaints">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="complaint_id" value="<?php echo $r['COMPLAINT_ID']; ?>">
                                    <select name="complaint_status">
                                        <option value="Pending" <?php if ($s === 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="In Progress" <?php if ($s === 'in progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="Resolved" <?php if ($s === 'resolved') echo 'selected'; ?>>Resolved</option>
                                    </select>
                                    <button type="submit" name="update_complaint" class="btn btn-primary btn-sm">Update</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this complaint?');">
                                    <input type="hidden" name="_section" value="complaints">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="complaint_id" value="<?php echo $r['COMPLAINT_ID']; ?>">
                                    <button type="submit" name="delete_complaint" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                        <?php } if (!$has) { echo '<tr><td colspan="8"><div class="empty">No complaints found.</div></td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- LOST & FOUND -->
    <div id="section-lost" class="section <?php echo $active_section === 'lost' ? 'active' : ''; ?>">
        <div class="page-header"><h2><?php echo $i_package; ?>Lost &amp; Found Management</h2><p>Track and manage unclaimed items</p></div>
        <!-- Customer Reports -->
        <div class="card">
            <h3><?php echo $i_user; ?>Customer Reports <span class="tag tag-blue" style="margin-left:auto;"><?php echo $lc_cust; ?> unclaimed</span><button class="btn btn-outline btn-sm" onclick="document.getElementById('lostFilterModal').classList.add('open')" style="margin-left:10px;"><?php echo $i_search; ?>Filter</button></h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Item</th><th>Category</th><th>Owner</th><th>Contact</th><th>Bus</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php $lost_cust_q = oci_parse($conn, $cust_sql);
                            if ($filter_search > 0) oci_bind_by_name($lost_cust_q, ':search', $filter_search);
                            if ($filter_category !== '') oci_bind_by_name($lost_cust_q, ':cat', $filter_category);
                            if ($filter_bus > 0) oci_bind_by_name($lost_cust_q, ':bus', $filter_bus);
                            oci_execute($lost_cust_q); $has_c = false; while ($r = oci_fetch_array($lost_cust_q, OCI_ASSOC)) { $has_c = true;
                            $s = strtolower($r['CLAIM_STATUS']);
                            $c = $s === 'claimed' ? 'tag-green' : 'tag-red';
                            $desc_display = htmlspecialchars($r['ITEM_DESCRIPTION'] ?? '');
                            $desc_js = htmlspecialchars(addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $r['ITEM_DESCRIPTION'] ?? '')));
                        ?>
                        <tr>
                            <td>#<?php echo $r['LOST_ITEM_ID']; ?></td>
                            <td><strong title="<?php echo $desc_display ?: 'No description'; ?>"><?php echo htmlspecialchars($r['ITEM_NAME']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['ITEM_CATEGORY']); ?></td>
                            <td><?php echo htmlspecialchars($r['CUSTOMER_NAME'] ?? '—'); ?></td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($r['EMAIL'] ?? ''); ?><br><?php echo htmlspecialchars($r['PHONE_NUMBER'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['BUS_NUMBER'] ?? '—'); ?></td>
                            <td style="font-size:12px;"><?php echo $r['LOST_DATE']; ?></td>
                            <td><span class="tag <?php echo $c; ?>"><?php echo $r['CLAIM_STATUS']; ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <button class="btn btn-outline btn-sm" onclick="editLostItem(<?php echo $r['LOST_ITEM_ID']; ?>, '<?php echo htmlspecialchars(addslashes($r['ITEM_NAME'])); ?>', '<?php echo htmlspecialchars(addslashes($r['ITEM_CATEGORY'])); ?>', '<?php echo $desc_js; ?>', <?php echo intval($r['BUS_ID'] ?? 0); ?>, '<?php echo htmlspecialchars(addslashes($r['CLAIM_STATUS'])); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this lost item?');">
                                        <input type="hidden" name="_section" value="lost">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $r['LOST_ITEM_ID']; ?>">
                                        <button type="submit" name="delete_lost" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php } if (!$has_c) { echo '<tr><td colspan="9"><div class="empty">No customer reports.</div></td></tr>'; } oci_free_statement($lost_cust_q); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Admin Records -->
        <div class="card" style="margin-top:20px;">
            <h3><?php echo $i_wrench; ?>Admin Records <span class="tag tag-red" style="margin-left:auto;"><?php echo $lc_admin; ?> unclaimed</span><button class="btn btn-outline btn-sm" onclick="document.getElementById('lostFilterModal').classList.add('open')" style="margin-left:10px;"><?php echo $i_search; ?>Filter</button></h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Item</th><th>Category</th><th>Owner</th><th>Contact</th><th>Bus</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php $lost_admin_q = oci_parse($conn, $admin_sql);
                            if ($filter_search > 0) oci_bind_by_name($lost_admin_q, ':search', $filter_search);
                            if ($filter_category !== '') oci_bind_by_name($lost_admin_q, ':cat', $filter_category);
                            if ($filter_bus > 0) oci_bind_by_name($lost_admin_q, ':bus', $filter_bus);
                            oci_execute($lost_admin_q); $has_a = false; while ($r = oci_fetch_array($lost_admin_q, OCI_ASSOC)) { $has_a = true;
                            $s = strtolower($r['CLAIM_STATUS']);
                            $c = $s === 'claimed' ? 'tag-green' : 'tag-red';
                            $desc_display = htmlspecialchars($r['ITEM_DESCRIPTION'] ?? '');
                            $desc_js = htmlspecialchars(addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $r['ITEM_DESCRIPTION'] ?? '')));
                        ?>
                        <tr>
                            <td>#<?php echo $r['LOST_ITEM_ID']; ?></td>
                            <td><strong title="<?php echo $desc_display ?: 'No description'; ?>"><?php echo htmlspecialchars($r['ITEM_NAME']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['ITEM_CATEGORY']); ?></td>
                            <td>—</td>
                            <td style="font-size:12px;">—</td>
                            <td><?php echo htmlspecialchars($r['BUS_NUMBER'] ?? '—'); ?></td>
                            <td style="font-size:12px;"><?php echo $r['LOST_DATE']; ?></td>
                            <td><span class="tag <?php echo $c; ?>"><?php echo $r['CLAIM_STATUS']; ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <button class="btn btn-outline btn-sm" onclick="editLostItem(<?php echo $r['LOST_ITEM_ID']; ?>, '<?php echo htmlspecialchars(addslashes($r['ITEM_NAME'])); ?>', '<?php echo htmlspecialchars(addslashes($r['ITEM_CATEGORY'])); ?>', '<?php echo $desc_js; ?>', <?php echo intval($r['BUS_ID'] ?? 0); ?>, '<?php echo htmlspecialchars(addslashes($r['CLAIM_STATUS'])); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this lost item?');">
                                        <input type="hidden" name="_section" value="lost">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $r['LOST_ITEM_ID']; ?>">
                                        <button type="submit" name="delete_lost" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php } if (!$has_a) { echo '<tr><td colspan="9"><div class="empty">No admin records.</div></td></tr>'; } oci_free_statement($lost_admin_q); ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('addLostModal').classList.add('open')" style="margin-top:15px;">＋ Add Item</button>
        </div>
    </div>

<!-- Add Lost Item Modal -->
<div id="addLostModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('addLostModal')">&times;</button>
        <h3><?php echo $i_package; ?>Add Lost Item</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="lost">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <div class="form-group"><label>Item Name</label><input type="text" name="item_name" required></div>
            <div class="form-row">
                <div class="form-group"><label>Category</label>
                    <select name="item_category" required>
                        <option value="">— Select —</option>
                        <option value="Bag">Bag</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Wallet">Wallet</option>
                        <option value="Phone">Phone</option>
                        <option value="Keys">Keys</option>
                        <option value="ID Card">ID Card</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Bus (optional)</label>
                    <select name="bus_id">
                        <option value="">— Unknown —</option>
                        <?php foreach ($bus_options as $b) { ?>
                        <option value="<?php echo $b['BUS_ID']; ?>"><?php echo htmlspecialchars($b['BUS_NUMBER']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="item_description" rows="3" placeholder="Describe the item..."></textarea></div>
            <button type="submit" name="add_lost" class="btn btn-success">Add Item</button>
        </form>
    </div>
</div>

<!-- Edit Lost Item Modal -->
<div id="editLostModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editLostModal')">&times;</button>
        <h3><?php echo $i_package; ?>Edit Lost Item</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="lost">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="item_id" id="el_id">
            <div class="form-group"><label>Item Name</label><input type="text" name="item_name" id="el_name" required></div>
            <div class="form-row">
                <div class="form-group"><label>Category</label>
                    <select name="item_category" id="el_cat" required>
                        <option value="">— Select —</option>
                        <option value="Bag">Bag</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Wallet">Wallet</option>
                        <option value="Phone">Phone</option>
                        <option value="Keys">Keys</option>
                        <option value="ID Card">ID Card</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="claim_status" id="el_status">
                        <option value="Unclaimed">Unclaimed</option>
                        <option value="Claimed">Claimed</option>
                        <option value="Disposed">Disposed</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Bus (optional)</label>
                    <select name="bus_id" id="el_bus">
                        <option value="">— Unknown —</option>
                        <?php foreach ($bus_options as $b) { ?>
                        <option value="<?php echo $b['BUS_ID']; ?>"><?php echo htmlspecialchars($b['BUS_NUMBER']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="item_description" id="el_desc" rows="3" placeholder="Describe the item..."></textarea></div>
            <button type="submit" name="edit_lost" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<!-- Lost & Found Filter Modal -->
<div id="lostFilterModal" class="modal-overlay">
    <div class="modal" style="max-width:450px;">
        <button class="modal-close" onclick="closeModal('lostFilterModal')">&times;</button>
        <h3><?php echo $i_search; ?>Filter Lost Items</h3>
        <form method="GET">
            <input type="hidden" name="_section" value="lost">
            <div class="form-group"><label>Search by Item ID</label>
                <input type="number" name="filter_search" value="<?php echo $filter_search ?: ''; ?>" placeholder="Item #">
            </div>
            <div class="form-group"><label>Category</label>
                <select name="filter_category">
                    <option value="">All Categories</option>
                    <option value="Bag" <?php if ($filter_category === 'Bag') echo 'selected'; ?>>Bag</option>
                    <option value="Electronics" <?php if ($filter_category === 'Electronics') echo 'selected'; ?>>Electronics</option>
                    <option value="Clothing" <?php if ($filter_category === 'Clothing') echo 'selected'; ?>>Clothing</option>
                    <option value="Wallet" <?php if ($filter_category === 'Wallet') echo 'selected'; ?>>Wallet</option>
                    <option value="Phone" <?php if ($filter_category === 'Phone') echo 'selected'; ?>>Phone</option>
                    <option value="Keys" <?php if ($filter_category === 'Keys') echo 'selected'; ?>>Keys</option>
                    <option value="ID Card" <?php if ($filter_category === 'ID Card') echo 'selected'; ?>>ID Card</option>
                    <option value="Other" <?php if ($filter_category === 'Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div class="form-group"><label>Bus</label>
                <select name="filter_bus">
                    <option value="0">All Buses</option>
                    <?php foreach ($bus_options as $b) { ?>
                    <option value="<?php echo $b['BUS_ID']; ?>" <?php if ($filter_bus === intval($b['BUS_ID'])) echo 'selected'; ?>><?php echo htmlspecialchars($b['BUS_NUMBER']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group"><label>Date</label>
                <select name="filter_date">
                    <option value="">All Time</option>
                    <option value="today" <?php if ($filter_date === 'today') echo 'selected'; ?>>Today</option>
                    <option value="week" <?php if ($filter_date === 'week') echo 'selected'; ?>>This Week</option>
                    <option value="month" <?php if ($filter_date === 'month') echo 'selected'; ?>>This Month</option>
                    <option value="year" <?php if ($filter_date === 'year') echo 'selected'; ?>>This Year</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="?_section=lost" class="btn btn-outline" style="text-decoration:none;">Reset</a>
            </div>
        </form>
    </div>
</div>

    <!-- BUS ROUTES & BUSES -->
    <div id="section-routes" class="section <?php echo $active_section === 'routes' ? 'active' : ''; ?>">
        <div class="page-header"><h2><?php echo $i_route; ?>Routes &amp; Buses Management</h2><p>Add, edit, and manage bus routes and their buses</p></div>
        <div class="grid-2">
            <div class="card">
                <h3>➕ Add New Route</h3>
                <form method="POST">
                    <input type="hidden" name="_section" value="routes">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="form-group"><label>Route Name</label><input type="text" name="route_name" placeholder="e.g. KL–Penang Express" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Departure</label>
                            <select name="departure_location" id="add_dep" required>
                                <option value="">— Select Departure —</option>
                                <?php foreach ($peninsular_states as $st) { echo '<option value="' . htmlspecialchars($st) . '">' . htmlspecialchars($st) . '</option>'; } ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Arrival</label>
                            <select name="arrival_location" id="add_arr" required>
                                <option value="">— Select Arrival —</option>
                                <?php foreach ($peninsular_states as $st) { echo '<option value="' . htmlspecialchars($st) . '">' . htmlspecialchars($st) . '</option>'; } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Distance (km)</label><input type="number" name="distance_km" placeholder="e.g. 350"></div>
                        <div class="form-group"><label>Est. Duration</label><input type="text" name="estimated_duration" placeholder="e.g. 4h 30m"></div>
                    </div>
                    <button type="submit" name="add_route" class="btn btn-success">Add Route</button>
                </form>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #f1f5f9;">

                <h3><?php echo $i_bus; ?>Add New Bus</h3>
                <form method="POST">
                    <input type="hidden" name="_section" value="routes">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="form-group"><label>Assigned Driver</label>
                        <select name="driver_id" required>
                            <option value="">— Select Driver —</option>
                            <?php foreach ($driver_options as $d) { ?>
                            <option value="<?php echo $d['DRIVER_ID']; ?>"><?php echo htmlspecialchars($d['DRIVER_NAME']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Bus Number</label><input type="text" name="bus_number" placeholder="e.g. BUS-001" required></div>
                        <div class="form-group"><label>Total Seats</label><input type="number" name="total_seats" placeholder="e.g. 40" required></div>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="bus_status">
                            <option value="Active">Active</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Out of Service">Out of Service</option>
                        </select>
                    </div>
                    <button type="submit" name="add_bus" class="btn btn-success">Add Bus</button>
                </form>
            </div>
            <div class="card">
                <h3><?php echo $i_route; ?>Existing Routes <span class="tag tag-blue" style="margin-left:auto;"><?php echo $rc; ?> total</span></h3>
                <div class="table-wrap" style="margin-bottom:20px;">
                    <table>
                        <thead><tr><th>ID</th><th>Route</th><th>From</th><th>To</th><th>Distance</th><th>Duration</th><th>Buses</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $has = false; while ($r = oci_fetch_array($route_q, OCI_ASSOC)) { $has = true; ?>
                            <tr>
                                <td>#<?php echo $r['ROUTE_ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($r['ROUTE_NAME']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['DEPARTURE_LOCATION']); ?></td>
                                <td><?php echo htmlspecialchars($r['ARRIVAL_LOCATION']); ?></td>
                                <td><?php echo $r['DISTANCE_KM'] ? $r['DISTANCE_KM'] . ' km' : '—'; ?></td>
                                <td><?php echo htmlspecialchars($r['ESTIMATED_DURATION'] ?? '—'); ?></td>
                                <td style="font-size:12px;"><?php echo htmlspecialchars($r['ASSIGNED_BUSES'] ?? '—'); ?></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="editRoute(<?php echo $r['ROUTE_ID']; ?>, '<?php echo htmlspecialchars(addslashes($r['ROUTE_NAME'])); ?>', '<?php echo htmlspecialchars(addslashes($r['DEPARTURE_LOCATION'])); ?>', '<?php echo htmlspecialchars(addslashes($r['ARRIVAL_LOCATION'])); ?>', '<?php echo $r['DISTANCE_KM']; ?>', '<?php echo htmlspecialchars(addslashes($r['ESTIMATED_DURATION'] ?? '')); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this route?');">
                                        <input type="hidden" name="_section" value="routes">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="route_id" value="<?php echo $r['ROUTE_ID']; ?>">
                                        <button type="submit" name="delete_route" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php } if (!$has) { echo '<tr><td colspan="8"><div class="empty">No routes defined.</div></td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>

                <h3><?php echo $i_bus; ?>Existing Buses <span class="tag tag-amber" style="margin-left:auto;"><?php echo $bc; ?> total</span></h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Bus #</th><th>Driver</th><th>Seats</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $has_b = false; while ($b = oci_fetch_array($bus_q, OCI_ASSOC)) { $has_b = true;
                                $bs = strtolower($b['BUS_STATUS'] ?? '');
                                $bc_tag = $bs === 'active' ? 'tag-green' : ($bs === 'maintenance' ? 'tag-amber' : 'tag-red');
                            ?>
                            <tr>
                                <td>#<?php echo $b['BUS_ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($b['BUS_NUMBER']); ?></strong></td>
                                <td><?php echo htmlspecialchars($b['DRIVER_NAME'] ?? '—'); ?></td>
                                <td><?php echo $b['TOTAL_SEATS']; ?></td>
                                <td><span class="tag <?php echo $bc_tag; ?>"><?php echo htmlspecialchars($b['BUS_STATUS']); ?></span></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="editBus(<?php echo $b['BUS_ID']; ?>, <?php echo intval($b['DRIVER_ID']); ?>, '<?php echo htmlspecialchars(addslashes($b['BUS_NUMBER'])); ?>', <?php echo intval($b['TOTAL_SEATS']); ?>, '<?php echo htmlspecialchars(addslashes($b['BUS_STATUS'])); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this bus?');">
                                        <input type="hidden" name="_section" value="routes">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="bus_id" value="<?php echo $b['BUS_ID']; ?>">
                                        <button type="submit" name="delete_bus" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php } if (!$has_b) { echo '<tr><td colspan="6"><div class="empty">No buses registered.</div></td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DRIVERS -->
    <div id="section-drivers" class="section <?php echo $active_section === 'drivers' ? 'active' : ''; ?>">
        <div class="page-header"><h2><?php echo $i_user; ?>Driver Details Management</h2><p>Add, edit, and manage driver profiles</p></div>
        <div class="grid-2">
            <div class="card">
                <h3>Add New Driver</h3>
                <form method="POST">
                    <input type="hidden" name="_section" value="drivers">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name</label><input type="text" name="driver_name" required></div>
                        <div class="form-group"><label>Gender</label><select name="gender"><option value="Male">Male</option><option value="Female">Female</option></select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
                        <div class="form-group"><label>IC Number</label><input type="text" name="ic_number" placeholder="e.g. 900101-01-1234"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone_number" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Login password" required></div>
                        <div class="form-group"><label>License #</label><input type="text" name="license_number" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" value="0"></div>
                        <div class="form-group"><label>Health Status</label><select name="health_status"><option value="Fit">Fit</option><option value="Unfit">Unfit</option><option value="Under Review">Under Review</option></select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Safety Certification</label><input type="text" name="safety_certification" placeholder="e.g. Certified"></div>
                        <div class="form-group"><label>Employment</label><select name="employment_status"><option value="Active">Active</option><option value="Suspended">Suspended</option><option value="Inactive">Inactive</option></select></div>
                    </div>
                    <button type="submit" name="add_driver" class="btn btn-success">Add Driver</button>
                </form>
            </div>
            <div class="card">
                <h3>Existing Drivers <span class="tag tag-green" style="margin-left:auto;"><?php echo $dc; ?> total</span></h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>License</th><th>Health</th><th>Cert</th><th>Exp</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $has = false; while ($r = oci_fetch_array($driver_q, OCI_ASSOC)) { $has = true;
                                $hs = strtolower($r['HEALTH_STATUS'] ?? '');
                                $hc = $hs === 'fit' ? 'tag-green' : ($hs === 'unfit' ? 'tag-red' : 'tag-amber');
                                $es = strtolower($r['EMPLOYMENT_STATUS'] ?? '');
                                $ec = $es === 'active' ? 'tag-green' : 'tag-red';
                            ?>
                            <tr>
                                <td>#<?php echo $r['DRIVER_ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($r['DRIVER_NAME']); ?></strong></td>
                                <td style="font-size:12px;"><?php echo htmlspecialchars($r['EMAIL']); ?><br><?php echo htmlspecialchars($r['PHONE_NUMBER']); ?></td>
                                <td><?php echo htmlspecialchars($r['LICENSE_NUMBER']); ?></td>
                                <td><span class="tag <?php echo $hc; ?>"><?php echo htmlspecialchars($r['HEALTH_STATUS'] ?? '—'); ?></span></td>
                                <td><?php echo htmlspecialchars($r['SAFETY_CERTIFICATION'] ?? '—'); ?></td>
                                <td><?php echo $r['EXPERIENCE_YEARS']; ?> yrs</td>
                                <td><span class="tag <?php echo $ec; ?>"><?php echo htmlspecialchars($r['EMPLOYMENT_STATUS']); ?></span></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="editDriver(<?php echo $r['DRIVER_ID']; ?>, '<?php echo htmlspecialchars(addslashes($r['DRIVER_NAME'])); ?>', '<?php echo htmlspecialchars(addslashes($r['GENDER'])); ?>', '<?php echo htmlspecialchars(addslashes($r['EMAIL'])); ?>', '<?php echo htmlspecialchars(addslashes($r['PHONE_NUMBER'])); ?>', '<?php echo htmlspecialchars(addslashes($r['LICENSE_NUMBER'])); ?>', <?php echo $r['EXPERIENCE_YEARS']; ?>, '<?php echo htmlspecialchars(addslashes($r['HEALTH_STATUS'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($r['SAFETY_CERTIFICATION'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($r['EMPLOYMENT_STATUS'])); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this driver?');">
                                        <input type="hidden" name="_section" value="drivers">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="driver_id" value="<?php echo $r['DRIVER_ID']; ?>">
                                        <button type="submit" name="delete_driver" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php } if (!$has) { echo '<tr><td colspan="9"><div class="empty">No drivers registered.</div></td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SCHEDULES -->
    <div id="section-schedules" class="section <?php echo $active_section === 'schedules' ? 'active' : ''; ?>">
        <div class="page-header"><h2><?php echo $i_cal; ?>Schedule Management</h2><p>Assign buses, drivers, and departure times to routes</p></div>
        <div class="grid-2">
            <div class="card">
                <h3>➕ Add New Schedule</h3>
                <form method="POST">
                    <input type="hidden" name="_section" value="schedules">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="form-group"><label>Route</label>
                        <select name="route_id" required>
                            <option value="">— Select Route —</option>
                            <?php foreach ($route_options as $r) { ?>
                            <option value="<?php echo $r['ROUTE_ID']; ?>"><?php echo htmlspecialchars($r['ROUTE_NAME']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Bus</label>
                            <select name="bus_id" id="as_bus" required onchange="updateDriver()">
                                <option value="">— Select Bus —</option>
                                <?php foreach ($bus_options as $b) { ?>
                                <option value="<?php echo $b['BUS_ID']; ?>" data-driver-id="<?php echo $b['DRIVER_ID']; ?>" data-driver-name="<?php echo htmlspecialchars($b['DRIVER_NAME'] ?? '—'); ?>"><?php echo htmlspecialchars($b['BUS_NUMBER']); ?> (<?php echo htmlspecialchars($b['DRIVER_NAME'] ?? 'No driver'); ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Driver</label>
                            <input type="text" id="as_driver" class="form-control" readonly placeholder="Select a bus first" style="background:#1e293b;color:#94a3b8;">
                        </div>
                    </div>
                    <div class="form-group"><label>Departure Date &amp; Time</label>
                        <input type="datetime-local" name="departure_datetime" required>
                        <div class="form-hint" style="font-size:11px;color:#94a3b8;margin-top:2px;">Format: YYYY-MM-DD HH:MM (24-hour)</div>
                    </div>
                    <button type="submit" name="add_schedule" class="btn btn-success">Add Schedule</button>
                </form>
            </div>
            <div class="card">
                <h3><?php echo $i_cal; ?>Existing Schedules <span class="tag tag-blue" style="margin-left:auto;"><?php echo $sc; ?> total</span></h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Route</th><th>Bus</th><th>Driver</th><th>Departure</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $has_s = false; while ($s = oci_fetch_array($sched_q, OCI_ASSOC)) { $has_s = true; ?>
                            <tr>
                                <td>#<?php echo $s['SCHEDULE_ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($s['ROUTE_NAME']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['BUS_NUMBER']); ?></td>
                                <td><?php echo htmlspecialchars($s['DRIVER_NAME']); ?></td>
                                <td><?php echo $s['DEPART_FMT']; ?></td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="editSchedule(<?php echo $s['SCHEDULE_ID']; ?>, <?php echo intval($s['ROUTE_ID']); ?>, <?php echo intval($s['BUS_ID']); ?>, '<?php echo $s['DEPART_FMT']; ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule?');">
                                        <input type="hidden" name="_section" value="schedules">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="schedule_id" value="<?php echo $s['SCHEDULE_ID']; ?>">
                                        <button type="submit" name="delete_schedule" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php } if (!$has_s) { echo '<tr><td colspan="6"><div class="empty">No schedules defined.</div></td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Edit Route Modal -->
<div id="editRouteModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editRouteModal')">&times;</button>
        <h3><?php echo $i_route; ?>Edit Route</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="routes">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="route_id" id="er_id">
            <div class="form-group"><label>Route Name</label><input type="text" name="route_name" id="er_name" required></div>
            <div class="form-row">
                <div class="form-group"><label>Departure</label>
                    <select name="departure_location" id="er_dep" required>
                        <option value="">— Select Departure —</option>
                        <?php foreach ($peninsular_states as $st) { echo '<option value="' . htmlspecialchars($st) . '">' . htmlspecialchars($st) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group"><label>Arrival</label>
                    <select name="arrival_location" id="er_arr" required>
                        <option value="">— Select Arrival —</option>
                        <?php foreach ($peninsular_states as $st) { echo '<option value="' . htmlspecialchars($st) . '">' . htmlspecialchars($st) . '</option>'; } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Distance (km)</label><input type="number" name="distance_km" id="er_dist"></div>
                <div class="form-group"><label>Est. Duration</label><input type="text" name="estimated_duration" id="er_dur"></div>
            </div>
            <button type="submit" name="edit_route" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Driver Modal -->
<div id="editDriverModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editDriverModal')">&times;</button>
        <h3><?php echo $i_user; ?>Edit Driver</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="drivers">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="driver_id" id="ed_id">
            <div class="form-row">
                <div class="form-group"><label>Full Name</label><input type="text" name="driver_name" id="ed_name" required></div>
                <div class="form-group"><label>Gender</label><select name="gender" id="ed_gender"><option value="Male">Male</option><option value="Female">Female</option></select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" name="email" id="ed_email" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone_number" id="ed_phone" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>License #</label><input type="text" name="license_number" id="ed_lic" required></div>
                <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" id="ed_exp"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Health Status</label><select name="health_status" id="ed_health"><option value="Fit">Fit</option><option value="Unfit">Unfit</option><option value="Under Review">Under Review</option></select></div>
                <div class="form-group"><label>Safety Cert</label><input type="text" name="safety_certification" id="ed_safety"></div>
            </div>
            <div class="form-group"><label>Employment</label><select name="employment_status" id="ed_emp"><option value="Active">Active</option><option value="Suspended">Suspended</option><option value="Inactive">Inactive</option></select></div>
            <button type="submit" name="edit_driver" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Bus Modal -->
<div id="editBusModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editBusModal')">&times;</button>
        <h3><?php echo $i_bus; ?>Edit Bus</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="routes">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="bus_id" id="eb_id">
            <div class="form-group"><label>Assigned Driver</label>
                <select name="driver_id" id="eb_driver" required>
                    <option value="">— Select Driver —</option>
                    <?php foreach ($driver_options as $d) { ?>
                    <option value="<?php echo $d['DRIVER_ID']; ?>"><?php echo htmlspecialchars($d['DRIVER_NAME']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Bus Number</label><input type="text" name="bus_number" id="eb_number" required></div>
                <div class="form-group"><label>Total Seats</label><input type="number" name="total_seats" id="eb_seats" required></div>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="bus_status" id="eb_status">
                    <option value="Active">Active</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Out of Service">Out of Service</option>
                </select>
            </div>
            <button type="submit" name="edit_bus" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editScheduleModal" class="modal-overlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editScheduleModal')">&times;</button>
        <h3><?php echo $i_cal; ?>Edit Schedule</h3>
        <form method="POST">
            <input type="hidden" name="_section" value="schedules">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="schedule_id" id="es_id">
            <div class="form-group"><label>Route</label>
                <select name="route_id" id="es_route" required>
                    <option value="">— Select Route —</option>
                    <?php foreach ($route_options as $r) { ?>
                    <option value="<?php echo $r['ROUTE_ID']; ?>"><?php echo htmlspecialchars($r['ROUTE_NAME']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Bus</label>
                    <select name="bus_id" id="es_bus" required onchange="updateEditDriver()">
                        <option value="">— Select Bus —</option>
                        <?php foreach ($bus_options as $b) { ?>
                        <option value="<?php echo $b['BUS_ID']; ?>" data-driver-id="<?php echo $b['DRIVER_ID']; ?>" data-driver-name="<?php echo htmlspecialchars($b['DRIVER_NAME'] ?? '—'); ?>"><?php echo htmlspecialchars($b['BUS_NUMBER']); ?> (<?php echo htmlspecialchars($b['DRIVER_NAME'] ?? 'No driver'); ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group"><label>Driver</label>
                    <input type="text" id="es_driver" class="form-control" readonly placeholder="Select a bus first" style="background:#1e293b;color:#94a3b8;">
                </div>
            </div>
            <div class="form-group"><label>Departure Date &amp; Time</label>
                <input type="text" name="departure_datetime" id="es_depart" placeholder="YYYY-MM-DD HH:MM" required>
                <div class="form-hint" style="font-size:11px;color:#94a3b8;margin-top:2px;">Format: YYYY-MM-DD HH:MM (24-hour)</div>
            </div>
            <button type="submit" name="edit_schedule" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<script>
function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + id).classList.add('active');
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    let link = document.querySelector('.sidebar-nav a[data-section="' + id + '"]');
    if (link) link.classList.add('active');
    document.querySelector('.sidebar').classList.remove('open');
    window.location.hash = id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
document.querySelectorAll('.sidebar-nav a[data-section]').forEach(a => {
    a.addEventListener('click', function(e) { e.preventDefault(); showSection(this.dataset.section); });
});
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function editRoute(id, name, dep, arr, dist, dur) {
    document.getElementById('er_id').value = id;
    document.getElementById('er_name').value = name;
    document.getElementById('er_dep').value = dep;
    document.getElementById('er_arr').value = arr;
    document.getElementById('er_dist').value = dist;
    document.getElementById('er_dur').value = dur;
    document.getElementById('editRouteModal').classList.add('open');
}
function editDriver(id, name, gender, email, phone, lic, exp, health, safety, emp) {
    document.getElementById('ed_id').value = id;
    document.getElementById('ed_name').value = name;
    document.getElementById('ed_gender').value = gender;
    document.getElementById('ed_email').value = email;
    document.getElementById('ed_phone').value = phone;
    document.getElementById('ed_lic').value = lic;
    document.getElementById('ed_exp').value = exp;
    document.getElementById('ed_health').value = health;
    document.getElementById('ed_safety').value = safety;
    document.getElementById('ed_emp').value = emp;
    document.getElementById('editDriverModal').classList.add('open');
}
function editBus(id, driverId, number, seats, status) {
    document.getElementById('eb_id').value = id;
    document.getElementById('eb_driver').value = driverId;
    document.getElementById('eb_number').value = number;
    document.getElementById('eb_seats').value = seats;
    document.getElementById('eb_status').value = status;
    document.getElementById('editBusModal').classList.add('open');
}
function updateDriver() {
    var sel = document.getElementById('as_bus');
    var driverField = document.getElementById('as_driver');
    if (sel.selectedIndex > 0) {
        var opt = sel.options[sel.selectedIndex];
        driverField.value = (opt.dataset.driverName || '—') + ' (ID: ' + (opt.dataset.driverId || '—') + ')';
    } else {
        driverField.value = 'Select a bus first';
    }
}
function updateEditDriver() {
    var sel = document.getElementById('es_bus');
    var driverField = document.getElementById('es_driver');
    if (sel.selectedIndex > 0) {
        var opt = sel.options[sel.selectedIndex];
        driverField.value = (opt.dataset.driverName || '—') + ' (ID: ' + (opt.dataset.driverId || '—') + ')';
    } else {
        driverField.value = 'Select a bus first';
    }
}
function editLostItem(id, name, cat, desc, busId, status) {
    document.getElementById('el_id').value = id;
    document.getElementById('el_name').value = name;
    document.getElementById('el_cat').value = cat;
    document.getElementById('el_desc').value = desc;
    document.getElementById('el_bus').value = busId || '';
    document.getElementById('el_status').value = status;
    document.getElementById('editLostModal').classList.add('open');
}
function editSchedule(id, routeId, busId, depart) {
    document.getElementById('es_id').value = id;
    document.getElementById('es_route').value = routeId;
    document.getElementById('es_bus').value = busId;
    updateEditDriver();
    document.getElementById('es_depart').value = depart;
    document.getElementById('editScheduleModal').classList.add('open');
}
// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
// ─── Charts ───
var bkCtx = document.getElementById('chartBookings').getContext('2d');
new Chart(bkCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($bk_months); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode($bk_counts); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.1)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#2563eb',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
    }
});
var ccCtx = document.getElementById('chartComplaints').getContext('2d');
var ccColors = ['#f59e0b','#ef4444','#3b82f6','#10b981','#8b5cf6','#ec4899','#06b6d4'];
new Chart(ccCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($cc_labels); ?>,
        datasets: [{
            label: 'Complaints',
            data: <?php echo json_encode($cc_counts); ?>,
            backgroundColor: ccColors.slice(0, <?php echo count($cc_labels); ?>),
            borderRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
    }
});
// ─── End Charts ───

// ─── Shah Alam hub logic: restrict arrival based on departure ───
function filterArrival(depSelect, arrSelect) {
    var dep = depSelect.value;
    var options = arrSelect.options;
    for (var i = 0; i < options.length; i++) {
        var v = options[i].value;
        if (v === '') continue;
        if (dep === '' || dep === 'Shah Alam') {
            options[i].style.display = '';
        } else {
            options[i].style.display = (v === 'Shah Alam') ? '' : 'none';
        }
    }
    if (dep !== '' && dep !== 'Shah Alam' && arrSelect.value !== 'Shah Alam') {
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === 'Shah Alam') { arrSelect.value = 'Shah Alam'; break; }
        }
    }
}
document.getElementById('add_dep').addEventListener('change', function() { filterArrival(this, document.getElementById('add_arr')); });
document.getElementById('er_dep').addEventListener('change', function() { filterArrival(this, document.getElementById('er_arr')); });
filterArrival(document.getElementById('add_dep'), document.getElementById('add_arr'));
filterArrival(document.getElementById('er_dep'), document.getElementById('er_arr'));
// ─── End Shah Alam logic ───

var hash = window.location.hash.replace('#', '');
if (hash && document.getElementById('section-' + hash)) {
    showSection(hash);
} else {
    showSection('<?php echo $active_section; ?>');
}
</script>

</body>
</html>
<?php
oci_free_statement($comp_q);
oci_free_statement($route_q);
oci_free_statement($bus_q);
oci_free_statement($driver_q);
oci_free_statement($sched_q);
oci_close($conn);
?>
