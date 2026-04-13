<?php
namespace App\Services;

/**
 * Jordan E-Invoicing QR (نسخة مبسطة)
 *
 * - يبني payload (JSON) يحتوي البيانات الأساسية + UUID
 * - يولّد QR كصورة PNG داخل public/cache/qr
 * - يرجّع URL للصورة لاستخدامه في <img> (أفضل للطباعة من base64)
 */
class JordanEInvoicingQR
{
    protected string $sellerName;
    protected string $sellerTaxNo;

    public function __construct(string $sellerName, string $sellerTaxNo)
    {
        $this->sellerName  = $sellerName;
        $this->sellerTaxNo = $sellerTaxNo;
    }

    /**
     * بناء payload JSON للـ QR.
     *
     * @param array $invoice صف الفاتورة من sales_invoices (ويجب أن يحتوي uuid)
     */
    public function buildPayload(array $invoice): string
    {
        $data = [
            'seller_name'   => $this->sellerName,
            'seller_tax_no' => $this->sellerTaxNo,
            'invoice_no'    => $invoice['invoice_no'] ?? '',
            'invoice_date'  => $invoice['invoice_date'] ?? '',
            'total'         => (float)($invoice['total'] ?? 0),
            'vat_total'     => (float)($invoice['vat_total'] ?? 0),
            'uuid'          => $invoice['uuid'] ?? '',
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * توليد صورة QR كـ PNG داخل public/cache/qr وإرجاع URL للصورة.
     *
     * @param string $payload النص المراد ترميزه داخل QR
     * @param string $key مفتاح لتسمية الملف (يفضل uuid أو invoice_no)
     * @return string URL تحت /public (مثال: /20/accounting/acc1/public/cache/qr/<file>.png)
     */
    public function generatePngUrl(string $payload, string $key): string
    {
        // تحميل مكتبة phpqrcode
        require_once APP_PATH . '/Libraries/phpqrcode/qrlib.php';

        // مجلد public (يُفترض أن BASE_PATH و APP_PATH معرفين في public/index.php)
        $publicDir = BASE_PATH . '/public';

        // المسار داخل public
        $relDir = '/cache/qr';
        $absDir = $publicDir . $relDir;

        // إنشاء المجلد إذا غير موجود
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        // اسم ملف آمن
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$key);
        $safe = trim($safe, '_');
        if ($safe === '') {
            $safe = 'inv';
        }

        $file = $absDir . '/' . $safe . '.png';

        // توليد QR إلى ملف (إعادة الكتابة مسموحة)
        // QR_ECLEVEL_M: مستوى تصحيح متوسط
        // 5: حجم الموديول (نرفعها قليلاً ليكون واضح في الطباعة)
        // 2: هامش
        \QRcode::png($payload, $file, QR_ECLEVEL_M, 5, 2);

        // رجّع URL مناسب لمشروعك داخل /public
        return '/20/accounting/acc1/public' . $relDir . '/' . basename($file);
    }
}