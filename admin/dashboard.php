<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Flora Fit</title>
    <link rel="icon" type="image/png" href="../assets/img/Flora.png">
    <link rel="stylesheet" href="../assets/css/website.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
/* ===========================================
   ADMIN DASHBOARD - 100% CLICKABLE VERSION
   =========================================== */

/* ROOT VARIABLES */
:root {
    --primary: #2e7d32;
    --primary-dark: #276128;
    --success: #4caf50;
    --warning: #ff9800;
    --danger: #f44336;
    --info: #2196f3;
    --light: #f8f9fa;
    --dark: #333;
    --border: rgba(0,0,0,0.08);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.15);
    --radius: 12px;
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* LAYOUT */
.admin-dashboard {
    display: flex;
    min-height: calc(100vh - 120px);
    background: #fafbfc;
}

body {
    overflow-x: hidden;
}

/* SIDEBAR */
.sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid var(--border);
    padding: 32px 24px;
    position: sticky !important;
    top: 120px !important;
    height: calc(100vh - 120px);
    overflow-y: auto;
    z-index: 1000 !important;
    box-shadow: var(--shadow-sm);
}

.sidebar h3 {
    margin: 0 0 32px 0;
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--primary);
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* MENU ITEMS */
.menu-item {
    display: flex !important;
    align-items: center;
    gap: 12px;
    padding: 16px 20px !important;
    margin-bottom: 8px !important;
    border-radius: var(--radius);
    cursor: pointer !important;
    transition: var(--transition);
    font-weight: 600 !important;
    font-size: 1rem;
    color: #64748b;
    border: 2px solid transparent;
    background: white;
    position: relative;
    z-index: 1001 !important;
    user-select: none;
}

.menu-item:hover {
    background: linear-gradient(135deg, #f0f8ff 0%, #e3f2fd 100%) !important;
    color: var(--primary) !important;
    border-color: rgba(46, 125, 50, 0.2) !important;
    transform: translateX(6px) !important;
    box-shadow: var(--shadow-sm) !important;
}

.menu-item.active {
    background: linear-gradient(135deg, var(--primary) 0%, #4caf50 100%) !important;
    color: white !important;
    box-shadow: var(--shadow-md) !important;
    border-color: var(--primary) !important;
}

.menu-item.active i {
    color: white !important;
}

/* MAIN CONTENT */
.main-content {
    flex: 1;
    padding: 40px;
    overflow-y: auto;
    z-index: 10;
}

.panel {
    display: block;
    animation: fadeIn 0.3s ease-out;
}

.panel.hidden {
    display: none;
}

.analytics-toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
    margin-bottom: 24px;
}

.analytics-toolbar .field {
    min-width: 160px;
    flex: 0 0 180px;
}

.analytics-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    padding: 24px;
}

.analytics-layout {
    display: grid;
    grid-template-columns: 1.4fr 0.9fr;
    gap: 20px;
    margin-bottom: 20px;
}

.analytics-chart {
    min-height: 310px;
    padding: 14px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fcfffd 0%, #f3f9ff 100%);
    border: 1px solid rgba(46, 125, 50, 0.08);
}

.analytics-chart svg {
    width: 100%;
    height: 260px;
    display: block;
    overflow: visible;
}

.analytics-graph-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.analytics-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f8fafc;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 700;
    border: 1px solid rgba(148, 163, 184, 0.16);
}

.analytics-legend-swatch {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.analytics-legend-swatch.orders {
    background: #2e7d32;
}

.analytics-legend-swatch.revenue {
    background: #2196f3;
}

.analytics-graph-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.analytics-graph-stat {
    padding: 12px 14px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(46, 125, 50, 0.08);
}

.analytics-graph-stat strong {
    display: block;
    font-size: 0.78rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.analytics-graph-stat span {
    display: block;
    color: var(--dark);
    font-weight: 700;
}

.admin-mobile-toolbar {
    display: none;
    margin-bottom: 18px;
}

.admin-menu-toggle {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary) 0%, #4caf50 100%);
    color: white;
    font-weight: 700;
    box-shadow: var(--shadow-sm);
}

.admin-sidebar-backdrop {
    display: none;
}

.analytics-bar {
    min-width: 62px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.analytics-bar-track {
    width: 100%;
    height: 210px;
    display: flex;
    align-items: flex-end;
    padding: 6px;
    border-radius: 16px;
    background: linear-gradient(180deg, #f7fbff 0%, #eef7ee 100%);
}

.analytics-bar-fill {
    width: 100%;
    border-radius: 12px 12px 10px 10px;
    background: linear-gradient(180deg, #66bb6a 0%, #2e7d32 100%);
    min-height: 12px;
    box-shadow: inset 0 -2px 0 rgba(255,255,255,0.2);
}

.analytics-bar-value {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--primary);
}

.analytics-bar-label {
    font-size: 0.78rem;
    color: #64748b;
    text-align: center;
}

.analytics-insight-list {
    display: grid;
    gap: 12px;
}

.analytics-insight-item {
    padding: 14px 16px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fffafc 0%, #f8fbff 100%);
    border: 1px solid rgba(46, 125, 50, 0.08);
}

.analytics-insight-item strong {
    display: block;
    color: #64748b;
    font-size: 0.82rem;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.analytics-insight-item span {
    color: var(--dark);
    font-size: 1rem;
    font-weight: 700;
}

.analytics-status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.analytics-status-pill {
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(46, 125, 50, 0.08);
    color: var(--primary);
    font-size: 0.82rem;
    font-weight: 700;
}

.analytics-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    color: #94a3b8;
    text-align: center;
    padding: 20px;
}

@media (max-width: 992px) {
    .analytics-layout {
        grid-template-columns: 1fr;
    }
}

/* PANEL ANIMATIONS */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

header {
    z-index: 1700 !important;
    position: sticky !important;
    top: 0 !important;
}

/* TYPOGRAPHY */
h1, h2, h3, h4 {
    margin: 0 0 20px 0;
    font-weight: 600;
}

h2 {
    font-size: 2rem;
    color: var(--dark);
    margin-bottom: 32px;
}

/* STATS CARDS */
.stat-cards, .metrics {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.stat-card, .metric-card {
    flex: 1;
    min-width: 220px;
    padding: 32px;
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.stat-card:hover, .metric-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.stat-card h4, .metric-card h4 {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 8px;
    font-weight: 500;
}

.stat-card p, .metric-card p {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

#stat-orders-today { 
    color: var(--primary); 
}

#stat-low-stock { 
    color: var(--warning); 
}
#stat-pending-custom { 
    color: var(--info); 
}

/* BUTTONS - FULLY CLICKABLE */
.btn-large, .btn-small, button {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer !important;
    transition: var(--transition);
    z-index: 100 !important;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.btn-large {
    padding: 16px 28px;
    font-size: 1rem;
}

.btn-large:not(.outline) {
    background: var(--primary) !important;
    color: white !important;
}

.btn-large:not(.outline):hover {
    background: var(--primary-dark) !important;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-large.outline {
    background: transparent;
    color: var(--primary);
    border: 2px solid rgba(46, 125, 50, 0.2);
}

.btn-large.outline:hover {
    background: rgba(46, 125, 50, 0.08);
    border-color: var(--primary);
}

.btn-small {
    padding: 10px 20px;
    font-size: 0.9rem;
}

.btn-small.danger { 
    background: var(--danger); color: white; 
}
.btn-small.danger:hover { 
    background: #d32f2f; 
}
.btn-small.success { 
    background: var(--success); color: white; 
}
.btn-small.success:hover { 
    background: #45a049; 
}
.btn-small.info { 
    background: var(--info); color: white; 
}
.btn-small.info:hover { 
    background: #1976d2; 
}

/* FORMS - FULLY INTERACTIVE */
.flower-form {
    background: white;
    padding: 32px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 32px;
    box-shadow: var(--shadow-sm);
}

.form-row {
    display: flex;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.form-row > div {
    flex: 1;
    min-width: 220px;
}

label {
    display: block;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
}

input, select, textarea {
    width: 100% !important;
    padding: 14px 16px !important;
    border: 2px solid var(--border) !important;
    border-radius: 8px !important;
    font-size: 1rem !important;
    transition: var(--transition);
    cursor: text !important;
    box-sizing: border-box;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
}

/* TABLES - FULLY CLICKABLE */
.simple-table, .inventory-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    table-layout: fixed;
}

.simple-table thead th,
.inventory-table thead th {
    background: white !important;
    padding: 20px 16px;
    font-weight: 600;
    color: var(--dark);
    text-align: left;
    border-bottom: 2px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 50;
}

.simple-table tbody tr,
.inventory-table tbody tr {
    cursor: pointer !important;
    transition: var(--transition);
}

.simple-table tbody tr:hover,
.inventory-table tbody tr:hover {
    background: rgba(46, 125, 50, 0.04) !important;
}

.simple-table td,
.inventory-table td {
    padding: 20px 16px;
    border-bottom: 1px solid var(--border);
}

/* STATUS FILTERS - CLICKABLE */
.status-filter {
    padding: 14px 20px;
    border-radius: 8px;
    border: 2px solid;
    background: white;
    cursor: pointer !important;
    font-weight: 600;
    transition: var(--transition);
    white-space: nowrap;
    z-index: 100 !important;
}

.status-filter.active {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* STATUS BADGES */
.stock-badge, .order-status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.stock-badge.ok, .order-status-badge.accepted, .order-status-badge.delivered {
    background: rgba(76, 175, 80, 0.2);
    color: var(--success);
}

.stock-badge.low, .order-status-badge.preparing {
    background: rgba(255, 152, 0, 0.2);
    color: var(--warning);
}

.stock-badge.critical, .order-status-badge.delivering, .order-status-badge.pending {
    background: rgba(244, 67, 54, 0.2);
    color: var(--danger);
}

.stock-badge.out, .order-status-badge.declined {
    background: rgba(244, 67, 54, 0.15);
    color: #d32f2f;
}

/* FILTERS */
.inventory-filters, .orders-filters {
    display: flex;
    gap: 20px;
    margin: 32px 0;
    align-items: center;
    flex-wrap: wrap;
}

/* TABLE SCROLL */
div[style*="max-height"] {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: white;
}

/* IMAGES */
.thumb-small {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
    cursor: zoom-in !important;
}

/* UTILITIES */
.muted { opacity: 0.7; font-size: 0.9em; }
.orders-empty {
    padding: 60px 20px;
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}

.muted { opacity: 0.7; font-size: 0.9em; }
.orders-empty {
    padding: 60px 20px;
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}

/* ===========================================
   NOTIFICATIONS - BEAUTIFUL ANIMATED 
   =========================================== */
.notification {
    animation: slideInRight 0.4s cubic-bezier(0.25,0.8,0.25,1) forwards;
}

.notification-success { 
    border-left-color: #10b981 !important; 
}

.notification-error { 
    border-left-color: #ef4444 !important; 
}

.notification-warning { 
    border-left-color: #f59e0b !important; 
}
.notification-danger { 
    border-left-color: #dc2626 !important; 
}
.notification-info { 
    border-left-color: #3b82f6 !important; 
}

@keyframes slideInRight {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 768px) {
    #notifications-container {
        top: 120px !important; 
        left: 10px !important; 
        right: 10px !important;
    }
}

/* MODALS */
.modal {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000 !important;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: white;
    padding: 32px;
    border-radius: var(--radius);
    max-width: 450px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 20px;
    background: none;
    border: none;
    font-size: 1.8rem;
    color: #9ca3af;
    cursor: pointer !important;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10001 !important;
}

@media (max-width: 768px) {
    main.container {
        padding-top: 96px !important;
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    .admin-dashboard {
        position: relative;
        min-height: auto;
    }

    .admin-mobile-toolbar {
        display: flex;
        margin-bottom: 12px;
    }

    .admin-menu-toggle {
        width: 100%;
        justify-content: center;
        padding: 10px 14px;
        font-size: 0.92rem;
    }

    .admin-sidebar-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
        z-index: 1600;
    }

    .admin-sidebar-backdrop.open {
        opacity: 1;
        pointer-events: auto;
    }

    .sidebar {
        position: fixed !important;
        width: min(86vw, 300px);
        height: calc(100vh - 78px);
        top: 78px !important;
        left: 0;
        z-index: 1700 !important;
        transform: translateX(-110%);
        transition: transform 0.25s ease;
        border-radius: 0 16px 16px 0;
        padding: 18px 14px;
    }

    .sidebar h3 {
        margin-bottom: 18px;
        font-size: 1.15rem;
    }

    .menu-item {
        padding: 12px 14px !important;
        font-size: 0.92rem;
        gap: 10px;
    }
    
    .sidebar.open {
        transform: translateX(0) !important;
    }
    
    .main-content {
        width: 100%;
        padding: 14px 10px;
        overflow-x: hidden;
    }

    h2 {
        font-size: 1.5rem;
        margin-bottom: 18px;
    }

    .flower-form,
    .analytics-card,
    .stat-card,
    .metric-card {
        padding: 16px !important;
    }

    .stat-cards,
    .metrics,
    .quick-actions,
    .inventory-filters,
    .orders-filters,
    .stock-status-summary,
    .orders-bulk-actions,
    .form-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }

    .stat-card,
    .metric-card,
    .form-row > div,
    .inventory-filters > *,
    .orders-filters > *,
    .quick-actions > *,
    .stock-status-summary > *,
    .orders-bulk-actions > *,
    .analytics-toolbar .field {
        min-width: 0 !important;
        width: 100% !important;
        flex: 1 1 100% !important;
    }

    .analytics-toolbar {
        gap: 10px;
        margin-bottom: 16px;
    }

    .analytics-toolbar .field {
        flex: 1 1 100%;
    }

    .btn-large,
    .btn-small {
        justify-content: center;
    }

    .order-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }

    .analytics-layout,
    .analytics-graph-meta,
    #voucher-user-list {
        grid-template-columns: 1fr !important;
    }

    .analytics-chart {
        min-height: 240px;
        padding: 10px;
    }

    .analytics-chart svg {
        height: 200px;
    }

    .orders-table-wrap,
    div[style*="max-height"] {
        overflow-x: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .simple-table,
    .inventory-table {
        table-layout: auto;
        min-width: 680px;
    }

    .simple-table thead th,
    .inventory-table thead th,
    .simple-table td,
    .inventory-table td {
        padding: 12px 10px;
        font-size: 0.85rem;
    }

    .confirm-modal-card {
        width: min(96vw, 420px);
        padding: 18px;
    }
}

@media (max-width: 560px) {
    .analytics-graph-meta {
        grid-template-columns: 1fr;
    }

    .main-content {
        padding: 12px 8px;
    }

    .order-actions {
        grid-template-columns: 1fr;
    }

    .status-filter {
        padding: 10px 12px;
        font-size: 0.84rem;
    }

    .simple-table,
    .inventory-table {
        min-width: 620px;
    }
}

/* SCROLLBAR */
::-webkit-scrollbar { 
    width: 8px; 
}
::-webkit-scrollbar-track { 
    background: #f1f1f1; 
}
::-webkit-scrollbar-thumb { 
    background: rgba(46, 125, 50, 0.3);
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover { 
    background: rgba(46, 125, 50, 0.5); 
}

/* FOCUS STATES */
button:focus, input:focus, select:focus, .menu-item:focus {
    outline: 2px solid var(--primary) !important;
    outline-offset: 2px;
}

/* LOADING */
.loading { opacity: 0.6; pointer-events: none; }

/* 3D PREVIEW STYLES */
@keyframes spin {
    to { 
        transform: rotate(360deg); }
}
#3d-canvas { 
    cursor: grab; 
}

#3d-canvas:active { 
    cursor: grabbing; 
}

#admin-3d-preview-canvas {
    width: 100%;
    height: 100%;
    display: block;
    cursor: grab;
}
#admin-3d-preview-canvas:active {
    cursor: grabbing;
}
@keyframes spin3d {
    to { transform: rotate(360deg); }
}
.spinner-3d {
    width: 36px;
    height: 36px;
    border: 3px solid #e5e7eb;
    border-top: 3px solid #2196f3;
    border-radius: 50%;
    animation: spin3d 1s linear infinite;
    margin-bottom: 10px;
}

