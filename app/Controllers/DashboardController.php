<?php
namespace App\Controllers;

use App\Core\View;

class DashboardController
{
    public function index(): void
    {
        $title = 'لوحة التحكم';
        $active = 'dashboard';

        require APP_PATH . '/Views/layout.php';

        $base = '/20/accounting/acc1/public';
        ?>
<div>
  <div class="h1">لوحة التحكم</div>
  <div class="sub">مؤشرات وتشغيل سريع للأقسام الأساسية.</div>
</div>

<div class="card">
  <div class="row" style="justify-content:space-between;align-items:center">
    <div class="pill">روابط سريعة</div>
  </div>

  <div class="row" style="margin-top:12px">
    <a class="btn" href="<?= View::e($base . '/entry/new') ?>">+ إدخال قيد</a>
    <a class="btn-outline" href="<?= View::e($base . '/journal-entries') ?>">عرض القيود</a>
    <a class="btn-outline" href="<?= View::e($base . '/reports/trial-balance') ?>">ميزان المراجعة</a>
    <a class="btn-outline" href="<?= View::e($base . '/reports/income-statement') ?>">قائمة الدخل</a>
    <a class="btn-outline" href="<?= View::e($base . '/reports/balance-sheet') ?>">الميزانية</a>
    <a class="btn-outline" href="<?= View::e($base . '/reports/ledger') ?>">كشف حساب</a>
  </div>
</div>

<div class="card">
  <div class="row" style="justify-content:space-between;align-items:center">
    <div class="pill">مؤشرات الشهر (من قاعدة البيانات)</div>
    <div class="sub" style="margin:0">يتم جلبها من API</div>
  </div>

  <div class="row" style="margin-top:12px">
    <div class="card" style="margin-top:0;flex:1;min-width:220px">
      <div class="sub" style="margin:0">قيود الشهر</div>
      <div class="h1 num" id="je_month" style="font-size:1.8rem">-</div>
    </div>
    <div class="card" style="margin-top:0;flex:1;min-width:220px">
      <div class="sub" style="margin:0">إجمالي مدين الشهر</div>
      <div class="h1 num" id="debit_month" style="font-size:1.8rem">-</div>
    </div>
    <div class="card" style="margin-top:0;flex:1;min-width:220px">
      <div class="sub" style="margin:0">إجمالي دائن الشهر</div>
      <div class="h1 num" id="credit_month" style="font-size:1.8rem">-</div>
    </div>
    <div class="card" style="margin-top:0;flex:1;min-width:220px">
      <div class="sub" style="margin:0">صافي التدفق النقدي (شهر)</div>
      <div class="h1 num" id="cashflow_month" style="font-size:1.8rem">-</div>
    </div>
  </div>
</div>

<script>
(async function(){
  try{
    const res = await fetch('<?= View::e($base . "/api/reports/dashboard-metrics") ?>');
    const j = await res.json();
    if(j.status !== 'success') return;

    const d = j.data;
    const fmt = (n) => (Number(n||0)).toFixed(2);

    document.getElementById('je_month').textContent = d.counts.journal_entries_month ?? 0;
    document.getElementById('debit_month').textContent = fmt(d.totals.month_debit);
    document.getElementById('credit_month').textContent = fmt(d.totals.month_credit);
    document.getElementById('cashflow_month').textContent = fmt(d.cashflow.net_cashflow_month);
  }catch(e){}
})();
</script>
<?php
        require APP_PATH . '/Views/footer.php';
    }
}