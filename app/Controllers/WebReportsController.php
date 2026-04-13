<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\TrialBalance;
use App\Models\FinancialStatements;
use Exception;

class WebReportsController
{
    private function headerHtml(string $title): void
    {
        echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>" . View::e($title) . "</title>
<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
<link href=\"https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">
<style>
:root{
  --bg:#f3f5fb;--nav:#020617;--card:#fff;--primary:#2563eb;--muted:#6b7280;
  --shadow:0 18px 45px rgba(15,23,42,.10);--r:18px;--border:#e5e7eb;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Cairo',Tahoma,Arial;background:var(--bg);color:#0f172a}
.top{background:linear-gradient(to left,#020617,#111827);color:#e5e7eb;padding:10px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 6px 18px rgba(15,23,42,.45)}
.brand{display:flex;align-items:center;gap:10px;font-weight:800}
.badge{background:#1d4ed8;padding:4px 10px;border-radius:999px;font-size:.72rem}
a{color:#e5e7eb;text-decoration:none}
.page{padding:22px 28px 40px;max-width:1200px;margin:0 auto}
.h1{font-size:1.6rem;font-weight:900;margin:0}
.sub{color:var(--muted);margin-top:6px}
.card{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);padding:16px 18px;margin-top:16px}
.row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
.field{display:flex;flex-direction:column;gap:6px}
label{font-size:.85rem;color:var(--muted)}
input{padding:10px 12px;border:1px solid var(--border);border-radius:12px;font-family:'Cairo',Tahoma,Arial}
.btn{background:var(--primary);border:0;color:#fff;padding:10px 14px;border-radius:12px;font-weight:800;cursor:pointer}
.btn2{background:#0f172a;border:0;color:#fff;padding:10px 14px;border-radius:12px;font-weight:800;cursor:pointer}
.table{width:100%;border-collapse:separate;border-spacing:0;margin-top:10px;overflow:hidden;border-radius:14px;border:1px solid var(--border)}
.table th{background:#f8fafc;color:#111827;text-align:right;font-size:.85rem;padding:10px;border-bottom:1px solid var(--border)}
.table td{padding:10px;border-bottom:1px solid var(--border);font-size:.9rem}
.table tr:last-child td{border-bottom:0}
.num{text-align:left;direction:ltr;font-variant-numeric:tabular-nums}
.footer-total{font-weight:900;background:#f8fafc}
.pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.pill{background:#eef2ff;color:#1d4ed8;border:1px solid #dbeafe;padding:6px 12px;border-radius:999px;font-weight:800;font-size:.8rem}
</style>
</head>
<body>
<header class='top'>
  <div class='brand'><a href='/20/accounting/acc1/public/dashboard'>Enterprise ERP</a> <span class='badge'>ERP</span></div>
  <div class='pills'>
    <a class='pill' href='/20/accounting/acc1/public/reports/trial-balance'>ميزان المراجعة</a>
    <a class='pill' href='/20/accounting/acc1/public/reports/income-statement'>قائمة الدخل</a>
    <a class='pill' href='/20/accounting/acc1/public/reports/balance-sheet'>الميزانية</a>
  </div>
</header>
<main class='page'>
";
    }

    private function footerHtml(): void
    {
        echo "</main></body></html>";
    }

    public function trialBalancePage(): void
    {
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to'] ?? '';

        $this->headerHtml('ميزان المراجعة');

        echo "<div>
          <div class='h1'>ميزان المراجعة</div>
          <div class='sub'>يعرض أرصدة الحسابات للفترة المحددة (افتتاحي + حركات + ختامي).</div>
        </div>";

        echo "<div class='card'>
          <form method='GET'>
            <div class='row'>
              <div class='field'>
                <label>من تاريخ</label>
                <input type='date' name='from' value='" . View::e($from) . "'>
              </div>
              <div class='field'>
                <label>إلى تاريخ</label>
                <input type='date' name='to' value='" . View::e($to) . "'>
              </div>
              <button class='btn' type='submit'>عرض</button>
              <a class='btn2' href='/20/accounting/acc1/public/reports/trial-balance'>مسح الفلاتر</a>
            </div>
          </form>
        </div>";

        try {
            $model = new TrialBalance();
            $rows = $model->get($from ?: null, $to ?: null);

            echo "<div class='card'>
              <table class='table'>
                <thead>
                  <tr>
                    <th>الكود</th>
                    <th>اسم الحساب</th>
                    <th class='num'>افتتاحي</th>
                    <th class='num'>حركات (مدين)</th>
                    <th class='num'>حركات (دائن)</th>
                    <th class='num'>��تامي (مدين)</th>
                    <th class='num'>ختامي (دائن)</th>
                  </tr>
                </thead>
                <tbody>";

            $sumOpen = 0; $sumPd = 0; $sumPc = 0; $sumCd = 0; $sumCc = 0;

            foreach ($rows as $r) {
                $openNet = (float)$r['opening_debit'] - (float)$r['opening_credit'];
                $sumOpen += $openNet;
                $sumPd += (float)$r['period_debit'];
                $sumPc += (float)$r['period_credit'];
                $sumCd += (float)$r['closing_debit'];
                $sumCc += (float)$r['closing_credit'];

                echo "<tr>
                  <td>" . View::e($r['code']) . "</td>
                  <td>" . View::e($r['name_ar']) . "</td>
                  <td class='num'>" . View::money($openNet) . "</td>
                  <td class='num'>" . View::money($r['period_debit']) . "</td>
                  <td class='num'>" . View::money($r['period_credit']) . "</td>
                  <td class='num'>" . View::money($r['closing_debit']) . "</td>
                  <td class='num'>" . View::money($r['closing_credit']) . "</td>
                </tr>";
            }

            echo "<tr class='footer-total'>
              <td colspan='2'>الإجمالي</td>
              <td class='num'>" . View::money($sumOpen) . "</td>
              <td class='num'>" . View::money($sumPd) . "</td>
              <td class='num'>" . View::money($sumPc) . "</td>
              <td class='num'>" . View::money($sumCd) . "</td>
              <td class='num'>" . View::money($sumCc) . "</td>
            </tr>";

            echo "</tbody></table></div>";
        } catch (Exception $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        $this->footerHtml();
    }

    public function incomeStatementPage(): void
    {
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-t');

        $this->headerHtml('قائمة الدخل');

        echo "<div>
          <div class='h1'>قائمة الدخل</div>
          <div class='sub'>إيرادات ومصروفات وصافي الربح للفترة.</div>
        </div>";

        echo "<div class='card'>
          <form method='GET'>
            <div class='row'>
              <div class='field'>
                <label>من تاريخ</label>
                <input type='date' name='from' value='" . View::e($from) . "'>
              </div>
              <div class='field'>
                <label>إلى تاريخ</label>
                <input type='date' name='to' value='" . View::e($to) . "'>
              </div>
              <button class='btn' type='submit'>عرض</button>
            </div>
          </form>
        </div>";

        try {
            $model = new FinancialStatements();
            $data = $model->incomeStatement($from, $to);

            echo "<div class='card'>
              <div class='h1' style='font-size:1.1rem;margin-bottom:8px'>الإيرادات</div>
              <table class='table'>
                <thead><tr><th>الكود</th><th>الحساب</th><th class='num'>المبلغ</th></tr></thead><tbody>";

            foreach ($data['revenue']['lines'] as $r) {
                echo "<tr>
                  <td>" . View::e($r['code']) . "</td>
                  <td>" . View::e($r['name_ar']) . "</td>
                  <td class='num'>" . View::money($r['amount']) . "</td>
                </tr>";
            }

            echo "<tr class='footer-total'><td colspan='2'>إجمالي الإيرادات</td><td class='num'>" . View::money($data['revenue']['total']) . "</td></tr>
              </tbody></table>
            </div>";

            echo "<div class='card'>
              <div class='h1' style='font-size:1.1rem;margin-bottom:8px'>المصروفات</div>
              <table class='table'>
                <thead><tr><th>الكود</th><th>الحساب</th><th class='num'>المبلغ</th></tr></thead><tbody>";

            foreach ($data['expenses']['lines'] as $r) {
                echo "<tr>
                  <td>" . View::e($r['code']) . "</td>
                  <td>" . View::e($r['name_ar']) . "</td>
                  <td class='num'>" . View::money($r['amount']) . "</td>
                </tr>";
            }

            echo "<tr class='footer-total'><td colspan='2'>إجمالي المصروفات</td><td class='num'>" . View::money($data['expenses']['total']) . "</td></tr>
              </tbody></table>
            </div>";

            echo "<div class='card'>
              <div class='h1' style='font-size:1.1rem'>صافي الربح</div>
              <div class='sub'>صافي الربح = الإيرادات - المصروفات</div>
              <div style='margin-top:10px;font-size:1.6rem;font-weight:900' class='num'>" . View::money($data['net_profit']) . "</div>
            </div>";

        } catch (Exception $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        $this->footerHtml();
    }

    public function balanceSheetPage(): void
    {
        $asOf = $_GET['as_of'] ?? date('Y-m-d');

        $this->headerHtml('الميزانية العمومية');

        echo "<div>
          <div class='h1'>الميزانية العمومية</div>
          <div class='sub'>أصول/خصوم/حقوق ملكية حتى تاريخ محدد.</div>
        </div>";

        echo "<div class='card'>
          <form method='GET'>
            <div class='row'>
              <div class='field'>
                <label>حتى تاريخ</label>
                <input type='date' name='as_of' value='" . View::e($asOf) . "'>
              </div>
              <button class='btn' type='submit'>عرض</button>
            </div>
          </form>
        </div>";

        try {
            $model = new FinancialStatements();
            $data = $model->balanceSheet($asOf);

            $renderSection = function(string $title, array $section) {
                echo "<div class='card'>
                  <div class='h1' style='font-size:1.1rem;margin-bottom:8px'>" . View::e($title) . "</div>
                  <table class='table'>
                    <thead><tr><th>الكود</th><th>الحساب</th><th class='num'>الرصيد</th></tr></thead><tbody>";

                foreach ($section['lines'] as $r) {
                    echo "<tr>
                      <td>" . View::e($r['code']) . "</td>
                      <td>" . View::e($r['name_ar']) . "</td>
                      <td class='num'>" . View::money($r['closing']) . "</td>
                    </tr>";
                }

                echo "<tr class='footer-total'><td colspan='2'>الإجمالي</td><td class='num'>" . View::money($section['total']) . "</td></tr>
                    </tbody></table>
                </div>";
            };

            $renderSection('الأصول', $data['assets']);
            $renderSection('الخصوم', $data['liabilities']);
            $renderSection('حقوق الملكية', $data['equity']);

            echo "<div class='card'>
              <div class='sub'>فحص التوازن (الأصول - (الخصوم + الحقوق)):</div>
              <div class='num' style='font-size:1.2rem;font-weight:900;margin-top:6px'>" . View::money($data['check']['assets_minus_liab_equity']) . "</div>
            </div>";

        } catch (Exception $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        $this->footerHtml();
    }
}