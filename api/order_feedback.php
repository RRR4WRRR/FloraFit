<?php
require_once '../includes/db.php';

function ensureOrderFeedbackSchema(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS order_feedback (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        rating TINYINT(1) NOT NULL,
        feedback_text TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_order_user (order_id, user_id),
        KEY idx_order_id (order_id),
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function getJsonPayload(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function fetchFeedbackOrder(PDO $conn, int $orderId, int $userId): ?array {
    $hasOrdersTable = $conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if (!$hasOrdersTable) {
        return null;
    }

    ensureOrderFeedbackSchema($conn);

    $stmt = $conn->prepare("SELECT
            o.id,
            CONCAT('ORD-', LPAD(o.id, 6, '0')) AS order_number,
            COALESCE(o.status, 'Pending') AS status,
            COALESCE(o.total, 0) AS total,
            o.created_at,
            CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS has_feedback,
            COALESCE(f.rating, 0) AS feedback_rating,
            COALESCE(f.feedback_text, '') AS feedback_text,
            f.created_at AS feedback_created_at,
            f.updated_at AS feedback_updated_at
        FROM orders o
        LEFT JOIN order_feedback f ON f.order_id = o.id AND f.user_id = ?
        WHERE o.id = ? AND o.user_id = ?
        LIMIT 1");
    $stmt->execute([$userId, $orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        return null;
    }

    $items = [];
    $hasMetaTable = $conn->query("SHOW TABLES LIKE 'order_item_meta'")->rowCount() > 0;
    if ($hasMetaTable) {
        $itemStmt = $conn->prepare("SELECT item_name, quantity FROM order_item_meta WHERE order_id = ? ORDER BY id ASC");
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $order['items'] = $items;
    return $order;
}

$action = $_GET['action'] ?? '';

if ($action !== '') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $payload = getJsonPayload();
        $orderId = isset($payload['order_id']) ? (int)$payload['order_id'] : (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
        $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

        if ($orderId <= 0 || $userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid order and user are required.']);
            exit;
        }

        $order = fetchFeedbackOrder($conn, $orderId, $userId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Delivered order not found for this account.']);
            exit;
        }

        if (($order['status'] ?? '') !== 'Delivered') {
            echo json_encode(['success' => false, 'message' => 'Feedback is available only after the bouquet is officially marked Delivered.']);
            exit;
        }

        if ($action === 'get') {
            echo json_encode(['success' => true, 'order' => $order]);
            exit;
        }

        if ($action === 'submit') {
            $rating = isset($payload['rating']) ? (int)$payload['rating'] : (int)($_POST['rating'] ?? 0);
            $feedbackText = trim((string)($payload['feedback_text'] ?? $_POST['feedback_text'] ?? ''));

            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Please choose a rating from 1 to 5 stars.']);
                exit;
            }

            if ($feedbackText === '' || mb_strlen($feedbackText) < 5) {
                echo json_encode(['success' => false, 'message' => 'Please share a short feedback message.']);
                exit;
            }

            if (mb_strlen($feedbackText) > 1000) {
                echo json_encode(['success' => false, 'message' => 'Feedback is too long. Please keep it under 1000 characters.']);
                exit;
            }

            ensureOrderFeedbackSchema($conn);

            $saveStmt = $conn->prepare("INSERT INTO order_feedback (order_id, user_id, rating, feedback_text)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    rating = VALUES(rating),
                    feedback_text = VALUES(feedback_text),
                    updated_at = CURRENT_TIMESTAMP");
            $saveStmt->execute([$orderId, $userId, $rating, $feedbackText]);

            $updatedOrder = fetchFeedbackOrder($conn, $orderId, $userId);
            echo json_encode([
                'success' => true,
                'message' => 'Thank you! Your feedback has been saved.',
                'order' => $updatedOrder
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Feedback | Flora Fit</title>
    <link rel="stylesheet" href="assets/css/website.css">
    <link rel="stylesheet" href="notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #fff8fb 0%, #f7fbff 100%);
            min-height: 100vh;
        }

        .feedback-page {
            max-width: 760px;
            margin: 130px auto 60px;
            padding: 0 18px;
        }

        .feedback-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(93, 64, 55, 0.12);
            padding: 28px;
            border: 1px solid #f3d8e1;
        }

        .feedback-card h2 {
            margin: 0 0 8px;
            color: #5d4037;
            text-align: center;
        }

        .feedback-intro {
            margin: 0 0 20px;
            text-align: center;
            color: #6d5a54;
        }

        .order-summary {
            background: #fff8fb;
            border: 1px solid #f3c6d3;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 18px;
        }

        .order-summary-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            color: #5d4037;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .order-summary small {
            color: #777;
        }

        .order-items {
            margin: 10px 0 0;
            padding-left: 18px;
            color: #555;
        }

        .feedback-form {
            display: grid;
            gap: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #5d4037;
            font-weight: 600;
        }

        .star-picker {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .star-btn {
            border: none;
            background: #fff3cd;
            color: #d4a017;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            font-size: 1.35rem;
            cursor: pointer;
            transition: transform 0.15s, background 0.15s;
        }

        .star-btn:hover,
        .star-btn.active {
            background: #ffd54f;
            transform: translateY(-2px);
        }

        textarea {
            width: 100%;
            min-height: 140px;
            border: 2px solid #f3c6d3;
            border-radius: 12px;
            padding: 12px 14px;
            resize: vertical;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
        }

        textarea:focus {
            outline: none;
            border-color: #ff8fa3;
        }

        .button-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #ff8fa3;
            color: #fff;
        }

        .btn-primary:hover {
            background: #ff6f8e;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e7f0ff;
            color: #1a73e8;
        }

        .btn-secondary:hover {
            background: #d6e8ff;
        }

        .feedback-status {
            border-radius: 12px;
            padding: 12px 14px;
            background: #eefbf0;
            color: #236d2e;
            border: 1px solid #bfe3c7;
            display: none;
        }

        .feedback-error {
            display: none;
            border-radius: 12px;
            padding: 12px 14px;
            background: #fff1f1;
            color: #b3261e;
            border: 1px solid #f2c0be;
            margin-bottom: 16px;
        }

        .rating-caption {
            color: #7a5d55;
            font-size: 0.92rem;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.html" class="logo">
                <span style="font-size: 2rem;">🌸</span>
                <h1>FLORAFIT</h1>
            </a>
        </div>
    </header>

    <main class="feedback-page">
        <div class="feedback-card">
            <h2>Bouquet Feedback</h2>
            <p class="feedback-intro">Share your experience once your bouquet has been officially delivered.</p>

            <div id="feedback-error" class="feedback-error"></div>
            <div id="feedback-status" class="feedback-status"></div>

            <div id="order-summary" class="order-summary" style="display:none;"></div>

            <form id="feedbackForm" class="feedback-form" style="display:none;">
                <div class="field">
                    <label>How would you rate your bouquet?</label>
                    <div class="star-picker" id="star-picker">
                        <button type="button" class="star-btn" data-value="1">★</button>
                        <button type="button" class="star-btn" data-value="2">★</button>
                        <button type="button" class="star-btn" data-value="3">★</button>
                        <button type="button" class="star-btn" data-value="4">★</button>
                        <button type="button" class="star-btn" data-value="5">★</button>
                    </div>
                    <div id="rating-caption" class="rating-caption">Choose 1 to 5 stars.</div>
                    <input type="hidden" id="rating" value="0">
                </div>

                <div class="field">
                    <label for="feedbackText">Tell us about the bouquet and delivery</label>
                    <textarea id="feedbackText" maxlength="1000" placeholder="Example: The bouquet looked fresh and beautiful, and the delivery arrived on time."></textarea>
                </div>

                <div class="button-row">
                    <button type="submit" id="saveFeedbackBtn" class="btn-primary">Save Feedback</button>
                    <a href="profile.html" class="btn-secondary">Back to Profile</a>
                </div>
            </form>
        </div>
    </main>

    <script src="notifications.js"></script>
    <script>
        const storedUser = JSON.parse(localStorage.getItem('user') || 'null');
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const orderId = Number(new URLSearchParams(window.location.search).get('order_id') || 0);

        const feedbackForm = document.getElementById('feedbackForm');
        const ratingInput = document.getElementById('rating');
        const feedbackText = document.getElementById('feedbackText');
        const orderSummary = document.getElementById('order-summary');
        const feedbackError = document.getElementById('feedback-error');
        const feedbackStatus = document.getElementById('feedback-status');
        const saveFeedbackBtn = document.getElementById('saveFeedbackBtn');
        const ratingCaption = document.getElementById('rating-caption');
        const starButtons = Array.from(document.querySelectorAll('.star-btn'));

        if (!isLoggedIn || !storedUser || !storedUser.id) {
            window.location.href = 'login.html';
        }

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

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

        function setRating(value) {
            const safeValue = Math.max(0, Math.min(5, Number(value || 0)));
            ratingInput.value = String(safeValue);
            starButtons.forEach(button => {
                button.classList.toggle('active', Number(button.dataset.value) <= safeValue);
            });
            ratingCaption.textContent = safeValue > 0 ? `${safeValue} star${safeValue > 1 ? 's' : ''} selected.` : 'Choose 1 to 5 stars.';
        }

        starButtons.forEach(button => {
            button.addEventListener('click', () => setRating(Number(button.dataset.value)));
        });

        function showPageError(message) {
            feedbackError.style.display = 'block';
            feedbackError.textContent = message;
            feedbackForm.style.display = 'none';
            orderSummary.style.display = 'none';
        }

        function renderOrderSummary(order) {
            const items = Array.isArray(order.items) ? order.items : [];
            orderSummary.style.display = 'block';
            orderSummary.innerHTML = `
                <div class="order-summary-top">
                    <span>${escapeHtml(order.order_number || 'Order')}</span>
                    <span>${formatCurrency(order.total)}</span>
                </div>
                <small>Status: ${escapeHtml(order.status || 'Delivered')} • ${formatDateTime(order.created_at)}</small>
                ${items.length ? `
                    <ul class="order-items">
                        ${items.map(item => `<li>${escapeHtml(item.item_name || item.name || 'Item')} x${Number(item.quantity || 1)}</li>`).join('')}
                    </ul>
                ` : ''}
            `;
        }

        async function loadFeedbackDetails() {
            if (!orderId) {
                showPageError('Missing order reference. Please go back to your profile and open the feedback button again.');
                return;
            }

            try {
                const response = await fetch('api/order_feedback.php?action=get', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, user_id: storedUser.id })
                });
                const data = await response.json();

                if (!data || !data.success || !data.order) {
                    showPageError((data && data.message) || 'Unable to load this delivered order.');
                    return;
                }

                renderOrderSummary(data.order);
                feedbackForm.style.display = 'grid';
                feedbackText.value = data.order.feedback_text || '';
                setRating(Number(data.order.feedback_rating || 0));

                if (Number(data.order.has_feedback || 0) === 1) {
                    feedbackStatus.style.display = 'block';
                    feedbackStatus.textContent = `You already submitted feedback on ${formatDateTime(data.order.feedback_updated_at || data.order.feedback_created_at)}. You can update it anytime.`;
                    saveFeedbackBtn.textContent = 'Update Feedback';
                } else {
                    feedbackStatus.style.display = 'none';
                    saveFeedbackBtn.textContent = 'Save Feedback';
                }
            } catch (error) {
                console.error('Failed to load feedback details:', error);
                showPageError('Unable to load the feedback form right now.');
            }
        }

        feedbackForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const rating = Number(ratingInput.value || 0);
            const message = feedbackText.value.trim();

            if (rating < 1 || rating > 5) {
                showNotification('Please select a star rating first.', 'warning');
                return;
            }

            if (message.length < 5) {
                showNotification('Please write a short feedback message.', 'warning');
                return;
            }

            try {
                saveFeedbackBtn.disabled = true;
                const response = await fetch('api/order_feedback.php?action=submit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        user_id: storedUser.id,
                        rating,
                        feedback_text: message
                    })
                });
                const data = await response.json();

                if (data && data.success) {
                    feedbackStatus.style.display = 'block';
                    feedbackStatus.textContent = data.message || 'Feedback saved successfully.';
                    saveFeedbackBtn.textContent = 'Update Feedback';
                    showNotification(data.message || 'Feedback saved successfully.', 'success');
                } else {
                    showNotification((data && data.message) || 'Failed to save feedback.', 'error');
                }
            } catch (error) {
                console.error('Failed to save feedback:', error);
                showNotification('Failed to save feedback.', 'error');
            } finally {
                saveFeedbackBtn.disabled = false;
            }
        });

        loadFeedbackDetails();
    </script>
</body>
</html>

