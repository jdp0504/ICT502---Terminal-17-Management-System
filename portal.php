<?php
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

include('db.php');

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Referred by
$referred_by_name = '';
$ref_q = oci_parse($conn, "SELECT r.CUSTOMER_NAME FROM CUSTOMER c JOIN CUSTOMER r ON c.REFERRED_BY = r.CUSTOMER_ID WHERE c.CUSTOMER_ID = :cid");
oci_bind_by_name($ref_q, ':cid', $customer_id);
oci_execute($ref_q);
if ($ref_r = oci_fetch_array($ref_q, OCI_ASSOC)) { $referred_by_name = $ref_r['CUSTOMER_NAME']; }
oci_free_statement($ref_q);

// CSRF token
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

// ─── SVG Icons ───
$i_user = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$i_phone = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
$i_mail = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
$i_clock = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$i_cal = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$i_ticket = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><circle cx="12" cy="12" r="1"/></svg>';
$i_search = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
$i_map = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/><line x1="8" y1="2" x2="8" y2="22"/></svg>';
$i_bell = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
$i_pin = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
$i_money = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>';
$i_file = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
$i_pin2 = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M12 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6z"/><circle cx="12" cy="8" r="2"/></svg>';
$i_package = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
$i_bus = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><rect x="2" y="4" width="20" height="14" rx="2"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/><line x1="2" y1="12" x2="22" y2="12"/></svg>';

// ─── Booking POST handler (before queries, needs transaction control) ───
$bk_msg = ''; $bk_msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $bk_msg = 'Invalid session. Please refresh and try again.';
        $bk_msg_type = 'error';
    } else {
        $schedule_id = intval($_POST['schedule_id']);
        // Lock bus row for this schedule to serialize seat availability
        $lk = oci_parse($conn, "SELECT s.BUS_ID, b.TOTAL_SEATS, r.DISTANCE_KM
                                 FROM SCHEDULE s
                                 JOIN BUS b ON s.BUS_ID = b.BUS_ID
                                 JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID
                                 WHERE s.SCHEDULE_ID = :sid
                                 FOR UPDATE OF b.TOTAL_SEATS");
        oci_bind_by_name($lk, ':sid', $schedule_id);
        oci_execute($lk, OCI_NO_AUTO_COMMIT);
        $lkr = oci_fetch_array($lk, OCI_ASSOC);
        if (!$lkr) { $bk_msg = 'Schedule not found.'; $bk_msg_type = 'error'; oci_rollback($conn); }
        else {
            $tseats = intval($lkr['TOTAL_SEATS']);
            $dist = floatval($lkr['DISTANCE_KM']);
            $cnt = oci_parse($conn, "SELECT COUNT(*) AS C FROM BOOKING WHERE SCHEDULE_ID = :sid AND LOWER(BOOKING_STATUS) != 'cancelled'");
            oci_bind_by_name($cnt, ':sid', $schedule_id);
            oci_execute($cnt, OCI_NO_AUTO_COMMIT);
            $cntr = oci_fetch_array($cnt, OCI_ASSOC);
            $avail = $tseats - intval($cntr['C']);
            if ($avail <= 0) { $bk_msg = 'Sorry, no seats available on this trip.'; $bk_msg_type = 'error'; oci_rollback($conn); }
            else {
                $fare = round($dist * 0.06, 2);
                $ins = oci_parse($conn, "INSERT INTO BOOKING (BOOKING_ID, CUSTOMER_ID, SCHEDULE_ID, BOOKING_DATE_TIME, TOTAL_PASSENGER, TOTAL_FARE, BOOKING_STATUS) VALUES (BOOKING_SEQ.NEXTVAL, :cid, :sid, SYSDATE, :pc, :fare, 'Confirmed')");
                $pc = 1;
                oci_bind_by_name($ins, ':cid', $customer_id);
                oci_bind_by_name($ins, ':sid', $schedule_id);
                oci_bind_by_name($ins, ':pc', $pc);
                oci_bind_by_name($ins, ':fare', $fare);
                if (oci_execute($ins, OCI_NO_AUTO_COMMIT)) {
                    $cur = oci_parse($conn, "SELECT BOOKING_SEQ.CURRVAL FROM DUAL");
                    oci_execute($cur, OCI_NO_AUTO_COMMIT);
                    $cr = oci_fetch_array($cur, OCI_NUM);
                    $new_id = intval($cr[0]);
                    oci_free_statement($cur);
                    oci_commit($conn);
                    $bk_msg = "Booking #$new_id confirmed! Fare: RM$fare.";
                    $bk_msg_type = 'success';
                } else { $e = oci_error($ins); $bk_msg = 'Error: ' . htmlentities($e['message']); $bk_msg_type = 'error'; oci_rollback($conn); }
                oci_free_statement($ins);
            }
            oci_free_statement($cnt); oci_free_statement($lk);
        }
    }
}

// ─── Complaint POST handler ───
$comp_msg = ''; $comp_msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $comp_msg = 'Invalid session.'; $comp_msg_type = 'error';
    } else {
        $comp_title = trim($_POST['complaint_title']);
        $comp_cat = $_POST['complaint_category'];
        $comp_descr = trim($_POST['complaint_description']);
        $comp_bus = !empty($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
        $comp_id = rand(10000, 49999);
        $ins = oci_parse($conn, "INSERT INTO COMPLAINT (COMPLAINT_ID, CUSTOMER_ID, BUS_ID, COMPLAINT_TITLE, COMPLAINT_CATEGORY, COMPLAINT_DESCRIPTION, COMPLAINT_STATUS, COMPLAINT_DATE_TIME) VALUES (:id, :cid, :bid, :title, :cat, :descr, 'Pending', SYSDATE)");
        oci_bind_by_name($ins, ':id', $comp_id);
        oci_bind_by_name($ins, ':cid', $customer_id);
        oci_bind_by_name($ins, ':bid', $comp_bus);
        oci_bind_by_name($ins, ':title', $comp_title);
        oci_bind_by_name($ins, ':cat', $comp_cat);
        oci_bind_by_name($ins, ':descr', $comp_descr);
        if (oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
            $comp_msg = "Complaint #$comp_id filed! Status: Pending."; $comp_msg_type = 'success';
        } else { $e = oci_error($ins); $comp_msg = 'Error: ' . htmlentities($e['message']); $comp_msg_type = 'error'; }
        oci_free_statement($ins);
    }
}

// ─── Lost & Found POST handlers ───
$lf_msg = ''; $lf_msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_item'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $lf_msg = 'Invalid session.'; $lf_msg_type = 'error';
    } else {
        $item_name = trim($_POST['item_name']);
        $item_cat = $_POST['item_category'];
        $item_descr = trim($_POST['item_description']);
        $item_bus = intval($_POST['bus_id']);
        $item_id = rand(1000, 9999);
        $ins = oci_parse($conn, "INSERT INTO LOST_ITEM (LOST_ITEM_ID, CUSTOMER_ID, BUS_ID, ITEM_NAME, ITEM_CATEGORY, ITEM_DESCRIPTION, LOST_DATE, CLAIM_STATUS) VALUES (:id, :cid, :bid, :iname, :cat, :descr, SYSDATE, 'Unclaimed')");
        oci_bind_by_name($ins, ':id', $item_id);
        oci_bind_by_name($ins, ':cid', $customer_id);
        oci_bind_by_name($ins, ':bid', $item_bus);
        oci_bind_by_name($ins, ':iname', $item_name);
        oci_bind_by_name($ins, ':cat', $item_cat);
        oci_bind_by_name($ins, ':descr', $item_descr);
        if (oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
            $lf_msg = "Lost item reported! ID: #$item_id."; $lf_msg_type = 'success';
        } else { $e = oci_error($ins); $lf_msg = 'Error: ' . htmlentities($e['message']); $lf_msg_type = 'error'; }
        oci_free_statement($ins);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_item'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $lf_msg = 'Invalid session.'; $lf_msg_type = 'error';
    } else {
        $claim_id = intval($_POST['item_id']);
        $up = oci_parse($conn, "UPDATE LOST_ITEM SET CLAIM_STATUS = 'Claimed' WHERE LOST_ITEM_ID = :id AND CLAIM_STATUS = 'Unclaimed'");
        oci_bind_by_name($up, ':id', $claim_id);
        if (oci_execute($up, OCI_COMMIT_ON_SUCCESS) && oci_num_rows($up) > 0) {
            $lf_msg = "Item #$claim_id marked as Claimed!"; $lf_msg_type = 'success';
        } else { $lf_msg = 'Could not claim item.'; $lf_msg_type = 'error'; }
        oci_free_statement($up);
    }
}

