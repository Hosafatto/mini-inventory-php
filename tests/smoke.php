<?php
declare(strict_types=1);
require __DIR__ . '/../src/db.php';
if (!extension_loaded('pdo_sqlite') || !extension_loaded('mbstring')) {
    fwrite(STDERR, "Required extensions missing.\n"); exit(1);
}
$pdo = db();
$sku = 'TEST-' . bin2hex(random_bytes(5));
try {
    $pdo->prepare('INSERT INTO products (sku, name, price, quantity) VALUES (?, ?, ?, ?)')->execute([$sku, 'Smoke test', 1.25, 3]);
    $stmt = $pdo->prepare('SELECT * FROM products WHERE sku = ?');
    $stmt->execute([$sku]);
    $row = $stmt->fetch();
    if (!$row || $row['quantity'] != 3) throw new RuntimeException('Insert/read failed');
    $pdo->prepare('UPDATE products SET quantity = ? WHERE sku = ?')->execute([4, $sku]);
    $stmt->execute([$sku]);
    if ((int)$stmt->fetch()['quantity'] !== 4) throw new RuntimeException('Update failed');
    echo "PASS: create/read/update/delete SQL smoke test\n";
} finally {
    $pdo->prepare('DELETE FROM products WHERE sku = ?')->execute([$sku]);
}
