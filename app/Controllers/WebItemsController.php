<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Item;

class WebItemsController
{
    private string $base = '/20/accounting/acc1/public';

    public function index(): void
    {
        $title  = 'الأصناف';
        $active = 'items';
        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>الأصناف والمنتجات</div><div class='sub'>إدارة الأصناف والمواد.</div></div>";

        // Add form
        echo "<div class='card'>
                <div class='pill' style='margin-bottom:12px'>إضافة صنف جديد</div>
                <form method='POST' action='" . View::e($this->base . '/items') . "'>
                  <div class='row'>
                    <div class='field'><label>الكود (SKU) *</label><input type='text' name='sku' required></div>
                    <div class='field'><label>الاسم *</label><input type='text' name='name' required></div>
                    <div class='field'><label>الوحدة</label><input type='text' name='unit' value='وحدة'></div>
                  </div>
                  <div class='row' style='margin-top:8px'>
                    <div class='field'><label>سعر التكلفة</label><input type='number' step='0.0001' name='cost_price' value='0'></div>
                    <div class='field'><label>سعر البيع</label><input type='number' step='0.0001' name='sale_price' value='0'></div>
                    <div class='field'><label>نسبة الضريبة (مثال: 0.16)</label><input type='number' step='0.0001' name='vat_rate' value='0.16'></div>
                    <button class='btn' type='submit'>حفظ</button>
                  </div>
                </form>
              </div>";

        // List
        echo "<div class='card'><div class='pill' style='margin-bottom:12px'>قائمة الأصناف</div>";
        try {
            $model = new Item();
            $rows  = $model->getAll();
            if (empty($rows)) {
                echo "<p class='sub'>لا توجد أصناف حتى الآن.</p>";
            } else {
                echo "<table class='table'>
                        <thead><tr>
                          <th>#</th><th>SKU</th><th>الاسم</th><th>الوحدة</th>
                          <th class='num'>سعر التكلفة</th><th class='num'>سعر البيع</th><th class='num'>نسبة الضريبة</th><th>نشط</th>
                        </tr></thead><tbody>";
                foreach ($rows as $r) {
                    echo "<tr>
                            <td>" . View::e($r['id']) . "</td>
                            <td>" . View::e($r['sku']) . "</td>
                            <td>" . View::e($r['name']) . "</td>
                            <td>" . View::e($r['unit']) . "</td>
                            <td class='num'>" . View::money($r['cost_price']) . "</td>
                            <td class='num'>" . View::money($r['sale_price']) . "</td>
                            <td class='num'>" . View::money((float)$r['vat_rate'] * 100, 1) . "%</td>
                            <td>" . ($r['is_active'] ? '✔' : '✘') . "</td>
                          </tr>";
                }
                echo "</tbody></table>";
            }
        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }
        echo "</div>";

        require APP_PATH . '/Views/footer.php';
    }

    public function store(): void
    {
        $data = [
            'sku'        => trim($_POST['sku']        ?? ''),
            'name'       => trim($_POST['name']       ?? ''),
            'unit'       => trim($_POST['unit']       ?? 'وحدة'),
            'cost_price' => (float)($_POST['cost_price'] ?? 0),
            'sale_price' => (float)($_POST['sale_price'] ?? 0),
            'vat_rate'   => (float)($_POST['vat_rate']   ?? 0.16),
            'is_active'  => 1,
        ];
        try {
            $model = new Item();
            $model->create($data);
        } catch (\Throwable $e) {
            // redirect regardless
        }
        header('Location: ' . $this->base . '/items');
        exit;
    }
}
