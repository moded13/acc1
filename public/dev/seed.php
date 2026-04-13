<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo "curl_init: " . (function_exists('curl_init') ? "YES" : "NO");
exit;
/**
 * DEV SEED (Acc1 ERP) - Professional realistic dataset
 *
 * الهدف:
 * - تعبئة النظام ببيانات تبدو حقيقية (عملاء/موردين/أصناف/مشتريات/مبيعات/سندات)
 * - ترحيل (POST) المستندات عبر نفس Endpoints الموجودة لديك ليُنشئ القيود + المخزون + VAT + QR
 * - توفير حذف سهل وآمن عبر tag واحد (استخدم purge_seed.php)
 *
 * التشغيل:
 *   https://www.shneler.com/20/accounting/acc1/public/dev/seed.php
 *
 * مهم جداً:
 * - احذف مجلد public/dev بعد الانتهاء أو ضع حماية عليه.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__, 2)); // .../acc1
define('APP_PATH', BASE_PATH . '/app');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

require APP_PATH . '/Config/Database.php';

use App\Config\Database;

header('Content-Type: text/html; charset=utf-8');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nowTag(){ return 'TST-' . date('Ymd-His'); }

$db = (new Database())->getConnection();
$tag = nowTag();

$BASE_URL = 'https://www.shneler.com/20/accounting/acc1/public'; // ثابت حسب مشروعك

// ============== DB helpers ==============
function q($db, $sql, $params = []) {
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st;
}
function one($db, $sql, $params = []) {
    return q($db, $sql, $params)->fetch();
}
function all($db, $sql, $params = []) {
    return q($db, $sql, $params)->fetchAll();
}

function ensureSetting($db, $key, $value, $note = null) {
    $row = one($db, "SELECT setting_key FROM settings WHERE setting_key=:k LIMIT 1", [':k'=>$key]);

    if ($row) {
        q($db, "UPDATE settings SET setting_val=:v, note=:n, updated_at=CURRENT_TIMESTAMP WHERE setting_key=:k", [
            ':k' => $key,
            ':v' => (string)$value,
            ':n' => $note
        ]);
    } else {
        q($db, "INSERT INTO settings (setting_key, setting_val, note, updated_at) VALUES (:k,:v,:n,CURRENT_TIMESTAMP)", [
            ':k' => $key,
            ':v' => (string)$value,
            ':n' => $note
        ]);
    }
}

function randDateBack(int $daysBack): string {
    $ts = time() - rand(0, max(1,$daysBack)) * 86400;
    return date('Y-m-d', $ts);
}

function pick(array $arr) {
    return $arr[array_rand($arr)];
}

// ============== API helper (cURL) ==============
function apiCallJson(string $baseUrl, string $method, string $path, array $data = [], bool $asForm = false): array
{
    $url = $baseUrl . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    if (strtoupper($method) === 'POST') {
        if ($asForm) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=utf-8']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        }
    }

    // بيئة الاستضافة قد تكون ذات SSL غير موثّق
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error calling $path: $err");
    }
    curl_close($ch);

    $j = json_decode($resp, true);

    if ($j === null || !is_array($j)) {
        throw new Exception("Invalid JSON from $path (HTTP $code): " . substr($resp, 0, 500));
    }

    if (($j['status'] ?? '') !== 'success') {
        $msg = $j['message'] ?? ('Unknown API error. Raw: ' . substr($resp, 0, 300));
        throw new Exception("API error from $path (HTTP $code): $msg");
    }

    return $j;
}

function apiGetIdFromResponse(array $j): int
{
    foreach (['id','invoice_id','sales_invoice_id','purchase_invoice_id','receipt_id','payment_id','account_id','customer_id','supplier_id','item_id'] as $k) {
        if (isset($j[$k])) return (int)$j[$k];
        if (isset($j['data'][$k])) return (int)$j['data'][$k];
    }
    if (isset($j['data']) && is_array($j['data']) && isset($j['data']['id'])) return (int)$j['data']['id'];

    // لو ما رجّع id صريح، نعتبره نجاح لكن بدون id
    return 0;
}

// ============== Output ==============
echo "<h2>Seeding started</h2>";
echo "<div>Batch tag: <b>".h($tag)."</b></div>";

try {
    ensureSetting($db, 'dev_seed_last_tag', $tag, 'آخر batch تم إدخاله بواسطة public/dev/seed.php');

    // ---------- 1) Customers ----------
    $customers = [
        ['code'=>"$tag-CUST-001",'name'=>'شركة النخبة للتجارة العامة','phone'=>'0790000001','email'=>'elite.trading@example.com','address'=>'عمّان - الشميساني','tax_no'=>'JO-ELITE-001'],
        ['code'=>"$tag-CUST-002",'name'=>'مؤسسة الرافدين للتقنية','phone'=>'0790000002','email'=>'rafidain.tech@example.com','address'=>'إربد - الحي الشرقي','tax_no'=>'JO-RAF-002'],
        ['code'=>"$tag-CUST-003",'name'=>'عيادة الشفاء الطبية','phone'=>'0790000003','email'=>'shifa.clinic@example.com','address'=>'الزرقاء - الجديدة','tax_no'=>'JO-SHIFA-003'],
        ['code'=>"$tag-CUST-004",'name'=>'متجر الريادة للإلكترونيات','phone'=>'0790000004','email'=>'reyada.store@example.com','address'=>'الكرك - السوق','tax_no'=>'JO-REY-004'],
        ['code'=>"$tag-CUST-005",'name'=>'شركة سمارت هوم','phone'=>'0790000005','email'=>'smart.home@example.com','address'=>'عمّان - خلدا','tax_no'=>'JO-SH-005'],
        ['code'=>"$tag-CUST-006",'name'=>'شركة البرق للخدمات','phone'=>'0790000006','email'=>'albarq.services@example.com','address'=>'السلط - المدينة','tax_no'=>'JO-BARQ-006'],
        ['code'=>"$tag-CUST-007",'name'=>'مكتبة الأفق','phone'=>'0790000007','email'=>'horizon.books@example.com','address'=>'مادبا - شارع الجامعة','tax_no'=>'JO-HOR-007'],
        ['code'=>"$tag-CUST-008",'name'=>'شركة المدار للاتصالات','phone'=>'0790000008','email'=>'almadar.comm@example.com','address'=>'العقبة - الحي التجاري','tax_no'=>'JO-MADAR-008'],
    ];

    foreach ($customers as $c) {
        q($db, "INSERT INTO customers (code,name,phone,email,address,tax_no,is_active,created_at,updated_at)
                VALUES (:code,:name,:phone,:email,:address,:tax_no,1,NOW(),NOW())", [
            ':code'=>$c['code'],':name'=>$c['name'],':phone'=>$c['phone'],':email'=>$c['email'],
            ':address'=>$c['address'],':tax_no'=>$c['tax_no']
        ]);
    }

    $customerRows = all($db, "SELECT id, code, name FROM customers WHERE code LIKE :p ORDER BY id ASC", [':p'=>"$tag-CUST-%"]);
    echo "<h3>Customers: ".count($customerRows)."</h3>";
    $customerIds = array_column($customerRows, 'id');

    // ---------- 2) Suppliers ----------
    $suppliers = [
        ['code'=>"$tag-SUP-001",'name'=>'شركة الشرق للتوريدات','phone'=>'0780000001','email'=>'east.supplies@example.com','address'=>'عمّان - ماركا','tax_no'=>'JO-EAST-001'],
        ['code'=>"$tag-SUP-002",'name'=>'مستودع المستقبل','phone'=>'0780000002','email'=>'future.warehouse@example.com','address'=>'إربد - المدينة الصناعية','tax_no'=>'JO-FUT-002'],
        ['code'=>"$tag-SUP-003",'name'=>'مزود الخليج للأجهزة','phone'=>'0780000003','email'=>'gulf.devices@example.com','address'=>'الزرقاء - المنطقة الحرة','tax_no'=>'JO-GULF-003'],
        ['code'=>"$tag-SUP-004",'name'=>'شركة الأمان للتقنيات','phone'=>'0780000004','email'=>'aman.tech@example.com','address'=>'عمّان - تلاع العلي','tax_no'=>'JO-AMAN-004'],
        ['code'=>"$tag-SUP-005",'name'=>'مورد الشمال','phone'=>'0780000005','email'=>'north.vendor@example.com','address'=>'جرش - وسط البلد','tax_no'=>'JO-NORTH-005'],
    ];

    foreach ($suppliers as $s) {
        q($db, "INSERT INTO suppliers (code,name,phone,email,address,tax_no,is_active,created_at,updated_at)
                VALUES (:code,:name,:phone,:email,:address,:tax_no,1,NOW(),NOW())", [
            ':code'=>$s['code'],':name'=>$s['name'],':phone'=>$s['phone'],':email'=>$s['email'],
            ':address'=>$s['address'],':tax_no'=>$s['tax_no']
        ]);
    }

    $supplierRows = all($db, "SELECT id, code, name FROM suppliers WHERE code LIKE :p ORDER BY id ASC", [':p'=>"$tag-SUP-%"]);
    echo "<h3>Suppliers: ".count($supplierRows)."</h3>";
    $supplierIds = array_column($supplierRows, 'id');

    // ---------- 3) Items ----------
    $items = [
        ['sku'=>"$tag-ITM-001",'name'=>'شاشة 24 بوصة','unit'=>'pcs','cost'=>120,'sale'=>200],
        ['sku'=>"$tag-ITM-002",'name'=>'لوحة مفاتيح ميكانيكية','unit'=>'pcs','cost'=>25,'sale'=>45],
        ['sku'=>"$tag-ITM-003",'name'=>'فأرة لاسلكية','unit'=>'pcs','cost'=>10,'sale'=>18],
        ['sku'=>"$tag-ITM-004",'name'=>'راوتر Wi‑Fi 6','unit'=>'pcs','cost'=>55,'sale'=>85],
        ['sku'=>"$tag-ITM-005",'name'=>'هارد SSD 512GB','unit'=>'pcs','cost'=>35,'sale'=>55],
        ['sku'=>"$tag-ITM-006",'name'=>'خدمة تركيب وتهيئة','unit'=>'svc','cost'=>0,'sale'=>35],
        ['sku'=>"$tag-ITM-007",'name'=>'طابعة ليزر','unit'=>'pcs','cost'=>90,'sale'=>140],
        ['sku'=>"$tag-ITM-008",'name'=>'حبر طابعة','unit'=>'pcs','cost'=>12,'sale'=>20],
        ['sku'=>"$tag-ITM-009",'name'=>'كاميرا مراقبة','unit'=>'pcs','cost'=>22,'sale'=>39],
        ['sku'=>"$tag-ITM-010",'name'=>'جهاز DVR','unit'=>'pcs','cost'=>65,'sale'=>99],
        ['sku'=>"$tag-ITM-011",'name'=>'كابل HDMI 2m','unit'=>'pcs','cost'=>2,'sale'=>5],
        ['sku'=>"$tag-ITM-012",'name'=>'سويتش 8 منافذ','unit'=>'pcs','cost'=>18,'sale'=>29],
        ['sku'=>"$tag-ITM-013",'name'=>'نقطة وصول (Access Point)','unit'=>'pcs','cost'=>40,'sale'=>65],
        ['sku'=>"$tag-ITM-014",'name'=>'كيبورد عادي','unit'=>'pcs','cost'=>8,'sale'=>15],
        ['sku'=>"$tag-ITM-015",'name'=>'خدمة صيانة شهرية','unit'=>'svc','cost'=>0,'sale'=>60],
    ];

    foreach ($items as $it) {
        q($db, "INSERT INTO items (sku,name,unit,cost_price,sale_price,vat_rate,is_active,created_at,updated_at)
                VALUES (:sku,:name,:unit,:cost,:sale,0.16,1,NOW(),NOW())", [
            ':sku'=>$it['sku'],':name'=>$it['name'],':unit'=>$it['unit'],':cost'=>$it['cost'],':sale'=>$it['sale'],
        ]);
    }

    $itemRows = all($db, "SELECT id, sku, name, cost_price, sale_price FROM items WHERE sku LIKE :p ORDER BY id ASC", [':p'=>"$tag-ITM-%"]);
    echo "<h3>Items: ".count($itemRows)."</h3>";
    $itemIds = array_column($itemRows, 'id');

    // ---------- 4) Create Purchase Invoices via API (build inventory) ----------
    echo "<h3>Creating purchase invoices...</h3>";

    $purchaseInvoiceIds = [];
    $purchaseCount = 12;
    for ($k=1; $k<=$purchaseCount; $k++) {
        $supplierId  = pick($supplierIds);
        $invoiceDate = randDateBack(75);
        $notes       = "توريد بضائع - دفعة ($k) - $tag";

        $lines = [];
        $linesCount = rand(2, 6);
        $pickedIdx = array_rand($itemIds, $linesCount);
        if (!is_array($pickedIdx)) $pickedIdx = [$pickedIdx];

        foreach ($pickedIdx as $pi) {
            $itemId = $itemIds[$pi];
            $row = one($db, "SELECT cost_price, name FROM items WHERE id=:id", [':id'=>$itemId]);
            $unitCost = (float)($row['cost_price'] ?? 10);
            $qty = rand(6, 22);

            // realistic discount sometimes
            if (rand(1,10) <= 2) $unitCost = round($unitCost * 0.97, 2);

            $lines[] = [
                'item_id'     => $itemId,
                'description' => $row['name'] ?? 'Item',
                'qty'         => $qty,
                'unit_cost'   => $unitCost,
                'vat_rate'    => 0.16,
            ];
        }

        // store payload (JSON)
        $payload = [
            'supplier_id'  => $supplierId,
            'invoice_date' => $invoiceDate,
            'vat_rate'     => 0.16,
            'notes'        => $notes,
            'lines'        => $lines,
        ];

        $resp = apiCallJson($BASE_URL, 'POST', '/api/purchase-invoices', $payload, false);
        $id = apiGetIdFromResponse($resp);
        if ($id <= 0) {
            throw new Exception("Purchase invoice created but no id returned (k=$k).");
        }
        $purchaseInvoiceIds[] = $id;
    }

    echo "<div>Purchase invoices created: <b>".h(count($purchaseInvoiceIds))."</b></div>";

    echo "<h3>Posting purchase invoices...</h3>";
    foreach ($purchaseInvoiceIds as $id) {
        // post endpoints غالباً تعتمد $_POST لذلك نرسل كـ form
        apiCallJson($BASE_URL, 'POST', '/api/purchase-invoices/post', ['id' => $id], true);
    }
    echo "<div>Purchase invoices posted: <b>".h(count($purchaseInvoiceIds))."</b></div>";

    // ---------- 5) Create Sales Invoices via API ----------
    echo "<h3>Creating sales invoices...</h3>";

    $salesInvoiceIds = [];
    $salesCount = 24;
    for ($k=1; $k<=$salesCount; $k++) {
        $customerId  = pick($customerIds);
        $invoiceDate = randDateBack(55);
        $notes       = "عملية بيع احترافية - فاتورة ($k) - $tag";

        $lines = [];
        $linesCount = rand(2, 7);
        $pickedIdx = array_rand($itemIds, $linesCount);
        if (!is_array($pickedIdx)) $pickedIdx = [$pickedIdx];

        foreach ($pickedIdx as $pi) {
            $itemId = $itemIds[$pi];
            $row = one($db, "SELECT sale_price, name FROM items WHERE id=:id", [':id'=>$itemId]);
            $unitPrice = (float)($row['sale_price'] ?? 20);
            $qty = rand(1, 5);

            // slight variation for realism (±6%)
            $unitPrice = round($unitPrice * (rand(94, 106) / 100), 2);

            $lines[] = [
                'item_id'     => $itemId,
                'description' => $row['name'] ?? 'Item',
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'vat_rate'    => 0.16,
            ];
        }

        $payload = [
            'customer_id'  => $customerId,
            'invoice_date' => $invoiceDate,
            'vat_rate'     => 0.16,
            'notes'        => $notes,
            'lines'        => $lines,
        ];

        $resp = apiCallJson($BASE_URL, 'POST', '/api/sales-invoices', $payload, false);
        $id = apiGetIdFromResponse($resp);
        if ($id <= 0) {
            throw new Exception("Sales invoice created but no id returned (k=$k).");
        }
        $salesInvoiceIds[] = $id;
    }

    echo "<div>Sales invoices created: <b>".h(count($salesInvoiceIds))."</b></div>";

    echo "<h3>Posting sales invoices...</h3>";
    foreach ($salesInvoiceIds as $id) {
        apiCallJson($BASE_URL, 'POST', '/api/sales-invoices/post', ['id' => $id], true);
    }
    echo "<div>Sales invoices posted: <b>".h(count($salesInvoiceIds))."</b></div>";

    // ---------- 6) Receipts (customer payments) ----------
    echo "<h3>Creating receipts...</h3>";

    $receiptIds = [];
    $receiptCount = 16;

    for ($k=1; $k<=$receiptCount; $k++) {
        $customerId = pick($customerIds);
        $date = randDateBack(35);
        $amount = rand(60, 450);
        $method = (rand(0,1) ? 'cash' : 'bank');

        $payload = [
            'receipt_date' => $date,
            'customer_id'  => $customerId,
            'amount'       => $amount,
            'method'       => $method,
            'notes'        => "سند قبض - دفعة ($k) - $tag",
        ];

        $resp = apiCallJson($BASE_URL, 'POST', '/api/receipts', $payload, false);
        $id = apiGetIdFromResponse($resp);
        if ($id <= 0) {
            throw new Exception("Receipt created but no id returned (k=$k).");
        }
        $receiptIds[] = $id;

        apiCallJson($BASE_URL, 'POST', '/api/receipts/post', ['id' => $id], true);
    }
    echo "<div>Receipts created+posted: <b>".h(count($receiptIds))."</b></div>";

    // ---------- 7) Payments (supplier payments) ----------
    echo "<h3>Creating payments...</h3>";

    $paymentIds = [];
    $paymentCount = 12;

    for ($k=1; $k<=$paymentCount; $k++) {
        $supplierId = pick($supplierIds);
        $date = randDateBack(45);
        $amount = rand(90, 650);
        $method = (rand(0,1) ? 'cash' : 'bank');

        $payload = [
            'payment_date' => $date,
            'supplier_id'  => $supplierId,
            'amount'       => $amount,
            'method'       => $method,
            'notes'        => "سند دفع - دفعة ($k) - $tag",
        ];

        $resp = apiCallJson($BASE_URL, 'POST', '/api/payments', $payload, false);
        $id = apiGetIdFromResponse($resp);
        if ($id <= 0) {
            throw new Exception("Payment created but no id returned (k=$k).");
        }
        $paymentIds[] = $id;

        apiCallJson($BASE_URL, 'POST', '/api/payments/post', ['id' => $id], true);
    }
    echo "<div>Payments created+posted: <b>".h(count($paymentIds))."</b></div>";

    // ---------- Summary ----------
    echo "<hr>";
    echo "<h2>Seed completed successfully ✅</h2>";

    echo "<div style='margin-top:10px'><b>Next:</b> افتح التقارير لتشوف النظام ممتلئ:</div>";
    echo "<ul>";
    echo "<li><a href='/20/accounting/acc1/public/dashboard'>Dashboard</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/sales-invoices'>Sales invoices</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/purchase-invoices'>Purchase invoices</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/reports/income-statement'>Income Statement (P&L)</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/reports/trial-balance'>Trial Balance</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/reports/balance-sheet'>Balance Sheet</a></li>";
    echo "<li><a href='/20/accounting/acc1/public/reports/ledger'>Ledger</a></li>";
    echo "</ul>";

    echo "<div style='margin-top:10px'>لحذف هذه الدفعة فقط (آمن):</div>";
    echo "<div><a href='/20/accounting/acc1/public/dev/purge_seed.php?tag=".h(urlencode($tag))."'>purge_seed.php?tag=$tag</a></div>";

    echo "<*
