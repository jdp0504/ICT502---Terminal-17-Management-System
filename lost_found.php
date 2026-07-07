<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
include('db.php');

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_item'])) {
    $item_name = $_POST['item_name'];
    $category = $_POST['item_category'];
    $description = $_POST['item_description'];
    $bus_id = intval($_POST['bus_id']);
    $new_id = rand(1000, 9999);

    $insert_query = "INSERT INTO LOST_ITEM (lost_item_id, customer_id, bus_id, item_name, item_category, item_description, lost_date, claim_status) 
                     VALUES (:id, :cust_id, :bus_id, :iname, :cat, :descr, SYSDATE, 'Unclaimed')";
    $stmt = oci_parse($conn, $insert_query);
    oci_bind_by_name($stmt, ':id', $new_id);
    oci_bind_by_name($stmt, ':cust_id', $customer_id);
    oci_bind_by_name($stmt, ':bus_id', $bus_id);
    oci_bind_by_name($stmt, ':iname', $item_name);
    oci_bind_by_name($stmt, ':cat', $category);
    oci_bind_by_name($stmt, ':descr', $description);
    if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
        echo "<script>alert('Lost item reported successfully!'); window.location.href='lost_found.php';</script>";
    } else {
        $e = oci_error($stmt);
        echo "<p style='color:red;'>Error: " . htmlentities($e['message']) . "</p>";
    }
    oci_free_statement($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_item'])) {
    $item_id = intval($_POST['item_id']);
    $up = oci_parse($conn, "UPDATE LOST_ITEM SET CLAIM_STATUS = 'Claimed' WHERE LOST_ITEM_ID = :id AND CLAIM_STATUS = 'Unclaimed'");
    oci_bind_by_name($up, ':id', $item_id);
    if (oci_execute($up, OCI_COMMIT_ON_SUCCESS) && oci_num_rows($up) > 0) {
        echo "<script>alert('Item #$item_id marked as Claimed!'); window.location.href='lost_found.php';</script>";
    } else {
        echo "<script>alert('Could not claim item. It may already be claimed.');</script>";
    }
    oci_free_statement($up);
}

// Fetch active buses safely to build the dropdown menu dynamically
$buses_dropdown_query = oci_parse($conn, "SELECT BUS_ID, BUS_NUMBER FROM BUS ORDER BY BUS_NUMBER ASC");
oci_execute($buses_dropdown_query);
$valid_buses = [];
while ($bus_row = oci_fetch_array($buses_dropdown_query, OCI_ASSOC)) {
    $valid_buses[] = $bus_row;
}
oci_free_statement($buses_dropdown_query);

$cid = intval($_SESSION['customer_id']);
$query = "SELECT l.LOST_ITEM_ID, l.ITEM_NAME, l.ITEM_CATEGORY, l.LOST_DATE, l.CLAIM_STATUS, 
                 b.BUS_NUMBER, c.CUSTOMER_NAME, c.EMAIL, c.PHONE_NUMBER,
                 d.DRIVER_NAME, d.PHONE_NUMBER AS DRIVER_PHONE
          FROM LOST_ITEM l 
          LEFT JOIN BUS b ON l.BUS_ID = b.BUS_ID
          LEFT JOIN CUSTOMER c ON l.CUSTOMER_ID = c.CUSTOMER_ID
          LEFT JOIN DRIVER d ON b.DRIVER_ID = d.DRIVER_ID
          WHERE l.CUSTOMER_ID = :cid
          ORDER BY l.LOST_ITEM_ID DESC";
