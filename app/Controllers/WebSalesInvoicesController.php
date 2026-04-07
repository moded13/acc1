<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Services\PostingService;

class WebSalesInvoicesController
{
    private string $base = '/20/accounting/acc1/public';

    /** GET /sales-invoices */
    public function index(): void
    {
        $title  = 'فواتير المبيعات';
        $active = 'sales';
        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>فواتير المبيعات</div><div class='sub'>إنشاء وعرض فواتير المبيعات.</div></div>";
        echo "<div class='row' style='margin-top:12px'>
                <a class='btn' href='" . View::e($this->base . '/sales-invoices/new') . "'>+ فاتورة جديدة</a>
              </div>";

        echo "<div class='card'>";
        try {
            $model = new SalesInvoice();
            $rows  = $model->list();
            if (empty($rows)) {
                echo "<p class='sub'>لا توجد فواتير حتى الآن.</p>";
            } else {
                echo "<table class='table'>
                        <thead><tr>
                          <th>رقم الفاتورة</th><th>التاريخ</th><th>العميل</th>
                          <th class='num'>الإجمالي قبل الضريبة</th><th class='num'>الضريبة</th><th class='num'>الإجمالي</th>
                          <th>الحالة</th><th>إجراء</th>
                        </tr></thead><tbody>";
                foreach ($rows as $r) {
                    $showUrl = $this->base . '/sales-invoices/show?id=' . urlencode($r['id']);
                    $statusLabel = $this->statusLabel($r['status']);
                    echo "<tr>
                            <td>" . View::e($r['invoice_no']) . "</td>
                            <td class='num'>" . View::e($r['invoice_date']) . "</td>
                            <td>" . View::e($r['customer_name'] ?? '') . "</td>
                            <td class='num'>" . View::money($r['subtotal']) . "</td>
                            <td class='num'>" . View::money($r['vat_total']) . "</td>
                            <td class='num'>" . View::money($r['total']) . "</td>
                            <td>" . View::e($statusLabel) . "</td>
                            <td><a class='btn-outline' href='" . View::e($showUrl) . "'>عرض</a></td>
                          </tr>";
                }
                echo "</tbody></table>";
            }
        } catch (\Throwable $e) {
            echo "<p class='sub' style='color:red'>خطأ: " . View::e($e->getMessage()) . "</p>";
        }
        echo "</div>";

        require APP_PATH . '/Views/footer.php';
    }

