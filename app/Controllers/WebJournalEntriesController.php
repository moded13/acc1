<?php
namespace App\Controllers;


use App\Core\View;

class WebJournalEntriesController
{
    public function newEntryForm(): void
    {
        $today = date('Y-m-d');

        $API_ACCOUNTS = '/20/accounting/acc1/public/api/accounts';
        $API_SAVE     = '/20/accounting/acc1/public/api/journal-entries';
        $API_LIST     = '/20/accounting/acc1/public/api/journal-entries';

        $title = 'إدخال قيد يومية';
        $active = 'entry';

        require APP_PATH . '/Views/layout.php';
        ?>

<style>
  .h1{font-size:1.6rem;font-weight:900;margin:0}
  .sub{color:var(--muted);margin-top:6px}
  .card{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);padding:16px 18px;margin-top:16px}
  .row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
  .field{display:flex;flex-direction:column;gap:6px;min-width:220px;flex:1}
  label{font-size:.85rem;color:var(--muted)}
  input, select{padding:10px 12px;border:1px solid var(--border);border-radius:12px;font-family:'Cairo',Tahoma,Arial;background:#fff}
  .btn{background:var(--primary);border:0;color:#fff;padding:10px 14px;border-radius:12px;font-weight:900;cursor:pointer}
  .btn-outline{background:#fff;border:1px solid var(--border);color:#111827;padding:10px 14px;border-radius:12px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-block}
  .btn-danger{background:var(--danger);border:0;color:#fff;padding:8px 12px;border-radius:12px;font-weight:900;cursor:pointer}
  .table{width:100%;border-collapse:separate;border-spacing:0;margin-top:10px;overflow:hidden;border-radius:14px;border:1px solid var(--border)}
  .table th{background:var(--soft);text-align:right;font-size:.85rem;padding:10px;border-bottom:1px solid var(--border)}
  .table td{padding:10px;border-bottom:1px solid var(--border);font-size:.9rem;vertical-align:middle}
  .table tr:last-child td{border-bottom:0}
  .num{text-align:left;direction:ltr;font-variant-numeric:tabular-nums}
  .totalbar{display:flex;gap:12px;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-top:12px}
  .pill{background:#eef2ff;color:#1d4ed8;border:1px solid #dbeafe;padding:6px 12px;border-radius:999px;font-weight:900;font-size:.8rem}
  .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:10px 12px;border-radius:12px;display:none;margin-top:10px}
  .success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:10px 12px;border-radius:12px;display:none;margin-top:10px}
</style>

<div>
  <div class="h1">إدخال قيد يومية</div>
  <div class="sub">أضف أسطر مدين/دائن، وسيتم التحقق من التوازن قبل الحفظ.</div>
</div>

<div class="card">
  <div class="row">
    <div class="field" style="max-width:260px">
      <label>تاريخ القيد</label>
      <input type="date" id="entry_date" value="<?= View::e($today) ?>">
    </div>
    <div class="field">
      <label>وصف القيد</label>
      <input type="text" id="description" placeholder="مثال: قيد بيع نقدي / قيد مصروف...">
    </div>
  </div>

  <div class="totalbar">
    <div class="pill">إجمالي المدين: <span class="num" id="total_debit">0.00</span></div>
    <div class="pill">إجمالي الدائن: <span class="num" id="total_credit">0.00</span></div>
    <div class="pill">الفرق: <span class="num" id="diff">0.00</span></div>
    <button class="btn-outline" type="button" id="addRowBtn">+ إضافة سطر</button>
  </div>

  <div class="error" id="errBox"></div>
  <div class="success" id="okBox"></div>

  <table class="table">
    <thead>
      <tr>
        <th style="width:42%">الحساب</th>
        <th class="num" style="width:16%">مدين</th>
        <th class="num" style="width:16%">دائن</th>
        <th style="width:20%">وصف السطر</th>
        <th style="width:6%">حذف</th>
      </tr>
    </thead>
    <tbody id="linesBody"></tbody>
  </table>

  <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn" type="button" id="saveBtn">حفظ القيد</button>
    <a class="btn-outline" href="<?= View::e($API_LIST) ?>">عرض القيود (JSON)</a>
  </div>
</div>

<script>
const API_ACCOUNTS = <?= json_encode($API_ACCOUNTS, JSON_UNESCAPED_SLASHES) ?>;
const API_SAVE     = <?= json_encode($API_SAVE, JSON_UNESCAPED_SLASHES) ?>;
let accounts = [];

function money(n){ return (Number(n||0)).toFixed(2); }
function showError(msg){ const b=document.getElementById('errBox'); b.style.display='block'; b.textContent=msg; document.getElementById('okBox').style.display='none'; }
function showOk(msg){ const b=document.getElementById('okBox'); b.style.display='block'; b.textContent=msg; document.getElementById('errBox').style.display='none'; }

function recalcTotals(){
  let td=0, tc=0;
  document.querySelectorAll('input[data-debit]').forEach(i => td += Number(i.value||0));
  document.querySelectorAll('input[data-credit]').forEach(i => tc += Number(i.value||0));
  document.getElementById('total_debit').textContent = money(td);
  document.getElementById('total_credit').textContent = money(tc);
  document.getElementById('diff').textContent = money(td - tc);
}

function buildAccountSelect(){
  const sel=document.createElement('select');
  sel.innerHTML = '<option value=\"\">-- اختر الحساب --</option>' + accounts.map(a => `<option value=\"${a.id}\">${a.code} - ${a.name_ar}</option>`).join('');
  return sel;
}

function addRow(){
  const tr=document.createElement('tr');

  const tdAcc=document.createElement('td');
  const sel=buildAccountSelect();
  tdAcc.appendChild(sel);

  const tdD=document.createElement('td'); tdD.className='num';
  const inD=document.createElement('input'); inD.type='number'; inD.step='0.01'; inD.min='0'; inD.setAttribute('data-debit','1');

  const tdC=document.createElement('td'); tdC.className='num';
  const inC=document.createElement('input'); inC.type='number'; inC.step='0.01'; inC.min='0'; inC.setAttribute('data-credit','1');

  inD.addEventListener('input', ()=>{ if(Number(inD.value||0)>0) inC.value=''; recalcTotals(); });
  inC.addEventListener('input', ()=>{ if(Number(inC.value||0)>0) inD.value=''; recalcTotals(); });

  tdD.appendChild(inD); tdC.appendChild(inC);

  const tdDesc=document.createElement('td');
  const inDesc=document.createElement('input'); inDesc.type='text'; inDesc.placeholder='اختياري';
  tdDesc.appendChild(inDesc);

  const tdDel=document.createElement('td');
  const del=document.createElement('button'); del.type='button'; del.className='btn-danger'; del.textContent='×';
  del.addEventListener('click', ()=>{ tr.remove(); recalcTotals(); });
  tdDel.appendChild(del);

  tr.appendChild(tdAcc); tr.appendChild(tdD); tr.appendChild(tdC); tr.appendChild(tdDesc); tr.appendChild(tdDel);
  document.getElementById('linesBody').appendChild(tr);
  recalcTotals();
}

async function loadAccounts(){
  const res=await fetch(API_ACCOUNTS);
  const j=await res.json();
  if(j.status!=='success') throw new Error('فشل تحميل الحسابات');
  accounts=j.data;
}

function collectLines(){
  return Array.from(document.querySelectorAll('#linesBody tr')).map(r=>{
    const sel=r.querySelector('select');
    const debit=r.querySelector('input[data-debit]');
    const credit=r.querySelector('input[data-credit]');
    const desc=r.querySelector('td:nth-child(4) input');
    return { account_id:Number(sel.value||0), debit:Number(debit.value||0), credit:Number(credit.value||0), description:desc.value||null };
  }).filter(l=>l.account_id>0 && (l.debit>0 || l.credit>0));
}

async function saveEntry(){
  const entry_date=document.getElementById('entry_date').value;
  const description=document.getElementById('description').value||null;
  const lines=collectLines();
  if(!entry_date) return showError('تاريخ القيد مطلوب');
  if(lines.length<2) return showError('أدخل سطرين على الأقل');
  const td=lines.reduce((s,l)=>s+Number(l.debit||0),0);
  const tc=lines.reduce((s,l)=>s+Number(l.credit||0),0);
  if(Math.abs(td-tc)>0.0001) return showError('القيد غير متوازن');
  const res=await fetch(API_SAVE,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({entry_date,description,lines})});
  const j=await res.json();
  if(!res.ok || j.status!=='success') return showError(j.message||'فشل حفظ القيد');
  showOk('تم حفظ القيد بنجاح. entry_id=' + j.entry_id);
  document.getElementById('linesBody').innerHTML='';
  addRow(); addRow();
  document.getElementById('description').value='';
}

document.getElementById('addRowBtn').addEventListener('click', addRow);
document.getElementById('saveBtn').addEventListener('click', saveEntry);

(async function init(){
  try{ await loadAccounts(); addRow(); addRow(); }
  catch(e){ showError('تعذر تحميل الحسابات: ' + e.message); }
})();
</script>

<?php
        require APP_PATH . '/Views/footer.php';
    }
}