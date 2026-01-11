<?php
header('Content-Type: application/json');
require 'db.php';

$sql = "
SELECT *
FROM orders
WHERE status='processing'
ORDER BY order_date DESC
";

$res = $mysqli->query($sql);

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;

echo json_encode($out);
?>