/* Enhanced Flower Picker Styles */
.flower-picker-panel {
    background: linear-gradient(145deg, #fff, #f8f9ff);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(233,30,140,0.1);
}

.search-section { 
    margin-bottom: 16px; 
}

#flower-search {
    width: 100%; padding: 12px 16px; border: 2px solid #e9ecef;
    border-radius: 25px; font-size: 14px; transition: all 0.3s ease; background: #fff;
}
#flower-search:focus {
    outline: none; border-color: var(--primary, #e91e8c);
    box-shadow: 0 0 0 3px rgba(233,30,140,0.1);
}

.category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.category-tab {
    padding: 5px 12px;
    border-radius: 30px;
    border: 1px solid rgba(255, 182, 193, 0.35);
    background: white;
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--dark);
    transition: all 0.2s ease;
    font-family: 'Montserrat', sans-serif;
    white-space: nowrap;
}

.category-tab:hover {
    background: rgba(255, 182, 193, 0.12);
    border-color: var(--primary);
}

.category-tab.active {
    background: linear-gradient(135deg, var(--primary), #ff8fa3);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 10px rgba(255, 182, 193, 0.35);
}
.category-tab:hover:not(.active) { background: #e9ecef; transform: translateY(-1px); }

.category-panel { 
    display: none; 
    flex-direction: column; 
    gap: 8px; 
    padding: 12px 0; 
    animation: fadeIn 0.3s ease; 
}
.category-panel.active { 
    display: flex; 
}

.flower-btn {
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    padding: 12px 16px; 
    border: 2px solid transparent; 
    border-radius: 12px;
    background: white; 
    color: #333; 
    font-size: 14px; 
    font-weight: 500;
    cursor: pointer; 
    transition: all 0.2s ease; 
    position: relative; 
    overflow: hidden;
}
.flower-btn::before {
    content: attr(data-emoji); margin-right: 10px; font-size: 18px;
}
.flower-btn:hover { 
    border-color: var(--primary, #e91e8c); 
    transform: translateY(-2px); 
    box-shadow: 0 6px 20px rgba(0,0,0,0.12); 
}
.flower-btn.active {
    background: linear-gradient(135deg, var(--primary, #e91e8c), #ad1457);
    color: white; 
    border-color: rgba(255,255,255,0.3); 
    box-shadow: 0 6px 24px rgba(233,30,140,0.4);
}
.flower-btn.out-of-stock {
    opacity: 0.6; 
    cursor: not-allowed; 
    background: #f8f9fa; 
    border-color: #dee2e6;
}
.stock-count {
    background: rgba(76,175,80,0.2); 
    color: #2e7d32; 
    padding: 2px 8px;
    border-radius: 12px; 
    font-size: 12px; 
    font-weight: 600;
}
.stock-label {
    background: rgba(244,67,54,0.2); 
    color: #c62828; 
    padding: 2px 8px;
    border-radius: 12px; 
    font-size: 12px; 
    font-weight: 600;
}
.picker-stats {
    margin-top: 12px;   
    padding: 12px; 
    background: rgba(233,30,140,0.05);
    border-radius: 12px; 
    font-size: 13px; 
    text-align: center; 
    color: #666;
}
.picker-stats span { 
    font-weight: 700; 
    color: var(--primary, #e91e8c); 
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<header>
    <div class="container header-container">
        <button id="hamburger-btn" class="hamburger-btn" aria-label="Open menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <a href="../index.html" class="logo" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px;">
            <span style="font-size: 2rem;">🌸</span>
            <h1>FLORAFIT</h1>
        </a>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu" aria-hidden="true">
            <button id="mobile-menu-close" class="mobile-menu-close" aria-label="Close menu">&times;</button>
            <nav class="mobile-nav">
                <ul>
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../index.html#about">About</a></li>
                    <li><a href="../contact.html">Contact</a></li>
                    <li><a href="../shop.html">Shop</a></li>
                    <li><a href="../customize.html">Customize</a></li>
                </ul>
            </nav>
        </div>
        <div id="mobile-menu-overlay" class="mobile-menu-overlay"></div>
        <div id="auth-toggle">
            <div id="auth-buttons">
                <a href="../login.html" class="nav-cta">Login</a>
                <a href="../signup.html" class="nav-cta">Sign Up</a>
            </div>
            <div id="profile-section" style="display: none;">
                <div class="profile-menu">
                    <img id="cornerProfilePic" src="../uploads/default-avatar.svg" alt="profile">
                    <i class="fas fa-user-circle"></i>
                    <span id="user-name"></span>
                    <button id="logout-btn" class="nav-cta">Logout</button>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container" style="padding-top:120px;">
    <div class="admin-dashboard">
        <div id="admin-sidebar-backdrop" class="admin-sidebar-backdrop"></div>
        <!-- SIDEBAR -->
        <aside class="sidebar" id="admin-sidebar">
            <h3>Admin Menu</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li class="menu-item active" data-panel="panel-dashboard"><i class="fas fa-gauge"></i> Dashboard</li>
                <li class="menu-item" data-panel="panel-florists"><i class="fas fa-user-friends"></i> Florists</li>
                <li class="menu-item" data-panel="panel-inventory"><i class="fas fa-boxes"></i> Inventory</li>
                <li class="menu-item" data-panel="panel-orders"><i class="fas fa-receipt"></i> Orders</li>
                <li class="menu-item" data-panel="panel-analytics"><i class="fas fa-chart-line"></i> Analytics</li>
                <li class="menu-item" data-panel="panel-vouchers"><i class="fas fa-ticket-alt"></i> Give Vouchers</li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <section class="main-content">
            <div class="admin-mobile-toolbar">
                <button type="button" id="admin-menu-toggle" class="admin-menu-toggle" aria-expanded="false" aria-controls="admin-sidebar">
                    <i class="fas fa-bars"></i>
                    <span>Admin Menu</span>
                </button>
            </div>
            <!-- DASHBOARD PANEL -->
            <div id="panel-dashboard" class="panel">
                <h2>Overview</h2>
                <div class="stat-cards" style="display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap;">
                    <div class="stat-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Total Orders Today</h4>
                        <p id="stat-orders-today" style="font-size: 2rem; font-weight: 700; color: #2e7d32;">—</p>
                    </div>
                    <div class="stat-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Low Stock Alerts</h4>
                        <p id="stat-low-stock" style="font-size: 2rem; font-weight: 700; color: #e65100;">—</p>
                    </div>
                    <div class="stat-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Pending Customizations</h4>
                        <p id="stat-pending-custom" style="font-size: 2rem; font-weight: 700; color: #1976d2;">—</p>
                    </div>
                </div>

                <div class="quick-actions" style="margin-bottom:24px; display:flex; gap:12px; flex-wrap:wrap;">
                    <button id="add-new-flower" class="btn-large" style="padding:12px 24px;">+ Add New Flower</button>
                    <button id="print-delivery" class="btn-large outline" style="padding:12px 24px;">🖨️ Print Delivery List</button>
                </div>

                <div style="margin-bottom:24px; padding:24px; background:white; border-radius:12px; border:1px solid rgba(0,0,0,0.06); box-shadow:var(--shadow-sm);">
                    <div style="display:flex; justify-content:space-between; gap:18px; flex-wrap:wrap; align-items:flex-start;">
                        <div style="flex:1; min-width:280px;">
                            <h3 style="margin:0 0 8px; color:#1f2937;">GCash Payment QR</h3>
                            <p style="margin:0 0 12px; color:#64748b;">Upload the official GCash QR image that customers will scan from their profile payment screen.</p>
                            <div id="admin-gcash-qr-status" style="padding:10px 12px; border-radius:10px; background:#eef7ff; color:#1d4ed8; border:1px solid #dbeafe;">Checking current QR...</div>
                        </div>
                        <div style="flex:0 0 240px; width:240px; max-width:100%; text-align:center;">
                            <img id="admin-gcash-qr-preview" src="" alt="Current GCash QR" style="display:none; width:100%; max-width:220px; border-radius:12px; border:1px solid #d6e8ff; background:#fff; padding:6px; margin:0 auto;">
                            <div id="admin-gcash-qr-empty" style="padding:20px; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b; background:#fff;">No GCash QR uploaded yet.</div>
                        </div>
                    </div>
                    <form id="gcash-qr-form" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap; margin-top:18px;">
                        <div style="flex:1; min-width:260px;">
                            <label for="gcash-qr-file">Upload QR Image</label>
                            <input type="file" id="gcash-qr-file" accept="image/png,image/jpeg,image/webp" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); background:#fff;">
                            <small style="color:#64748b; display:block; margin-top:4px;">Use your official GCash merchant/personal QR image (JPG, PNG, or WEBP).</small>
                        </div>
                        <button type="submit" id="gcash-qr-upload-btn" class="btn-large" style="padding:12px 24px; background:#1a73e8; color:white;">Upload / Replace QR</button>
                    </form>
                </div>

                <div class="metrics" style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div class="metric-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Total Orders</h4>
                        <p id="metric-orders" style="font-size: 1.8rem; font-weight: 700;">—</p>
                    </div>
                    <div class="metric-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Active Inventory Items</h4>
                        <p id="metric-inventory" style="font-size: 1.8rem; font-weight: 700;">—</p>
                    </div>
                    <div class="metric-card" style="flex: 1; min-width: 200px; padding: 24px; background: white; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                        <h4>Today's Revenue</h4>
                        <p id="metric-revenue" style="font-size: 1.8rem; font-weight: 700;">—</p>
                    </div>
                </div>
            </div>

            <!-- INVENTORY PANEL -->
            <div id="panel-inventory" class="panel hidden">
                <h2>Inventory</h2>

                <form id="flower-form" class="flower-form" style="background:white; padding:24px; border-radius:12px; border:1px solid rgba(0,0,0,0.06); margin-bottom:24px;">
                    <div class="form-row" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap;">
                        <div style="flex:1; min-width:200px;">
                            <label>Item Name</label>
                            <input type="text" id="f-name" required style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                        </div>
                        <div style="flex:1; min-width:200px;">
                            <label>Variety</label>
                            <input type="text" id="f-variety" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                        </div>
                    </div>
                    <div class="form-row" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap; margin-top:16px;">
                        <div style="flex:1; min-width:200px;">
                            <label>Category</label>
                            <select id="f-category" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                                <option value="">All Categories</option>
                                <option value="Flowers">Flowers</option>
                                <option value="Filler">Filler</option>
                                <option value="Greenery">Greenery</option>
                                <option value="Base">Base (Paper Wrap)</option>
                            </select>
                        </div>
                        <div style="flex:1; min-width:200px;">
                            <label>Stock Quantity</label>
                            <input type="number" id="f-qty" min="0" value="0" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                        </div>
                        <div style="flex:1; min-width:200px;">
                            <label>Unit Price</label>
                            <input type="number" id="f-price" step="0.01" min="0" value="0.00" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                        </div>
                    </div>
                    <div class="form-row" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap; margin-top:16px;">
                        <div style="flex:1; min-width:300px;">
                            <label>Thumbnail / Texture <span style="color:#e65100;">*</span></label>
                            <input type="file" id="f-file" accept="image/*" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                            <small style="color:#64748b; display:block; margin-top:4px;">Upload flower photo (JPG/PNG)</small>
                        </div>
                    </div>
                    <div class="form-row" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap; margin-top:16px;">
                        <div style="flex:1; min-width:300px;">
                            <label>3D Model File <span style="color:#64748b;">(Optional)</span></label>
                            <input type="file" id="f-3d-file" accept=".glb,.gltf,.obj,.fbx" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                            <small style="color:#64748b; display:block; margin-top:4px;">Upload existing 3D model OR auto-generate from photo</small>
                        </div>
                    </div>
                    <div class="form-actions" style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                        <button type="button" id="f-reset" class="btn-large outline" style="padding:12px 24px;">Reset</button>
                        <button type="submit" class="btn-large" style="padding:12px 24px; background:#2e7d32; color:white;">Save Item</button>
                    </div>
                </form>

                <div class="stock-status-summary" style="display:flex; gap:12px; margin:24px 0; flex-wrap:wrap;">
                    <button class="status-filter active" data-status="" style="padding:12px 20px; border-radius:8px; border:2px solid rgba(0,0,0,0.1); background:white; cursor:pointer; font-weight:600; transition:all 0.2s;">
                        All Items: <span id="count-all">0</span>
                    </button>
                    <button class="status-filter" data-status="ok" style="padding:12px 20px; border-radius:8px; border:2px solid #2e7d32; background:#eefaf0; color:#2e7d32; cursor:pointer; font-weight:600; transition:all 0.2s;">
                        ✓ Good Stock: <span id="count-ok">0</span>
                    </button>
                    <button class="status-filter" data-status="low" style="padding:12px 20px; border-radius:8px; border:2px solid #e65100; background:#fff4e5; color:#e65100; cursor:pointer; font-weight:600; transition:all 0.2s;">
                        ⚠ Low Stock: <span id="count-low">0</span>
                    </button>
                    <button class="status-filter" data-status="critical" style="padding:12px 20px; border-radius:8px; border:2px solid #b71c1c; background:#fdecea; color:#b71c1c; cursor:pointer; font-weight:600; transition:all 0.2s;">
                        ⛔ Critical: <span id="count-critical">0</span>
                    </button>
                    <button class="status-filter" data-status="out" style="padding:12px 20px; border-radius:8px; border:2px solid #b71c1c; background:#fdecea; color:#b71c1c; cursor:pointer; font-weight:600; transition:all 0.2s;">
                        ✖ Out of Stock: <span id="count-out">0</span>
                    </button>
                </div>

                <div class="inventory-filters" style="display:flex; gap:16px; margin:24px 0; align-items:center; flex-wrap:wrap;">
                    <input type="text" id="search-inventory" placeholder="🔍 Search by name or variety..." style="flex:1; min-width:300px; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                    <select id="filter-category" style="padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1); min-width:180px;">
                        <option value="">All Categories</option>
                        <option value="Flowers">Flowers</option>
                        <option value="Filler">Filler</option>
                        <option value="Greenery">Greenery</option>
                        <option value="Base">Base (Paper Wrap)</option>
                    </select>
                    <button id="print-inventory" class="btn-large outline" style="padding:12px 24px; white-space:nowrap;">
                        🖨️ Print Inventory
                    </button>
                </div>

                <div style="max-height: 500px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; background:white;">
                    <table class="simple-table inventory-table" style="width:100%; border-collapse:collapse;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10; border-bottom:2px solid rgba(0,0,0,0.06);">
                            <tr>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">SKU</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Item</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Category</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Stock</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Price</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Thumb</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-body">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ORDERS PANEL -->
            <div id="panel-orders" class="panel hidden">
                <h2>Orders</h2>
                <div class="orders-filters" style="display:flex; gap:16px; margin:24px 0; align-items:center; flex-wrap:wrap;">
                    <input type="text" id="search-orders" placeholder="🔍 Search order #, customer, status, item" style="flex:1; min-width:300px; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                    <input type="date" id="filter-orders-date" style="padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                    <button type="button" id="clear-orders-date" class="btn-small outline" style="padding:12px 20px;">Clear Date</button>
                    <div class="order-actions" style="display:flex; gap:8px;">
                        <button type="button" id="bulk-preparing-orders" class="btn-small info" style="padding:12px 16px;">Preparing</button>
                        <button type="button" id="bulk-delivering-orders" class="btn-small" style="padding:12px 16px;">Delivering</button>
                        <button type="button" id="bulk-delivered-orders" class="btn-small success" style="padding:12px 16px;">Delivered</button>
                    </div>
                </div>
                <div class="orders-table-wrap" style="max-height:500px; overflow-y:auto; border:1px solid rgba(0,0,0,0.06); border-radius:12px; background:white;">
                    <table class="simple-table" style="width:100%; border-collapse:collapse;">
                        <thead style="position:sticky; top:0; background:white; z-index:10; border-bottom:2px solid rgba(0,0,0,0.06);">
                            <tr>
                                <th style="padding:16px 12px; font-weight:600;"><input type="checkbox" id="select-all-orders"></th>
                                <th style="padding:16px 12px; font-weight:600;">Order #</th>
                                <th style="padding:16px 12px; font-weight:600;">Customer</th>
                                <th style="padding:16px 12px; font-weight:600;">Florist</th>
                                <th style="padding:16px 12px; font-weight:600;">Status</th>
                                <th style="padding:16px 12px; font-weight:600;">Total</th>
                                <th style="padding:16px 12px; font-weight:600;">Date</th>
                                <th style="padding:16px 12px; font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-body">
                            <tr><td colspan="8" class="orders-empty" style="padding:40px; text-align:center; color:rgba(0,0,0,0.5);">Loading orders...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="orders-bulk-actions" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding:20px; background:#f8f9fa; border-radius:12px;">
                    <span id="orders-selected-count" style="color:rgba(0,0,0,0.6); font-weight:500;">0 selected</span>
                    <div class="order-actions" style="display:flex; gap:8px;">
                        <button type="button" id="bulk-accept-orders" class="btn-small success" style="padding:12px 20px;">Accept</button>
                        <button type="button" id="bulk-decline-orders" class="btn-small danger" style="padding:12px 20px;">Decline</button>
                        <button type="button" id="bulk-delete-orders" class="btn-small danger" style="padding:12px 20px;">Delete</button>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS PANEL -->
            <div id="panel-analytics" class="panel hidden">
                <h2>Analytics</h2>
                <p style="color:rgba(0,0,0,0.6); margin-bottom:24px;">View orders by date, top bouquet ingredients, and print the report.</p>

                <div class="analytics-toolbar">
                    <div class="field">
                        <label for="analytics-start-date">From</label>
                        <input type="date" id="analytics-start-date">
                    </div>
                    <div class="field">
                        <label for="analytics-end-date">To</label>
                        <input type="date" id="analytics-end-date">
                    </div>
                    <button type="button" id="apply-analytics-filter" class="btn-small info">Apply</button>
                    <button type="button" id="analytics-today" class="btn-small outline">Today</button>
                    <button type="button" id="analytics-this-month" class="btn-small outline">This Month</button>
                    <button type="button" id="clear-analytics-filter" class="btn-small outline">Clear</button>
                    <button type="button" id="print-analytics" class="btn-large outline" style="margin-left:auto;">🖨️ Print Results</button>
                </div>

                <div class="metrics">
                    <div class="metric-card">
                        <h4>Orders in Range</h4>
                        <p id="analytics-order-count">0</p>
                    </div>
                    <div class="metric-card">
                        <h4>Revenue in Range</h4>
                        <p id="analytics-revenue">₱0.00</p>
                    </div>
                    <div class="metric-card">
                        <h4>Top Flower</h4>
                        <p id="analytics-top-flower" style="font-size:1.2rem;">None</p>
                    </div>
                </div>

                <div class="analytics-layout">
                    <div class="analytics-card">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:8px;">
                            <div>
                                <h3 style="margin-bottom:6px;">Orders & Revenue Graph</h3>
                                <p id="analytics-range-label" style="margin:0; color:#64748b;">Loading analytics…</p>
                            </div>
                            <div class="analytics-graph-legend" aria-label="Graph legend">
                                <span class="analytics-legend-item"><span class="analytics-legend-swatch orders"></span>Orders</span>
                                <span class="analytics-legend-item"><span class="analytics-legend-swatch revenue"></span>Revenue</span>
                            </div>
                        </div>
                        <div id="analytics-chart" class="analytics-chart">
                            <div class="analytics-empty">Loading graph…</div>
                        </div>
                        <div id="analytics-graph-meta" class="analytics-graph-meta"></div>
                    </div>

                    <div class="analytics-card">
                        <h3 style="margin-bottom:14px;">Top Ordered Ingredients</h3>
                        <div id="analytics-top-items" class="analytics-insight-list">
                            <div class="analytics-empty">Loading insights…</div>
                        </div>
                        <div id="analytics-status-counts" class="analytics-status-row"></div>
                    </div>
                </div>

                <div class="analytics-card">
                    <h3 style="margin-bottom:14px;">Daily Results</h3>
                    <div style="overflow-x:auto;">
                        <table class="simple-table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="analytics-table-body">
                                <tr><td colspan="3" style="padding:24px; text-align:center; color:#94a3b8;">Loading results…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- FLORISTS PANEL -->
            <div id="panel-florists" class="panel hidden">
                <h2>Florists Management</h2>
                
                <div class="quick-actions" style="margin-bottom: 24px; display:flex; gap:16px; flex-wrap:wrap;">
                    <button id="create-florist-btn" class="btn-large" style="padding:12px 32px; background:#2e7d32; color:white;">+ Create Florist</button>
                    <button id="print-florists" class="btn-large outline" style="padding:12px 32px; white-space:nowrap;">🖨️ Print Florist List</button>
                </div>

                <div class="inventory-filters" style="display:flex; gap:16px; margin:24px 0; align-items:center; flex-wrap:wrap;">
                    <input type="text" id="search-florists" placeholder="🔍 Search florists by name..." style="flex:1; min-width:300px; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                </div>

                <div style="max-height: 500px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; background:white;">
                    <table class="simple-table" style="width:100%; border-collapse:collapse;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10; border-bottom:2px solid rgba(0,0,0,0.06);">
                            <tr>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">ID</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Florist Name</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Total Orders</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Status</th>
                                <th style="padding:16px 12px; font-weight:600; text-align:left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="florists-body">
                            <tr>
                                <td colspan="5" class="orders-empty" style="padding:60px 20px; text-align:center; color:rgba(0,0,0,0.4); font-style:italic;">
                                    <i class="fas fa-users" style="font-size:3rem; color:rgba(0,0,0,0.1); display:block; margin-bottom:16px;"></i>
                                    Loading florists...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VOUCHERS PANEL -->
            <div id="panel-vouchers" class="panel hidden">
                <h2>Give Vouchers</h2>

                <div style="position:relative; max-width:360px; margin-bottom:20px;">
                    <i class="fas fa-search" style="position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                    <input type="text" id="voucher-user-search" placeholder="Search users by name or email…"
                           style="width:100%; padding:10px 16px 10px 38px; border:1px solid rgba(0,0,0,0.1); border-radius:10px; font-size:.9rem; box-sizing:border-box;"
                           oninput="filterVoucherUsers()">
                </div>

                <div id="voucher-user-list" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px;">
                    <div style="grid-column:1/-1; text-align:center; padding:48px; color:#94a3b8;">Loading users…</div>
                </div>
            </div>
        </section>
    </div>
</main>

<footer>
    <div class="container">
        <div style="text-align: center;">
            <h3>FLORA FIT</h3>
            <p>Admin Dashboard</p>
        </div>
    </div>
</footer>

<div id="confirm-modal" class="confirm-modal hidden" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" hidden>
    <div class="confirm-modal-card">
        <div class="confirm-modal-icon">🌸</div>
        <h3 id="confirm-modal-title">Please Confirm</h3>
        <p id="confirm-modal-message">Are you sure?</p>
        <div class="confirm-modal-actions">
            <button type="button" id="confirm-modal-cancel" class="btn-small outline">Cancel</button>
            <button type="button" id="confirm-modal-ok" class="btn-small success">Confirm</button>
        </div>
    </div>
</div>

<script>
// ==============================
// PANEL TOGGLING
// ==============================
document.addEventListener('click', function(e) {
    let menuItem = e.target;
    while (menuItem && !menuItem.classList.contains('menu-item')) {
        menuItem = menuItem.parentElement;
    }
    
    if (!menuItem || !menuItem.classList.contains('menu-item')) return;
    
    console.log('🔥 MENU CLICK DETECTED:', menuItem.dataset.panel);
    
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
        item.style.boxShadow = 'none';
    });
    
    document.querySelectorAll('.panel').forEach(panel => {
        panel.classList.add('hidden');
    });
    
    menuItem.classList.add('active');
    menuItem.style.boxShadow = '0 4px 16px rgba(46,125,50,0.3)';
    
    const targetPanelId = menuItem.dataset.panel;
    const targetPanel = document.getElementById(targetPanelId);
    
    if (targetPanel) {
        targetPanel.classList.remove('hidden');
        targetPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (targetPanelId === 'panel-analytics' && typeof loadAnalyticsFromDB === 'function') {
            loadAnalyticsFromDB();
        }
        console.log('✅ SWITCHED TO:', targetPanelId);
    } else {
        console.error('❌ PANEL NOT FOUND:', targetPanelId);
    }
    
    e.preventDefault();
}, true);

// ==============================
// DASHBOARD DATA
// ==============================
let orders = [];
let orderSearchQuery = '';
let orderDateFilter = '';
let selectedOrderIds = new Set();
const floristStorageKey = 'florafit_florists';
const defaultFloristOptions = ['Unassigned', 'Florist A', 'Florist B', 'Florist C'];

function getFloristOptions() {
    try {
        const raw = localStorage.getItem(floristStorageKey);
        const parsed = raw ? JSON.parse(raw) : [];
        const cleaned = Array.isArray(parsed)
            ? parsed.map(value => String(value || '').trim()).filter(Boolean)
            : [];

        if (!cleaned.some(value => value.toLowerCase() === 'unassigned')) {
            cleaned.unshift('Unassigned');
        }

        return cleaned.length ? cleaned : [...defaultFloristOptions];
    } catch (err) {
        return [...defaultFloristOptions];
    }
}

function saveFloristOptions(options) {
    const unique = [];
    options.forEach(option => {
        const value = String(option || '').trim();
        if (!value) return;
        if (!unique.some(existing => existing.toLowerCase() === value.toLowerCase())) {
            unique.push(value);
        }
    });

    if (!unique.some(value => value.toLowerCase() === 'unassigned')) {
        unique.unshift('Unassigned');
    }

    localStorage.setItem(floristStorageKey, JSON.stringify(unique));
    floristOptions = unique;
}

let floristOptions = getFloristOptions();

function formatCurrency(amount) {
    return `₱${Number(amount || 0).toFixed(2)}`;
}

function formatDateTime(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

const confirmModal = document.getElementById('confirm-modal');
const confirmModalTitle = document.getElementById('confirm-modal-title');
const confirmModalMessage = document.getElementById('confirm-modal-message');
const confirmModalOk = document.getElementById('confirm-modal-ok');
const confirmModalCancel = document.getElementById('confirm-modal-cancel');
let confirmModalResolver = null;

function closeConfirmModal(result) {
    if (confirmModal) {
        confirmModal.hidden = true;
        confirmModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (confirmModalResolver) {
        const resolver = confirmModalResolver;
        confirmModalResolver = null;
        resolver(result);
    }
}

function confirmActionPopup(message, title = 'Please Confirm') {
    return new Promise((resolve) => {
        if (!confirmModal || !confirmModalTitle || !confirmModalMessage) {
            resolve(window.confirm(message));
            return;
        }

        confirmModalTitle.textContent = title;
        confirmModalMessage.textContent = message;
        confirmModal.hidden = false;
        confirmModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        confirmModalResolver = resolve;
    });
}

confirmModalOk?.addEventListener('click', () => closeConfirmModal(true));
confirmModalCancel?.addEventListener('click', () => closeConfirmModal(false));
confirmModal?.addEventListener('click', (e) => {
    if (e.target === confirmModal) closeConfirmModal(false);
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
        closeConfirmModal(false);
    }
});

function statusActionLabel(status) {
    const normalized = String(status || '').toLowerCase();
    if (normalized === 'accepted') return 'Accept';
    if (normalized === 'preparing') return 'Set to Preparing';
    if (normalized === 'delivering') return 'Set to Delivering';
    if (normalized === 'delivered') return 'Set to Delivered';
    if (normalized === 'declined') return 'Decline';
    if (normalized === 'pending') return 'Set to Pending';
    return 'Update';
}

function normalizeOrderStatus(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'delivered') return 'Delivered';
    if (value === 'delivering') return 'Delivering';
    if (value === 'preparing') return 'Preparing';
    if (value === 'accepted') return 'Accepted';
    if (value === 'declined') return 'Declined';
    return 'Pending';
}

function normalizePaymentStatus(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'paid') return 'Paid';
    if (value === 'pending confirmation') return 'Pending Confirmation';
    return 'Unpaid';
}

const adminGcashQrStatus = document.getElementById('admin-gcash-qr-status');
const adminGcashQrPreview = document.getElementById('admin-gcash-qr-preview');
const adminGcashQrEmpty = document.getElementById('admin-gcash-qr-empty');
const adminGcashQrForm = document.getElementById('gcash-qr-form');
const adminGcashQrFile = document.getElementById('gcash-qr-file');
const adminGcashQrUploadBtn = document.getElementById('gcash-qr-upload-btn');

function setAdminGcashQrState(state = {}) {
    const available = Boolean(state.available && state.url);
    const message = String(state.message || (available
        ? 'Current GCash QR is live on the customer payment screen.'
        : 'No GCash QR uploaded yet.'));

    if (adminGcashQrStatus) {
        adminGcashQrStatus.textContent = message;
        adminGcashQrStatus.style.background = available ? '#eefbf0' : '#fff8e1';
        adminGcashQrStatus.style.color = available ? '#236d2e' : '#8a6500';
        adminGcashQrStatus.style.borderColor = available ? '#bfe3c7' : '#f3d18d';
    }

    if (available && adminGcashQrPreview) {
        adminGcashQrPreview.src = String(state.url);
        adminGcashQrPreview.style.display = 'block';
    } else if (adminGcashQrPreview) {
        adminGcashQrPreview.removeAttribute('src');
        adminGcashQrPreview.style.display = 'none';
    }

    if (adminGcashQrEmpty) {
        adminGcashQrEmpty.textContent = message;
        adminGcashQrEmpty.style.display = available ? 'none' : 'block';
    }
}

async function loadAdminGcashQr() {
    if (!adminGcashQrStatus) return;

    setAdminGcashQrState({ available: false, url: '', message: 'Checking current QR...' });

    try {
        const response = await fetch(`../api/get_gcash_qr.php?t=${Date.now()}`, { cache: 'no-store' });
        const data = await response.json();

        if (data && data.success) {
            setAdminGcashQrState({
                available: Boolean(data.available && data.url),
                url: data.url || '',
                message: data.message || ''
            });
        } else {
            setAdminGcashQrState({ available: false, url: '', message: 'Unable to load the current GCash QR.' });
        }
    } catch (error) {
        console.error('Failed to load admin GCash QR:', error);
        setAdminGcashQrState({ available: false, url: '', message: 'Unable to load the current GCash QR.' });
    }
}

adminGcashQrForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const file = adminGcashQrFile?.files?.[0];
    if (!file) {
        showNotification('Choose a QR image first.', 'warning');
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(String(file.type || '').toLowerCase())) {
        showNotification('Only JPG, PNG, or WEBP images are allowed.', 'warning');
        return;
    }

    const originalText = adminGcashQrUploadBtn?.textContent || 'Upload / Replace QR';
    if (adminGcashQrUploadBtn) {
        adminGcashQrUploadBtn.disabled = true;
        adminGcashQrUploadBtn.textContent = 'Uploading...';
    }

    try {
        const formData = new FormData();
        formData.append('gcash_qr', file);

        const response = await fetch('../api/upload_gcash_qr.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data && data.success) {
            setAdminGcashQrState({
                available: true,
                url: data.url || data.path || '',
                message: 'GCash QR updated successfully. Customers will now see the latest QR.'
            });
            if (adminGcashQrFile) adminGcashQrFile.value = '';
            showNotification(data.message || 'GCash QR uploaded successfully.', 'success');
        } else {
            showNotification(data?.message || 'Failed to upload the GCash QR.', 'error');
            await loadAdminGcashQr();
        }
    } catch (error) {
        console.error('GCash QR upload failed:', error);
        showNotification('Failed to upload the GCash QR.', 'error');
        await loadAdminGcashQr();
    } finally {
        if (adminGcashQrUploadBtn) {
            adminGcashQrUploadBtn.disabled = false;
            adminGcashQrUploadBtn.textContent = originalText;
        }
    }
});

