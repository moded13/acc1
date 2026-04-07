<?php
// قفل بسيط جداً (غير مناسب كحل نهائي، فقط للتطوير)
$token = $_GET['token'] ?? '';
if ($token !== 'dev123') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden";
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require APP_PATH . '/Config/Database.php';

use App\Models\JournalEntry;

header('Content-Type: text/plain; charset=utf-8');

try {
    $entryDate = date('Y-m-d');
    $description = 'قيد تجريبي تلقائي';

    $lines = [
        ['account_id' => 1, 'debit' => 100.00, 'credit' => 0.00, 'description' => 'مدين تجريبي'],
        ['account_id' => 4, 'debit' => 0.00, 'credit' => 100.00, 'description' => 'دائن تجريبي'],
    ];

    $model = new JournalEntry();
    $entryId = $model->createWithLines($entryDate, $description, $lines);

    echo "OK: created entry_id = {$entryId}\n";
} catch (Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage();
}