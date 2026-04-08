<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Supplier;

class WebSuppliersController
{
    private string $base = '/20/accounting/acc1/public';

    public function index(): void
    {
        $title  = 'الموردون';
        $active = 'suppliers';
        require APP_PATH . '/Views/layout.php';
        ?>
<div>
  <div class="h1">الموردون</div>
  <div class="sub">إدارة بيانات الموردين.</div>
</div>

<!-- نموذج إضافة مورد -->
<div class="card">
  <div class="pill" style="margin-bottom:12px">إضافة مورد جديد</div>
  <form method="POST" action="<?= View::e($this->base . '/suppliers') ?>">
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

<!-- قائمة الموردين -->
<div class="card">
  <div class="pill" style="margin-bottom:12px">قائمة الموردين</div>
  <?php
        try {
            $model = new Supplier();
            $rows  = $model->getAll();
            if (empty($rows)) {
                echo "<p class='sub'>لا يوجد موردون حتى الآن.</p>";
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
            $model = new Supplier();
            $model->create($data);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/suppliers');
        exit;
    }
}