    /** GET /sales-invoices/new */
    public function newForm(): void
    {
        $title  = 'فاتورة مبيعات جديدة';
        $active = 'sales';
        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>فاتورة مبيعات جديدة</div></div>";

        try {
            $customers = (new Customer())->getAll(true);
            $items     = (new Item())->getAll(true);

            $custOpts = "<option value=''>-- اختر العميل --</option>";
            foreach ($customers as $c) {
                $custOpts .= "<option value='" . View::e($c['id']) . "'>" . View::e($c['name']) . "</option>";
            }

            $itemsJson = json_encode(array_map(fn($i) => [
                'id'         => $i['id'],
                'name'       => $i['name'],
                'sale_price' => $i['sale_price'],
                'vat_rate'   => $i['vat_rate'],
            ], $items), JSON_UNESCAPED_UNICODE);

            echo "<div class='card'>
                    <form method='POST' action='" . View::e($this->base . '/sales-invoices') . "'>
                      <div class='row'>
                        <div class='field'><label>العميل *</label>
                          <select name='customer_id' required>$custOpts</select>
                        </div>
                        <div class='field'><label>تاريخ الفاتورة *</label>
                          <input type='date' name='invoice_date' value='" . date('Y-m-d') . "' required>
                        </div>
                        <div class='field'><label>ملاحظات</label>
                          <input type='text' name='notes'>
                        </div>
                      </div>

                      <div class='pill' style='margin:14px 0 8px'>أسطر الفاتورة</div>
                      <table class='table' id='lines-table'>
                        <thead><tr>
                          <th>الصنف</th><th>الوصف</th><th>الكمية</th>
                          <th>سعر الوحدة</th><th>نسبة الضريبة</th><th>الإجمالي</th><th></th>
                        </tr></thead>
                        <tbody id='lines-body'></tbody>
                      </table>

                      <button type='button' class='btn-outline' onclick='addLine()' style='margin-top:8px'>+ إضافة سطر</button>

                      <div class='row' style='margin-top:14px;justify-content:flex-end'>
                        <div>
                          <strong>الإجمالي قبل الضريبة: </strong><span id='subtotal'>0.00</span> &nbsp;
                          <strong>الضريبة: </strong><span id='vat-total'>0.00</span> &nbsp;
                          <strong>الإجمالي: </strong><span id='grand-total'>0.00</span>
                        </div>
                      </div>

                      <input type='hidden' name='lines_json' id='lines_json'>
                      <div class='row' style='margin-top:14px'>
                        <button class='btn' type='submit' onclick='return prepareSubmit()'>حفظ كمسودة</button>
                        <a class='btn-outline' href='" . View::e($this->base . '/sales-invoices') . "'>إلغاء</a>
                      </div>
                    </form>
                  </div>";

            echo "<script>
const ITEMS = $itemsJson;
let lineCount = 0;

function addLine() {
    lineCount++;
    const itemOpts = ITEMS.map(i =>
        '<option value=\"' + i.id + '\" data-price=\"' + i.sale_price + '\" data-vat=\"' + i.vat_rate + '\">' + i.name + '</option>'
    ).join('');
    const tr = document.createElement('tr');
    tr.id = 'line_' + lineCount;
    tr.innerHTML = '<td><select name=\"item_\" onchange=\"itemSelected(this,' + lineCount + ')\" style=\"min-width:140px\">' +
        '<option value=\"\">-- اختياري --</option>' + itemOpts + '</select></td>' +
        '<td><input type=\"text\" name=\"desc_\" id=\"desc_' + lineCount + '\"></td>' +
        '<td><input type=\"number\" step=\"0.001\" value=\"1\" name=\"qty_\" id=\"qty_' + lineCount + '\" oninput=\"recalc(' + lineCount + ')\" style=\"width:80px\"></td>' +
        '<td><input type=\"number\" step=\"0.0001\" value=\"0\" name=\"price_\" id=\"price_' + lineCount + '\" oninput=\"recalc(' + lineCount + ')\" style=\"width:110px\"></td>' +
        '<td><input type=\"number\" step=\"0.0001\" value=\"0.16\" name=\"vat_\" id=\"vat_' + lineCount + '\" oninput=\"recalc(' + lineCount + ')\" style=\"width:80px\"></td>' +
        '<td id=\"linetotal_' + lineCount + '\" class=\"num\">0.00</td>' +
        '<td><button type=\"button\" class=\"btn-outline\" onclick=\"removeLine(' + lineCount + ')\">حذف</button></td>';
    document.getElementById('lines-body').appendChild(tr);
    recalc(lineCount);
}

function itemSelected(sel, n) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('price_' + n).value = opt.dataset.price || 0;
    document.getElementById('vat_' + n).value   = opt.dataset.vat   || 0.16;
    recalc(n);
}

function removeLine(n) {
    const tr = document.getElementById('line_' + n);
    if (tr) tr.remove();
    updateTotals();
}

function recalc(n) {
    const qty   = parseFloat(document.getElementById('qty_' + n).value)   || 0;
    const price = parseFloat(document.getElementById('price_' + n).value) || 0;
    const vat   = parseFloat(document.getElementById('vat_' + n).value)   || 0;
    const base  = qty * price;
    const total = base + base * vat;
    document.getElementById('linetotal_' + n).textContent = total.toFixed(2);
    updateTotals();
}

function updateTotals() {
    let sub = 0, vatT = 0;
    document.querySelectorAll('#lines-body tr').forEach(tr => {
        const n = tr.id.replace('line_', '');
        const qty   = parseFloat(document.getElementById('qty_' + n)?.value)   || 0;
        const price = parseFloat(document.getElementById('price_' + n)?.value) || 0;
        const vat   = parseFloat(document.getElementById('vat_' + n)?.value)   || 0;
        const base  = qty * price;
        sub  += base;
        vatT += base * vat;
    });
    document.getElementById('subtotal').textContent   = sub.toFixed(2);
    document.getElementById('vat-total').textContent  = vatT.toFixed(2);
    document.getElementById('grand-total').textContent = (sub + vatT).toFixed(2);
}

function prepareSubmit() {
    const rows = [];
    document.querySelectorAll('#lines-body tr').forEach(tr => {
        const n = tr.id.replace('line_', '');
        const selEl = tr.querySelector('select');
        rows.push({
            item_id:     selEl?.value || null,
            description: document.getElementById('desc_' + n)?.value || '',
            qty:         document.getElementById('qty_' + n)?.value  || 1,
            unit_price:  document.getElementById('price_' + n)?.value || 0,
            vat_rate:    document.getElementById('vat_' + n)?.value   || 0.16,
        });
    });
    if (rows.length === 0) { alert('أضف سطراً واحداً على الأقل'); return false; }
    document.getElementById('lines_json').value = JSON.stringify(rows);
    return true;
}

addLine();
</script>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        require APP_PATH . '/Views/footer.php';
    }

