<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = db();

echo json_encode([
    "users" => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    "orders" => (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='success'")->fetchColumn(),
    "revenue" => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='success'")->fetchColumn()
]);
