<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Customer;

class WebCustomersController
{
    private string $base = '/20/accounting/acc1/public';

    public function index(): void
    {
        $title  = 'العملاء';
        $active = 'customers';
        require APP_PATH . '/Views/layout.php';
        ?>
<div>
  <div class="h1">العملاء</div>
  <div class="sub">إدارة بيانات العملاء.</div>
</div>

<!-- نموذج إضافة عميل -->
<div class="card">
  <div class="pill" style="margin-bottom:12px">إضافة عميل جديد</div>
  <form method="POST" action="<?= View::e($this->base . '/customers') ?>">
    <div class="row">
      <div class="field"><label>الكود *</label><input type="text" name="code" required></div>
      <div class="field"><label>الاسم *</label><input type="text" name="name" required></div>
      <div class="field"><label>الهاتف</label><input type="text" name="phone"></div>
      <div class="field"><label>البريد الإلكتروني</label><input type="email" name="email"></div>
    </div>
    <div class="row" style="margin-top:8px">
      <div class="field"><label>العنوان</label><input type="text" name="address"></div>
      <div class="field"><label>الرقم الضريبي</label><input type="text" name="tax_no"></div>
      <button class="btn" type="submit">حفظ</button>
    </div>
  </form>
</div>

<!-- قائمة العملاء -->
<div class="card">
  <div class="pill" style="margin-bottom:12px">قائمة العملاء</div>
  <?php
        try {
            $model = new Customer();
            $rows  = $model->getAll();
            if (empty($rows)) {
                echo "<p class='sub'>لا يوجد عملاء حتى الآن.</p>";
            } else {
                echo "<table class='table'>
                        <thead>
                          <tr>
                            <th>#</th><th>الكود</th><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>الرقم الضريبي</th><th>نشط</th>
                          </tr>
                        </thead><tbody>";
                foreach ($rows as $r) {
                    echo "<tr>
                            <td>" . View::e($r['id']) . "</td>
                            <td>" . View::e($r['code']) . "</td>
                            <td>" . View::e($r['name']) . "</td>
                            <td>" . View::e($r['phone'] ?? '') . "</td>
                            <td>" . View::e($r['email'] ?? '') . "</td>
                            <td>" . View::e($r['tax_no'] ?? '') . "</td>
                            <td>" . ($r['is_active'] ? '✔' : '✘') . "</td>
                          </tr>";
                }
                echo "</tbody></table>";
            }
        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }
        ?>
</div>
<?php
        require APP_PATH . '/Views/footer.php';
    }

    public function store(): void
    {
        $data = [
            'code'      => trim($_POST['code']    ?? ''),
            'name'      => trim($_POST['name']    ?? ''),
            'phone'     => trim($_POST['phone']   ?? '') ?: null,
            'email'     => trim($_POST['email']   ?? '') ?: null,
            'address'   => trim($_POST['address'] ?? '') ?: null,
            'tax_no'    => trim($_POST['tax_no']  ?? '') ?: null,
            'is_active' => 1,
        ];
        try {
            $model = new Customer();
            $model->create($data);
        } catch (\Throwable $e) {
            // simple redirect with error ignored for now
        }
        header('Location: ' . $this->base . '/customers');
        exit;
    }
}
