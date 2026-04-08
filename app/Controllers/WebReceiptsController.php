<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Customer;
use App\Models\Receipt;
use App\Services\PostingService;

class WebReceiptsController
{
    private string $base = '/20/accounting/acc1/public';

    /** GET /receipts */
    public function index(): void
    {
        $title  = 'سندات القبض';
        $active = 'receipts';
        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>سندات القبض</div><div class='sub'>تسجيل المبالغ المستلمة من العملاء.</div></div>";

        // Add form
        echo "<div class='card'>
                <div class='pill' style='margin-bottom:12px'>إضافة سند قبض</div>";
        try {
            $customers = (new Customer())->getAll(true);
            $custOpts  = "<option value=''>-- بدون عميل --</option>";
            foreach ($customers as $c) {
                $custOpts .= "<option value='" . View::e($c['id']) . "'>" . View::e($c['name']) . "</option>";
            }
            echo "<form method='POST' action='" . View::e($this->base . '/receipts') . "'>
                    <div class='row'>
                      <div class='field'><label>العميل</label><select name='customer_id'>$custOpts</select></div>
                      <div class='field'><label>التاريخ *</label><input type='date' name='receipt_date' value='" . date('Y-m-d') . "' required></div>
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
        echo "<div class='card'><div class='pill' style='margin-bottom:12px'>قائمة سندات القبض</div>";
        try {
            $model = new Receipt();
            $rows  = $model->list();
            if (empty($rows)) {
                echo "<p class='sub'>لا توجد سندات حتى الآن.</p>";
            } else {
                echo "<table class='table'>
                        <thead><tr>
                          <th>رقم السند</th><th>التاريخ</th><th>العميل</th>
                          <th class='num'>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>إجراء</th>
                        </tr></thead><tbody>";
                foreach ($rows as $r) {
                    $statusLabel = $this->statusLabel($r['status']);
                    $methodLabel = ($r['method'] === 'bank') ? 'بنك' : 'نقد';
                    $postBtn = '';
                    if ($r['status'] === 'draft') {
                        $postBtn = "<form method='POST' action='" . View::e($this->base . '/receipts/post') . "' style='display:inline'>
                                      <input type='hidden' name='id' value='" . View::e($r['id']) . "'>
                                      <button class='btn' type='submit' onclick=\"return confirm('ترحيل سند القبض؟')\">ترحيل</button>
                                    </form>";
                    }
                    echo "<tr>
                            <td>" . View::e($r['receipt_no']) . "</td>
                            <td class='num'>" . View::e($r['receipt_date']) . "</td>
                            <td>" . View::e($r['customer_name'] ?? '') . "</td>
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

    /** POST /receipts */
    public function store(): void
    {
        $data = [
            'customer_id'  => (int)($_POST['customer_id'] ?? 0) ?: null,
            'receipt_date' => trim($_POST['receipt_date'] ?? date('Y-m-d')),
            'amount'       => (float)($_POST['amount']    ?? 0),
            'method'       => $_POST['method']    ?? 'cash',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        try {
            $model = new Receipt();
            $model->create($data);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/receipts');
        exit;
    }

    /** POST /receipts/post */
    public function postReceipt(): void
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        try {
            $service = new PostingService();
            $service->postReceipt($id);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/receipts');
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