// All queries parsed first

$drv_query = "SELECT d.*, r.ROUTE_NAME, b.BUS_NUMBER,
                     TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_TIME
              FROM DRIVER d
              LEFT JOIN SCHEDULE s ON d.DRIVER_ID = s.DRIVER_ID
              LEFT JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID
              LEFT JOIN BUS b ON s.BUS_ID = b.BUS_ID
              ORDER BY d.DRIVER_NAME, s.DEPARTURE_DATE_TIME DESC";
$drv_stmt = oci_parse($conn, $drv_query);

// FIX: Changed b.BUS_ID to s.BUS_ID because BOOKING table does not contain BUS_ID column (ORA-00904)
$alert_query = "SELECT b.BOOKING_ID, r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION,
                       TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_TIME,
                       s.DEPARTURE_DATE_TIME as DEPART_RAW,
                       b.BOOKING_STATUS, s.BUS_ID, bu.BUS_STATUS
                FROM BOOKING b
                JOIN SCHEDULE s ON b.SCHEDULE_ID = s.SCHEDULE_ID
                JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID
                JOIN BUS bu ON s.BUS_ID = bu.BUS_ID
                WHERE b.CUSTOMER_ID = :cid
                ORDER BY s.DEPARTURE_DATE_TIME";
$alert_stmt = oci_parse($conn, $alert_query);
oci_bind_by_name($alert_stmt, ':cid', $customer_id);

// My Bookings query
$mybk_query = "SELECT b.BOOKING_ID, r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION,
                       r.DISTANCE_KM, r.ESTIMATED_DURATION,
                       bu.BUS_NUMBER, bu.BUS_STATUS,
                       d.DRIVER_NAME, d.PHONE_NUMBER, d.EMAIL,
                       d.HEALTH_STATUS, d.SAFETY_CERTIFICATION, d.EXPERIENCE_YEARS,
                       d.LICENSE_NUMBER, d.EMPLOYMENT_STATUS,
                       TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_FMT,
                       b.TOTAL_PASSENGER, b.TOTAL_FARE, b.BOOKING_STATUS
                FROM BOOKING b
                JOIN SCHEDULE s ON b.SCHEDULE_ID = s.SCHEDULE_ID
                JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID
                JOIN BUS bu ON s.BUS_ID = bu.BUS_ID
                JOIN DRIVER d ON s.DRIVER_ID = d.DRIVER_ID
                WHERE b.CUSTOMER_ID = :cid
                ORDER BY b.BOOKING_DATE_TIME DESC";
$mybk_stmt = oci_parse($conn, $mybk_query);
oci_bind_by_name($mybk_stmt, ':cid', $customer_id);

// Complaint history
$comp_query = "SELECT COMPLAINT_ID, COMPLAINT_TITLE, COMPLAINT_CATEGORY, COMPLAINT_STATUS, COMPLAINT_DESCRIPTION, TO_CHAR(COMPLAINT_DATE_TIME, 'YYYY-MM-DD HH24:MI') AS FILED FROM COMPLAINT WHERE CUSTOMER_ID = :cid ORDER BY COMPLAINT_DATE_TIME DESC";
$comp_stmt = oci_parse($conn, $comp_query);
oci_bind_by_name($comp_stmt, ':cid', $customer_id);

// Bus dropdown for complaint form
$comp_bus_stmt = oci_parse($conn, "SELECT BUS_ID, BUS_NUMBER, BUS_STATUS FROM BUS ORDER BY BUS_NUMBER");

// Lost items query
$lf_query = "SELECT l.LOST_ITEM_ID, l.ITEM_NAME, l.ITEM_CATEGORY, l.LOST_DATE, l.CLAIM_STATUS, b.BUS_NUMBER, c.CUSTOMER_NAME, c.EMAIL, c.PHONE_NUMBER, d.DRIVER_NAME, d.PHONE_NUMBER AS DRIVER_PHONE FROM LOST_ITEM l LEFT JOIN BUS b ON l.BUS_ID = b.BUS_ID LEFT JOIN CUSTOMER c ON l.CUSTOMER_ID = c.CUSTOMER_ID LEFT JOIN DRIVER d ON b.DRIVER_ID = d.DRIVER_ID WHERE l.CUSTOMER_ID = :cid2 ORDER BY l.LOST_ITEM_ID DESC";
$lf_stmt = oci_parse($conn, $lf_query);
oci_bind_by_name($lf_stmt, ':cid2', $customer_id);

// Bus dropdown for lost & found form
$lf_bus_stmt = oci_parse($conn, "SELECT BUS_ID, BUS_NUMBER FROM BUS ORDER BY BUS_NUMBER");

// Route query
$route_query = "SELECT * FROM ROUTE ORDER BY ROUTE_NAME";
$route_stmt = oci_parse($conn, $route_query);

// Schedule query — upcoming departures
$sched_query = "SELECT s.SCHEDULE_ID, r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION, b.BUS_NUMBER, d.DRIVER_NAME, TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_FMT FROM SCHEDULE s JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID JOIN BUS b ON s.BUS_ID = b.BUS_ID JOIN DRIVER d ON s.DRIVER_ID = d.DRIVER_ID WHERE s.DEPARTURE_DATE_TIME > SYSDATE ORDER BY s.DEPARTURE_DATE_TIME";
$sched_stmt = oci_parse($conn, $sched_query);

// Stat counters
$stat_bk = oci_parse($conn, "SELECT COUNT(*) as C FROM BOOKING WHERE CUSTOMER_ID = :cid");
oci_bind_by_name($stat_bk, ':cid', $customer_id);
$stat_lf = oci_parse($conn, "SELECT COUNT(*) as C FROM LOST_ITEM WHERE CUSTOMER_ID = :cid AND LOWER(CLAIM_STATUS) = 'unclaimed'");
oci_bind_by_name($stat_lf, ':cid', $customer_id);
$stat_route = oci_parse($conn, "SELECT COUNT(*) as C FROM ROUTE");
$stat_sched = oci_parse($conn, "SELECT COUNT(*) as C FROM SCHEDULE WHERE DEPARTURE_DATE_TIME > SYSDATE");

// Location dropdowns for search — Peninsular Malaysia states
$peninsular_states = ['Johor','Kedah','Kelantan','Kuala Lumpur','Malacca','Negeri Sembilan','Pahang','Penang','Perak','Perlis','Putrajaya','Selangor','Shah Alam','Terengganu'];

