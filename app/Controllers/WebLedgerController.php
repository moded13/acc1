<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Account;
use App\Models\LedgerReport;

class WebLedgerController
{
    public function index(): void
    {
        $title = 'كشف حساب';
        $active = 'ledger';

        require APP_PATH . '/Views/layout.php';

        $accountId = (int)($_GET['account_id'] ?? 0);
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to'] ?? '';

        // افتراضات لطيفة
        if ($from === '' && $to === '') {
            $from = date('Y-m-01');
            $to   = date('Y-m-t');
        }

        echo "<div>
                <div class='h1'>كشف حساب</div>
                <div class='sub'>اختر حساباً وحدد الفترة لعرض الحركات والرصيد الجاري.</div>
              </div>";

        // نموذج الفلاتر
        try {
            $accModel = new Account();
            $accounts = $accModel->getFlatActive();

            echo "<div class='card'>
                    <form method='GET'>
                      <div class='row'>
                        <div class='field'>
                          <label>الحساب</label>
                          <select name='account_id' required>";

            echo "<option value=''>-- اختر الحساب --</option>";
            foreach ($accounts as $a) {
                $sel = ($accountId === (int)$a['id']) ? "selected" : "";
                $txt = $a['code'] . " - " . $a['name_ar'];
                echo "<option value='" . View::e($a['id']) . "' $sel>" . View::e($txt) . "</option>";
            }

            echo "        </select>
                        </div>

                        <div class='field' style='max-width:260px'>
                          <label>من تاريخ</label>
                          <input type='date' name='from' value='" . View::e($from) . "'>
                        </div>

                        <div class='field' style='max-width:260px'>
                          <label>إلى تاريخ</label>
                          <input type='date' name='to' value='" . View::e($to) . "'>
                        </div>

                        <button class='btn' type='submit'>عرض</button>
                        <a class='btn-outline' href='/20/accounting/acc1/public/reports/ledger'>مسح</a>
                      </div>
                    </form>
                  </div>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ في تحميل الحسابات: " . View::e($e->getMessage()) . "</div>";
            require APP_PATH . '/Views/footer.php';
            return;
        }

        // إذا لم يختر حساباً بعد، لا نعرض جدول
        if ($accountId <= 0) {
            require APP_PATH . '/Views/footer.php';
            return;
        }

        // جلب بيانات كشف الحساب
        try {
            $model = new LedgerReport();
            $data = $model->getAccountLedger($accountId, $from ?: null, $to ?: null);

            $acc = $data['account'];
            $lines = $data['lines'];

            $opening = (float)$data['opening_balance'];
            $closing = (float)$data['totals']['closing_balance'];

            $openingType = $opening >= 0 ? 'مدين' : 'دائن';
            $closingType = $closing >= 0 ? 'مدين' : 'دائن';

            echo "<div class='card'>
                    <div class='row' style='justify-content:space-between;align-items:center'>
                      <div class='pill'>الحساب: " . View::e($acc['code'] . ' - ' . $acc['name_ar']) . "</div>
                      <div class='pill'>الرصيد الافتتاحي: <span class='num'>" . View::money(abs($opening)) . "</span> ($openingType)</div>
                      <div class='pill'>الرصيد الختامي: <span class='num'>" . View::money(abs($closing)) . "</span> ($closingType)</div>
                    </div>

                    <table class='table'>
                      <thead>
                        <tr>
                          <th>التاريخ</th>
                          <th>رقم القيد</th>
                          <th>وصف القيد</th>
                          <th>وصف السطر</th>
                          <th class='num'>مدين</th>
                          <th class='num'>دائن</th>
                          <th class='num'>الرصيد الجاري</th>
                        </tr>
                      </thead>
                      <tbody>";

            if (empty($lines)) {
                echo "<tr><td colspan='7'>لا توجد حركات ضمن الفترة.</td></tr>";
            } else {
                foreach ($lines as $l) {
                    $rb = (float)$l['running_balance'];
                    $rbType = $rb >= 0 ? 'مدين' : 'دائن';

                    echo "<tr>
                            <td class='num'>" . View::e($l['entry_date']) . "</td>
                            <td>" . View::e($l['entry_no']) . "</td>
                            <td>" . View::e($l['entry_description']) . "</td>
                            <td>" . View::e($l['line_description']) . "</td>
                            <td class='num'>" . View::money($l['debit']) . "</td>
                            <td class='num'>" . View::money($l['credit']) . "</td>
                            <td class='num'>" . View::money(abs($rb)) . " ($rbType)</td>
                          </tr>";
                }
            }

            // إجماليات الفترة
            echo "<tr style='font-weight:900;background:#f8fafc'>
                    <td colspan='4'>إجمالي الفترة</td>
                    <td class='num'>" . View::money($data['totals']['period_debit']) . "</td>
                    <td class='num'>" . View::money($data['totals']['period_credit']) . "</td>
                    <td></td>
                  </tr>";

            echo "    </tbody>
                    </table>
                  </div>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ في إنشاء كشف الحساب: " . View::e($e->getMessage()) . "</div>";
        }

        require APP_PATH . '/Views/footer.php';
    }
}