$statement = oci_parse($conn, $query);
oci_bind_by_name($statement, ':cid', $cid);
oci_execute($statement);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found — Terminal 17</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #1e293b;
            padding: 40px;
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
        .topbar nav a {
            padding: 6px 14px; border-radius: 6px;
            font-size: 13px; font-weight: 500;
            color: #94a3b8; transition: all 0.15s;
        }
        .topbar nav a:hover { background: #1e293b; color: #fff; }
        .container { display: flex; gap: 24px; align-items: flex-start; }
        .form-box, .list-box { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .form-box { width: 320px; flex-shrink: 0; }
        .list-box { flex: 1; min-width: 0; }
        h2 { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
        label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; margin-top: 12px; }
        label:first-child { margin-top: 0; }
        input, select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 13px; font-family: inherit; background: #fff; color: #1e293b;
            transition: border 0.15s; margin-top: 2px;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 20px; border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-danger { background: #dc2626; color: #fff; width: 100%; margin-top: 14px; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-sm { padding: 6px 14px; width: auto; font-size: 12px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; padding: 8px 8px 8px 0; border-bottom: 2px solid #f1f5f9; white-space: nowrap; }
        td { padding: 10px 8px 10px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .tag {
            display: inline-block; padding: 3px 10px; border-radius: 6px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .tag-green { background: #dcfce7; color: #166534; }
        .contact-info { font-size: 12px; color: #475569; line-height: 1.5; }
        .contact-info strong { color: #1e293b; }
        .empty { text-align: center; padding: 40px 10px; color: #94a3b8; font-size: 13px; }
        .hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }

        @media (max-width: 800px) {
            body { padding: 16px; }
            .container { flex-direction: column; }
            .form-box { width: 100%; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <h1>📦 Terminal 17 — Lost & Found Center</h1>
    <nav>
        <a href="portal.php">← Portal</a>
    </nav>
</div>

<div class="container">
    <div class="form-box">
        <h2>Report a Lost Item</h2>
        <form method="POST">
            <label>Item Name</label>
            <input type="text" name="item_name" placeholder="e.g. Phone, Wallet" required>
            
            <label>Category</label>
            <select name="item_category" required>
                <option value="Electronics">Electronics</option>
                <option value="Personal Items">Personal Items</option>
                <option value="Documents">Documents</option>
                <option value="Bags & Luggage">Bags & Luggage</option>
                <option value="Clothing">Clothing</option>
                <option value="Other">Other</option>
            </select>
            
            <label>Bus Fleet Number</label>
            <select name="bus_id" required>
                <option value="" disabled selected>-- Select Bus --</option>
                <?php foreach ($valid_buses as $bus) { ?>
                    <option value="<?php echo $bus['BUS_ID']; ?>">
                        Bus #<?php echo htmlspecialchars($bus['BUS_NUMBER']); ?>
                    </option>
                <?php } ?>
            </select>
            
            <label>Description</label>
            <textarea name="item_description" rows="4" placeholder="Color, markings, seat row, etc."></textarea>
            
            <div class="hint">Your contact info from your profile will be attached automatically.</div>
            <button type="submit" name="report_item" class="btn btn-danger">Submit Lost Item Report</button>
        </form>
    </div>

    <div class="list-box">
        <h2>Lost & Found Registry</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>Item</th><th>Category</th><th>Bus</th><th>Date</th><th>Status</th><th>Contact / Driver</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = oci_fetch_array($statement, OCI_ASSOC)) {
                        $sc = strtolower($row['CLAIM_STATUS']) === 'claimed' ? 'tag-green' : 'tag-red';
                    ?>
                    <tr>
                        <td>#<?php echo $row['LOST_ITEM_ID']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['ITEM_NAME']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['ITEM_CATEGORY']); ?></td>
                        <td><?php echo htmlspecialchars($row['BUS_NUMBER'] ?? '—'); ?></td>
                        <td style="white-space:nowrap;"><?php echo $row['LOST_DATE']; ?></td>
                        <td><span class="tag <?php echo $sc; ?>"><?php echo $row['CLAIM_STATUS']; ?></span></td>
                        <td class="contact-info">
                            <?php if ($row['CUSTOMER_NAME']) { ?>
                                <strong><?php echo htmlspecialchars($row['CUSTOMER_NAME']); ?></strong><br>
                                <?php echo htmlspecialchars($row['EMAIL']); ?><br>
                                <?php echo htmlspecialchars($row['PHONE_NUMBER']); ?>
                            <?php } ?>
                            <?php if ($row['DRIVER_NAME']) { ?>
                                <br><span style="color:#64748b;">Driver:</span> <?php echo htmlspecialchars($row['DRIVER_NAME']); ?> (<?php echo htmlspecialchars($row['DRIVER_PHONE']); ?>)
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($row['CLAIM_STATUS'] === 'Unclaimed') { ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="item_id" value="<?php echo $row['LOST_ITEM_ID']; ?>">
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

</body>
</html>
<?php oci_free_statement($statement); oci_close($conn); ?>