function isToday(dateValue) {
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return false;
    const now = new Date();
    return date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth() &&
        date.getDate() === now.getDate();
}

function renderOrdersTable() {
    const tbody = document.getElementById('orders-body');
    if (!tbody) return;

    const filteredOrders = orders.filter(order => {
        const query = orderSearchQuery.trim().toLowerCase();
        const createdDate = new Date(order.created_at);
        const orderDate = Number.isNaN(createdDate.getTime())
            ? ''
            : `${createdDate.getFullYear()}-${String(createdDate.getMonth() + 1).padStart(2, '0')}-${String(createdDate.getDate()).padStart(2, '0')}`;

        const matchesDate = !orderDateFilter || orderDate === orderDateFilter;
        if (!matchesDate) return false;

        if (!query) return true;

        const haystacks = [
            order.order_number,
            order.customer_name,
            order.recipient_name,
            order.assigned_florist,
            order.status,
            order.payment_status,
            order.payment_method,
            ...(Array.isArray(order.items) ? order.items.flatMap(item => [item.name, item.description]) : [])
        ].map(value => String(value || '').toLowerCase());

        return haystacks.some(text => text.includes(query));
    });

    if (!filteredOrders.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="orders-empty">No orders yet.</td></tr>';
        updateSelectedOrdersUI();
        return;
    }

    tbody.innerHTML = '';
    filteredOrders.forEach(order => {
        const currentStatus = normalizeOrderStatus(order.status);
        const currentPaymentStatus = normalizePaymentStatus(order.payment_status);
        const items = Array.isArray(order.items) ? order.items : [];
        const isSelected = selectedOrderIds.has(Number(order.id));
        const assignedFlorist = String(order.assigned_florist || '').trim() || 'Unassigned';
        const showApprovePayment = String(currentPaymentStatus).toLowerCase() === 'pending confirmation';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="checkbox" class="order-select" data-id="${order.id}" ${isSelected ? 'checked' : ''}></td>
            <td>${order.order_number || '-'}</td>
            <td>${order.customer_name || order.recipient_name || '-'}</td>
            <td>
                <select class="florist-select" data-order-id="${order.id}" aria-label="Assign florist for order ${order.order_number || order.id}">
                    ${floristOptions.map(option => `<option value="${option}" ${option === assignedFlorist ? 'selected' : ''}>${option}</option>`).join('')}
                    <option value="__add_new__">+ Add new florist</option>
                </select>
            </td>
            <td><span class="order-status-badge ${String(currentStatus).toLowerCase()}">${currentStatus}</span></td>
            <td>${formatCurrency(order.total)}</td>
            <td>${formatDateTime(order.created_at)}</td>
            <td>
                <div class="order-actions">
                <button class="btn-small outline" data-action="toggle-order-items" data-id="${order.id}">Items</button>
                ${showApprovePayment ? `<button class="btn-small success" data-action="approve-payment" data-id="${order.id}">Approve Payment</button>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);

        const detailsRow = document.createElement('tr');
        detailsRow.className = 'order-items-row hidden';
        detailsRow.setAttribute('data-order-id', String(order.id));
        detailsRow.innerHTML = `
            <td colspan="8">
                ${items.length ? `
                    <div class="order-items-list">
                        <div class="order-item-card" style="justify-content:space-between; align-items:center;">
                            <div>
                                <div class="order-item-title">Payment</div>
                                <div class="order-item-description">Method: ${order.payment_method || '—'} • Status: ${currentPaymentStatus}</div>
                            </div>
                            <div class="order-item-qty">${order.payment_confirmed_at ? `Submitted: ${formatDateTime(order.payment_confirmed_at)}` : 'Not submitted'}</div>
                        </div>
                        ${items.map(item => `
                            <div class="order-item-card">
                                <img src="${inventoryImageUrl(item.image) || 'https://via.placeholder.com/100?text=No+Image'}" alt="${item.name || 'Item'}" class="order-item-image">
                                <div>
                                    <div class="order-item-title">${item.name || 'Item'}</div>
                                    <div class="order-item-description">${item.description || 'No description'}</div>
                                </div>
                                <div class="order-item-qty">Qty: ${Number(item.quantity || 1)}</div>
                            </div>
                        `).join('')}
                    </div>
                ` : '<div class="order-items-empty">No item details captured for this order.</div>'}
            </td>
        `;
        tbody.appendChild(detailsRow);
    });

    updateSelectedOrdersUI();
}

async function updateOrderStatus(orderId, status) {
    const actionLabel = statusActionLabel(status);
    if (!await confirmActionPopup(`${actionLabel} this order?`, 'Confirm Order Action')) return;

    const formData = new FormData();
    formData.append('id', orderId);
    formData.append('status', status);

    try {
        const res = await fetch('../api/update_order_status.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data && data.success) {
            showNotification('Order status updated.', 'success');
            await loadOrdersFromDB();
            if (status === 'Accepted') await loadInventoryFromDB();
        } else {
            showNotification(data.message || 'Failed to update order status.', 'error');
        }
    } catch (err) {
        console.error('Status update failed:', err);
        showNotification('Failed to update order status.', 'error');
    }
}

async function deleteOrder(orderId) {
    if (!await confirmActionPopup('Delete this order? This cannot be undone.', 'Delete Order')) return;

    const formData = new FormData();
    formData.append('id', orderId);

    fetch('../api/delete_order.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                showNotification('Order deleted.', 'success');
                loadOrdersFromDB();
            } else {
                showNotification(data.message || 'Failed to delete order.', 'error');
            }
        })
        .catch(err => {
            console.error('Delete order failed:', err);
            showNotification('Failed to delete order.', 'error');
        });
}

async function updateOrderFlorist(orderId, florist) {
    const formData = new FormData();
    formData.append('id', orderId);
    formData.append('florist', florist);

    try {
        const res = await fetch('../api/update_order_florist.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data && data.success) {
            const orderIndex = orders.findIndex(order => Number(order.id) === Number(orderId));
            if (orderIndex >= 0) orders[orderIndex].assigned_florist = florist;
            showNotification('Florist assigned.', 'success');
        } else {
            showNotification(data.message || 'Failed to assign florist.', 'error');
            await loadOrdersFromDB();
        }
    } catch (err) {
        console.error('Florist update failed:', err);
        showNotification('Failed to assign florist.', 'error');
        await loadOrdersFromDB();
    }
}

async function approveOrderPayment(orderId) {
    if (!await confirmActionPopup('Mark this order payment as paid?', 'Approve Payment')) return;

    const formData = new FormData();
    formData.append('id', orderId);

    try {
        const response = await fetch('../api/approve_order_payment.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data && data.success) {
            showNotification('Payment approved.', 'success');
            await loadOrdersFromDB();
        } else {
            showNotification(data.message || 'Failed to approve payment.', 'error');
        }
    } catch (error) {
        console.error('Payment approval failed:', error);
        showNotification('Failed to approve payment.', 'error');
    }
}

function updateSelectedOrdersUI() {
    const selectedCountEl = document.getElementById('orders-selected-count');
    const selectAll = document.getElementById('select-all-orders');
    const visibleChecks = Array.from(document.querySelectorAll('#orders-body .order-select'));
    const visibleSelected = visibleChecks.filter(chk => chk.checked).length;

    if (selectedCountEl) selectedCountEl.textContent = `${selectedOrderIds.size} selected`;

    if (selectAll) {
        if (visibleChecks.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else {
            selectAll.checked = visibleSelected === visibleChecks.length;
            selectAll.indeterminate = visibleSelected > 0 && visibleSelected < visibleChecks.length;
        }
    }
}

async function bulkUpdateOrderStatus(status) {
    const ids = Array.from(selectedOrderIds);
    if (!ids.length) {
        showNotification('Select at least one order first.', 'warning');
        return;
    }

    const actionLabel = statusActionLabel(status);
    if (!await confirmActionPopup(`${actionLabel} ${ids.length} selected order(s)?`, 'Confirm Bulk Action')) return;

    let updatedCount = 0;
    let failedCount = 0;
    let firstError = '';

    for (const id of ids) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            const response = await fetch('../api/update_order_status.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result && result.success) {
                updatedCount += 1;
            } else {
                failedCount += 1;
                if (!firstError) firstError = result?.message || 'Failed to update selected orders.';
            }
        } catch (error) {
            failedCount += 1;
            if (!firstError) firstError = 'Failed to update selected orders.';
        }
    }

    if (updatedCount > 0 && failedCount === 0) {
        showNotification('Selected orders updated.', 'success');
    } else if (updatedCount > 0) {
        showNotification(`Updated ${updatedCount} order(s). ${failedCount} failed.`, 'warning');
    } else {
        showNotification(firstError || 'No orders were updated.', 'error');
    }

    selectedOrderIds.clear();
    await loadOrdersFromDB();
    if (status === 'Accepted') await loadInventoryFromDB();
}

async function bulkDeleteOrders() {
    const ids = Array.from(selectedOrderIds);
    if (!ids.length) {
        showNotification('Select at least one order first.', 'warning');
        return;
    }

    if (!await confirmActionPopup('Delete selected orders? This cannot be undone.', 'Delete Selected Orders')) return;

    for (const id of ids) {
        const formData = new FormData();
        formData.append('id', id);
        await fetch('../api/delete_order.php', { method: 'POST', body: formData });
    }

    showNotification('Selected orders deleted.', 'success');
    selectedOrderIds.clear();
    loadOrdersFromDB();
}

function updateDashboardMetrics() {
    const lowStockCount = inventory.filter(item => {
        const stock = Number(item.stock) || 0;
        return stock > 0 && stock <= 50;
    }).length;

    const ordersToday = orders.filter(order => isToday(order.created_at));
    const todayRevenue = ordersToday.reduce((sum, order) => sum + Number(order.total || 0), 0);
    const pendingOrders = orders.filter(order => String(order.status || '').toLowerCase() === 'pending').length;

    document.getElementById('metric-orders').innerText = orders.length;
    document.getElementById('metric-inventory').innerText = inventory.length;
    document.getElementById('metric-revenue').innerText = formatCurrency(todayRevenue);
    document.getElementById('stat-orders-today').innerText = ordersToday.length;
    document.getElementById('stat-low-stock').innerText = lowStockCount;
    document.getElementById('stat-pending-custom').innerText = pendingOrders;
}

let analyticsReport = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatCompactCurrency(amount) {
    const value = Number(amount || 0);
    if (value >= 1000000) return `₱${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `₱${(value / 1000).toFixed(1)}k`;
    return `₱${value.toFixed(0)}`;
}

function renderAnalyticsChart(daily = []) {
    const chart = document.getElementById('analytics-chart');
    const meta = document.getElementById('analytics-graph-meta');
    if (!chart) return;

    if (!Array.isArray(daily) || daily.length === 0) {
        chart.innerHTML = '<div class="analytics-empty">No orders found for the selected date range.</div>';
        if (meta) meta.innerHTML = '';
        return;
    }

    const width = 760;
    const height = 260;
    const padding = { top: 20, right: 18, bottom: 44, left: 46 };
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const maxOrders = Math.max(...daily.map(item => Number(item.order_count || 0)), 1);
    const maxRevenue = Math.max(...daily.map(item => Number(item.revenue || 0)), 1);
    const stepX = daily.length > 1 ? innerWidth / (daily.length - 1) : 0;
    const baseY = padding.top + innerHeight;

    const orderPoints = daily.map((item, index) => {
        const count = Number(item.order_count || 0);
        const x = padding.left + (daily.length > 1 ? index * stepX : innerWidth / 2);
        const y = baseY - ((count / maxOrders) * innerHeight);
        return {
            x,
            y,
            value: count,
            label: String(item.label || item.date || ''),
            date: String(item.date || '')
        };
    });

    const revenuePoints = daily.map((item, index) => {
        const revenue = Number(item.revenue || 0);
        const x = padding.left + (daily.length > 1 ? index * stepX : innerWidth / 2);
        const y = baseY - ((revenue / maxRevenue) * innerHeight);
        return {
            x,
            y,
            value: revenue,
            label: String(item.label || item.date || ''),
            date: String(item.date || '')
        };
    });

    const ordersPath = orderPoints.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
    const revenuePath = revenuePoints.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
    const revenueAreaPath = revenuePoints.length
        ? `M ${revenuePoints[0].x} ${baseY} ${revenuePoints.map((point, index) => `L ${point.x} ${point.y}`).join(' ')} L ${revenuePoints[revenuePoints.length - 1].x} ${baseY} Z`
        : '';

    const gridLines = [0, 0.25, 0.5, 0.75, 1].map(ratio => {
        const y = padding.top + innerHeight - (innerHeight * ratio);
        const label = Math.round(maxOrders * ratio);
        return `
            <line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="rgba(148,163,184,0.28)" stroke-dasharray="4 6"></line>
            <text x="${padding.left - 10}" y="${y + 4}" text-anchor="end" font-size="11" fill="#94a3b8">${label}</text>
        `;
    }).join('');

    const xLabels = orderPoints.map(point => `
        <text x="${point.x}" y="${height - 12}" text-anchor="middle" font-size="11" fill="#64748b">${escapeHtml(point.label)}</text>
    `).join('');

    const orderDots = orderPoints.map(point => `
        <circle cx="${point.x}" cy="${point.y}" r="4.5" fill="#2e7d32" stroke="#ffffff" stroke-width="2">
            <title>${escapeHtml(point.date)} • ${point.value} order(s)</title>
        </circle>
    `).join('');

    const revenueDots = revenuePoints.map(point => `
        <circle cx="${point.x}" cy="${point.y}" r="4" fill="#2196f3" stroke="#ffffff" stroke-width="2">
            <title>${escapeHtml(point.date)} • ${formatCurrency(point.value)}</title>
        </circle>
    `).join('');

    chart.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Orders and revenue graph">
            <defs>
                <linearGradient id="analyticsRevenueFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="rgba(33,150,243,0.28)"></stop>
                    <stop offset="100%" stop-color="rgba(33,150,243,0.04)"></stop>
                </linearGradient>
            </defs>
            ${gridLines}
            <line x1="${padding.left}" y1="${baseY}" x2="${width - padding.right}" y2="${baseY}" stroke="rgba(100,116,139,0.35)"></line>
            <path d="${revenueAreaPath}" fill="url(#analyticsRevenueFill)"></path>
            <path d="${revenuePath}" fill="none" stroke="#2196f3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="${ordersPath}" fill="none" stroke="#2e7d32" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path>
            ${revenueDots}
            ${orderDots}
            ${xLabels}
        </svg>
    `;

    const totalOrders = daily.reduce((sum, item) => sum + Number(item.order_count || 0), 0);
    const averageOrders = (totalOrders / Math.max(daily.length, 1)).toFixed(1);
    const peakOrdersDay = daily.reduce((best, item) => Number(item.order_count || 0) > Number(best.order_count || 0) ? item : best, daily[0]);
    const peakRevenueDay = daily.reduce((best, item) => Number(item.revenue || 0) > Number(best.revenue || 0) ? item : best, daily[0]);

    if (meta) {
        meta.innerHTML = `
            <div class="analytics-graph-stat">
                <strong>Peak Orders Day</strong>
                <span>${escapeHtml(peakOrdersDay.date || 'N/A')} · ${Number(peakOrdersDay.order_count || 0)} orders</span>
            </div>
            <div class="analytics-graph-stat">
                <strong>Average Daily Orders</strong>
                <span>${averageOrders} per day</span>
            </div>
            <div class="analytics-graph-stat">
                <strong>Best Revenue Day</strong>
                <span>${escapeHtml(peakRevenueDay.date || 'N/A')} · ${formatCompactCurrency(peakRevenueDay.revenue || 0)}</span>
            </div>
        `;
    }
}

function renderAnalyticsReport(data) {
    analyticsReport = data;

    const summary = data?.summary || {};
    const range = data?.range || {};
    const topFlower = summary.top_flower || { name: 'None', quantity: 0 };
    const topFiller = summary.top_filler || { name: 'None', quantity: 0 };
    const topGreenery = summary.top_greenery || { name: 'None', quantity: 0 };

    const orderCountEl = document.getElementById('analytics-order-count');
    const revenueEl = document.getElementById('analytics-revenue');
    const topFlowerEl = document.getElementById('analytics-top-flower');
    const rangeLabelEl = document.getElementById('analytics-range-label');
    const topItemsEl = document.getElementById('analytics-top-items');
    const statusCountsEl = document.getElementById('analytics-status-counts');
    const tableBody = document.getElementById('analytics-table-body');

    if (orderCountEl) orderCountEl.textContent = String(summary.order_count || 0);
    if (revenueEl) revenueEl.textContent = formatCurrency(summary.total_revenue || 0);
    if (topFlowerEl) topFlowerEl.textContent = `${topFlower.name || 'None'} (${topFlower.quantity || 0})`;
    if (rangeLabelEl) rangeLabelEl.textContent = range.label || 'Selected date range';

    if (topItemsEl) {
        topItemsEl.innerHTML = `
            <div class="analytics-insight-item">
                <strong>Most Ordered Flower</strong>
                <span>${escapeHtml(topFlower.name || 'None')} (${Number(topFlower.quantity || 0)})</span>
            </div>
            <div class="analytics-insight-item">
                <strong>Most Ordered Filler</strong>
                <span>${escapeHtml(topFiller.name || 'None')} (${Number(topFiller.quantity || 0)})</span>
            </div>
            <div class="analytics-insight-item">
                <strong>Most Ordered Greenery</strong>
                <span>${escapeHtml(topGreenery.name || 'None')} (${Number(topGreenery.quantity || 0)})</span>
            </div>
        `;
    }

    if (statusCountsEl) {
        const statuses = Array.isArray(data?.status_counts) ? data.status_counts : [];
        statusCountsEl.innerHTML = statuses.length
            ? statuses.map(entry => `<span class="analytics-status-pill">${escapeHtml(entry.status)}: ${Number(entry.total || 0)}</span>`).join('')
            : '<span class="analytics-status-pill">No status data</span>';
    }

    if (tableBody) {
        const daily = Array.isArray(data?.daily) ? data.daily : [];
        tableBody.innerHTML = daily.length
            ? daily.map(entry => `
                <tr>
                    <td>${escapeHtml(entry.date)}</td>
                    <td>${Number(entry.order_count || 0)}</td>
                    <td>${formatCurrency(entry.revenue || 0)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="3" style="padding:24px; text-align:center; color:#94a3b8;">No orders found for this range.</td></tr>';
    }

    renderAnalyticsChart(Array.isArray(data?.daily) ? data.daily : []);
}

async function loadAnalyticsFromDB() {
    const startInput = document.getElementById('analytics-start-date');
    const endInput = document.getElementById('analytics-end-date');
    const chart = document.getElementById('analytics-chart');

    const params = new URLSearchParams();
    if (startInput?.value) params.set('start_date', startInput.value);
    if (endInput?.value) params.set('end_date', endInput.value);

    if (chart) {
        chart.innerHTML = '<div class="analytics-empty">Loading analytics…</div>';
    }

    try {
        const response = await fetch(`../api/get_order_analytics.php${params.toString() ? `?${params.toString()}` : ''}`);
        const data = await response.json();

        if (data && data.success) {
            if (startInput && !startInput.value && data.range?.start_date) startInput.value = data.range.start_date;
            if (endInput && !endInput.value && data.range?.end_date) endInput.value = data.range.end_date;
            renderAnalyticsReport(data);
        } else {
            throw new Error(data?.message || 'Failed to load analytics');
        }
    } catch (error) {
        console.error('Analytics load failed:', error);
        renderAnalyticsReport({
            range: { label: 'Analytics unavailable' },
            summary: {
                order_count: 0,
                total_revenue: 0,
                top_flower: { name: 'None', quantity: 0 },
                top_filler: { name: 'None', quantity: 0 },
                top_greenery: { name: 'None', quantity: 0 }
            },
            status_counts: [],
            daily: []
        });
        showNotification('Failed to load analytics.', 'error');
    }
}

function applyAnalyticsPreset(mode) {
    const startInput = document.getElementById('analytics-start-date');
    const endInput = document.getElementById('analytics-end-date');
    if (!startInput || !endInput) return;

    const today = new Date();
    const todayValue = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    if (mode === 'today') {
        startInput.value = todayValue;
        endInput.value = todayValue;
    } else if (mode === 'month') {
        const firstDay = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-01`;
        startInput.value = firstDay;
        endInput.value = todayValue;
    } else {
        startInput.value = '';
        endInput.value = '';
    }

    loadAnalyticsFromDB();
}

function printAnalyticsReport() {
    if (!analyticsReport) {
        showNotification('Load analytics first.', 'warning');
        return;
    }

    const summary = analyticsReport.summary || {};
    const range = analyticsReport.range || {};
    const daily = Array.isArray(analyticsReport.daily) ? analyticsReport.daily : [];
    const statuses = Array.isArray(analyticsReport.status_counts) ? analyticsReport.status_counts : [];

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        showNotification('Please allow pop-ups to print the report.', 'warning');
        return;
    }

    const statusHtml = statuses.length
        ? statuses.map(entry => `<span style="display:inline-block; margin:4px 8px 0 0; padding:6px 10px; border-radius:999px; background:#eef7ee; color:#2e7d32; font-weight:700; font-size:12px;">${escapeHtml(entry.status)}: ${Number(entry.total || 0)}</span>`).join('')
        : '<span style="color:#64748b;">No status data</span>';

    const rowsHtml = daily.length
        ? daily.map(entry => `<tr><td>${escapeHtml(entry.date)}</td><td>${Number(entry.order_count || 0)}</td><td>${formatCurrency(entry.revenue || 0)}</td></tr>`).join('')
        : '<tr><td colspan="3" style="text-align:center; color:#64748b;">No results found</td></tr>';

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>FloraFit Analytics Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 24px; color: #1f2937; }
                h1 { color: #2e7d32; margin-bottom: 6px; }
                .muted { color: #64748b; margin-bottom: 18px; }
                .summary { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 12px; margin: 18px 0; }
                .card { border: 1px solid #dfe7df; border-radius: 12px; padding: 14px; }
                .card strong { display: block; color: #64748b; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; }
                .card span { font-size: 20px; font-weight: 700; color: #2e7d32; }
                table { width: 100%; border-collapse: collapse; margin-top: 18px; }
                th, td { border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; }
                th { background: #f4faf4; color: #2e7d32; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <h1>🌸 FloraFit Analytics Report</h1>
            <div class="muted">Date range: ${escapeHtml(range.label || 'Selected range')}</div>
            <div class="summary">
                <div class="card"><strong>Orders in Range</strong><span>${Number(summary.order_count || 0)}</span></div>
                <div class="card"><strong>Revenue in Range</strong><span>${formatCurrency(summary.total_revenue || 0)}</span></div>
                <div class="card"><strong>Top Flower</strong><span>${escapeHtml(summary.top_flower?.name || 'None')} (${Number(summary.top_flower?.quantity || 0)})</span></div>
                <div class="card"><strong>Top Filler</strong><span>${escapeHtml(summary.top_filler?.name || 'None')} (${Number(summary.top_filler?.quantity || 0)})</span></div>
                <div class="card"><strong>Top Greenery</strong><span>${escapeHtml(summary.top_greenery?.name || 'None')} (${Number(summary.top_greenery?.quantity || 0)})</span></div>
            </div>
            <div style="margin: 12px 0 18px;"><strong>Status Summary:</strong><br>${statusHtml}</div>
            <table>
                <thead>
                    <tr><th>Date</th><th>Orders</th><th>Revenue</th></tr>
                </thead>
                <tbody>${rowsHtml}</tbody>
            </table>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 400);
}

function loadOrdersFromDB() {
    return fetch('../api/get_orders.php')
        .then(res => res.json())
        .then(data => {
            orders = (data && data.success && Array.isArray(data.orders)) ? data.orders : [];
            renderOrdersTable();
            updateDashboardMetrics();
            const analyticsPanel = document.getElementById('panel-analytics');
            if (analyticsPanel && !analyticsPanel.classList.contains('hidden') && typeof loadAnalyticsFromDB === 'function') {
                loadAnalyticsFromDB();
            }
        })
        .catch(err => {
            console.error('Failed to load orders:', err);
            orders = [];
            renderOrdersTable();
            updateDashboardMetrics();
        });
}

document.getElementById('search-orders')?.addEventListener('input', (e) => {
    orderSearchQuery = String(e.target.value || '');
    renderOrdersTable();
});

document.getElementById('filter-orders-date')?.addEventListener('change', (e) => {
    orderDateFilter = String(e.target.value || '');
    renderOrdersTable();
});

document.getElementById('clear-orders-date')?.addEventListener('click', () => {
    orderDateFilter = '';
    const dateInput = document.getElementById('filter-orders-date');
    if (dateInput) dateInput.value = '';
    renderOrdersTable();
});

document.getElementById('apply-analytics-filter')?.addEventListener('click', loadAnalyticsFromDB);
document.getElementById('analytics-today')?.addEventListener('click', () => applyAnalyticsPreset('today'));
document.getElementById('analytics-this-month')?.addEventListener('click', () => applyAnalyticsPreset('month'));
document.getElementById('clear-analytics-filter')?.addEventListener('click', () => applyAnalyticsPreset('clear'));
document.getElementById('print-analytics')?.addEventListener('click', printAnalyticsReport);

// ==============================
// QUICK ACTIONS
// ==============================
document.getElementById('add-new-flower').addEventListener('click', () => {
    window.location.href = 'customize.html';
});

document.getElementById('print-delivery').addEventListener('click', () => {
    window.print();
});

document.getElementById('print-inventory')?.addEventListener('click', () => {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
    
    let inventoryHtml = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Flora Fit - Inventory Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2e7d32; padding-bottom: 20px; }
                .header h1 { margin: 0; color: #2e7d32; font-size: 32px; }
                .header p { margin: 5px 0; color: #666; }
                .summary { display: flex; justify-content: space-around; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
                .summary-item { text-align: center; }
                .summary-item h3 { margin: 0; font-size: 28px; color: #2e7d32; }
                .summary-item p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #2e7d32; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .stock-ok { color: #2e7d32; font-weight: bold; }
                .stock-low { color: #e65100; font-weight: bold; }
                .stock-critical { color: #b71c1c; font-weight: bold; }
                .stock-out { color: #b71c1c; font-weight: bold; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🌸 FLORAFIT</h1>
                <p>Inventory Stock Report</p>
                <p>Generated on: ${currentDate}</p>
            </div>
            <div class="summary">
                <div class="summary-item">
                    <h3>${inventory.length}</h3>
                    <p>Total Items</p>
                </div>
                <div class="summary-item">
                    <h3 style="color:#2e7d32;">${inventory.filter(item => Number(item.stock) > 50).length}</h3>
                    <p>Good Stock</p>
                </div>
                <div class="summary-item">
                    <h3 style="color:#e65100;">${inventory.filter(item => { const s = Number(item.stock); return s > 10 && s <= 50; }).length}</h3>
                    <p>Low Stock</p>
                </div>
                <div class="summary-item">
                    <h3 style="color:#b71c1c;">${inventory.filter(item => Number(item.stock) <= 10).length}</h3>
                    <p>Critical/Out</p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th><th>Item Name</th><th>Variety</th>
                        <th>Category</th><th>Stock Qty</th><th>Unit Price</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    inventory.forEach(item => {
        const stockValue = Number(item.stock) || 0;
        let stockClass = 'stock-ok', status = 'Good';
        if (stockValue <= 0) { stockClass = 'stock-out'; status = 'OUT OF STOCK'; }
        else if (stockValue <= 10) { stockClass = 'stock-critical'; status = 'CRITICAL'; }
        else if (stockValue <= 50) { stockClass = 'stock-low'; status = 'Low'; }
        
        inventoryHtml += `
            <tr>
                <td>${item.sku || '-'}</td>
                <td>${item.name}</td>
                <td>${item.variety || '-'}</td>
                <td>${item.category}</td>
                <td class="${stockClass}">${stockValue}</td>
                <td>₱${Number(item.price).toFixed(2)}</td>
                <td class="${stockClass}">${status}</td>
            </tr>
        `;
    });
    
    inventoryHtml += `</tbody></table></body></html>`;
    
    printWindow.document.write(inventoryHtml);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 500);
});

/* ==========================================================================
   INVENTORY SYSTEM
   ========================================================================== */
// 🚨 CRITICAL STOCK CHECKER
async function checkCriticalStock() {
    const criticalItems = inventory
        .filter(item => getStockStatus(item.stock) === 'critical')
        .map(item => ({
            id: Number(item.id) || 0,
            name: item.name || 'Unknown Item',
            stock: Number(item.stock) || 0,
            variety: item.variety || '',
            category: item.category || ''
        }));

    if (!criticalItems.length) return;

    criticalItems.forEach(item => {
        showNotification(
            `🚨 CRITICAL: ${item.name} (${item.stock} left!)`,
            'danger'
        );
    });

    try {
        const response = await fetch('../api/notify_critical_stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ critical_items: criticalItems })
        });
        const result = await response.json();
        if (!result.success && result.message) {
            console.warn('Critical stock SMS notice:', result.message);
        }
    } catch (error) {
        console.error('Critical stock SMS request failed:', error);
    }
}

// ==============================
// 🔥 COMPLETE INVENTORY SYSTEM
// ==============================
let inventory = [];

// Converts any stored image value to a clean web URL
function inventoryImageUrl(raw) {
    if (!raw) return null;
    if (raw.startsWith('data:')) return raw;                    // base64 blob stored in DB
    const filename = raw.split('/').filter(Boolean).pop();      // strip ALL path prefixes e.g. "uploads/inventory/"
    return '/FloraFit/uploads/inventory/' + filename;
}
let editingIndex = null;
let editingItemId = null;
let searchQuery = '';
let categoryFilter = '';
let statusFilter = '';

// 🚀 LOADS FROM get_inventory.php
async function loadInventoryFromDB() {
    try {
        const response = await fetch('../api/get_inventory.php');
        const data = await response.json();
        if (Array.isArray(data)) {
            inventory = data.map(item => ({
                id: item.id, sku: item.sku || '', name: item.name || 'Unnamed',
                variety: item.variety || '', category: item.category || 'Flowers',
                stock: Number(item.stock) || 0, price: Number(item.price) || 0,
                image: item.image || null   // raw value; use inventoryImageUrl() when rendering
            }));
            renderInventoryTable();
            updateDashboardMetrics?.();
        }
    } catch (error) {
        console.error('Inventory failed:', error);
    }
}

function getStockStatus(stock) {
    const value = Number(stock) || 0;
    return value <= 0 ? 'out' : value <= 10 ? 'critical' : value <= 50 ? 'low' : 'ok';
}

function renderInventoryTable() {
    const tbody = document.getElementById('inventory-body');
    if (!tbody) return;
    
    // Status counts
    const counts = { all: 0, ok: 0, low: 0, critical: 0, out: 0 };
    inventory.forEach(item => counts[getStockStatus(item.stock)]++);
    ['all','ok','low','critical','out'].forEach(s => {
        const el = document.getElementById(`count-${s}`);
        el && (el.textContent = counts[s]);
    });
    
    // Filter & render
    const filtered = inventory.filter(item => {
        return (!searchQuery || item.name.toLowerCase().includes(searchQuery) || item.variety.toLowerCase().includes(searchQuery)) &&
               (!categoryFilter || item.category === categoryFilter) &&
               (!statusFilter || getStockStatus(item.stock) === statusFilter);
    });
    
    tbody.innerHTML = filtered.length ? 
        filtered.map((item, idx) => {
            const stock = Number(item.stock) || 0;
            const badge = stock <= 0 ? '✖ Out' : 
                         stock <= 10 ? `⚠️ ${stock}` : 
                         stock <= 50 ? `⚡ ${stock}` : `✅ ${stock}`;
            
            return `
                <tr>
                    <td style="font-family:monospace;">${item.sku || '-'}</td>
                    <td><b>${item.name}</b><br><small>${item.variety || '—'}</small></td>
                    <td><span style="background:rgba(46,125,50,.1);padding:4px 8px;border-radius:12px;font-size:.8rem;">${item.category}</span></td>
                    <td><span class="stock-badge ${getStockStatus(stock)}">${badge}</span></td>
                    <td style="font-family:monospace;color:var(--primary);">₱${item.price.toFixed(2)}</td>
                    <td>${item.image ? `<img src="${inventoryImageUrl(item.image)}" class="thumb-small">` : '—'}</td>
                    <td>
                        <button class="btn-small" data-idx="${idx}" data-action="edit" style="margin-right:4px;"><i class="fas fa-edit"></i></button>
                        <button class="btn-small danger" data-idx="${idx}" data-action="delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
        }).join('') : 
        '<tr><td colspan="7" style="text-align:center;padding:3rem;color:#9ca3af;"><i class="fas fa-box-open" style="font-size:4rem;color:#e5e7eb;"></i><br>No inventory</td></tr>';
}

// 🖱️ EVENTS (one-time setup)
document.addEventListener('DOMContentLoaded', () => {
    // Search/Filter
    document.getElementById('search-inventory')?.addEventListener('input', e => {
        searchQuery = e.target.value.toLowerCase().trim();
        renderInventoryTable();
    });
    document.getElementById('filter-category')?.addEventListener('change', e => {
        categoryFilter = e.target.value;
        renderInventoryTable();
    });
    document.querySelectorAll('.status-filter').forEach(btn => btn.addEventListener('click', e => {
        document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');
        statusFilter = e.currentTarget.dataset.status || '';
        renderInventoryTable();
    }));
    
    // Form reset
    document.getElementById('f-reset')?.addEventListener('click', () => {
        document.getElementById('flower-form').reset();
        document.getElementById('f-qty').value = 0;
        document.getElementById('f-price').value = '0.00';
        editingIndex = editingItemId = null;
    });
    
    // Table actions
    document.getElementById('inventory-body')?.addEventListener('click', async e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const idx = Number(btn.dataset.idx);
        const item = inventory[idx];
        
        if (btn.dataset.action === 'edit') {
            ['f-name','f-variety','f-category','f-qty','f-price'].forEach(id => {
                const el = document.getElementById(id);
                el.value = id === 'f-qty' ? item.stock : id === 'f-price' ? item.price : item[id.replace('f-','')];
            });
            editingIndex = idx; editingItemId = item.id;
            document.getElementById('flower-form').scrollIntoView({behavior:'smooth'});
        }
        
        if (btn.dataset.action === 'delete' && await confirmActionPopup(`Delete "${item.name}"?`)) {
            const formData = new FormData();
            formData.append('id', item.id);
            try {
                const res = await fetch('../api/delete_inventory.php', {method:'POST', body: formData});
                if ((await res.json()).status === 'success') {
                    loadInventoryFromDB();
                    showNotification('Deleted!', 'success');
                }
            } catch(e) { showNotification('Delete failed', 'error'); }
        }
    });
    
    // Form submit
    document.getElementById('flower-form')?.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...'; btn.disabled = true;
        
        const formData = new FormData();
        const category = document.getElementById('f-category').value;
        if (editingItemId) {
            formData.append('id', editingItemId);
            formData.append('sku', inventory[editingIndex]?.sku || '');
            formData.append('existing_image', inventory[editingIndex]?.image || '');
        } else {
            formData.append('existing_image', '');
        }
        formData.append('name', document.getElementById('f-name').value.trim());
        formData.append('variety', document.getElementById('f-variety').value.trim());
        formData.append('category', document.getElementById('f-category').value);
        formData.append('stock', Number(document.getElementById('f-qty').value));
        formData.append('price', Number(document.getElementById('f-price').value));

        const imageFile = document.getElementById('f-file').files[0];
        if (imageFile) formData.append('image', imageFile);

        formData.append('has_3d_model', '1');
        
        try {
            const res = await fetch('../api/save_inventory.php', {method: 'POST', body: formData});
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('f-reset').click();
                await loadInventoryFromDB();
                showNotification(editingItemId ? 'Updated!' : 'Added!', 'success');
            } else showNotification(data.message || 'Failed', 'error');
        } catch(e) {
            showNotification('Save failed', 'error');
        } finally {
            btn.textContent = originalText; btn.disabled = false;
        }
    });
});