// Search results query (conditional)
$search_results = null;
// Check both GET and POST for search params (POST used for booking form + hidden fields to preserve context)
$s_dep = $_GET['search_departure'] ?? $_POST['s_dep'] ?? '';
$s_arr = $_GET['search_arrival'] ?? $_POST['s_arr'] ?? '';
$s_date = $_GET['search_date'] ?? $_POST['s_date'] ?? '';
// Shah Alam hub rule: only Shah Alam can be a destination unless departing from Shah Alam
if ($s_dep !== '' && $s_dep !== 'Shah Alam' && $s_arr !== 'Shah Alam') {
    $s_arr = 'Shah Alam';
}
$searched = $s_dep !== '';
if ($searched) {
    $srch_sql = "SELECT s.SCHEDULE_ID, r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION,
                        b.BUS_NUMBER, d.DRIVER_NAME,
                        TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') as DEPART_FMT,
                        b.TOTAL_SEATS,
                        (b.TOTAL_SEATS - (SELECT COUNT(*) FROM BOOKING bk WHERE bk.SCHEDULE_ID = s.SCHEDULE_ID AND LOWER(bk.BOOKING_STATUS) != 'cancelled')) AS AVAILABLE_SEATS,
                        ROUND(r.DISTANCE_KM * 0.06, 2) AS FARE
                 FROM SCHEDULE s
                 JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID
                 JOIN BUS b ON s.BUS_ID = b.BUS_ID
                 JOIN DRIVER d ON s.DRIVER_ID = d.DRIVER_ID
                 WHERE r.DEPARTURE_LOCATION LIKE :dep
                   AND (:arr = '' OR r.ARRIVAL_LOCATION LIKE :arr)
                   AND (:dt = '' OR TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD') = :dt2)
                   AND s.DEPARTURE_DATE_TIME > SYSDATE
                 ORDER BY s.DEPARTURE_DATE_TIME";
    $search_results = oci_parse($conn, $srch_sql);
    $dep_like = "%$s_dep%"; $arr_like = "%$s_arr%";
    oci_bind_by_name($search_results, ':dep', $dep_like);
    oci_bind_by_name($search_results, ':arr', $arr_like);
    oci_bind_by_name($search_results, ':dt', $s_date);
    oci_bind_by_name($search_results, ':dt2', $s_date);
}

oci_execute($drv_stmt);
oci_execute($alert_stmt);
oci_execute($mybk_stmt);
oci_execute($route_stmt);
oci_execute($sched_stmt);
oci_execute($stat_bk);
oci_execute($stat_lf);
oci_execute($stat_route);
oci_execute($stat_sched);
// $loc_q and $loc_q2 replaced by static $peninsular_states array
oci_execute($comp_stmt);
oci_execute($comp_bus_stmt);
oci_execute($lf_stmt);
oci_execute($lf_bus_stmt);
if ($search_results) oci_execute($search_results);

$total_bookings = oci_fetch_array($stat_bk, OCI_ASSOC)['C'];
$unclaimed_items = oci_fetch_array($stat_lf, OCI_ASSOC)['C'];
$active_trips_q = oci_parse($conn, "SELECT COUNT(*) as C FROM BOOKING b JOIN SCHEDULE s ON b.SCHEDULE_ID = s.SCHEDULE_ID WHERE b.CUSTOMER_ID = :cid AND s.DEPARTURE_DATE_TIME > SYSDATE AND LOWER(b.BOOKING_STATUS) = 'confirmed'");
oci_bind_by_name($active_trips_q, ':cid', $customer_id);
oci_execute($active_trips_q);
$active_trips = oci_fetch_array($active_trips_q, OCI_ASSOC)['C'];
oci_free_statement($active_trips_q);

$total_routes = oci_fetch_array($stat_route, OCI_ASSOC)['C'];
$upcoming_scheds = oci_fetch_array($stat_sched, OCI_ASSOC)['C'];

// Location dropdown arrays use static Peninsular Malaysia state list
$dep_locs = $peninsular_states;
$arr_locs = $peninsular_states;

// Next trip removed

// Popular routes — first 4 upcoming schedules
$pop_stmt = oci_parse($conn, "SELECT * FROM (SELECT r.ROUTE_NAME, r.DEPARTURE_LOCATION, r.ARRIVAL_LOCATION, ROUND(r.DISTANCE_KM * 0.06, 2) AS FARE, b.BUS_NUMBER, TO_CHAR(s.DEPARTURE_DATE_TIME, 'YYYY-MM-DD HH24:MI') AS NEXT_DEPART, s.SCHEDULE_ID FROM SCHEDULE s JOIN ROUTE r ON s.ROUTE_ID = r.ROUTE_ID JOIN BUS b ON s.BUS_ID = b.BUS_ID WHERE s.DEPARTURE_DATE_TIME > SYSDATE ORDER BY s.DEPARTURE_DATE_TIME) WHERE ROWNUM <= 4");
oci_execute($pop_stmt);

// Default section: booking if search was performed, otherwise section from POST, otherwise home
$target_section = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_complaint'])) $target_section = 'complaint';
    elseif (isset($_POST['report_item']) || isset($_POST['claim_item'])) $target_section = 'lostfound';
}
$default_section = $target_section ?: ($searched ? 'booking' : 'home');
$ignore_hash = $target_section !== '' || $searched;

