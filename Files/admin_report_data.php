<?php
header('Content-Type: application/json');
require 'db.php'; // provides $mysqli

$response = [
    'kpis' => [],
    'salesTrends' => [
        'daily' => [],
        'weekly' => [],
        'monthly' => []
    ],
    'bestSellersByUnits' => [],
    'detailedProductSales' => []
];

/* =========================
   KPI DATA (DELIVERED ONLY)
========================= */
$sql = "
    SELECT 
        SUM(o.total_amount) AS revenue,
        COUNT(DISTINCT o.orders_id) AS orders,
        AVG(o.total_amount) AS aov
    FROM orders o
    JOIN shipments s ON s.order_id = o.orders_id
    WHERE s.status = 'delivered'
";
$result = $mysqli->query($sql);
$kpi = $result->fetch_assoc();

$response['kpis'] = [
    'revenue' => (float)($kpi['revenue'] ?? 0),
    'orders'  => (int)($kpi['orders'] ?? 0),
    'aov'     => (float)($kpi['aov'] ?? 0),
];

/* =========================
   DAILY SALES
========================= */
$sql = "
    SELECT 
        DATE(o.order_date) AS label,
        SUM(o.total_amount) AS total
    FROM orders o
    JOIN shipments s ON s.order_id = o.orders_id
    WHERE s.status = 'delivered'
    GROUP BY DATE(o.order_date)
    ORDER BY label ASC
    LIMIT 14
";
$result = $mysqli->query($sql);

$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['label'];
    $data[] = (float)$row['total'];
}

$response['salesTrends']['daily'] = [
    'labels' => $labels,
    'data' => $data
];

/* =========================
   WEEKLY SALES
========================= */
$sql = "
    SELECT 
        CONCAT(YEAR(o.order_date), '-W', WEEK(o.order_date)) AS label,
        SUM(o.total_amount) AS total
    FROM orders o
    JOIN shipments s ON s.order_id = o.orders_id
    WHERE s.status = 'delivered'
    GROUP BY YEAR(o.order_date), WEEK(o.order_date)
    ORDER BY label ASC
    LIMIT 12
";
$result = $mysqli->query($sql);

$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['label'];
    $data[] = (float)$row['total'];
}

$response['salesTrends']['weekly'] = [
    'labels' => $labels,
    'data' => $data
];

/* =========================
   MONTHLY SALES
========================= */
$sql = "
    SELECT 
        DATE_FORMAT(o.order_date,'%Y-%m') AS label,
        SUM(o.total_amount) AS total
    FROM orders o
    JOIN shipments s ON s.order_id = o.orders_id
    WHERE s.status = 'delivered'
    GROUP BY DATE_FORMAT(o.order_date,'%Y-%m')
    ORDER BY label ASC
    LIMIT 12
";
$result = $mysqli->query($sql);

$labels = [];
$data = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['label'];
    $data[] = (float)$row['total'];
}

$response['salesTrends']['monthly'] = [
    'labels' => $labels,
    'data' => $data
];

/* =========================
   BEST SELLING PRODUCTS
========================= */
$sql = "
    SELECT 
        p.name,
        SUM(oi.quantity) AS units
    FROM order_items oi
    JOIN orders o ON oi.orders_id = o.orders_id
    JOIN shipments s ON s.order_id = o.orders_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE s.status = 'delivered'
    GROUP BY oi.product_id
    ORDER BY units DESC
    LIMIT 10
";
$result = $mysqli->query($sql);

while ($row = $result->fetch_assoc()) {
    $response['bestSellersByUnits'][] = [
        'name' => $row['name'],
        'units' => (int)$row['units']
    ];
}

/* =========================
   PRODUCT SALES TABLE
========================= */
$sql = "
    SELECT 
        p.name,
        SUM(oi.quantity) AS units,
        SUM(oi.quantity * oi.price_at_time) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.orders_id = o.orders_id
    JOIN shipments s ON s.order_id = o.orders_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE s.status = 'delivered'
    GROUP BY oi.product_id
    ORDER BY revenue DESC
";
$result = $mysqli->query($sql);

while ($row = $result->fetch_assoc()) {
    $response['detailedProductSales'][] = [
        'name' => $row['name'],
        'units' => (int)$row['units'],
        'revenue' => (float)$row['revenue']
    ];
}

echo json_encode($response);
