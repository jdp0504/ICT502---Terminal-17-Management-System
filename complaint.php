<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
include('db.php');

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $title = trim($_POST['complaint_title']);
    $category = $_POST['complaint_category'];
    $description = trim($_POST['complaint_description']);
    $bus_id = !empty($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
    $comp_id = rand(10000, 49999);

    $ins = oci_parse($conn, "INSERT INTO COMPLAINT (complaint_id, customer_id, bus_id, complaint_title, complaint_category, complaint_description, complaint_status, complaint_date_time) VALUES (:id, :cid, :bid, :title, :cat, :descr, 'Pending', SYSDATE)");
    oci_bind_by_name($ins, ':id', $comp_id);
    oci_bind_by_name($ins, ':cid', $customer_id);
    oci_bind_by_name($ins, ':bid', $bus_id);
    oci_bind_by_name($ins, ':title', $title);
    oci_bind_by_name($ins, ':cat', $category);
    oci_bind_by_name($ins, ':descr', $description);
    if (oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
        echo "<script>alert('Complaint #$comp_id filed successfully! Status: Pending.'); window.location.href='complaint.php';</script>";
        exit;
    } else {
        $e = oci_error($ins);
        $error = "Error: " . htmlentities($e['message']);
    }
    oci_free_statement($ins);
}

// Fetch complaint history
$cp_query = "SELECT COMPLAINT_ID, COMPLAINT_TITLE, COMPLAINT_CATEGORY, COMPLAINT_STATUS, COMPLAINT_DESCRIPTION, TO_CHAR(COMPLAINT_DATE_TIME, 'YYYY-MM-DD HH24:MI') as FILED FROM COMPLAINT WHERE CUSTOMER_ID = :cid ORDER BY COMPLAINT_DATE_TIME DESC";
$cp_stmt = oci_parse($conn, $cp_query);
oci_bind_by_name($cp_stmt, ':cid', $customer_id);
oci_execute($cp_stmt);

// Bus list for dropdown
$bus_q = "SELECT BUS_ID, BUS_NUMBER, BUS_STATUS FROM BUS ORDER BY BUS_NUMBER";
$bus_stmt = oci_parse($conn, $bus_q);
oci_execute($bus_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint — Terminal 17</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5; color: #1e293b; padding: 40px;
        }
        a { text-decoration: none; color: inherit; }
        .topbar {
            background: #0f172a; color: #fff;
            border-radius: 12px; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }
        .topbar h1 { font-size: 18px; font-weight: 700; }
        .topbar nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .topbar nav a { padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 500; color: #94a3b8; transition: all 0.15s; }
        .topbar nav a:hover { background: #1e293b; color: #fff; }
        .container { display: flex; gap: 24px; align-items: flex-start; }
        .form-box, .list-box { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .form-box { width: 360px; flex-shrink: 0; }
        .list-box { flex: 1; min-width: 0; }
        h2 { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 13px; font-family: inherit; background: #fff; color: #1e293b;
            transition: border 0.15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 20px; border: none; border-radius: 8px; width: 100%;
            font-size: 13px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-amber { background: #d97706; color: #fff; }
        .btn-amber:hover { background: #b45309; }
        .btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { background: #f8fafc; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; padding: 8px 8px 8px 0; border-bottom: 2px solid #f1f5f9; white-space: nowrap; }
        td { padding: 10px 8px 10px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .tag { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .tag-amber { background: #fef3c7; color: #92400e; }
        .tag-blue { background: #dbeafe; color: #1e40af; }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .empty { text-align: center; padding: 40px 10px; color: #94a3b8; font-size: 13px; }
        .empty .empty-icon { font-size: 36px; margin-bottom: 8px; }
        .error-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 800px) {
            body { padding: 16px; }
            .container { flex-direction: column; }
            .form-box { width: 100%; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <h1>📝 Terminal 17 — Complaint Management</h1>
    <nav>
        <a href="portal.php">← Portal</a>
        <a href="lost_found.php">Lost & Found</a>
    </nav>
</div>

<div class="container">
    <div class="form-box">
        <h2>Submit a Complaint</h2>
        <?php if (isset($error)) { echo "<div class=\"error-msg\">$error</div>"; } ?>
        <form method="POST">
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
                    <?php while ($b = oci_fetch_array($bus_stmt, OCI_ASSOC)) {
                        echo "<option value=\"{$b['BUS_ID']}\">#{$b['BUS_ID']} — {$b['BUS_NUMBER']} ({$b['BUS_STATUS']})</option>";
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

    <div class="list-box">
        <h2>Your Complaint History</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>Subject</th><th>Category</th><th>Status</th><th>Filed</th></tr>
                </thead>
                <tbody>
                    <?php
                    $has = false;
                    while ($row = oci_fetch_array($cp_stmt, OCI_ASSOC)) { $has = true;
                        $s = strtolower($row['COMPLAINT_STATUS']);
                        $c = $s === 'resolved' ? 'tag-blue' : ($s === 'pending' ? 'tag-amber' : 'tag-red');
                    ?>
                    <tr>
                        <td>#<?php echo $row['COMPLAINT_ID']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['COMPLAINT_TITLE']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['COMPLAINT_CATEGORY']); ?></td>
                        <td><span class="tag <?php echo $c; ?>"><?php echo $row['COMPLAINT_STATUS']; ?></span></td>
                        <td style="font-size:12px;"><?php echo $row['FILED']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php if (!$has) { ?>
            <div class="empty"><div class="empty-icon">📌</div><p>No complaints filed yet.</p></div>
            <?php } ?>
        </div>
    </div>
</div>

</body>
</html>
<?php
oci_free_statement($cp_stmt);
oci_free_statement($bus_stmt);
oci_close($conn);
?>
