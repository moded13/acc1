<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\SalesInvoice;
use App\Services\JordanEInvoicingQR;
use Exception;

class WebSalesInvoicesController
{
    /**
     * GET /sales-invoices
     */
    public function index(): void
    {
        $model = new SalesInvoice();
        $invoices = $model->list();

        $title = 'فواتير المبيعات';
        $active = 'sales';

        require APP_PATH . '/Views/layout.php';

        $viewPath = APP_PATH . '/Views/sales_invoices/index.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<div class="card">';
            echo '<div class="h1 mb-4">قائمة فواتير المبيعات</div>';
            echo '<div class="invoice-actions no-print" style="margin-bottom:12px">';
            echo '  <a class="btn" href="/20/accounting/acc1/public/sales-invoices/new">فاتورة مبيعات جديدة</a>';
            echo '</div>';

            echo '<table class="table">';
            echo '<thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>العميل</th><th class="num">الإجمالي</th><th>الحالة</th><th>إجراءات</th></tr></thead>';
            echo '<tbody>';
            foreach ($invoices as $inv) {
                echo '<tr>';
                echo '<td>' . View::e($inv['invoice_no']) . '</td>';
                echo '<td>' . View::e($inv['invoice_date']) . '</td>';
                echo '<td>' . View::e($inv['customer_name'] ?? '-') . '</td>';
                echo '<td class="num">' . View::money($inv['total']) . '</td>';
                echo '<td>' . View::e($inv['status']) . '</td>';
                echo '<td>
                        <a href="/20/accounting/acc1/public/sales-invoices/show?id=' . (int)$inv['id'] . '" class="btn-outline">عرض</a>
                      </td>';
                echo '</tr>';
            }
            if (empty($invoices)) {
                echo '<tr><td colspan="6" style="text-align:center">لا توجد فواتير</td></tr>';
            }
            echo '</tbody></table></div>';
        }

