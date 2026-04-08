<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Supplier;
use App\Models\Payment;
use App\Services\PostingService;

class WebPaymentsController
{
    private string $base = '/20/accounting/acc1/public';

    /** GET /payments */
    public function index(): void
    {
        $title  = 'سندات الدفع';
        $active = 'payments';
        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>سندات الدفع</div><div class='sub'>تسجيل المبالغ المدفوعة للموردين.</div></div>";

        // Add form
        echo "<div class='card'>
                <div class='pill' style='margin-bottom:12px'>إضافة سند دفع</div>";
        try {
            $suppliers = (new Supplier())->getAll(true);
            $supOpts   = "<option value=''>-- بدون مورد --</option>";
            foreach ($suppliers as $s) {
                $supOpts .= "<option value='" . View::e($s['id']) . "'>" . View::e($s['name']) . "</option>";
            }
            echo "<form method='POST' action='" . View::e($this->base . '/payments') . "'>
                    <div class='row'>
                      <div class='field'><label>المورد</label><select name='supplier_id'>$supOpts</select></div>
                      <div class='field'><label>التاريخ *</label><input type='date' name='payment_date' value='" . date('Y-m-d') . "' required></div>
                      <div class='field'><label>المبلغ *</label><input type='number' step='0.01' name='amount' required></div>
                      <div class='field'><label>طريقة الدفع</label>
                        <select name='method'>
                          <option value='cash'>نقد</option>
                          <option value='bank'>بنك</option>
                        </select>
                      </div>
                      <div class='field'><label>ملاحظات</label><input type='text' name='notes'></div>
                      <button class='btn' type='submit'>حفظ</button>
                    </div>
                  </form>";
        } catch (\Throwable $e) {
            echo "<p class='sub' style='color:red'>خطأ: " . View::e($e->getMessage()) . "</p>";
        }
        echo "</div>";

        // List
        echo "<div class='card'><div class='pill' style='margin-bottom:12px'>قائمة سندات الدفع</div>";
        try {
            $model = new Payment();
            $rows  = $model->list();
            if (empty($rows)) {
                echo "<p class='sub'>لا توجد سندات حتى الآن.</p>";
            } else {
                echo "<table class='table'>
                        <thead><tr>
                          <th>رقم السند</th><th>التاريخ</th><th>المورد</th>
                          <th class='num'>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>إجراء</th>
                        </tr></thead><tbody>";
                foreach ($rows as $r) {
                    $statusLabel = $this->statusLabel($r['status']);
                    $methodLabel = ($r['method'] === 'bank') ? 'بنك' : 'نقد';
                    $postBtn = '';
                    if ($r['status'] === 'draft') {
                        $postBtn = "<form method='POST' action='" . View::e($this->base . '/payments/post') . "' style='display:inline'>
                                      <input type='hidden' name='id' value='" . View::e($r['id']) . "'>
                                      <button class='btn' type='submit' onclick=\"return confirm('ترحيل سند الدفع؟')\">ترحيل</button>
                                    </form>";
                    }
                    echo "<tr>
                            <td>" . View::e($r['payment_no']) . "</td>
                            <td class='num'>" . View::e($r['payment_date']) . "</td>
                            <td>" . View::e($r['supplier_name'] ?? '') . "</td>
                            <td class='num'>" . View::money($r['amount']) . "</td>
                            <td>" . View::e($methodLabel) . "</td>
                            <td>" . View::e($statusLabel) . "</td>
                            <td>$postBtn</td>
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

    /** POST /payments */
    public function store(): void
    {
        $data = [
            'supplier_id'  => (int)($_POST['supplier_id'] ?? 0) ?: null,
            'payment_date' => trim($_POST['payment_date'] ?? date('Y-m-d')),
            'amount'       => (float)($_POST['amount']    ?? 0),
            'method'       => $_POST['method']    ?? 'cash',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        try {
            $model = new Payment();
            $model->create($data);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/payments');
        exit;
    }

    /** POST /payments/post */
    public function postPayment(): void
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        try {
            $service = new PostingService();
            $service->postPayment($id);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/payments');
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
