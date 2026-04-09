<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, role, contact_number, address, profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$florist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$florist || $florist['role'] !== 'florist') {
    session_destroy();
    header("Location: login.html");
    exit;
}

// Handle profile update
$profileSuccess = '';
$profileError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $contact   = trim($_POST['contact']    ?? '');
    $address   = trim($_POST['address']    ?? '');

    if (!$firstName || !$lastName) {
        $profileError = "First and last name are required.";
    } else {
        $upd = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, address = ? WHERE id = ?");
        $upd->execute([$firstName, $lastName, $contact, $address, $_SESSION['user_id']]);
        $florist['first_name']     = $firstName;
        $florist['last_name']      = $lastName;
        $florist['contact_number'] = $contact;
        $florist['address']        = $address;
        $profileSuccess = "Profile updated successfully!";
    }
}

// Fetch orders assigned to this florist
$floristAssignedName = trim(($florist['first_name'] ?? '') . ' ' . ($florist['last_name'] ?? ''));

$orderStmt = $conn->prepare("
    SELECT o.id, o.created_at, o.status, o.total, o.florist_commission,
           o.payment_method, o.payment_status,
           CONCAT(u.first_name, ' ', u.last_name) AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.assigned_florist = ?
    ORDER BY o.created_at DESC
");
$orderStmt->execute([$floristAssignedName]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$fullName    = htmlspecialchars($floristAssignedName);
$initials    = strtoupper(substr($florist['first_name'], 0, 1) . substr($florist['last_name'], 0, 1));
$totalOrders = count($orders);
$pending     = count(array_filter($orders, fn($o) => $o['status'] === 'Pending'));
$completed   = count(array_filter($orders, fn($o) => $o['status'] === 'Delivered'));
$totalCommissions = array_sum(array_column($orders, 'florist_commission'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | FloraFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cream:      #faf6f1;
            --petal:      #f2e8e0;
            --blush:      #e8c5b0;
            --rose:       #c17b5c;
            --deep-rose:  #8b4a35;
            --dark:       #2c1f1a;
            --text:       #4a3728;
            --text-light: #8a7060;
            --white:      #ffffff;
            --shadow:     rgba(139, 74, 53, 0.12);
        }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--cream);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: var(--dark);
            display: flex; flex-direction: column;
            z-index: 100; overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute; top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(193,123,92,0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sidebar-brand {
            padding: 36px 28px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem; font-weight: 300;
            color: var(--blush); letter-spacing: 2px;
        }

        .sidebar-brand span {
            font-size: 0.7rem; color: var(--text-light);
            letter-spacing: 3px; text-transform: uppercase;
            display: block; margin-top: 2px;
        }

        .sidebar-avatar {
            padding: 24px 28px;
            display: flex; align-items: center; gap: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .avatar-circle {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--rose), var(--deep-rose));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.1rem; color: var(--white);
            font-weight: 600; flex-shrink: 0;
        }

        .avatar-info p { font-size: 0.85rem; color: var(--white); font-weight: 500; }
        .avatar-info span { font-size: 0.72rem; color: var(--text-light); letter-spacing: 1px; }

        .sidebar-nav { flex: 1; padding: 20px 0; }

        .nav-label {
            font-size: 0.65rem; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,0.25);
            padding: 0 28px 8px; margin-top: 16px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 28px; color: rgba(255,255,255,0.55);
            cursor: pointer; transition: all 0.2s;
            font-size: 0.88rem; font-weight: 400;
            letter-spacing: 0.5px; border-left: 3px solid transparent;
            text-decoration: none;
        }

        .nav-item:hover { color: var(--blush); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--blush); border-left-color: var(--rose); background: rgba(193,123,92,0.1); }
        .nav-item i { width: 18px; text-align: center; font-size: 0.9rem; }

        .sidebar-footer {
            padding: 20px 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.35); cursor: pointer;
            font-size: 0.82rem; letter-spacing: 0.5px;
            transition: color 0.2s; background: none; border: none;
            width: 100%; text-align: left;
        }
        .logout-btn:hover { color: #e57373; }

        /* ── Main ── */
        .main { margin-left: 260px; min-height: 100vh; padding: 40px 48px; }
        .page { display: none; animation: fadeIn 0.3s ease; }
        .page.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .page-header { margin-bottom: 36px; }
        .page-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.4rem; font-weight: 300;
            color: var(--dark); letter-spacing: 1px;
        }
        .page-header p { color: var(--text-light); font-size: 0.88rem; margin-top: 4px; font-weight: 300; }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white); border-radius: 16px;
            padding: 28px; box-shadow: 0 4px 20px var(--shadow);
            position: relative; overflow: hidden;
        }

        .stat-card::after {
            content: ''; position: absolute; bottom: -20px; right: -20px;
            width: 80px; height: 80px; border-radius: 50%;
            background: var(--petal); opacity: 0.6;
        }

        .stat-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 16px;
        }

        .stat-card.total   .stat-icon { background: #fde8df; color: var(--rose); }
        .stat-card.pending .stat-icon { background: #fff3cd; color: #b8860b; }
        .stat-card.done    .stat-icon { background: #d4edda; color: #2e7d32; }

        .stat-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.6rem; font-weight: 600;
            color: var(--dark); line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem; color: var(--text-light);
            letter-spacing: 1.5px; text-transform: uppercase; margin-top: 4px;
        }

        /* ── Card ── */
        .card {
            background: var(--white); border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow); overflow: hidden;
        }

        .card-header {
            padding: 24px 28px; border-bottom: 1px solid var(--petal);
            display: flex; align-items: center; justify-content: space-between;
        }

        .card-header h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem; font-weight: 400; color: var(--dark);
        }

        .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }

        .filter-tab {
            padding: 6px 14px; border-radius: 20px; font-size: 0.78rem;
            border: 1px solid var(--blush); background: transparent;
            color: var(--text-light); cursor: pointer; transition: all 0.2s;
            font-family: 'Jost', sans-serif;
        }

        .filter-tab.active, .filter-tab:hover {
            background: var(--rose); border-color: var(--rose); color: var(--white);
        }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 14px 20px; text-align: left;
            font-size: 0.7rem; letter-spacing: 2px;
            text-transform: uppercase; color: var(--text-light);
            font-weight: 500; background: var(--cream);
        }

        tbody tr { border-top: 1px solid var(--petal); transition: background 0.15s; }
        tbody tr:hover { background: #fdf9f6; }
        tbody td { padding: 14px 20px; font-size: 0.88rem; color: var(--text); }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;
        }

        .status-badge::before {
            content: ''; width: 6px; height: 6px;
            border-radius: 50%; background: currentColor;
        }

        .status-badge.Pending    { background: #fff3cd; color: #856404; }
        .status-badge.Accepted   { background: #d1ecf1; color: #0c5460; }
        .status-badge.Declined   { background: #f8d7da; color: #721c24; }
        .status-badge.Preparing  { background: #e2d9f3; color: #4a235a; }
        .status-badge.Delivering { background: #cce5ff; color: #004085; }
        .status-badge.Delivered  { background: #d4edda; color: #155724; }

        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-light);
        }
        .empty-state i { font-size: 2.5rem; color: var(--blush); margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 0.9rem; }

        /* ── Status Select ── */
        .status-select {
            padding: 5px 10px; border: 1px solid var(--blush);
            border-radius: 8px; font-family: 'Jost', sans-serif;
            font-size: 0.8rem; color: var(--text);
            background: var(--white); cursor: pointer;
        }

        .update-btn {
            padding: 5px 12px; background: var(--rose);
            color: white; border: none; border-radius: 8px;
            font-size: 0.78rem; cursor: pointer;
            font-family: 'Jost', sans-serif; transition: background 0.2s;
            margin-left: 6px;
        }
        .update-btn:hover { background: var(--deep-rose); }

        /* ── Profile ── */
        .profile-grid {
            display: grid; grid-template-columns: 280px 1fr;
            gap: 24px; align-items: start;
        }

        .profile-card {
            background: var(--white); border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow);
            padding: 36px 28px; text-align: center;
        }

        .profile-avatar {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, var(--rose), var(--deep-rose));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem; color: var(--white); font-weight: 600;
            margin: 0 auto 16px;
            box-shadow: 0 8px 24px rgba(193,123,92,0.35);
        }

        .profile-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem; font-weight: 400; color: var(--dark);
        }

        .role-tag {
            display: inline-block; margin-top: 8px;
            padding: 4px 14px; background: var(--petal);
            color: var(--rose); border-radius: 20px;
            font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase;
        }

        .profile-meta { margin-top: 24px; text-align: left; }

        .profile-meta-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid var(--petal);
            font-size: 0.83rem; color: var(--text-light);
        }
        .profile-meta-item i { color: var(--blush); width: 16px; }
        .profile-meta-item span { color: var(--text); }

        .form-card {
            background: var(--white); border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow); padding: 32px;
        }

        .form-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem; font-weight: 400; color: var(--dark);
            margin-bottom: 24px; padding-bottom: 14px;
            border-bottom: 1px solid var(--petal);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block; font-size: 0.72rem;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--text-light); margin-bottom: 7px; font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--petal); border-radius: 10px;
            font-family: 'Jost', sans-serif; font-size: 0.88rem;
            color: var(--text); background: var(--cream);
            transition: border-color 0.2s; outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus { border-color: var(--blush); background: var(--white); }
        .form-group textarea { resize: vertical; min-height: 80px; }

        .submit-btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--rose), var(--deep-rose));
            color: white; border: none; border-radius: 10px;
            font-family: 'Jost', sans-serif; font-size: 0.88rem;
            font-weight: 500; letter-spacing: 0.5px;
            cursor: pointer; transition: opacity 0.2s, transform 0.2s;
        }
        .submit-btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 0.85rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error   { background: #f8d7da; color: #721c24; }

        /* ── Welcome Banner ── */
        .welcome-banner {
            background: linear-gradient(135deg, var(--dark) 0%, #3d2318 100%);
            border-radius: 16px; padding: 32px 36px; margin-bottom: 32px;
            position: relative; overflow: hidden;
        }

        .welcome-banner::before {
            content: '🌸';
            position: absolute; right: 36px; top: 50%;
            transform: translateY(-50%);
            font-size: 5rem; opacity: 0.15;
        }

        .welcome-banner h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.9rem; font-weight: 300;
            color: var(--blush); letter-spacing: 1px;
        }

        .welcome-banner p { color: rgba(255,255,255,0.45); font-size: 0.85rem; margin-top: 4px; }

        /* ── Toast ── */
        .toast {
            position: fixed; bottom: 28px; right: 28px;
            padding: 14px 20px; border-radius: 10px;
            font-size: 0.85rem; font-family: 'Jost', sans-serif;
            color: white; z-index: 999;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            transform: translateY(80px); opacity: 0;
            transition: all 0.3s ease;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: #2e7d32; }
        .toast.error   { background: #c62828; }

        @media (max-width: 900px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 28px 24px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>FloraFit</h1>
        <span>Florist Portal</span>
    </div>

    <div class="sidebar-avatar">
        <div class="avatar-circle"><?= $initials ?></div>
        <div class="avatar-info">
            <p><?= $fullName ?></p>
            <span>🌿 Florist</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a class="nav-item active" onclick="showPage('orders', this)" href="#">
            <i class="fas fa-clipboard-list"></i> Orders
        </a>
        <a class="nav-item" onclick="showPage('profile', this)" href="#">
            <i class="fas fa-user"></i> My Profile
        </a>
    </nav>

    <div class="sidebar-footer">
        <button class="logout-btn" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </button>
    </div>
</aside>

<main class="main">

    <?php if (isset($_GET['welcome'])): ?>
    <div class="welcome-banner">
        <div>
            <h2>Welcome aboard, <?= htmlspecialchars($florist['first_name']) ?>! 🌷</h2>
            <p>Your florist account is all set. Start managing your orders below.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Orders Page -->
    <div class="page active" id="page-orders">
        <div class="page-header">
            <h2>Orders</h2>
            <p>Manage and update your assigned customer orders</p>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-num"><?= $totalOrders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-num"><?= $pending ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card done">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-num"><?= $completed ?></div>
                <div class="stat-label">Delivered</div>
            </div>
            <div class="stat-card done" style="border-left: 4px solid #2e7d32;">
                <div class="stat-icon" style="background: #e8f5e9; color: #2e7d32;"><i class="fas fa-hand-holding-dollar"></i></div>
                <div class="stat-num" style="color: #2e7d32;">₱<?= number_format($totalCommissions, 2) ?></div>
                <div class="stat-label">Total Commissions</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Order List</h3>
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterOrders('all', this)">All</button>
                    <button class="filter-tab" onclick="filterOrders('Pending', this)">Pending</button>
                    <button class="filter-tab" onclick="filterOrders('Accepted', this)">Accepted</button>
                    <button class="filter-tab" onclick="filterOrders('Preparing', this)">Preparing</button>
                    <button class="filter-tab" onclick="filterOrders('Delivering', this)">Delivering</button>
                    <button class="filter-tab" onclick="filterOrders('Delivered', this)">Delivered</button>
                    <button class="filter-tab" onclick="filterOrders('Declined', this)">Declined</button>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No orders assigned to you yet.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Commission</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <?php foreach ($orders as $order): ?>
                    <tr data-status="<?= htmlspecialchars($order['status']) ?>">
                        <td>#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        <td>₱<?= number_format($order['total'], 2) ?></td>
                        <td style="font-weight: 500; color: #2e7d32;">₱<?= number_format($order['florist_commission'], 2) ?></td>
                        <td>
                            <span style="font-size:0.8rem; color: var(--text-light);">
                                <?= htmlspecialchars($order['payment_method'] ?? '—') ?>
                                <br>
                                <span style="color: <?= $order['payment_status'] === 'Paid' ? '#2e7d32' : '#856404' ?>; font-weight:500;">
                                    <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
                                </span>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= htmlspecialchars($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <select class="status-select" id="status-<?= $order['id'] ?>">
                                <option value="Pending"    <?= $order['status']==='Pending'    ? 'selected':'' ?>>Pending</option>
                                <option value="Accepted"   <?= $order['status']==='Accepted'   ? 'selected':'' ?>>Accepted</option>
                                <option value="Declined"   <?= $order['status']==='Declined'   ? 'selected':'' ?>>Declined</option>
                                <option value="Preparing"  <?= $order['status']==='Preparing'  ? 'selected':'' ?>>Preparing</option>
                                <option value="Delivering" <?= $order['status']==='Delivering' ? 'selected':'' ?>>Delivering</option>
                                <option value="Delivered"  <?= $order['status']==='Delivered'  ? 'selected':'' ?>>Delivered</option>
                            </select>
                            <button class="update-btn" onclick="updateStatus(<?= $order['id'] ?>)">Save</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Page -->
    <div class="page" id="page-profile">
        <div class="page-header">
            <h2>My Profile</h2>
            <p>View and update your personal information</p>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <div class="profile-avatar"><?= $initials ?></div>
                <h3><?= $fullName ?></h3>
                <span class="role-tag">Florist</span>
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($florist['email']) ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($florist['contact_number'] ?: 'Not set') ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($florist['address'] ?: 'Not set') ?></span>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h3>Edit Information</h3>

                <?php if ($profileSuccess): ?>
                    <div class="alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($profileSuccess) ?></div>
                <?php endif; ?>
                <?php if ($profileError): ?>
                    <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($profileError) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($florist['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($florist['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?= htmlspecialchars($florist['email']) ?>" disabled style="opacity:0.6; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" value="<?= htmlspecialchars($florist['contact_number'] ?? '') ?>" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Your address..."><?= htmlspecialchars($florist['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save" style="margin-right:8px;"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

</main>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>
function showPage(name, el) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    el.classList.add('active');
}

function filterOrders(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#ordersTableBody tr').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    setTimeout(() => toast.classList.remove('show'), 3000);
}

async function updateStatus(orderId) {
    const select    = document.getElementById('status-' + orderId);
    const newStatus = select.value;

    try {
        const formData = new FormData();
        formData.append('id', orderId);
        formData.append('status', newStatus);

        const res  = await fetch('api/update_order_status.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            const row   = select.closest('tr');
            row.dataset.status = newStatus;
            const badge = row.querySelector('.status-badge');
            badge.className   = 'status-badge ' + newStatus;
            badge.textContent = newStatus;
            showToast('Order #' + String(orderId).padStart(4, '0') + ' updated to ' + newStatus);
        } else {
            showToast(data.message || 'Failed to update order.', 'error');
        }
    } catch (err) {
        showToast('Connection error. Please try again.', 'error');
    }
}

function logout() {
    localStorage.clear();
    window.location.href = 'logout.php';
}
</script>
</body>
</html>