    /** POST /sales-invoices */
    public function store(): void
    {
        $customerId  = (int)($_POST['customer_id']  ?? 0);
        $invoiceDate = trim($_POST['invoice_date']  ?? date('Y-m-d'));
        $notes       = trim($_POST['notes']         ?? '') ?: null;
        $linesJson   = trim($_POST['lines_json']    ?? '[]');
        $lines       = json_decode($linesJson, true) ?: [];

        $header = [
            'customer_id'  => $customerId,
            'invoice_date' => $invoiceDate,
            'notes'        => $notes,
        ];

        try {
            $model = new SalesInvoice();
            $id    = $model->create($header, $lines);
            header('Location: ' . $this->base . '/sales-invoices/show?id=' . $id);
        } catch (\Throwable $e) {
            header('Location: ' . $this->base . '/sales-invoices/new?err=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /** GET /sales-invoices/show */
    public function show(): void
    {
        $id    = (int)($_GET['id'] ?? 0);
        $title = 'تفاصيل فاتورة مبيعات';
        $active = 'sales';
        require APP_PATH . '/Views/layout.php';

        if ($id <= 0) {
            echo "<div class='card'>id غير صحيح</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        try {
            $model = new SalesInvoice();
            $inv   = $model->findById($id);
            if (!$inv) throw new \Exception("الفاتورة غير موجودة");

            $lines = $model->getLines($id);

            $statusLabel = $this->statusLabel($inv['status']);

            echo "<div><div class='h1'>فاتورة مبيعات: " . View::e($inv['invoice_no']) . "</div>
                    <div class='sub'>التاريخ: " . View::e($inv['invoice_date']) . " | الحالة: <strong>" . View::e($statusLabel) . "</strong></div>
                  </div>";

            // Post button
            if ($inv['status'] === 'draft') {
                echo "<form method='POST' action='" . View::e($this->base . '/sales-invoices/post') . "' style='margin-top:10px'>
                        <input type='hidden' name='id' value='" . View::e($inv['id']) . "'>
                        <button class='btn' type='submit' onclick=\"return confirm('هل تريد ترحيل الفاتورة؟')\">ترحيل الفاتورة</button>
                        <a class='btn-outline' href='" . View::e($this->base . '/sales-invoices') . "'>رجوع</a>
                      </form>";
            } else {
                echo "<div style='margin-top:10px'><a class='btn-outline' href='" . View::e($this->base . '/sales-invoices') . "'>رجوع للقائمة</a></div>";
            }

            echo "<div class='card'>
                    <div class='row' style='gap:20px'>
                      <div><span class='sub'>العميل: </span><strong>" . View::e($inv['customer_name'] ?? '') . "</strong></div>
                      <div><span class='sub'>ملاحظات: </span>" . View::e($inv['notes'] ?? '') . "</div>
                    </div>
                    <table class='table' style='margin-top:12px'>
                      <thead><tr>
                        <th>#</th><th>الصنف</th><th>الوصف</th>
                        <th class='num'>الكمية</th><th class='num'>سعر الوحدة</th>
                        <th class='num'>الضريبة</th><th class='num'>الإجمالي</th>
                      </tr></thead><tbody>";

            foreach ($lines as $l) {
                echo "<tr>
                        <td>" . View::e($l['line_no']) . "</td>
                        <td>" . View::e($l['item_name'] ?? '') . "</td>
                        <td>" . View::e($l['description']) . "</td>
                        <td class='num'>" . View::money($l['qty'], 3) . "</td>
                        <td class='num'>" . View::money($l['unit_price']) . "</td>
                        <td class='num'>" . View::money($l['vat_amount']) . "</td>
                        <td class='num'>" . View::money($l['line_total']) . "</td>
                      </tr>";
            }

            echo "  <tr style='font-weight:900;background:#f8fafc'>
                      <td colspan='6'>الإجمالي قبل الضريبة</td>
                      <td class='num'>" . View::money($inv['subtotal']) . "</td>
                    </tr>
                    <tr style='font-weight:900;background:#f8fafc'>
                      <td colspan='6'>الضريبة (VAT)</td>
                      <td class='num'>" . View::money($inv['vat_total']) . "</td>
                    </tr>
                    <tr style='font-weight:900;background:#eef2ff'>
                      <td colspan='6'>الإجمالي الكلي</td>
                      <td class='num'>" . View::money($inv['total']) . "</td>
                    </tr>
                  </tbody></table>
                </div>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        require APP_PATH . '/Views/footer.php';
    }

    /** POST /sales-invoices/post */
    public function postInvoice(): void
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        try {
            $service = new PostingService();
            $service->postSalesInvoice($id);
        } catch (\Throwable $e) {
            // redirect to show with no error display for simplicity
        }
        header('Location: ' . $this->base . '/sales-invoices/show?id=' . $id);
        exit;
    }

    private function statusLabel(string $status): string
    {
        return match($status) {
            'draft'  => 'مسودة',
            'posted' => 'مرحّل',
            'void'   => 'ملغي',
            default  => $status,
        };
    }
}
