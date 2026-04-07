<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\JournalEntry;

class WebJournalEntriesListController
{
    public function index(): void
    {
        $title = 'القيود اليومية';
        $active = 'je';

        require APP_PATH . '/Views/layout.php';

        echo "<div>
                <div class='h1'>القيود اليومية</div>
                <div class='sub'>عرض القيود المحفوظة مع الإجماليات.</div>
              </div>";

        try {
            $model = new JournalEntry();
            $rows = $model->listEntries(200);

            echo "<div class='card'>";
            echo "<div class='row' style='justify-content:space-between;align-items:center'>
                    <div class='pill'>عدد القيود: " . count($rows) . "</div>
                    <a class='btn' href='/20/accounting/acc1/public/entry/new'>+ إدخال قيد</a>
                  </div>";

            echo "<table class='table'>
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>رقم القيد</th>
                        <th>التاريخ</th>
                        <th>الوصف</th>
                        <th class='num'>إجمالي مدين</th>
                        <th class='num'>إجمالي دائن</th>
                        <th>تفاصيل</th>
                      </tr>
                    </thead>
                    <tbody>";

            foreach ($rows as $r) {
                $url = '/20/accounting/acc1/public/journal-entries/show?id=' . urlencode($r['id']);

                echo "<tr>
                        <td>" . View::e($r['id']) . "</td>
                        <td>" . View::e($r['entry_no']) . "</td>
                        <td class='num'>" . View::e($r['entry_date']) . "</td>
                        <td>" . View::e($r['description']) . "</td>
                        <td class='num'>" . View::money($r['total_debit']) . "</td>
                        <td class='num'>" . View::money($r['total_credit']) . "</td>
                        <td><a class='btn-outline' href='" . View::e($url) . "'>عرض</a></td>
                      </tr>";
            }

            echo "  </tbody>
                  </table>";

            echo "</div>";
        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        require APP_PATH . '/Views/footer.php';
    }

    public function show(): void
    {
        $title = 'تفاصيل القيد';
        $active = 'je';

        require APP_PATH . '/Views/layout.php';

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo "<div class='card'>id غير صحيح</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        try {
            $model = new JournalEntry();
            $data = $model->getEntryWithLines($id);

            $e = $data['entry'];
            $lines = $data['lines'];

            echo "<div>
                    <div class='h1'>تفاصيل القيد</div>
                    <div class='sub'>رقم القيد: " . View::e($e['entry_no']) . " | تاريخ: " . View::e($e['entry_date']) . "</div>
                  </div>";

            echo "<div class='card'>
                    <div class='row' style='justify-content:space-between;align-items:center'>
                      <div class='pill'>الوصف: " . View::e($e['description']) . "</div>
                      <a class='btn-outline' href='/20/accounting/acc1/public/journal-entries'>رجوع للقائمة</a>
                    </div>

                    <table class='table'>
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>الحساب</th>
                          <th>وصف السطر</th>
                          <th class='num'>مدين</th>
                          <th class='num'>دائن</th>
                        </tr>
                      </thead>
                      <tbody>";

            foreach ($lines as $l) {
                $acc = $l['account_code'] . ' - ' . $l['account_name'];
                echo "<tr>
                        <td>" . View::e($l['line_no']) . "</td>
                        <td>" . View::e($acc) . "</td>
                        <td>" . View::e($l['description']) . "</td>
                        <td class='num'>" . View::money($l['debit']) . "</td>
                        <td class='num'>" . View::money($l['credit']) . "</td>
                      </tr>";
            }

            echo "<tr style='font-weight:900;background:#f8fafc'>
                    <td colspan='3'>الإجمالي</td>
                    <td class='num'>" . View::money($data['totals']['debit']) . "</td>
                    <td class='num'>" . View::money($data['totals']['credit']) . "</td>
                  </tr>";

            echo "    </tbody>
                    </table>
                  </div>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        require APP_PATH . '/Views/footer.php';
    }
}