oci_free_statement($stat_bk);
oci_free_statement($stat_lf);
oci_free_statement($stat_route);
oci_free_statement($stat_sched);
// $loc_q/$loc_q2 removed — using static $peninsular_states instead

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal 17 — Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #1e293b;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* ── Top Nav ── */
        .topnav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 72px;
            background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 50%, #0f172a 100%);
            color: #e2e8f0;
            z-index: 100;
            display: flex;
            align-items: center;
            padding: 0 32px;
        }
        .topnav-inner {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            gap: 20px;
        }
        .topnav-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .topnav-brand h1 { font-size: 17px; font-weight: 700; color: #fff; }
        .topnav-subtitle { font-size: 11px; color: #64748b; }
        .topnav-links {
            display: flex;
            gap: 2px;
            flex: 1;
            overflow: visible;
        }
        .topnav-links a {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.75);
            white-space: nowrap;
            transition: all 0.15s;
        }
        .topnav-links a:hover, .topnav-links a.active { background: rgba(0,0,0,0.15); color: #fff; }

        /* ── Dropdown ── */
        .topnav-dropdown { position: relative; display: inline-flex; }
        .topnav-dropdown summary {
            list-style: none; cursor: pointer; user-select: none;
            padding: 6px 12px; border-radius: 6px;
            font-size: 13px; font-weight: 500;
            color: rgba(255,255,255,0.75); white-space: nowrap;
            transition: all 0.15s;
        }
        .topnav-dropdown summary::-webkit-details-marker { display: none; }
        .topnav-dropdown summary:hover,
        .topnav-dropdown[open] summary { background: rgba(0,0,0,0.15); color: #fff; }
        .topnav-dropdown .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            border-radius: 8px;
            padding: 4px;
            min-width: 180px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            z-index: 200;
            margin-top: 4px;
        }
        .dropdown-menu a {
            display: block;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .dropdown-menu a:hover { background: #f1f5f9; color: #0f172a; }
        .dropdown-menu a.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .topnav-user {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .topnav-name { font-size: 13px; font-weight: 600; color: #fff; }
        .topnav-bell {
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
            line-height: 1;
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.75);
        }
        .topnav-bell:hover { background: rgba(0,0,0,0.15); color: #fff; }
        .topnav-logout { font-size: 12px; color: rgba(255,255,255,0.7); }
        .topnav-logout:hover { color: #fff; }

        /* ── Main ── */
        .main {
            margin: 0 auto;
            padding: 80px 40px 60px;
            max-width: 1400px;
        }
        .page-header { margin-bottom: 28px; }
        .page-header h2 { font-size: 26px; font-weight: 700; color: #0f172a; }
        .page-header p { color: #64748b; margin-top: 4px; font-size: 14px; }

        /* ── Card (generic) ── */
        .card {
            background: #fff; border-radius: 12px; padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .card h3 {
            font-size: 16px; font-weight: 600; color: #0f172a;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .card h3 .badge-count {
            margin-left: auto;
            background: #f1f5f9; color: #475569;
            font-size: 12px; font-weight: 600;
            padding: 2px 10px; border-radius: 10px;
        }

        /* ── Tables ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; padding: 8px 8px 8px 0; border-bottom: 2px solid #f1f5f9; }
        td { padding: 10px 8px 10px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        /* ── Badges ── */
        .tag {
            display: inline-block; padding: 3px 10px; border-radius: 6px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .tag-green { background: #dcfce7; color: #166534; }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .tag-amber { background: #fef3c7; color: #92400e; }
        .tag-blue { background: #dbeafe; color: #1e40af; }
        .tag-gray { background: #f1f5f9; color: #475569; }

        /* ── Forms ── */
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
        .form-group select, .form-group input, .form-group textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 13px; font-family: inherit; background: #fff; color: #1e293b;
            transition: border 0.15s;
        }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 20px; border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all 0.15s; width: 100%;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-amber { background: #d97706; color: #fff; }
        .btn-amber:hover { background: #b45309; }
        .btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { background: #f8fafc; }
        .btn-info { background: #0891b2; color: #fff; }
        .btn-info:hover { background: #0e7490; }
        .btn-sm { padding: 6px 14px; width: auto; font-size: 12px; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
        .msg-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .msg { position: relative; padding-right: 40px; }
        .msg-close { position: absolute; top: 8px; right: 10px; cursor: pointer; font-size: 18px; font-weight: 700; line-height: 1; opacity: .6; background: none; border: none; color: inherit; }
        .msg-close:hover { opacity: 1; }

        /* ── Driver Cards ── */
        .driver-card {
            border: 1px solid #f1f5f9; border-radius: 10px; padding: 14px;
            margin-bottom: 12px; background: #fafbfc;
        }
        .driver-card:last-child { margin-bottom: 0; }
        .driver-card .name { font-weight: 600; font-size: 14px; }
        .driver-card .meta { font-size: 12px; color: #64748b; display: flex; gap: 12px; flex-wrap: wrap; margin: 4px 0 8px; }
        .driver-details { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
        .driver-details span { padding: 5px 8px; background: #fff; border-radius: 6px; font-size: 12px; border: 1px solid #f1f5f9; }
        .driver-details .lbl { color: #94a3b8; font-size: 10px; text-transform: uppercase; display: block; }

        /* ── Alerts ── */
        .alert-item {
            padding: 12px 14px; border-radius: 8px; margin-bottom: 8px;
            font-size: 13px; border-left: 4px solid;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert-item .alert-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .alert-boarding { background: #fffbeb; border-color: #d97706; }
        .alert-info { background: #eff6ff; border-color: #3b82f6; }
        .alert-cancelled { background: #fef2f2; border-color: #dc2626; }
        .alert-departed { background: #f8fafc; border-color: #94a3b8; }

        /* ── Empty states ── */
        .empty { text-align: center; padding: 30px 10px; color: #94a3b8; font-size: 13px; }
        .empty .empty-icon { font-size: 36px; margin-bottom: 8px; }
        .empty .empty-icon svg { width: 36px; height: 36px; margin: 0; }

        /* ── Section visibility (sidebar toggles) ── */
        .section { display: none; }
        .section.active { display: block; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%);
            border-radius: 16px;
            padding: 48px 40px;
            margin-bottom: 32px;
            color: #fff;
            text-align: center;
        }
        .hero h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .hero p { font-size: 15px; opacity: 0.85; margin-bottom: 28px; }
        .hero-search {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: end; justify-content: center;
            background: #fff; padding: 20px 24px; border-radius: 12px;
            max-width: 720px; margin: 0 auto 24px; box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .hero-search .form-group { margin-bottom: 0; min-width: 140px; flex: 1; }
        .hero-search .form-group label { color: #475569; font-size: 11px; }
        .hero-search .form-group select,
        .hero-search .form-group input { background: #f8fafc; border-color: #e2e8f0; }
        .hero-search .btn { width: auto; padding: 10px 28px; background: #d97706; color: #fff; border-radius: 8px; font-weight: 600; }
        .hero-search .btn:hover { background: #b45309; }
        .hero-trust {
            display: flex; justify-content: center; gap: 24px; flex-wrap: wrap;
            font-size: 13px; opacity: 0.9;
        }
        .hero-trust span { display: flex; align-items: center; gap: 6px; }

        /* ── Popular Routes ── */
        .section-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .popular-routes { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .route-card {
            background: #fff; border-radius: 12px; padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
        }
        .route-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .route-card .route-name { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 4px; }
        .route-card .route-places { font-size: 12px; color: #64748b; margin-bottom: 12px; }
        .route-card .route-meta { display: flex; justify-content: space-between; align-items: center; }
        .route-card .route-fare { font-size: 18px; font-weight: 700; color: #059669; }
        .route-card .route-bus { font-size: 11px; color: #94a3b8; }
        .route-card .route-date { font-size: 11px; color: #64748b; white-space: nowrap; }
        .route-card .route-action { margin-top: 12px; }

        /* ── Features ── */
        .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
        .feature-card {
            background: #fff; border-radius: 12px; padding: 24px; text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .feature-card .feature-icon {
            width: 48px; height: 48px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px; font-size: 22px;
        }
        .feature-card .feature-icon.blue { background: #eff6ff; }
        .feature-card .feature-icon.green { background: #ecfdf5; }
        .feature-card .feature-icon.amber { background: #fffbeb; }
        .feature-card h4 { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 4px; }
        .feature-card p { font-size: 13px; color: #64748b; line-height: 1.5; }

        /* ── Booking Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 14px;
            padding: 32px;
            max-width: 580px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 16px; right: 18px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #94a3b8;
            line-height: 1;
        }
        .modal-close:hover { color: #1e293b; }
        .modal-box h2 { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 20px; padding-right: 30px; }
        .modal-section {
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 14px;
            background: #fafbfc;
        }
        .modal-section h3 {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .modal-section .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
        }
        .modal-section .row .lbl { color: #94a3b8; }
        .modal-section .row .val { color: #1e293b; font-weight: 500; }
        .modal-section .driver-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-top: 8px;
        }
        .modal-section .driver-grid span {
            padding: 5px 8px;
            background: #fff;
            border-radius: 6px;
            font-size: 12px;
            border: 1px solid #f1f5f9;
        }
        .modal-section .driver-grid .lbl2 {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            display: block;
        }

        @media (max-width: 900px) {
            .main { padding: 82px 20px 40px; }
            .driver-details { grid-template-columns: 1fr 1fr; }
            .hero { padding: 32px 20px; }
            .hero h1 { font-size: 24px; }
            .hero-search { padding: 16px; }
            .hero-search .form-group { min-width: 120px; }
            .features { grid-template-columns: 1fr; }
            .popular-routes { grid-template-columns: repeat(2, 1fr); }
            .topnav-links a { padding: 6px 10px; font-size: 12px; }
        }
        @media (max-width: 500px) {
            .driver-details { grid-template-columns: 1fr; }
            .popular-routes { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="topnav">
    <div class="topnav-inner">
        <div class="topnav-brand">
            <img src="img/t17-removebg-preview.png" alt="Terminal 17" style="height:42px;width:auto;">
        </div>
        <nav class="topnav-links">
            <a href="#" class="active" data-section="home">Home</a>
            <a href="#" data-section="booking">Book a Trip</a>
            <a href="#" data-section="mybookings">My Bookings</a>
            <details class="topnav-dropdown">
                <summary>Report ▾</summary>
                <div class="dropdown-menu">
                    <a href="#" data-section="complaint">Complaint</a>
                    <a href="#" data-section="lostfound">Report Lost Items</a>
                </div>
            </details>
        </nav>
        <div class="topnav-user">
            <a href="#" data-section="alerts" class="topnav-bell" title="Service Alerts">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </a>
            <span class="topnav-name"><?php echo htmlspecialchars(explode(' ', $customer_name)[0]); if ($referred_by_name) { echo ' <span style="font-size:11px;color:#94a3b8;font-weight:400;">(ref: ' . htmlspecialchars($referred_by_name) . ')</span>'; } ?></span>
            <a href="logout.php" class="topnav-logout">Sign Out</a>
        </div>
    </div>
</header>

<div class="main">

    <div id="section-driver" class="section">
        <div class="card">
            <h3><?php echo $i_user; ?>Driver Transparency &amp; Safety</h3>
            <p style="font-size:13px;color:#64748b;margin-bottom:14px;">Full credentials, health status, and safety certifications for drivers on your booked trips.</p>
            <?php
            $has_drv = false;
            while ($row = oci_fetch_array($drv_stmt, OCI_ASSOC)) { $has_drv = true;
                $h = strtolower($row['HEALTH_STATUS'] ?? 'unknown');
                $hc = $h === 'fit' ? 'tag-green' : ($h === 'unfit' ? 'tag-red' : 'tag-amber');
                $e = strtolower($row['EMPLOYMENT_STATUS'] ?? 'unknown');
                $ec = $e === 'active' ? 'tag-green' : ($e === 'suspended' ? 'tag-red' : 'tag-amber');
            ?>
            <div class="driver-card">
                <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:4px;">
                    <span class="name"><?php echo htmlspecialchars($row['DRIVER_NAME']); ?></span>
                    <span style="font-size:12px;color:#64748b;">Route: <?php echo htmlspecialchars($row['ROUTE_NAME']); ?> · Bus: <?php echo htmlspecialchars($row['BUS_NUMBER']); ?></span>
                </div>
                <div class="meta"><?php echo $i_phone; ?><?php echo htmlspecialchars($row['PHONE_NUMBER']); ?>  · <?php echo $i_mail; ?><?php echo htmlspecialchars($row['EMAIL']); ?>  · <?php echo $i_clock; ?><?php echo $row['DEPART_TIME']; ?></div>
                <div class="driver-details">
                    <span><span class="lbl">Health</span><span class="tag <?php echo $hc; ?>" style="margin-top:4px;"><?php echo htmlspecialchars($row['HEALTH_STATUS'] ?? 'N/A'); ?></span></span>
                    <span><span class="lbl">Safety Cert</span><?php echo htmlspecialchars($row['SAFETY_CERTIFICATION'] ?? 'N/A'); ?></span>
                    <span><span class="lbl">Experience</span><?php echo $row['EXPERIENCE_YEARS']; ?> years</span>
                    <span><span class="lbl">License</span><?php echo htmlspecialchars($row['LICENSE_NUMBER'] ?? 'N/A'); ?></span>
                    <span><span class="lbl">Employment</span><span class="tag <?php echo $ec; ?>" style="margin-top:4px;"><?php echo htmlspecialchars($row['EMPLOYMENT_STATUS'] ?? 'N/A'); ?></span></span>
                    <span><span class="lbl">Departure</span><?php echo $row['DEPART_TIME']; ?></span>
                </div>
            </div>
            <?php } if (!$has_drv) { ?>
            <div class="empty"><div class="empty-icon"><?php echo $i_user; ?></div><p>No driver info yet. Book a trip to see your driver's full profile.</p></div>
            <?php } ?>
        </div>
    </div>

    <div id="section-schedules" class="section">
        <div class="card">
            <h3><?php echo $i_cal; ?>Upcoming Schedules <span class="badge-count"><?php echo $upcoming_scheds; ?> upcoming</span></h3>
            <p style="font-size:13px;color:#64748b;margin-bottom:14px;">All upcoming departures with route, bus, and driver details.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>From → To</th>
                            <th>Bus</th>
                            <th>Driver</th>
                            <th>Departure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_sched = false;
                        while ($s = oci_fetch_array($sched_stmt, OCI_ASSOC)) { $has_sched = true; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['ROUTE_NAME']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['DEPARTURE_LOCATION']); ?> → <?php echo htmlspecialchars($s['ARRIVAL_LOCATION']); ?></td>
                            <td><?php echo htmlspecialchars($s['BUS_NUMBER']); ?></td>
                            <td><?php echo htmlspecialchars($s['DRIVER_NAME']); ?></td>
                            <td><?php echo $s['DEPART_FMT']; ?></td>
                        </tr>
                        <?php } if (!$has_sched) { ?>
                        <tr><td colspan="5"><div class="empty"><div class="empty-icon"><?php echo $i_cal; ?></div><p>No upcoming schedules at this time.</p></div></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="section-booking" class="section">
        <div class="card">
            <h3><?php echo $i_ticket; ?>Search Bus Ticket Online</h3>
            <?php if ($bk_msg) { echo '<div class="msg msg-' . $bk_msg_type . '">' . htmlspecialchars($bk_msg) . '<button class="msg-close" onclick="this.parentElement.remove()">×</button></div>'; } ?>
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;margin-bottom:20px;">
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label for="sd">From</label>
                    <select name="search_departure" id="sd" required>
                        <option value="">Select departure</option>
                        <?php foreach ($dep_locs as $loc) { $sel = $s_dep === $loc ? 'selected' : ''; echo "<option $sel>" . htmlspecialchars($loc) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label for="sa">To</label>
                    <select name="search_arrival" id="sa">
                        <option value="">Select Arrival</option>
                        <?php foreach ($arr_locs as $loc) { $sel = $s_arr === $loc ? 'selected' : ''; echo "<option $sel>" . htmlspecialchars($loc) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label for="sdt">Travel Date</label>
                    <input type="date" name="search_date" id="sdt" value="<?php echo htmlspecialchars($s_date); ?>">
                </div>
                <div class="form-group" style="flex:0;">
                    <button type="submit" class="btn btn-primary" style="width:auto;"><?php echo $i_search; ?>Search</button>
                </div>
            </form>

            <?php if ($search_results) { ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>From → To</th>
                            <th>Bus</th>
                            <th>Driver</th>
                            <th>Departure</th>
                            <th>Seats</th>
                            <th>Fare</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $has_sr = false; while ($sr = oci_fetch_array($search_results, OCI_ASSOC)) { $has_sr = true;
                            $avail = intval($sr['AVAILABLE_SEATS']);
                            $seat_class = $avail <= 0 ? 'tag-red' : ($avail <= 3 ? 'tag-amber' : 'tag-green');
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sr['ROUTE_NAME']); ?></strong></td>
                            <td><?php echo htmlspecialchars($sr['DEPARTURE_LOCATION']); ?> → <?php echo htmlspecialchars($sr['ARRIVAL_LOCATION']); ?></td>
                            <td><?php echo htmlspecialchars($sr['BUS_NUMBER']); ?></td>
                            <td><?php echo htmlspecialchars($sr['DRIVER_NAME']); ?></td>
                            <td><?php echo $sr['DEPART_FMT']; ?></td>
                            <td><span class="tag <?php echo $seat_class; ?>"><?php echo $avail; ?></span></td>
                            <td>RM<?php echo $sr['FARE']; ?></td>
                            <td>
                                <?php if ($avail > 0) { ?>
                                <form method="POST" onsubmit="return confirm('Confirm booking for <?php echo htmlspecialchars($sr['ROUTE_NAME']); ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="schedule_id" value="<?php echo $sr['SCHEDULE_ID']; ?>">
                                    <input type="hidden" name="s_dep" value="<?php echo htmlspecialchars($s_dep); ?>">
                                    <input type="hidden" name="s_arr" value="<?php echo htmlspecialchars($s_arr); ?>">
                                    <input type="hidden" name="s_date" value="<?php echo htmlspecialchars($s_date); ?>">
                                    <button type="submit" name="book_ticket" class="btn btn-info btn-sm">Book Now</button>
                                </form>
                                <?php } else { echo '<span class="tag tag-red">Full</span>'; } ?>
                            </td>
                        </tr>
                        <?php } if (!$has_sr) { echo '<tr><td colspan="8"><div class="empty"><div class="empty-icon">' . $i_ticket . '</div><p>No trips found matching your search criteria.</p></div></td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
            <?php } elseif (!$bk_msg) { ?>
            <div class="empty"><div class="empty-icon"><?php echo $i_ticket; ?></div><p>Select your departure, destination, and travel date above to search available trips.</p></div>
            <?php } ?>
        </div>
    </div>

    <div id="section-mybookings" class="section">
        <div class="card">
            <h3><?php echo $i_ticket; ?>My Bookings</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Route</th>
                            <th>Bus</th>
                            <th>Departure</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $has_mbk = false; $bk_data = []; while ($mb = oci_fetch_array($mybk_stmt, OCI_ASSOC)) { $has_mbk = true;
                            $bs = strtolower($mb['BOOKING_STATUS']);
                            $bt = $bs === 'confirmed' ? 'tag-green' : ($bs === 'cancelled' ? 'tag-red' : 'tag-amber');
                            $bid = $mb['BOOKING_ID'];
                            $bk_data[] = $mb;
                            ?>
                        <tr style="cursor:pointer;" onclick="openBookingModal(<?php echo $bid; ?>)">
                            <td style="white-space:nowrap;">#<?php echo $bid; ?></td>
                            <td><strong><?php echo htmlspecialchars($mb['ROUTE_NAME']); ?></strong><br><span style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($mb['DEPARTURE_LOCATION']); ?> → <?php echo htmlspecialchars($mb['ARRIVAL_LOCATION']); ?></span></td>
                            <td><?php echo htmlspecialchars($mb['BUS_NUMBER']); ?></td>
                            <td style="white-space:nowrap;"><?php echo $mb['DEPART_FMT']; ?></td>
                            <td>RM<?php echo $mb['TOTAL_FARE']; ?></td>
                            <td><span class="tag <?php echo $bt; ?>"><?php echo htmlspecialchars($mb['BOOKING_STATUS']); ?></span></td>
                            <td><button class="btn btn-outline btn-sm" style="width:auto;">View</button></td>
                        </tr>
                        <?php } if (!$has_mbk) { echo '<tr><td colspan="7"><div class="empty"><div class="empty-icon">' . $i_ticket . '</div><p>No bookings yet. Use the <a href="#" onclick="showSection(\'booking\');return false;" style="color:#2563eb;text-decoration:underline;">Book a Trip</a> section to get started!</p></div></td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Booking Detail Modal -->
    <div class="modal-overlay" id="bookingModal" onclick="if(event.target===this)closeBookingModal()">
        <div class="modal-box">
            <button class="modal-close" onclick="closeBookingModal()">✕</button>
            <h2 id="modalTitle">Booking Details</h2>
            <div id="modalContent"></div>
        </div>
    </div>

    <div id="section-routes" class="section">
        <div class="card">
            <h3><?php echo $i_map; ?>Bus Routes &amp; Schedules</h3>
            <p style="font-size:13px;color:#64748b;margin-bottom:14px;">All available routes operated by Terminal 17.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Distance</th>
                            <th>Duration</th>
                            <th>Bus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_route = false;
                        oci_execute($route_stmt); // re-execute after stat fetch consumed it
                        while ($r = oci_fetch_array($route_stmt, OCI_ASSOC)) { $has_route = true; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['ROUTE_NAME']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['DEPARTURE_LOCATION']); ?></td>
                            <td><?php echo htmlspecialchars($r['ARRIVAL_LOCATION']); ?></td>
                            <td><?php echo $r['DISTANCE_KM']; ?> km</td>
                            <td><?php echo htmlspecialchars($r['ESTIMATED_DURATION'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($r['BUS_NUMBER'] ?? '—'); ?></td>
                        </tr>
                        <?php } if (!$has_route) { ?>
                        <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?php echo $i_map; ?></div><p>No routes available at this time.</p></div></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="section-alerts" class="section">
        <div class="card">
            <h3><?php echo $i_bell; ?>Service Alerts &amp; Boarding Updates</h3>
            <p style="font-size:13px;color:#64748b;margin-bottom:14px;">Real-time notifications for your upcoming and past trips.</p>
            <?php
            $has_alert = false;
            while ($row = oci_fetch_array($alert_stmt, OCI_ASSOC)) { $has_alert = true;
                $depart_raw = $row['DEPART_RAW'];
                $now = time();
                $depart_ts = strtotime($depart_raw);
                $diff_hours = ($depart_ts - $now) / 3600;
                $bus_status = strtolower($row['BUS_STATUS'] ?? 'active');
                $bk_status = strtolower($row['BOOKING_STATUS']);

                if ($bk_status === 'cancelled') {
                    $acls = 'alert-cancelled'; $aic = '✕'; $amsg = "Booking <strong>#{$row['BOOKING_ID']}</strong> for <strong>{$row['ROUTE_NAME']}</strong> was cancelled.";
                } elseif ($diff_hours <= 0) {
                    $acls = 'alert-departed'; $aic = '🚌'; $amsg = "Booking <strong>#{$row['BOOKING_ID']}</strong> — <strong>{$row['ROUTE_NAME']}</strong> — Departed at {$row['DEPART_TIME']}.";
                } elseif ($diff_hours <= 2) {
                    $acls = 'alert-boarding'; $aic = '⏰'; $amsg = "Booking <strong>#{$row['BOOKING_ID']}</strong> — <strong>{$row['ROUTE_NAME']}</strong> from {$row['DEPARTURE_LOCATION']} — Boarding soon at {$row['DEPART_TIME']}!";
                } else {
                    $acls = 'alert-info'; $aic = 'ℹ'; $amsg = "Booking <strong>#{$row['BOOKING_ID']}</strong> — <strong>{$row['ROUTE_NAME']}</strong> — Scheduled {$row['DEPART_TIME']}.";
                }
                if ($bus_status !== 'active') {
                    $amsg .= ' <span style="color:#dc2626;">⚠ Bus: ' . htmlspecialchars($row['BUS_STATUS']) . '</span>';
                }
                echo "<div class=\"alert-item {$acls}\"><span class=\"alert-icon\">{$aic}</span><span>{$amsg}</span></div>";
            }
            if (!$has_alert) {
                echo '<div class="empty"><div class="empty-icon">' . $i_bell . '</div><p>No alerts. Book a trip to receive boarding notifications here.</p></div>';
            }
            ?>
        </div>
    </div>

    <div id="section-home" class="section active">

        <div class="hero">
            <h1>Where are you going today?</h1>
            <p>Safe, comfortable travel across Malaysia with Terminal 17</p>
            <form method="GET" class="hero-search">
                <div class="form-group">
                    <label for="hero-dep">From</label>
                    <select name="search_departure" id="hero-dep" required>
                        <option value="">Select departure</option>
                        <?php foreach ($dep_locs as $loc) { echo '<option value="' . htmlspecialchars($loc) . '">' . htmlspecialchars($loc) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hero-arr">To</label>
                    <select name="search_arrival" id="hero-arr">
                        <option value="">Select Arrival</option>
                        <?php foreach ($arr_locs as $loc) { echo '<option value="' . htmlspecialchars($loc) . '">' . htmlspecialchars($loc) . '</option>'; } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hero-date">Travel Date</label>
                    <input type="date" name="search_date" id="hero-date">
                </div>
                <button type="submit" class="btn"><?php echo $i_search; ?>Search Buses</button>
            </form>

        </div>

        <h2 class="section-title">Popular Routes</h2>
        <div class="popular-routes">
            <?php
            $has_pop = false;
            while ($pr = oci_fetch_array($pop_stmt, OCI_ASSOC)) { $has_pop = true;
                $r_name = htmlspecialchars($pr['ROUTE_NAME']);
                $r_from = htmlspecialchars($pr['DEPARTURE_LOCATION']);
                $r_to = htmlspecialchars($pr['ARRIVAL_LOCATION']);
                $r_fare = $pr['FARE'];
                $r_bus = htmlspecialchars($pr['BUS_NUMBER']);
                $r_date = htmlspecialchars($pr['NEXT_DEPART']);
            ?>
            <div class="route-card" onclick="showSection('booking')">
                <div class="route-name"><?php echo $r_name; ?></div>
                <div class="route-places"><?php echo $r_from; ?> → <?php echo $r_to; ?></div>
                <div class="route-meta">
                    <span class="route-fare">RM<?php echo $r_fare; ?></span>
                    <span class="route-bus">🚌 <?php echo $r_bus; ?></span>
                    <span class="route-date"><?php echo $r_date; ?></span>
                </div>
                <div class="route-action"><span class="btn btn-primary btn-sm">Book Now →</span></div>
            </div>
            <?php } if (!$has_pop) { ?>
            <div class="empty" style="grid-column:1/-1;"><div class="empty-icon"><?php echo $i_map; ?></div><p>No routes available at this time.</p></div>
            <?php } ?>
        </div>

        <h2 class="section-title">Why Choose Terminal 17</h2>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon blue">🚌</div>
                <h4>Comfortable Travel</h4>
                <p>Air-conditioned buses with reclining seats, onboard entertainment, and rest stops.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon green">🛡️</div>
                <h4>Safety First</h4>
                <p>Certified drivers, GPS-tracked fleet, and regular vehicle inspections for peace of mind.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon amber">⚡</div>
                <h4>Easy Booking</h4>
                <p>Instant confirmation, secure payments, and 24/7 customer support for all your trips.</p>
            </div>
        </div>

        <?php /* next trip removed */ ?>

    </div>

    <div id="section-complaint" class="section">
        <div class="card">
            <h3><?php echo $i_file; ?>File a Complaint</h3>
            <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;max-width:380px;">
                    <?php if ($comp_msg) { echo '<div class="msg msg-' . $comp_msg_type . '">' . htmlspecialchars($comp_msg) . '<button class="msg-close" onclick="this.parentElement.remove()">×</button></div>'; } ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="complaint_title" placeholder="e.g. Delayed departure" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="complaint_category" required>
                                <option value="">— Select —</option>
                                <option value="Delay">Delay / Schedule</option>
                                <option value="Cleanliness">Cleanliness</option>
                                <option value="Staff Behavior">Staff Behavior</option>
                                <option value="Lost Item">Lost Item</option>
                                <option value="Seat Issue">Seat / Booking Issue</option>
                                <option value="Facilities">Facilities</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Related Bus (optional)</label>
                            <select name="bus_id">
                                <option value="">— None —</option>
                                <?php while ($cb = oci_fetch_array($comp_bus_stmt, OCI_ASSOC)) {
                                    echo "<option value=\"{$cb['BUS_ID']}\">#{$cb['BUS_ID']} — {$cb['BUS_NUMBER']} ({$cb['BUS_STATUS']})</option>";
                                } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="complaint_description" rows="4" placeholder="Describe your issue in detail..." required></textarea>
                        </div>
                        <button type="submit" name="submit_complaint" class="btn btn-amber">Submit Complaint</button>
                    </form>
                </div>
                <div style="flex:2;min-width:300px;">
                    <h3 style="margin-bottom:12px;">Your Complaint History</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Subject</th><th>Category</th><th>Status</th><th>Filed</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $has_comp = false;
                                while ($cr = oci_fetch_array($comp_stmt, OCI_ASSOC)) { $has_comp = true;
                                    $cs = strtolower($cr['COMPLAINT_STATUS']);
                                    $cc = $cs === 'resolved' ? 'tag-blue' : ($cs === 'pending' ? 'tag-amber' : 'tag-red');
                                ?>
                                <tr>
                                    <td style="white-space:nowrap;">#<?php echo $cr['COMPLAINT_ID']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($cr['COMPLAINT_TITLE']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cr['COMPLAINT_CATEGORY']); ?></td>
                                    <td><span class="tag <?php echo $cc; ?>"><?php echo $cr['COMPLAINT_STATUS']; ?></span></td>
                                    <td style="font-size:12px;white-space:nowrap;"><?php echo $cr['FILED']; ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php if (!$has_comp) { echo '<div class="empty"><div class="empty-icon">' . $i_pin2 . '</div><p>No complaints filed yet.</p></div>'; } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="section-lostfound" class="section">
        <div class="card">
            <h3><?php echo $i_package; ?>Lost &amp; Found Center</h3>
            <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                <div style="flex:1;min-width:280px;max-width:340px;">
                    <?php if ($lf_msg) { echo '<div class="msg msg-' . $lf_msg_type . '">' . htmlspecialchars($lf_msg) . '<button class="msg-close" onclick="this.parentElement.remove()">×</button></div>'; } ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" name="item_name" placeholder="e.g. Phone, Wallet" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="item_category" required>
                                <option value="Electronics">Electronics</option>
                                <option value="Personal Items">Personal Items</option>
                                <option value="Documents">Documents</option>
                                <option value="Bags & Luggage">Bags & Luggage</option>
                                <option value="Clothing">Clothing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bus Fleet Number</label>
                            <select name="bus_id" required>
                                <option value="" disabled selected>-- Select Bus --</option>
                                <?php while ($lb = oci_fetch_array($lf_bus_stmt, OCI_ASSOC)) {
                                    echo "<option value=\"{$lb['BUS_ID']}\">Bus #{$lb['BUS_NUMBER']}</option>";
                                } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="item_description" rows="4" placeholder="Color, markings, seat row, etc."></textarea>
                        </div>
                        <div class="form-hint">Your contact info from your profile will be attached automatically.</div>
                        <button type="submit" name="report_item" class="btn btn-danger" style="margin-top:14px;">Submit Lost Item Report</button>
                    </form>
                </div>
                <div style="flex:2;min-width:300px;">
                    <h3 style="margin-bottom:12px;">Lost &amp; Found Registry</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>ID</th><th>Item</th><th>Category</th><th>Bus</th><th>Date</th><th>Status</th><th>Contact / Driver</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($lr = oci_fetch_array($lf_stmt, OCI_ASSOC)) {
                                    $lsc = strtolower($lr['CLAIM_STATUS']) === 'claimed' ? 'tag-green' : 'tag-red';
                                ?>
                                <tr>
                                    <td style="white-space:nowrap;">#<?php echo $lr['LOST_ITEM_ID']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($lr['ITEM_NAME']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($lr['ITEM_CATEGORY']); ?></td>
                                    <td><?php echo htmlspecialchars($lr['BUS_NUMBER'] ?? '—'); ?></td>
                                    <td style="white-space:nowrap;"><?php echo $lr['LOST_DATE']; ?></td>
                                    <td><span class="tag <?php echo $lsc; ?>"><?php echo $lr['CLAIM_STATUS']; ?></span></td>
                                    <td style="font-size:12px;color:#475569;line-height:1.5;">
                                        <?php if ($lr['CUSTOMER_NAME']) { ?>
                                            <strong style="color:#1e293b;"><?php echo htmlspecialchars($lr['CUSTOMER_NAME']); ?></strong><br>
                                            <?php echo htmlspecialchars($lr['EMAIL']); ?><br>
                                            <?php echo htmlspecialchars($lr['PHONE_NUMBER']); ?>
                                        <?php } ?>
                                        <?php if ($lr['DRIVER_NAME']) { ?>
                                            <br><span style="color:#64748b;">Driver:</span> <?php echo htmlspecialchars($lr['DRIVER_NAME']); ?> (<?php echo htmlspecialchars($lr['DRIVER_PHONE']); ?>)
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($lr['CLAIM_STATUS'] === 'Unclaimed') { ?>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $lr['LOST_ITEM_ID']; ?>">
                                                <button type="submit" name="claim_item" class="btn btn-success btn-sm" onclick="return confirm('Claim this item?')">Claim</button>
                                            </form>
                                        <?php } else { ?>
                                            <span style="color:#166534;font-weight:600;">✓ Claimed</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
var bookingData = {
<?php foreach ($bk_data as $mb) {
    $bid = $mb['BOOKING_ID'];
    $hc = strtolower($mb['HEALTH_STATUS'] ?? 'unknown') === 'fit' ? '<span class="tag tag-green">Fit</span>' : (strtolower($mb['HEALTH_STATUS'] ?? 'unknown') === 'unfit' ? '<span class="tag tag-red">Unfit</span>' : '<span class="tag tag-amber">' . htmlspecialchars($mb['HEALTH_STATUS'] ?? 'N/A') . '</span>');
    $ec = strtolower($mb['EMPLOYMENT_STATUS'] ?? 'unknown') === 'active' ? '<span class="tag tag-green">Active</span>' : (strtolower($mb['EMPLOYMENT_STATUS'] ?? 'unknown') === 'suspended' ? '<span class="tag tag-red">Suspended</span>' : '<span class="tag tag-amber">' . htmlspecialchars($mb['EMPLOYMENT_STATUS'] ?? 'N/A') . '</span>');
    $sc = strtolower($mb['BOOKING_STATUS']) === 'confirmed' ? 'tag-green' : (strtolower($mb['BOOKING_STATUS']) === 'cancelled' ? 'tag-red' : 'tag-amber');
    echo "$bid: {
        routeName: " . json_encode($mb['ROUTE_NAME']) . ",
        departLoc: " . json_encode($mb['DEPARTURE_LOCATION']) . ",
        arrivLoc: " . json_encode($mb['ARRIVAL_LOCATION']) . ",
        distance: " . json_encode($mb['DISTANCE_KM'] ?? '') . ",
        duration: " . json_encode($mb['ESTIMATED_DURATION'] ?? '') . ",
        busNumber: " . json_encode($mb['BUS_NUMBER']) . ",
        busStatus: " . json_encode($mb['BUS_STATUS'] ?? '') . ",
        driverName: " . json_encode($mb['DRIVER_NAME']) . ",
        driverPhone: " . json_encode($mb['PHONE_NUMBER'] ?? '') . ",
        driverEmail: " . json_encode($mb['EMAIL'] ?? '') . ",
        healthStatus: " . json_encode($hc) . ",
        safetyCert: " . json_encode($mb['SAFETY_CERTIFICATION'] ?? '') . ",
        experience: " . json_encode($mb['EXPERIENCE_YEARS'] ?? '') . ",
        license: " . json_encode($mb['LICENSE_NUMBER'] ?? '') . ",
        employment: " . json_encode($ec) . ",
        departFmt: " . json_encode($mb['DEPART_FMT']) . ",
        passengers: " . json_encode($mb['TOTAL_PASSENGER']) . ",
        fare: " . json_encode($mb['TOTAL_FARE']) . ",
        status: " . json_encode($mb['BOOKING_STATUS']) . ",
        statusClass: " . json_encode($sc) . "
    },\n";
} ?>
};

function openBookingModal(id) {
    var d = bookingData[id];
    if (!d) return;
    document.getElementById('modalTitle').textContent = 'Booking #' + id;
    document.getElementById('modalContent').innerHTML =
        '<div class=\"modal-section\">' +
            '<h3><?php echo $i_map; ?>Route</h3>' +
            '<div class=\"row\"><span class=\"lbl\">Route</span><span class=\"val\">' + d.routeName + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">From → To</span><span class=\"val\">' + d.departLoc + ' → ' + d.arrivLoc + '</span></div>' +
            (d.distance ? '<div class=\"row\"><span class=\"lbl\">Distance</span><span class=\"val\">' + d.distance + ' km</span></div>' : '') +
            (d.duration ? '<div class=\"row\"><span class=\"lbl\">Duration</span><span class=\"val\">' + d.duration + '</span></div>' : '') +
        '</div>' +
        '<div class=\"modal-section\">' +
            '<h3><?php echo $i_cal; ?>Schedule</h3>' +
            '<div class=\"row\"><span class=\"lbl\">Departure</span><span class=\"val\">' + d.departFmt + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Bus</span><span class=\"val\">' + d.busNumber + (d.busStatus ? ' (' + d.busStatus + ')' : '') + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Passengers</span><span class=\"val\">' + d.passengers + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Fare</span><span class=\"val\">RM' + d.fare + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Status</span><span class=\"val\"><span class=\"tag ' + d.statusClass + '\">' + d.status + '</span></span></div>' +
        '</div>' +
        '<div class=\"modal-section\">' +
            '<h3><?php echo $i_user; ?>Driver</h3>' +
            '<div class=\"row\"><span class=\"lbl\">Name</span><span class=\"val\">' + d.driverName + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Phone</span><span class=\"val\">' + (d.driverPhone || '—') + '</span></div>' +
            '<div class=\"row\"><span class=\"lbl\">Email</span><span class=\"val\">' + (d.driverEmail || '—') + '</span></div>' +
            '<div class=\"driver-grid\">' +
                '<span><span class=\"lbl2\">Health</span>' + d.healthStatus + '</span>' +
                '<span><span class=\"lbl2\">Safety Cert</span>' + (d.safetyCert || 'N/A') + '</span>' +
                '<span><span class=\"lbl2\">Experience</span>' + (d.experience || '0') + ' years</span>' +
                '<span><span class=\"lbl2\">License</span>' + (d.license || 'N/A') + '</span>' +
                '<span><span class=\"lbl2\">Employment</span>' + d.employment + '</span>' +
            '</div>' +
        '</div>';
    document.getElementById('bookingModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeBookingModal(); });

function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    const el = document.getElementById('section-' + id);
    if (el) el.classList.add('active');
    document.querySelectorAll('.topnav-links a').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.dropdown-menu a').forEach(a => a.classList.remove('active'));
    const link = document.querySelector('.topnav-links a[data-section="' + id + '"]') || document.querySelector('.dropdown-menu a[data-section="' + id + '"]');
    if (link) link.classList.add('active');
    document.querySelectorAll('.topnav-dropdown').forEach(d => d.removeAttribute('open'));
    window.location.hash = id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
document.querySelectorAll('.topnav-links a[data-section]').forEach(a => {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        showSection(this.dataset.section);
    });
});
document.querySelectorAll('.dropdown-menu a[data-section]').forEach(a => {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        showSection(this.dataset.section);
        var details = this.closest('.topnav-dropdown');
        if (details) details.removeAttribute('open');
    });
});
// Close dropdown on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.topnav-dropdown')) {
        document.querySelectorAll('.topnav-dropdown').forEach(function(d) { d.removeAttribute('open'); });
    }
});
document.querySelector('.topnav-bell').addEventListener('click', function(e) {
    e.preventDefault();
    showSection(this.dataset.section);
});
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
    if (dep !== 'Shah Alam' && arrSelect.value !== 'Shah Alam') {
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === 'Shah Alam') { arrSelect.value = 'Shah Alam'; break; }
        }
    }
}
document.getElementById('sd').addEventListener('change', function() { filterArrival(this, document.getElementById('sa')); });
document.getElementById('hero-dep').addEventListener('change', function() { filterArrival(this, document.getElementById('hero-arr')); });
filterArrival(document.getElementById('sd'), document.getElementById('sa'));
filterArrival(document.getElementById('hero-dep'), document.getElementById('hero-arr'));
// ─── End Shah Alam logic ───
// Restore last section from hash on load (ignore hash when search params or POST submission)
var sectionFromHash = window.location.hash ? window.location.hash.substring(1) : null;
if (sectionFromHash === 'dashboard') sectionFromHash = 'home';
if (window.location.search || <?php echo json_encode($ignore_hash); ?>) sectionFromHash = null;
showSection(sectionFromHash || '<?php echo $default_section; ?>');
</script>

</body>
</html>
<?php
oci_free_statement($drv_stmt);
oci_free_statement($alert_stmt);
oci_free_statement($mybk_stmt);
oci_free_statement($route_stmt);
oci_free_statement($sched_stmt);
if ($search_results) oci_free_statement($search_results);
oci_free_statement($comp_stmt);
oci_free_statement($comp_bus_stmt);
oci_free_statement($lf_stmt);
oci_free_statement($lf_bus_stmt);
oci_free_statement($pop_stmt);
oci_close($conn);
?>