        require APP_PATH . '/Views/footer.php';
    }

    /**
     * GET /sales-invoices/new
     */
    public function newForm(): void
    {
        $title  = 'فاتورة مبيعات جديدة';
        $active = 'sales';

        require APP_PATH . '/Views/layout.php';
        ?>
        <div class="card">
          <div class="h1 mb-4">فاتورة مبيعات جديدة</div>

          <?php if (!empty($_GET['error'])): ?>
            <div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:12px;margin-bottom:10px">
              <?= View::e($_GET['error']) ?>
            </div>
          <?php endif; ?>

          <form method="post" action="/20/accounting/acc1/public/sales-invoices/store">
            <div class="grid">
              <div>
                <label>العميل</label>
                <select id="customer_id" name="customer_id" required>
                  <option value="">تحميل العملاء...</option>
                </select>
              </div>
              <div>
                <label>تاريخ الفاتورة</label>
                <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>

            <div style="margin-top:12px">
              <label>ملاحظات</label>
              <input type="text" name="notes" placeholder="اختياري">
            </div>

            <hr style="margin:14px 0">

            <div class="h2">بنود الفاتورة</div>
            <div class="sub">أدخل حتى 6 بنود (إذا تركت الصنف فارغاً لن يتم حفظ السطر).</div>

            <?php for ($i=0; $i<6; $i++): ?>
              <div style="border:1px solid var(--border);border-radius:12px;padding:10px;margin-top:10px">
                <div class="grid">
                  <div>
                    <label>الصنف</label>
                    <select class="item-select" name="lines[<?= $i ?>][item_id]">
                      <option value="">تحميل الأصناف...</option>
                    </select>
                  </div>
                  <div>
                    <label>الكمية</label>
                    <input type="number" step="0.01" name="lines[<?= $i ?>][qty]" value="1">
                  </div>
                  <div>
                    <label>سعر الوحدة</label>
                    <input type="number" step="0.01" name="lines[<?= $i ?>][unit_price]" value="0">
                  </div>
                </div>
              </div>
            <?php endfor; ?>

            <div class="invoice-actions" style="margin-top:14px">
              <button class="btn" type="submit">حفظ (Draft)</button>
              <a class="btn-outline" href="/20/accounting/acc1/public/sales-invoices">رجوع</a>
            </div>
          </form>
        </div>

        <script>
        (async function(){
          // base path
          const base = '/20/accounting/acc1/public';

          // 1) customers
          try {
            const res = await fetch(base + '/api/customers');
            const data = await res.json();
            const sel = document.getElementById('customer_id');
            sel.innerHTML = '<option value="">اختر العميل</option>';
            (data.data || data || []).forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.id;
              opt.textContent = (c.code ? (c.code + ' - ') : '') + (c.name || '');
              sel.appendChild(opt);
            });
          } catch(e) {
            const sel = document.getElementById('customer_id');
            sel.innerHTML = '<option value="">تعذر تحميل العملاء</option>';
          }

          // 2) items
          let items = [];
          try {
            const res = await fetch(base + '/api/items');
            const data = await res.json();
            items = (data.data || data || []);
          } catch(e) {
            items = [];
          }

          document.querySelectorAll('.item-select').forEach(sel => {
            sel.innerHTML = '<option value="">-- اختر --</option>';
            items.forEach(it => {
              const opt = document.createElement('option');
              opt.value = it.id;
              // لو عندك sku
              opt.textContent = (it.sku ? (it.sku + ' - ') : '') + (it.name || '');
              sel.appendChild(opt);
            });
          });
        })();
        </script>
        <?php
        require APP_PATH . '/Views/footer.php';
    }

    /**
     * POST /sales-invoices/store
     */
    public function store(): void
    {
        try {
            $customerId  = (int)($_POST['customer_id'] ?? 0);
            $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
            $notes       = trim($_POST['notes'] ?? '');
            $linesPost   = $_POST['lines'] ?? [];

            if ($customerId <= 0) {
                throw new Exception('العميل مطلوب');
            }

            $lines = [];
            foreach ($linesPost as $l) {
                $itemId    = (int)($l['item_id'] ?? 0);
                $qty       = (float)($l['qty'] ?? 0);
                $unitPrice = (float)($l['unit_price'] ?? 0);

                if ($itemId > 0 && $qty > 0) {
                    $lines[] = [
                        'item_id'     => $itemId,
                        'description' => '',
                        'qty'         => $qty,
                        'unit_price'  => $unitPrice,
                        // vat_rate يترك للموديل (افتراضي 0.16)
                    ];
                }
            }

            $model = new SalesInvoice();
            $invoiceId = $model->create([
                'customer_id'  => $customerId,
                'invoice_date' => $invoiceDate,
                'notes'        => $notes,
                'vat_rate'     => 0.16,
            ], $lines);

            header('Location: /20/accounting/acc1/public/sales-invoices/show?id=' . $invoiceId);
            exit;
        } catch (Exception $e) {
            header('Location: /20/accounting/acc1/public/sales-invoices/new?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * POST /sales-invoices/post
     */
    public function post(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /20/accounting/acc1/public/sales-invoices?error=invalid_id');
            exit;
        }

        $model = new SalesInvoice();
        $model->markPosted($id);

        // جهّز UUID + QR بعد الترحيل
        try {
            $model->ensureUuidAndQr($id);
        } catch (\Throwable $t) {
            // لا نوقف العملية لو فشل QR
        }

        header('Location: /20/accounting/acc1/public/sales-invoices/show?id=' . $id);
        exit;
    }

    /**
     * GET /sales-invoices/show?id=1
     */
    public function show(): void
    {
        $this->renderInvoice(false);
    }

    /**
     * GET /sales-invoices/print?id=1
     */
    public function print(): void
    {
        $this->renderInvoice(true);
    }

    private function renderInvoice(bool $printMode): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $title = 'فاتورة مبيعات';
            $active = 'sales';
            require APP_PATH . '/Views/layout.php';
            echo "<div class='card'>رقم الفاتورة غير صالح</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        $model = new SalesInvoice();

        $inv = $model->findById($id);
        if (!$inv) {
            $title = 'فاتورة مبيعات';
            $active = 'sales';
            require APP_PATH . '/Views/layout.php';
            echo "<div class='card'>الفاتورة غير موجودة</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        $lines = $model->getLines($id);

        // فقط لو Posted نضمن UUID+QR (حتى لا نولّد QR لمسودة)
        if (($inv['status'] ?? '') === 'posted') {
            $model->ensureUuidAndQr($id);
            $inv = $model->findById($id);
        }

        $qrSrc = null;
        if (!empty($inv['qr_payload'])) {
            $sellerName  = 'اسم المنشأة لديك هنا';
            $sellerTaxNo = 'رقم الضريبة لديك هنا';

            $qrService = new JordanEInvoicingQR($sellerName, $sellerTaxNo);
            $key = $inv['uuid'] ?: $inv['invoice_no'];
            $qrSrc = $qrService->generatePngUrl($inv['qr_payload'], $key);
        }

        $title = 'فاتورة مبيعات: ' . ($inv['invoice_no'] ?? '');
        $active = 'sales';

        require APP_PATH . '/Views/layout.php';
        ?>

<style>
  .invoice-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
  .invoice-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
  .meta{background:#f1f5f9;border:1px solid var(--border);padding:6px 10px;border-radius:12px;font-weight:800;font-size:.85rem}
  .invoice-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}

  <?php if ($printMode): ?>
  header.top { display:none !important; }
  body { background:#fff !important; }
  .page { padding:0 !important; max-width:none !important; }
  .card { box-shadow:none !important; border:1px solid #e5e7eb; }
  .no-print { display:none !important; }
  <?php endif; ?>

  @media print {
    header.top { display:none !important; }
    body { background:#fff !important; }
    .page { padding:0 !important; max-width:none !important; }
    .card { box-shadow:none !important; border:1px solid #e5e7eb; }
    .no-print { display:none !important; }
  }
</style>

<div class="card">
  <div class="invoice-head">
    <div>
      <div class="h1">فاتورة مبيعات: <?= View::e($inv['invoice_no']) ?></div>
      <div class="sub">
        التاريخ: <?= View::e($inv['invoice_date']) ?> |
        الحالة: <?= View::e($inv['status']) ?>
      </div>

      <div class="invoice-meta">
        <div class="meta">العميل: <?= View::e($inv['customer_name'] ?? '-') ?></div>
        <div class="meta">UUID: <span class="num"><?= View::e($inv['uuid'] ?? '-') ?></span></div>
      </div>

      <div class="invoice-actions no-print">
        <?php if (($inv['status'] ?? '') === 'draft'): ?>
          <form method="post" action="/20/accounting/acc1/public/sales-invoices/post" style="display:inline">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <button class="btn" type="submit">ترحيل (Post)</button>
          </form>
        <?php endif; ?>

        <button class="btn-outline" type="button" onclick="window.print()">طباعة</button>
        <a class="btn-outline" href="/20/accounting/acc1/public/sales-invoices/print?id=<?= View::e($id) ?>" target="_blank">فتح وضع الطباعة</a>
        <a class="btn-outline" href="/20/accounting/acc1/public/sales-invoices">رجوع</a>
      </div>
    </div>

    <?php if ($qrSrc): ?>
      <div style="text-align:center">
        <img src="<?= View::e($qrSrc) ?>" alt="QR" style="width:150px;height:150px;border:1px solid var(--border);border-radius:12px;padding:6px;background:#fff">
        <div class="sub" style="font-size:.75rem;margin-top:6px">QR (الفوترة الأردنية)</div>
      </div>
    <?php endif; ?>
  </div>

  <table class="table" style="margin-top:16px">
    <thead>
      <tr>
        <th>#</th>
        <th>الصنف</th>
        <th>الوصف</th>
        <th class="num">الكمية</th>
        <th class="num">سعر الوحدة</th>
        <th class="num">نسبة VAT</th>
        <th class="num">VAT</th>
        <th class="num">الإجمالي</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($lines as $i => $l): ?>
      <tr>
        <td class="num"><?= $i + 1 ?></td>
        <td><?= View::e($l['item_name'] ?? '-') ?></td>
        <td><?= View::e($l['description'] ?? '') ?></td>
        <td class="num"><?= View::money($l['qty'] ?? 0) ?></td>
        <td class="num"><?= View::money($l['unit_price'] ?? 0) ?></td>
        <td class="num"><?= View::money(((float)($l['vat_rate'] ?? 0))*100, 2) ?>%</td>
        <td class="num"><?= View::money($l['vat_amount'] ?? 0) ?></td>
        <td class="num"><?= View::money($l['line_total'] ?? 0) ?></td>
      </tr>
      <?php endforeach; ?>

      <tr style="font-weight:900;background:#f8fafc">
        <td colspan="7">الإجمالي قبل الضريبة</td>
        <td class="num"><?= View::money($inv['subtotal'] ?? 0) ?></td>
      </tr>
      <tr style="font-weight:900;background:#f8fafc">
        <td colspan="7">الضريبة (VAT)</td>
        <td class="num"><?= View::money($inv['vat_total'] ?? 0) ?></td>
      </tr>
      <tr style="font-weight:900;background:#eef2ff">
        <td colspan="7">الإجمالي الكلي</td>
        <td class="num"><?= View::money($inv['total'] ?? 0) ?></td>
      </tr>
    </tbody>
  </table>

  <?php if (!empty($inv['notes'])): ?>
    <div class="sub" style="margin-top:10px">ملاحظات: <?= View::e($inv['notes']) ?></div>
  <?php endif; ?>
</div>

<?php
        require APP_PATH . '/Views/footer.php';
    }
}