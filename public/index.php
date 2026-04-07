<?php
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

require APP_PATH . '/Core/Router.php';
require APP_PATH . '/Config/Database.php';

use App\Core\Router;

$routes = [
    'GET' => [
        '/'                               => ['App\Controllers\HomeController', 'index'],
        '/dashboard'                      => ['App\Controllers\DashboardController', 'index'],

        // Web pages
        '/entry/new'                      => ['App\Controllers\WebJournalEntriesController', 'newEntryForm'],
        '/journal-entries'                => ['App\Controllers\WebJournalEntriesListController', 'index'],
        '/journal-entries/show'           => ['App\Controllers\WebJournalEntriesListController', 'show'],

        '/accounts'                       => ['App\Controllers\WebAccountsController', 'index'],

        '/reports/trial-balance'          => ['App\Controllers\WebReportsController', 'trialBalancePage'],
        '/reports/income-statement'       => ['App\Controllers\WebReportsController', 'incomeStatementPage'],
        '/reports/balance-sheet'          => ['App\Controllers\WebReportsController', 'balanceSheetPage'],
        '/reports/ledger'                 => ['App\Controllers\WebLedgerController', 'index'],

        // APIs
        '/api/accounts'                   => ['App\Controllers\AccountsController', 'index'],
        '/api/accounts/tree'              => ['App\Controllers\ChartOfAccountsController', 'tree'],
        '/api/journal-entries'            => ['App\Controllers\JournalEntriesController', 'index'],

        '/api/reports/trial-balance'      => ['App\Controllers\ReportsController', 'trialBalance'],
        '/api/reports/dashboard-metrics'  => ['App\Controllers\ReportsController', 'dashboardMetrics'],
        '/api/reports/ledger'             => ['App\Controllers\ReportsController', 'ledger'],
        '/api/reports/income-statement'   => ['App\Controllers\ReportsController', 'incomeStatement'],
        '/api/reports/balance-sheet'      => ['App\Controllers\ReportsController', 'balanceSheet'],
    ],
    'POST' => [
        '/api/journal-entries'            => ['App\Controllers\JournalEntriesController', 'store'],
        '/api/accounts'                   => ['App\Controllers\AccountsController', 'store'],
    ],
];

$router = new Router($routes);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// basePath لأن المشروع داخل /public
$basePath = '/20/accounting/acc1/public';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

if ($requestUri === '' || $requestUri === false) {
    $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($method, $requestUri);