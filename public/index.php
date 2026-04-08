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

        // Web pages - core
        '/entry/new'                      => ['App\Controllers\WebJournalEntriesController', 'newEntryForm'],
        '/journal-entries'                => ['App\Controllers\WebJournalEntriesListController', 'index'],
        '/journal-entries/show'           => ['App\Controllers\WebJournalEntriesListController', 'show'],

        '/accounts'                       => ['App\Controllers\WebAccountsController', 'index'],

        '/reports/trial-balance'          => ['App\Controllers\WebReportsController', 'trialBalancePage'],
        '/reports/income-statement'       => ['App\Controllers\WebReportsController', 'incomeStatementPage'],
        '/reports/balance-sheet'          => ['App\Controllers\WebReportsController', 'balanceSheetPage'],
        '/reports/ledger'                 => ['App\Controllers\WebLedgerController', 'index'],

        // Web pages - commercial module
        '/customers'                      => ['App\Controllers\WebCustomersController', 'index'],
        '/suppliers'                      => ['App\Controllers\WebSuppliersController', 'index'],
        '/items'                          => ['App\Controllers\WebItemsController', 'index'],
        '/sales-invoices'                 => ['App\Controllers\WebSalesInvoicesController', 'index'],
        '/sales-invoices/new'             => ['App\Controllers\WebSalesInvoicesController', 'newForm'],
        '/sales-invoices/show'            => ['App\Controllers\WebSalesInvoicesController', 'show'],
        '/sales-invoices/print'           => ['App\Controllers\WebSalesInvoicesController', 'print'], // المسار الجديد
        '/purchase-invoices'              => ['App\Controllers\WebPurchaseInvoicesController', 'index'],
        '/purchase-invoices/new'          => ['App\Controllers\WebPurchaseInvoicesController', 'newForm'],
        '/purchase-invoices/show'         => ['App\Controllers\WebPurchaseInvoicesController', 'show'],
        '/receipts'                       => ['App\Controllers\WebReceiptsController', 'index'],
        '/payments'                       => ['App\Controllers\WebPaymentsController', 'index'],

        // APIs - core
        '/api/accounts'                   => ['App\Controllers\AccountsController', 'index'],
        '/api/accounts/tree'              => ['App\Controllers\ChartOfAccountsController', 'tree'],
        '/api/journal-entries'            => ['App\Controllers\JournalEntriesController', 'index'],

        '/api/reports/trial-balance'      => ['App\Controllers\ReportsController', 'trialBalance'],
        '/api/reports/dashboard-metrics'  => ['App\Controllers\ReportsController', 'dashboardMetrics'],
        '/api/reports/ledger'             => ['App\Controllers\ReportsController', 'ledger'],
        '/api/reports/income-statement'   => ['App\Controllers\ReportsController', 'incomeStatement'],
        '/api/reports/balance-sheet'      => ['App\Controllers\ReportsController', 'balanceSheet'],

        // APIs - commercial module
        '/api/customers'                  => ['App\Controllers\CustomersController', 'index'],
        '/api/suppliers'                  => ['App\Controllers\SuppliersController', 'index'],
        '/api/items'                      => ['App\Controllers\ItemsController', 'index'],
        '/api/sales-invoices'             => ['App\Controllers\SalesInvoicesController', 'index'],
        '/api/purchase-invoices'          => ['App\Controllers\PurchaseInvoicesController', 'index'],
        '/api/receipts'                   => ['App\Controllers\ReceiptsController', 'index'],
        '/api/payments'                   => ['App\Controllers\PaymentsController', 'index'],
    ],
    'POST' => [
        // Core
        '/api/journal-entries'            => ['App\Controllers\JournalEntriesController', 'store'],
        '/api/accounts'                   => ['App\Controllers\AccountsController', 'store'],

        // Web form posts - commercial module
        '/customers'                      => ['App\Controllers\WebCustomersController', 'store'],
        '/suppliers'                      => ['App\Controllers\WebSuppliersController', 'store'],
        '/items'                          => ['App\Controllers\WebItemsController', 'store'],
        '/sales-invoices'                 => ['App\Controllers\WebSalesInvoicesController', 'store'],
        '/sales-invoices/post'            => ['App\Controllers\WebSalesInvoicesController', 'postInvoice'],
        '/purchase-invoices'              => ['App\Controllers\WebPurchaseInvoicesController', 'store'],
        '/purchase-invoices/post'         => ['App\Controllers\WebPurchaseInvoicesController', 'postInvoice'],
        '/receipts'                       => ['App\Controllers\WebReceiptsController', 'store'],
        '/receipts/post'                  => ['App\Controllers\WebReceiptsController', 'postReceipt'],
        '/payments'                       => ['App\Controllers\WebPaymentsController', 'store'],
        '/payments/post'                  => ['App\Controllers\WebPaymentsController', 'postPayment'],

        // API posts - commercial module
        '/api/customers'                  => ['App\Controllers\CustomersController', 'store'],
        '/api/suppliers'                  => ['App\Controllers\SuppliersController', 'store'],
        '/api/items'                      => ['App\Controllers\ItemsController', 'store'],
        '/api/sales-invoices'             => ['App\Controllers\SalesInvoicesController', 'store'],
        '/api/sales-invoices/post'        => ['App\Controllers\SalesInvoicesController', 'post'],
        '/api/purchase-invoices'          => ['App\Controllers\PurchaseInvoicesController', 'store'],
        '/api/purchase-invoices/post'     => ['App\Controllers\PurchaseInvoicesController', 'post'],
        '/api/receipts'                   => ['App\Controllers\ReceiptsController', 'store'],
        '/api/receipts/post'              => ['App\Controllers\ReceiptsController', 'post'],
        '/api/payments'                   => ['App\Controllers\PaymentsController', 'store'],
        '/api/payments/post'              => ['App\Controllers\PaymentsController', 'post'],
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $requestUri);