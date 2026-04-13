<?php
/**
 * DEV PURGE for a specific seed tag.
 * Deletes only data created by seed.php (tag-based).
 *
 * Usage:
 * /20/accounting/acc1/public/dev/purge_seed.php?tag=TST-YYYYMMDD-HHMMSS
 *
 * IMPORTANT: Delete this file after use.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/Config/Database.php';

use App\Config\Database;

header('Content-Type: text/html; charset=utf-8');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$tag = $_GET['tag'] ?? '';
$tag = trim($tag);

if ($tag === '' || strpos($tag, 'TST-') !== 0) {
    echo "<h2 style='color:#b91c1c'>Invalid tag</h2>";
    echo "<div>Provide ?tag=TST-YYYYMMDD-HHMMSS</div>";
    exit;
}

$db = (new Database())->getConnection();

function q($db, $sql, $params = []) {
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st;
}
function all($db, $sql, $params = []) {
    return q($db, $sql, $params)->fetchAll();
}

echo "<h2>Purging seed batch: ".h($tag)."</h2>";

try {
    $db->beginTransaction();

    // Find invoices/receipts/payments IDs by notes or codes
    $sales = all($db, "SELECT id FROM sales_invoices WHERE notes LIKE :p", [':p'=>"%$tag%"]);
    $purch = all($db, "SELECT id FROM purchase_invoices WHERE notes LIKE :p", [':p'=>"%$tag%"]);
    $rece  = all($db, "SELECT id FROM receipts WHERE notes LIKE :p", [':p'=>"%$tag%"]);
    $pay   = all($db, "SELECT id FROM payments WHERE notes LIKE :p", [':p'=>"%$tag%"]);

    $idsSales = array_map(fn($r)=>(int)$r['id'], $sales);
    $idsPurch = array_map(fn($r)=>(int)$r['id'], $purch);
    $idsRece  = array_map(fn($r)=>(int)$r['id'], $rece);
    $idsPay   = array_map(fn($r)=>(int)$r['id'], $pay);

    // Delete journal entries linked by source_type/source_id (if your schema uses it)
    // Note: if you didn't add source_type/source_id, these statements will fail; comment them if needed.
    foreach ($idsSales as $id) q($db, "DELETE FROM journal_entries WHERE source_type='sales_invoice' AND source_id=:id", [':id'=>$id]);
    foreach ($idsPurch as $id) q($db, "DELETE FROM journal_entries WHERE source_type='purchase_invoice' AND source_id=:id", [':id'=>$id]);
    foreach ($idsRece as $id)  q($db, "DELETE FROM journal_entries WHERE source_type='receipt' AND source_id=:id", [':id'=>$id]);
    foreach ($idsPay as $id)   q($db, "DELETE FROM journal_entries WHERE source_type='payment' AND source_id=:id", [':id'=>$id]);

    // Delete movements linked by source_type/source_id (if used)
    foreach ($idsSales as $id) q($db, "DELETE FROM inventory_movements WHERE source_type='sales_invoice' AND source_id=:id", [':id'=>$id]);
    foreach ($idsPurch as $id) q($db, "DELETE FROM inventory_movements WHERE source_type='purchase_invoice' AND source_id=:id", [':id'=>$id]);

    // Delete lines then headers
    if ($idsSales) {
        q($db, "DELETE FROM sales_invoice_lines WHERE sales_invoice_id IN (".implode(',', $idsSales).")");
        q($db, "DELETE FROM sales_invoices WHERE id IN (".implode(',', $idsSales).")");
    }
    if ($idsPurch) {
        q($db, "DELETE FROM purchase_invoice_lines WHERE purchase_invoice_id IN (".implode(',', $idsPurch).")");
        q($db, "DELETE FROM purchase_invoices WHERE id IN (".implode(',', $idsPurch).")");
    }

    if ($idsRece) q($db, "DELETE FROM receipts WHERE id IN (".implode(',', $idsRece).")");
    if ($idsPay)  q($db, "DELETE FROM payments WHERE id IN (".implode(',', $idsPay).")");

    // Delete items/customers/suppliers by code/sku prefix
    q($db, "DELETE FROM items WHERE sku LIKE :p", [':p'=>"$tag-ITM-%"]);
    q($db, "DELETE FROM customers WHERE code LIKE :p", [':p'=>"$tag-CUST-%"]);
    q($db, "DELETE FROM suppliers WHERE code LIKE :p", [':p'=>"$tag-SUP-%"]);

    $db->commit();

    echo "<h2 style='color:#166534'>Purge completed ✅</h2>";
    echo "<div><a href='/20/accounting/acc1/public/dashboard'>Back to dashboard</a></div>";

} catch (Throwable $e) {
    $db->rollBack();
    echo "<h2 style='color:#b91c1c'>Purge failed</h2>";
    echo "<pre>".h($e->getMessage())."\n".h($e->getTraceAsString())."</pre>";
}