// INIT - Replace your existing initializeDashboard()
async function initializeDashboard() {
    await Promise.allSettled([
        loadInventoryFromDB(),
        typeof loadOrdersFromDB === 'function' && loadOrdersFromDB(),
        typeof loadFloristsFromDB === 'function' && loadFloristsFromDB()
    ]);
    updateDashboardMetrics?.(); updateAuthUI?.();
    console.log('✅ Dashboard ready!');
}

// ==============================
// ORDERS BODY EVENTS
// ==============================
document.getElementById('orders-body').addEventListener('click', async (e) => {
    const toggleBtn = e.target.closest('button[data-action="toggle-order-items"]');
    const approvePaymentBtn = e.target.closest('button[data-action="approve-payment"]');

    if (toggleBtn) {
        const orderId = Number(toggleBtn.dataset.id);
        if (!orderId) return;
        const detailRow = document.querySelector(`tr.order-items-row[data-order-id="${orderId}"]`);
        if (detailRow) detailRow.classList.toggle('hidden');
        return;
    }

    if (approvePaymentBtn) {
        const orderId = Number(approvePaymentBtn.dataset.id);
        if (!orderId) return;
        await approveOrderPayment(orderId);
    }
});

document.getElementById('orders-body').addEventListener('change', (e) => {
    const floristSelect = e.target.closest('.florist-select');
    if (floristSelect) {
        const orderId = Number(floristSelect.dataset.orderId);
        if (!orderId) return;

        if (floristSelect.value === '__add_new__') {
            const inputName = window.prompt('Enter new florist name:');
            const newFlorist = String(inputName || '').trim();

            if (!newFlorist) { renderOrdersTable(); return; }
            if (newFlorist.length > 100) { showNotification('Florist name is too long.', 'warning'); renderOrdersTable(); return; }

            if (!floristOptions.some(option => option.toLowerCase() === newFlorist.toLowerCase())) {
                saveFloristOptions([...floristOptions, newFlorist]);
            }

            floristSelect.value = newFlorist;
        }

        const florist = String(floristSelect.value || 'Unassigned').trim() || 'Unassigned';
        updateOrderFlorist(orderId, florist);
        return;
    }

    const checkbox = e.target.closest('.order-select');
    if (!checkbox) return;

    const orderId = Number(checkbox.dataset.id);
    if (!orderId) return;

    if (checkbox.checked) selectedOrderIds.add(orderId);
    else selectedOrderIds.delete(orderId);
    updateSelectedOrdersUI();
});

