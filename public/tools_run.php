<?php
/**
 * TOOLS SEED – يحقن بيانات واقعية في النظام (عملاء، موردين، أصناف، فواتير مشتريات ومبيعات كـ Draft)
 * المسار:
 *   https://www.shneler.com/20/accounting/acc1/public/tools_run.php
 * بعد الانتهاء يُفضّل حذف هذا الملف.
 */

header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

echo "<h2>TOOLS SEED</h2>";

try {
    define('BASE_PATH', dirname(__DIR__));   // .../acc1
    define('APP_PATH', BASE_PATH . '/app');

    echo "<p>BASE_PATH = ".h(BASE_PATH)."</p>";
    echo "<p>APP_PATH  = ".h(APP_PATH)."</p>";

    require APP_PATH . '/Config/Database.php';

    $dbClass = 'App\\Config\\Database';
    if (!class_exists($dbClass)) {
        throw new Exception("Class $dbClass not found");
    }

    /** @var App\Config\Database $dbObj */
    $dbObj = new $dbClass();
    $db = $dbObj->getConnection();
    echo "<p>DB connection OK</p>";

    // Helpers
    $q = function(string $sql, array $params = []) use ($db) {
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st;
    };
    $all = function(string $sql, array $params = []) use ($q) {
        return $q($sql,$params)->fetchAll(PDO::FETCH_ASSOC);
    };

    $tag = 'TST-'.date('Ymd-His');
    echo "<p>Batch tag: <b>".h($tag)."</b></p>";