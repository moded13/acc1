<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\SalesInvoice;
use App\Services\JordanEInvoicingQR;

class WebSalesInvoicesController
{
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

        // اجلب الفاتورة
        $inv = $model->findById($id);
        if (!$inv) {
            $title = 'فاتورة مبيعات';
            $active = 'sales';
            require APP_PATH . '/Views/layout.php';
            echo "<div class='card'>الفاتورة غير موجودة</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        // اجلب السطور
        $lines = $model->getLines($id);

        // تأكد من uuid + qr_payload
        $model->ensureUuidAndQr($id);

        // أعد جلب الفاتورة بعد تحديث uuid/qr_payload
        $inv = $model->findById($id);

        // أنشئ صورة QR من payload
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
  /* تحسين عرض الفاتورة */
  .invoice-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
  .invoice-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
  .meta{background:#f1f5f9;border:1px solid var(--border);padding:6px 10px;border-radius:12px;font-weight:800;font-size:.85rem}
  .invoice-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}

  /* وضع الطباعة */
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
        <button class="btn" type="button" onclick="window.print()">طباعة</button>
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