document.getElementById('select-all-orders')?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    document.querySelectorAll('#orders-body .order-select').forEach(chk => {
        chk.checked = checked;
        const orderId = Number(chk.dataset.id);
        if (!orderId) return;
        if (checked) selectedOrderIds.add(orderId);
        else selectedOrderIds.delete(orderId);
    });
    updateSelectedOrdersUI();
});

document.getElementById('bulk-accept-orders')?.addEventListener('click', () => bulkUpdateOrderStatus('Accepted'));
document.getElementById('bulk-delivering-orders')?.addEventListener('click', () => bulkUpdateOrderStatus('Delivering'));
document.getElementById('bulk-preparing-orders')?.addEventListener('click', () => bulkUpdateOrderStatus('Preparing'));
document.getElementById('bulk-delivered-orders')?.addEventListener('click', () => bulkUpdateOrderStatus('Delivered'));
document.getElementById('bulk-decline-orders')?.addEventListener('click', () => bulkUpdateOrderStatus('Declined'));
document.getElementById('bulk-delete-orders')?.addEventListener('click', () => bulkDeleteOrders());

// ==============================
// AUTH UI
// ==============================
function updateAuthUI() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const authButtons = document.getElementById('auth-buttons');
    const profileSection = document.getElementById('profile-section');
    const userName = document.getElementById('user-name');
    const cornerImg = document.getElementById('cornerProfilePic');
    
    if (isLoggedIn) {
        const user = JSON.parse(localStorage.getItem('user'));
        authButtons.style.display = 'none';
        profileSection.style.display = 'block';
        if (user && user.name) userName.textContent = user.name;
        if (user && user.profile_picture) {
            cornerImg.src = user.profile_picture;
            cornerImg.style.display = 'inline-block';
            const icon = profileSection.querySelector('.fa-user-circle');
            if (icon) icon.style.display = 'none';
        } else {
            cornerImg.style.display = 'none';
            const icon = profileSection.querySelector('.fa-user-circle');
            if (icon) icon.style.display = 'inline-block';
        }
    } else {
        authButtons.style.display = 'flex';
        profileSection.style.display = 'none';
    }
}

