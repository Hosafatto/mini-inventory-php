<?php
declare(strict_types=1);
if (PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80600) {
    http_response_code(500);
    exit('This demo requires a supported PHP 8.4 or 8.5 release.');
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require __DIR__ . '/../src/db.php';
$pdo = db();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Invalid form token.');
    }
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    try {
        if ($action === 'delete') {
            if (!$id) throw new InvalidArgumentException('Invalid product ID.');
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
        } elseif ($action === 'save') {
            $sku = trim((string)($_POST['sku'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $priceInput = trim((string)($_POST['price'] ?? ''));
            $quantityInput = trim((string)($_POST['quantity'] ?? ''));
            if ($sku === '' || mb_strlen($sku) > 32 || $name === '' || mb_strlen($name) > 120) {
                throw new InvalidArgumentException('SKU and name are required (max 32 and 120 characters).');
            }
            if (!is_numeric($priceInput) || !is_finite((float)$priceInput) || (float)$priceInput < 0 || (float)$priceInput > 99999999) {
                throw new InvalidArgumentException('Enter a valid nonnegative price.');
            }
            $quantity = filter_var($quantityInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000000]]);
            if ($quantity === false) throw new InvalidArgumentException('Enter a valid nonnegative whole-number quantity.');
            if ($id) {
                $stmt = $pdo->prepare('UPDATE products SET sku = ?, name = ?, price = ?, quantity = ? WHERE id = ?');
                $stmt->execute([$sku, $name, (float)$priceInput, $quantity, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO products (sku, name, price, quantity) VALUES (?, ?, ?, ?)');
                $stmt->execute([$sku, $name, (float)$priceInput, $quantity]);
            }
        } else {
            throw new InvalidArgumentException('Unknown action.');
        }
        header('Location: /?saved=1', true, 303);
        exit;
    } catch (PDOException $e) {
        $error = $e->getCode() === '23000' ? 'That SKU already exists.' : 'Unable to save product.';
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}
$edit = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :q OR sku LIKE :q ORDER BY id DESC');
    $stmt->execute(['q' => '%' . $q . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
}
$products = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mini Inventory Manager</title><link rel="stylesheet" href="/assets/style.css"></head><body>
<main><header><h1>Mini Inventory Manager</h1><p>A lightweight PHP 8 + SQLite CRUD demo.</p></header>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="success" role="status">Changes saved.</div><?php endif; ?>
<section class="panel"><h2><?= $edit ? 'Edit product' : 'Add product' ?></h2>
<form method="post" class="product-form"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<label>SKU<input name="sku" maxlength="32" required value="<?= h((string)($edit['sku'] ?? '')) ?>"></label>
<label>Name<input name="name" maxlength="120" required value="<?= h((string)($edit['name'] ?? '')) ?>"></label>
<label>Price<input name="price" type="number" step="0.01" min="0" max="99999999" required value="<?= h((string)($edit['price'] ?? '')) ?>"></label>
<label>Quantity<input name="quantity" type="number" step="1" min="0" max="100000000" required value="<?= h((string)($edit['quantity'] ?? '')) ?>"></label>
<div class="actions"><button type="submit"><?= $edit ? 'Update' : 'Add product' ?></button><?php if ($edit): ?><a href="/">Cancel</a><?php endif; ?></div></form></section>
<section class="panel"><div class="heading"><h2>Products (<?= count($products) ?>)</h2><form method="get" class="search"><input aria-label="Search products" name="q" placeholder="Search name or SKU" value="<?= h($q) ?>"><button type="submit">Search</button><a href="/">Clear</a></form></div>
<div class="table-wrap"><table><thead><tr><th>SKU</th><th>Name</th><th>Price</th><th>Qty</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($products as $p): ?><tr><td><?= h($p['sku']) ?></td><td><?= h($p['name']) ?></td><td>$<?= number_format((float)$p['price'], 2) ?></td><td><?= (int)$p['quantity'] ?></td><td class="row-actions"><a href="/?edit=<?= (int)$p['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$products): ?><tr><td colspan="5" class="empty">No products found.</td></tr><?php endif; ?>
</tbody></table></div></section><footer>Built with plain PHP, PDO, SQLite, and a little CSS.</footer></main></body></html>
