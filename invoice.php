<?php
session_start();
require_once 'includes/db.php';

// Require order id
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Missing order id');
}

$order_id = (int) $_GET['id'];

// Fetch order
$stmt = $pdo->prepare('SELECT o.*, u.email AS user_email, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    die('Order not found');
}

// Permission: admin or the user who owns the order
if (!isset($_SESSION['admin_logged_in'])) {
    if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== (int)$order['user_id']) {
        http_response_code(403);
        die('Forbidden');
    }
}

// Fetch items and address
$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll();

$addr = null;
if ($order['address_id']) {
    $aStmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ? LIMIT 1');
    $aStmt->execute([$order['address_id']]);
    $addr = $aStmt->fetch();
}

// Inline logo as base64 so the downloaded invoice shows the image even offline
$logoPath = __DIR__ . '/images/logo.png';
$logoSrc = 'images/logo.png';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $mime = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
    if (!$mime) $mime = 'image/png';
    $logoSrc = 'data:' . $mime . ';base64,' . $logoData;
}

// Build invoice HTML
$invoiceHtml = '<!doctype html><html><head><meta charset="utf-8"><title>Invoice #' . htmlspecialchars($order['id']) . '</title>';
$invoiceHtml .= '<style>body{font-family:Arial,Helvetica,sans-serif;color:#222;padding:20px} .inv-header{display:flex;justify-content:space-between;align-items:center} .inv-box{max-width:900px;margin:0 auto;border:1px solid #eee;padding:20px;border-radius:6px} h1{margin:0;color:#1a6fb3} table{width:100%;border-collapse:collapse;margin-top:12px} th,td{padding:8px;border:1px solid #eee;text-align:left} .right{text-align:right} .muted{color:#666;font-size:13px}</style>';
$invoiceHtml .= '</head><body><div class="inv-box"><div class="inv-header"><div><img src="' . $logoSrc . '" alt="XSports" style="height:80px;display:block;margin-bottom:6px;max-width:240px;width:auto"><div class="muted">Invoice</div></div><div class="muted">Order #' . htmlspecialchars($order['id']) . '<br>' . date('Y-m-d H:i', strtotime($order['created_at'])) . '</div></div>';

$invoiceHtml .= '<hr style="margin:14px 0">';

$invoiceHtml .= '<div style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap">';
$invoiceHtml .= '<div><strong>Billed To</strong><br>' . htmlspecialchars($order['user_name'] ?? $order['user_email'] ?? 'Customer') . '<br>';
if ($addr) {
    $invoiceHtml .= htmlspecialchars($addr['address_line']) . '<br>' . htmlspecialchars($addr['city']) . ', ' . htmlspecialchars($addr['state']) . ' - ' . htmlspecialchars($addr['pincode']) . '<br>';
}
$invoiceHtml .= '</div>';
$invoiceHtml .= '<div class="right"><strong>Payment</strong><br>' . htmlspecialchars($order['payment_method']) . '<br><span class="muted">' . htmlspecialchars(ucfirst($order['status'])) . '</span></div>';
$invoiceHtml .= '</div>';

$invoiceHtml .= '<table><thead><tr><th>Product</th><th>Brand</th><th>Size</th><th>Qty</th><th class="right">Unit</th><th class="right">Total</th></tr></thead><tbody>';
foreach ($items as $it) {
    $lineTotal = number_format($it['price'] * $it['quantity'], 2);
    $invoiceHtml .= '<tr>';
    $invoiceHtml .= '<td>' . htmlspecialchars($it['product_name']) . '</td>';
    $invoiceHtml .= '<td>' . htmlspecialchars($it['brand']) . '</td>';
    $invoiceHtml .= '<td>' . htmlspecialchars($it['size'] ?? '') . '</td>';
    $invoiceHtml .= '<td>' . (int)$it['quantity'] . '</td>';
    $invoiceHtml .= '<td class="right">₹' . number_format($it['price'],2) . '</td>';
    $invoiceHtml .= '<td class="right">₹' . $lineTotal . '</td>';
    $invoiceHtml .= '</tr>';
}
$invoiceHtml .= '</tbody></table>';

$invoiceHtml .= '<div style="display:flex;justify-content:flex-end;margin-top:12px;">';
$invoiceHtml .= '<table style="width:360px;border:0"><tbody>';
$invoiceHtml .= '<tr><td class="muted">Subtotal</td><td class="right">₹' . number_format($order['subtotal'],2) . '</td></tr>';
$invoiceHtml .= '<tr><td class="muted">Shipping</td><td class="right">₹' . number_format($order['shipping'],2) . '</td></tr>';
$invoiceHtml .= '<tr><td class="muted">Tax</td><td class="right">₹' . number_format($order['tax'],2) . '</td></tr>';
$invoiceHtml .= '<tr><th>Total</th><th class="right">₹' . number_format($order['total'],2) . '</th></tr>';
$invoiceHtml .= '</tbody></table></div>';

$invoiceHtml .= '<p class="muted" style="margin-top:20px">Thank you for shopping with XSports.</p>';
$invoiceHtml .= '</div></body></html>';

// Send as downloadable HTML file
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="invoice-' . $order_id . '.html"');
echo $invoiceHtml;
exit;

?>