updateAuthUI();

const profileSection = document.getElementById('profile-section');
if (profileSection) {
    profileSection.addEventListener('click', () => window.location.href = 'profile.html');
    profileSection.style.cursor = 'pointer';
}

const logoutBtn = document.getElementById('logout-btn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('user');
        updateAuthUI();
        window.location.href = 'index.html';
    });
}

window.addEventListener('storage', (e) => {
    if (e.key === 'user' || e.key === 'isLoggedIn') {
        try { updateAuthUI(); } catch (err) { console.error(err); }
    }
});

async function initializeDashboard() {
    console.log('🚀 Initializing Admin Dashboard...');
    
    try {
        await Promise.all([
            loadInventoryFromDB(),
            loadOrdersFromDB(),
            loadFloristsFromDB(),
            loadAnalyticsFromDB(),
            loadAdminGcashQr()
        ]);
        
        updateDashboardMetrics();
        updateAuthUI();

        const hamburgerBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const adminSidebar = document.getElementById('admin-sidebar');
        const adminMenuToggle = document.getElementById('admin-menu-toggle');
        const adminSidebarBackdrop = document.getElementById('admin-sidebar-backdrop');

        function syncPageScrollLock() {
            const siteMenuOpen = mobileMenu?.classList.contains('open');
            const adminMenuOpen = adminSidebar?.classList.contains('open');
            document.body.style.overflow = (siteMenuOpen || adminMenuOpen) ? 'hidden' : '';
        }

        function openMobileMenu() {
            mobileMenu.classList.add('open');
            mobileMenuOverlay.classList.add('open');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
            mobileMenu.setAttribute('aria-hidden', 'false');
            adminSidebar?.classList.remove('open');
            adminSidebarBackdrop?.classList.remove('open');
            adminMenuToggle?.setAttribute('aria-expanded', 'false');
            syncPageScrollLock();
        }

        function closeMobileMenu() {
            mobileMenu.classList.remove('open');
            mobileMenuOverlay.classList.remove('open');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
            syncPageScrollLock();
        }

        function openAdminSidebar() {
            if (window.innerWidth > 768) return;
            adminSidebar?.classList.add('open');
            adminSidebarBackdrop?.classList.add('open');
            adminMenuToggle?.setAttribute('aria-expanded', 'true');
            closeMobileMenu();
            syncPageScrollLock();
        }

        function closeAdminSidebar() {
            adminSidebar?.classList.remove('open');
            adminSidebarBackdrop?.classList.remove('open');
            adminMenuToggle?.setAttribute('aria-expanded', 'false');
            syncPageScrollLock();
        }

        hamburgerBtn?.addEventListener('click', openMobileMenu);
        mobileMenuClose?.addEventListener('click', closeMobileMenu);
        mobileMenuOverlay?.addEventListener('click', closeMobileMenu);
        adminMenuToggle?.addEventListener('click', () => {
            if (adminSidebar?.classList.contains('open')) closeAdminSidebar();
            else openAdminSidebar();
        });
        adminSidebarBackdrop?.addEventListener('click', closeAdminSidebar);

        document.querySelectorAll('.mobile-nav a').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
        document.querySelectorAll('.sidebar .menu-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) closeAdminSidebar();
            });
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeAdminSidebar();
            }
        });
        renderInventoryTable();
        renderOrdersTable();
        await checkCriticalStock();
        
        console.log('✅ Dashboard fully loaded!');
        showNotification('Dashboard loaded successfully!', 'success');
        
    } catch (error) {
        console.error('❌ Dashboard init failed:', error);
        showNotification('Dashboard loaded (some features may need PHP endpoints)', 'warning');
    }
}

