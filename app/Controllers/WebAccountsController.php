<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Account;

class WebAccountsController
{
    public function index(): void
    {
        $title = 'دليل الحسابات';
        $active = 'accounts';

        require APP_PATH . '/Views/layout.php';

        echo "<div><div class='h1'>دليل الحسابات</div><div class='sub'>عرض الحسابات وإضافة حساب جديد.</div></div>";

        try {
            $model = new Account();
            $accounts = $model->getAll();

            echo "<div class='card'>
                    <div class='pill'>إضافة حساب جديد</div>

                    <div class='row' style='margin-top:12px'>
                      <div class='field'><label>الكود</label><input id='code' placeholder='مثال: 4-10'></div>
                      <div class='field'><label>اسم الحساب (عربي)</label><input id='name_ar' placeholder='مثال: مبيعات'></div>

                      <div class='field'>
                        <label>نوع الحساب</label>
                        <select id='account_type'>
                          <option value='asset'>أصل</option>
                          <option value='liability'>التزام</option>
                          <option value='equity'>حقوق ملكية</option>
                          <option value='revenue'>إيراد</option>
                          <option value='expense'>مصروف</option>
                          <option value='other'>أخرى</option>
                        </select>
                      </div>

                      <div class='field'>
                        <label>الحساب الأب (اختياري)</label>
                        <select id='parent_id'>
                          <option value=''>-- بدون --</option>";
            foreach ($accounts as $a) {
                $txt = $a['code'] . ' - ' . $a['name_ar'];
                echo "<option value='" . View::e($a['id']) . "'>" . View::e($txt) . "</option>";
            }
            echo "      </select>
                      </div>

                      <div class='field'>
                        <label>sub_type (اختياري)</label>
                        <input id='sub_type' placeholder='cash, bank, sales...'>
                      </div>
                    </div>

                    <div class='row' style='margin-top:12px;justify-content:space-between;align-items:center'>
                      <button class='btn' type='button' id='saveAcc'>حفظ الحساب</button>
                      <div class='pill' id='msg' style='display:none'></div>
                    </div>
                  </div>";

            echo "<div class='card'>
                    <div class='pill'>قائمة الحسابات</div>
                    <table class='table'>
                      <thead>
                        <tr>
                          <th class='num'>ID</th>
                          <th>الكود</th>
                          <th>الاسم</th>
                          <th>النوع</th>
                          <th class='num'>Parent</th>
                          <th class='num'>Postable</th>
                          <th class='num'>Active</th>
                        </tr>
                      </thead>
                      <tbody>";
            foreach ($accounts as $a) {
                echo "<tr>
                        <td class='num'>" . View::e($a['id']) . "</td>
                        <td>" . View::e($a['code']) . "</td>
                        <td>" . View::e($a['name_ar']) . "</td>
                        <td>" . View::e($a['account_type']) . "</td>
                        <td class='num'>" . View::e($a['parent_id']) . "</td>
                        <td class='num'>" . View::e($a['is_postable'] ?? '') . "</td>
                        <td class='num'>" . View::e($a['is_active']) . "</td>
                      </tr>";
            }
            echo "    </tbody></table></div>";

        } catch (\Throwable $e) {
            echo "<div class='card'>خطأ: " . View::e($e->getMessage()) . "</div>";
        }

        ?>
<script>
const API_CREATE = '/20/accounting/acc1/public/api/accounts';

function showMsg(text, ok){
  const msg = document.getElementById('msg');
  msg.style.display = 'inline-block';
  msg.textContent = text;

  if(ok){
    msg.style.background = '#dcfce7';
    msg.style.color = '#166534';
    msg.style.border = '1px solid #bbf7d0';
  }else{
    msg.style.background = '#fee2e2';
    msg.style.color = '#991b1b';
    msg.style.border = '1px solid #fecaca';
  }
}

document.getElementById('saveAcc').addEventListener('click', async () => {
  const code = document.getElementById('code').value.trim();
  const name_ar = document.getElementById('name_ar').value.trim();
  const account_type = document.getElementById('account_type').value;
  const parentRaw = document.getElementById('parent_id').value;
  const sub_type = document.getElementById('sub_type').value.trim();

  if(!code) return showMsg('أدخل الكود', false);
  if(!name_ar) return showMsg('أدخل اسم الحساب (عربي)', false);

  const payload = {
    code,
    name_ar,
    account_type,
    parent_id: parentRaw ? Number(parentRaw) : null,
    sub_type: sub_type || null,
    is_active: 1
    // is_postable سيُحدد تلقائياً بالسيرفر حسب وجود parent
  };

  const res = await fetch(API_CREATE, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });

  let j = {};
  try { j = await res.json(); } catch(e){}

  if (!res.ok || j.status !== 'success') {
    return showMsg('خطأ: ' + (j.message || 'فشل إنشاء الحساب'), false);
  }

  showMsg('تم إنشاء الحساب بنجاح. account_id=' + j.account_id + ' — سيتم تحديث الصفحة...', true);

  setTimeout(() => window.location.reload(), 700);
});
</script>
<?php

        require APP_PATH . '/Views/footer.php';
    }
}