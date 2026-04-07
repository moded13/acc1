<?php
/** @var string $title */
/** @var string $active */

use App\Core\View;

$base = '/20/accounting/acc1/public';

$navItems = [
    ['key' => 'dashboard', 'label' => 'لوحة التحكم',    'href' => $base . '/dashboard'],
    ['key' => 'entry',     'label' => 'إدخال قيد',      'href' => $base . '/entry/new'],
    ['key' => 'je',        'label' => 'القيود',          'href' => $base . '/journal-entries'],
    ['key' => 'accounts',  'label' => 'دليل الحسابات',  'href' => $base . '/accounts'],
    ['key' => 'trial',     'label' => 'ميزان المراجعة', 'href' => $base . '/reports/trial-balance'],
    ['key' => 'income',    'label' => 'قائمة الدخل',    'href' => $base . '/reports/income-statement'],
    ['key' => 'balance',   'label' => 'الميزانية',      'href' => $base . '/reports/balance-sheet'],
    ['key' => 'ledger',    'label' => 'كشف حساب',       'href' => $base . '/reports/ledger'],
    // Commercial module
    ['key' => 'customers', 'label' => 'العملاء',         'href' => $base . '/customers'],
    ['key' => 'suppliers', 'label' => 'الموردون',        'href' => $base . '/suppliers'],
    ['key' => 'items',     'label' => 'الأصناف',         'href' => $base . '/items'],
    ['key' => 'sales',     'label' => 'مبيعات',          'href' => $base . '/sales-invoices'],
    ['key' => 'purchases', 'label' => 'مشتريات',         'href' => $base . '/purchase-invoices'],
    ['key' => 'receipts',  'label' => 'سندات القبض',     'href' => $base . '/receipts'],
    ['key' => 'payments',  'label' => 'سندات الدفع',     'href' => $base . '/payments'],
];

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg:#f3f5fb;--nav:#020617;--card:#fff;--primary:#2563eb;--danger:#ef4444;--muted:#6b7280;
      --shadow:0 18px 45px rgba(15,23,42,.10);--r:18px;--border:#e5e7eb;--soft:#f8fafc;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:'Cairo',Tahoma,Arial;background:var(--bg);color:#0f172a}
    a{text-decoration:none}
    .top{
      background:linear-gradient(to left,#020617,#111827);
      color:#e5e7eb;
      padding:10px 18px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      box-shadow:0 6px 18px rgba(15,23,42,.45);
      position:sticky;
      top:0;
      z-index:10;
    }
    .brand{display:flex;align-items:center;gap:10px;font-weight:900;white-space:nowrap}
    .badge{background:#1d4ed8;padding:4px 10px;border-radius:999px;font-size:.72rem}
    .menu{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:center}
    .menu a{
      color:#e5e7eb;
      padding:7px 12px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.12);
      font-weight:800;
      font-size:.82rem;
      background:rgba(15,23,42,.35);
    }
    .menu a.active{
      background:var(--primary);
      border-color:rgba(96,165,250,.8);
      box-shadow:0 10px 25px rgba(37,99,235,.35);
    }
    .right{display:flex;align-items:center;gap:10px;white-space:nowrap}
    .logout{
      background:var(--danger);
      border:0;
      color:#fff;
      padding:7px 14px;
      border-radius:999px;
      font-weight:900;
      cursor:pointer;
    }

    .page{padding:22px 28px 40px;max-width:1200px;margin:0 auto}

    /* عناصر عامة للصفحات */
    .h1{font-size:1.6rem;font-weight:900;margin:0}
    .sub{color:var(--muted);margin-top:6px}
    .card{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);padding:16px 18px;margin-top:16px}
    .table{width:100%;border-collapse:separate;border-spacing:0;margin-top:10px;overflow:hidden;border-radius:14px;border:1px solid var(--border)}
    .table th{background:var(--soft);text-align:right;font-size:.85rem;padding:10px;border-bottom:1px solid var(--border)}
    .table td{padding:10px;border-bottom:1px solid var(--border);font-size:.9rem;vertical-align:middle}
    .table tr:last-child td{border-bottom:0}
    .num{text-align:left;direction:ltr;font-variant-numeric:tabular-nums}
    .btn{background:var(--primary);border:0;color:#fff;padding:10px 14px;border-radius:12px;font-weight:900;cursor:pointer}
    .btn-outline{background:#fff;border:1px solid var(--border);color:#111827;padding:10px 14px;border-radius:12px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-block}
    .pill{background:#eef2ff;color:#1d4ed8;border:1px solid #dbeafe;padding:6px 12px;border-radius:999px;font-weight:900;font-size:.8rem}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
    .field{display:flex;flex-direction:column;gap:6px;min-width:220px;flex:1}
    label{font-size:.85rem;color:var(--muted)}
    input, select{padding:10px 12px;border:1px solid var(--border);border-radius:12px;font-family:'Cairo',Tahoma,Arial;background:#fff}
  </style>
</head>
<body>

<header class="top">
  <div class="brand">
    <span class="badge">ERP</span>
    <span>Enterprise ERP</span>
  </div>

  <nav class="menu">
    <?php foreach ($navItems as $it): ?>
      <a class="<?= ($active === $it['key']) ? 'active' : '' ?>" href="<?= View::e($it['href']) ?>">
        <?= View::e($it['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="right">
    <button class="logout" type="button" onclick="alert('سيتم ربط الخروج لاحقاً بنظام المستخدمين');">خروج</button>
  </div>
</header>

<main class="page">