document.addEventListener('DOMContentLoaded', initializeDashboard);

// 🔄 AUTO-CHECK CRITICAL STOCK EVERY 5 MINUTES
setInterval(async () => {
    console.log('🔍 Auto-checking critical stock...');
    await loadInventoryFromDB();
    await checkCriticalStock();
}, 5 * 60 * 1000); // 300,000ms = 5 minutes

// ==============================
// FLORISTS MANAGEMENT
// ==============================
let floristsList = [];
let floristSearchQuery = '';

function loadFloristsFromDB() {
    fetch('../api/get_florists.php')
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data)) {
                floristsList = data;
                renderFloristsTable();
            } else {
                console.error('Invalid florists data');
                document.getElementById('florists-body').innerHTML = '<tr><td colspan="5" class="orders-empty">Failed to load florists.</td></tr>';
            }
        })
        .catch(err => {
            console.error('Failed to load florists:', err);
            document.getElementById('florists-body').innerHTML = '<tr><td colspan="5" class="orders-empty">Failed to load florists.</td></tr>';
        });
}

function renderFloristsTable() {
    const tbody = document.getElementById('florists-body');
    if (!tbody) return;

    const filteredFlorists = floristsList.filter(f =>
        f.name.toLowerCase().includes(floristSearchQuery.toLowerCase())
    );

    if (filteredFlorists.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="orders-empty">No florists found.</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    filteredFlorists.forEach(florist => {
        const assignedCount = orders.filter(o => o.assigned_florist === florist.name).length;
        const status = florist.active ? 'Active' : 'Inactive';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${florist.id}</td>
            <td><strong>${florist.name}</strong></td>
            <td>${assignedCount}</td>
            <td><span class="order-status-badge ${status.toLowerCase()}">${status}</span></td>
            <td>
                <button class="btn-small outline" data-action="edit-florist" data-id="${florist.id}">Edit</button>
                <button class="btn-small danger" data-action="delete-florist" data-id="${florist.id}" data-name="${florist.name}">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function showCreateFloristModal(editFlorist = null) {
    const existingModal = document.getElementById('florist-modal');
    if (existingModal) existingModal.remove();
    
    const modalHtml = `
        <div id="florist-modal" class="modal" style="display:flex !important;">
            <div class="modal-content">
                <button type="button" class="modal-close" id="close-florist-modal">&times;</button>
                <h3>${editFlorist ? 'Edit Florist' : 'Create New Florist'}</h3>
                <form id="florist-form">
                    <div style="margin-bottom:16px;">
                        <label for="florist-name">Florist Name <span style="color:#e65100;">*</span></label>
                        <input type="text" id="florist-name" value="${editFlorist ? editFlorist.name : ''}" required
                               style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                    </div>
                    <div style="margin-bottom:24px;">
                        <label for="florist-email">Email Address <span style="color:#e65100;">*</span></label>
                        <input type="email" id="florist-email" value="${editFlorist ? editFlorist.email : ''}" required
                               style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                    </div>
                    ${editFlorist ? `
                    <div style="margin-bottom:24px;">
                        <label for="florist-status">Status</label>
                        <select id="florist-status" style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(0,0,0,0.1);">
                            <option value="1" ${editFlorist.active ? 'selected' : ''}>Active</option>
                            <option value="0" ${!editFlorist.active ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                    ` : ''}
                    <div style="display:flex; gap:12px; justify-content:flex-end;">
                        <button type="button" id="cancel-florist" class="btn-large outline" style="padding:12px 24px;">Cancel</button>
                        <button type="submit" class="btn-large" style="padding:12px 24px; background:#2e7d32; color:white;">${editFlorist ? 'Update Florist' : 'Create Florist'}</button>
                    </div>
                </form>
                ${!editFlorist ? `
                <div style="margin-top:16px; padding:12px; background:#f0f8ff; border-radius:8px; border-left:4px solid #2196f3; font-size:14px;">
                    <strong>ℹ️ Note:</strong> A temporary password will be sent to the florist's email.
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    document.getElementById('close-florist-modal').onclick = closeFloristModal;
    document.getElementById('cancel-florist').onclick = closeFloristModal;
    
    document.getElementById('florist-form').onsubmit = async (e) => {
        e.preventDefault();
        
        const nameInput = document.getElementById('florist-name');
        const emailInput = document.getElementById('florist-email');
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        
        if (!name || name.length < 2) {
            showNotification('Florist name must be at least 2 characters.', 'warning');
            nameInput.focus();
            nameInput.style.borderColor = '#e65100';
            setTimeout(() => nameInput.style.borderColor = 'rgba(0,0,0,0.1)', 2000);
            return;
        }
        
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showNotification('Please enter a valid email address.', 'warning');
            emailInput.focus();
            emailInput.style.borderColor = '#e65100';
            setTimeout(() => emailInput.style.borderColor = 'rgba(0,0,0,0.1)', 2000);
            return;
        }
        
        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        
        if (editFlorist) {
            formData.append('id', editFlorist.id);
            formData.append('status', document.getElementById('florist-status')?.value || '1');
        }
        
        try {
            const url = editFlorist ? 'update_florist.php' : 'create_florist.php';
            const res = await fetch(url, { method: 'POST', body: formData });
            const data = await res.json();
            
            closeFloristModal();
            
            if (data.success) {
                showNotification(data.message || (editFlorist ? 'Florist updated successfully!' : 'Florist created successfully!'), 'success');
                loadFloristsFromDB();
                floristOptions = getFloristOptions();
                renderOrdersTable();
            } else {
                showNotification(data.message || 'Failed to save florist.', 'error');
            }
        } catch (err) {
            console.error(err);
            showNotification('Network error. Please try again.', 'error');
            closeFloristModal();
        }
    };
}

function closeFloristModal() {
    const modal = document.getElementById('florist-modal');
    if (modal) modal.remove();
}

document.getElementById('create-florist-btn')?.addEventListener('click', () => showCreateFloristModal());

document.getElementById('search-florists')?.addEventListener('input', (e) => {
    floristSearchQuery = e.target.value;
    renderFloristsTable();
});

document.getElementById('florists-body')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const id = btn.dataset.id;
    const florist = floristsList.find(f => f.id == id);

    if (btn.dataset.action === 'edit-florist') {
        showCreateFloristModal(florist);
    }

    if (btn.dataset.action === 'delete-florist') {
        if (await confirmActionPopup(`Delete florist "${florist.name}"? This cannot be undone.`, 'Confirm Delete')) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('../api/delete_florist.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadFloristsFromDB();
                        showNotification('Florist deleted successfully.', 'success');
                    } else {
                        showNotification(data.message || 'Failed to delete florist.', 'error');
                    }
                })
                .catch(() => showNotification('Failed to delete florist.', 'error'));
        }
    }
});


const floristsPanel = document.getElementById('panel-florists');
if (floristsPanel) {
    const observer = new MutationObserver(() => {
        if (!floristsPanel.classList.contains('hidden')) {
            loadFloristsFromDB();
        }
    });
    observer.observe(floristsPanel, { attributes: true, attributeFilter: ['class'] });
}
</script>

<!-- VOUCHER MODAL -->
<div id="voucher-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); backdrop-filter:blur(3px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.2); animation:fadeIn .25s ease;">
        <h3 style="margin:0 0 6px; font-size:1.2rem; font-weight:700; color:#1e293b;">Issue Voucher</h3>
        <p style="font-size:.85rem; color:#64748b; margin-bottom:24px;">
            Giving voucher to: <strong id="modal-voucher-username" style="color:#2e7d32;">—</strong>
        </p>

        <div style="margin-bottom:18px;">
            <label style="display:block; font-size:.82rem; font-weight:600; color:#475569; margin-bottom:6px;">Voucher Type</label>
            <select id="voucher-type" onchange="updateVoucherHint()"
                    style="width:100%; padding:10px 14px; border:1px solid rgba(0,0,0,0.1); border-radius:9px; font-size:.9rem; box-sizing:border-box;">
                <option value="percentage">Percentage Discount (%)</option>
                <option value="fixed">Fixed Amount (₱)</option>
            </select>
        </div>

        <div style="margin-bottom:18px;">
            <label id="voucher-value-label" style="display:block; font-size:.82rem; font-weight:600; color:#475569; margin-bottom:6px;">Discount Value (%)</label>
            <input type="number" id="voucher-value" placeholder="e.g. 10" min="1" step="0.01"
                   style="width:100%; padding:10px 14px; border:1px solid rgba(0,0,0,0.1); border-radius:9px; font-size:.9rem; box-sizing:border-box;">
            <div id="voucher-value-hint" style="font-size:.78rem; color:#94a3b8; margin-top:5px;">Enter a number between 1 and 100.</div>
        </div>

        <p style="font-size:.82rem; color:#94a3b8; margin:0 0 24px;">
            ⏱ Expires <strong>30 days</strong> from today. Code is auto-generated.
        </p>

        <div style="display:flex; gap:10px;">
            <button onclick="closeVoucherModal()"
                    style="flex:1; padding:11px; border:1px solid rgba(0,0,0,0.1); border-radius:9px; background:#fff; color:#64748b; font-weight:600; cursor:pointer;">
                Cancel
            </button>
            <button id="voucher-submit-btn" onclick="submitVoucher()"
                    style="flex:2; padding:11px; background:var(--primary); color:#fff; border:none; border-radius:9px; font-weight:700; cursor:pointer;">
                Issue Voucher
            </button>
        </div>
    </div>
</div>

<script>
// ==============================
// VOUCHERS
// ==============================
let voucherUsers = [];
let activeVoucherUserId = null;

async function loadVoucherUsers() {
    const list = document.getElementById('voucher-user-list');
    list.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;">Loading users…</div>';

    try {
        const res  = await fetch('../api/get_users.php');
        const data = await res.json();

        if (!data.success || !data.users || !data.users.length) {
            list.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;">No users found.</div>';
            return;
        }

        voucherUsers = data.users;
        renderVoucherUsers(voucherUsers);
    } catch (err) {
        list.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;">Failed to load users.</div>';
    }
}

function renderVoucherUsers(users) {
    const list = document.getElementById('voucher-user-list');

    if (!users.length) {
        list.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px;color:#94a3b8;">No matching users.</div>';
        return;
    }

    list.innerHTML = users.map(u => {
        const name = u.full_name || '?';
        const initials = name.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
        return `
            <div style="background:#fff;border:1px solid rgba(0,0,0,0.08);border-radius:12px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:box-shadow .2s;"
                 onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'"
                 onmouseout="this.style.boxShadow='none'">
                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#4caf50);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:1rem;flex-shrink:0;">
                    ${initials}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escVoucher(name)}</div>
                    <div style="font-size:.8rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escVoucher(u.email || '')}</div>
                </div>
                <button onclick="openVoucherModal(${u.id}, '${escVoucher(name)}')"
                        style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:.8rem;font-weight:600;cursor:pointer;white-space:nowrap;"
                        onmouseover="this.style.background='var(--primary-dark)'"
                        onmouseout="this.style.background='var(--primary)'">
                    Give
                </button>
            </div>`;
    }).join('');
}

function filterVoucherUsers() {
    const q = document.getElementById('voucher-user-search').value.toLowerCase();
    renderVoucherUsers(voucherUsers.filter(u =>
        (u.name || u.username || '').toLowerCase().includes(q) ||
        (u.email || '').toLowerCase().includes(q)
    ));
}

function openVoucherModal(userId, userName) {
    activeVoucherUserId = userId;
    document.getElementById('modal-voucher-username').textContent = userName;
    document.getElementById('voucher-type').value  = 'percentage';
    document.getElementById('voucher-value').value = '';
    updateVoucherHint();
    document.getElementById('voucher-modal').style.display = 'flex';
}

function closeVoucherModal() {
    document.getElementById('voucher-modal').style.display = 'none';
    activeVoucherUserId = null;
}

function updateVoucherHint() {
    const type  = document.getElementById('voucher-type').value;
    const label = document.getElementById('voucher-value-label');
    const hint  = document.getElementById('voucher-value-hint');
    const input = document.getElementById('voucher-value');
    if (type === 'percentage') {
        label.textContent = 'Discount Value (%)';
        input.placeholder = 'e.g. 10';
        input.max         = '100';
        hint.textContent  = 'Enter a number between 1 and 100.';
    } else {
        label.textContent = 'Fixed Amount (₱)';
        input.placeholder = 'e.g. 50';
        input.removeAttribute('max');
        hint.textContent  = 'Enter the peso amount to discount.';
    }
}

async function submitVoucher() {
    const type  = document.getElementById('voucher-type').value;
    const value = parseFloat(document.getElementById('voucher-value').value);
    const btn   = document.getElementById('voucher-submit-btn');

    if (!value || value <= 0)                        { showNotification('Please enter a valid value.', 'warning'); return; }
    if (type === 'percentage' && value > 100)        { showNotification('Percentage cannot exceed 100.', 'warning'); return; }

    btn.disabled = true;
    btn.textContent = 'Issuing…';

    try {
        const res  = await fetch('../api/assign_voucher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: activeVoucherUserId, type, value })
        });
        const data = await res.json();

        if (data.success) {
            closeVoucherModal();
            const label = type === 'percentage' ? `${value}% off` : `₱${value} off`;
            showNotification(`Voucher ${data.voucher.code} (${label}) issued!`, 'success');
        } else {
            showNotification(data.message || 'Failed to issue voucher.', 'error');
        }
    } catch (err) {
        showNotification('Network error. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Issue Voucher';
    }
}

function escVoucher(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// Auto-load users when panel becomes visible
const voucherPanelObserver = new MutationObserver(() => {
    const panel = document.getElementById('panel-vouchers');
    if (panel && !panel.classList.contains('hidden') && voucherUsers.length === 0) {
        loadVoucherUsers();
    }
});
const vPanel = document.getElementById('panel-vouchers');
if (vPanel) voucherPanelObserver.observe(vPanel, { attributes: true, attributeFilter: ['class'] });

// Close modal when clicking the dark backdrop
document.getElementById('voucher-modal').addEventListener('click', function(e) {
    if (e.target === this) closeVoucherModal();
});

</script>

<script src="../assets/js/notifications.js"></script>

</body>
</html>