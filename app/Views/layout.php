<?php
/** @var string $title */
/** @var string $active */

use App\Core\View;

$base = '/20/accounting/acc1/public';

$navItems = [
    ['key' => 'dashboard', 'label' => 'لوحة التحكم',    'href' => $base . '/dashboard'],
    ['key' => 'entry',     'label' => 'إدخال قيد',      'href' => $base . '/entry/new'],
    ['key' => 'je',        'label' => 'القيود',         'href' => $base . '/journal-entries'],
    ['key' => 'accounts',  'label' => 'دليل الحسابات',  'href' => $base . '/accounts'],
    ['key' => 'trial',     'label' => 'ميزان المراجعة', 'href' => $base . '/reports/trial-balance'],
    ['key' => 'income',    'label' => 'قائمة الدخل',    'href' => $base . '/reports/income-statement'],
    ['key' => 'balance',   'label' => 'الميزانية',      'href' => $base . '/reports/balance-sheet'],
    ['key' => 'ledger',    'label' => 'كشف حساب',       'href' => $base . '/reports/ledger'],

    // Commercial module
    ['key' => 'customers', 'label' => 'العملاء',        'href' => $base . '/customers'],
    ['key' => 'suppliers', 'label' => 'الموردون',       'href' => $base . '/suppliers'],
    ['key' => 'items',     'label' => 'الأصناف',        'href' => $base . '/items'],
    ['key' => 'sales',     'label' => 'مبيعات',         'href' => $base . '/sales-invoices'],
    ['key' => 'purchases', 'label' => 'مشتريات',        'href' => $base . '/purchase-invoices'],
    ['key' => 'receipts',  'label' => 'سندات القبض',    'href' => $base . '/receipts'],
    ['key' => 'payments',  'label' => 'سندات الدفع',    'href' => $base . '/payments'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900;1000&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= View::e($base) ?>/assets/app.css?v=3">
</head>

<body>
<div class="app">

  <!-- Fixed Header -->
  <header class="top">
    <div class="brand">
      <span class="badge">ERP</span>
      <span>Enterprise ERP</span>
    </div>

    <button class="hamburger" type="button" data-drawer-btn>القائمة</button>

    <nav class="menu" aria-label="Main navigation">
      <?php foreach ($navItems as $it): ?>
        <a class="<?= ($active === $it['key']) ? 'active' : '' ?>"
           href="<?= View::e($it['href']) ?>">
          <?= View::e($it['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="right">
      <button class="logout" type="button" onclick="alert('سيتم ربط الخروج لاحقاً بنظام المستخدمين');">
        خروج
      </button>
    </div>
  </header>

  <!-- Mobile Drawer -->
  <div class="drawer-backdrop" data-drawer-backdrop></div>

  <aside class="drawer" data-drawer aria-label="Mobile navigation">
    <div class="drawer-head">
      <div class="brand">
        <span class="badge">ERP</span>
        <span>Enterprise ERP</span>
      </div>

      <button class="hamburger" type="button" data-drawer-close>إغلاق</button>
    </div>

    <div class="drawer-links">
      <?php foreach ($navItems as $it): ?>
        <a class="<?= ($active === $it['key']) ? 'active' : '' ?>"
           href="<?= View::e($it['href']) ?>">
          <?= View::e($it['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Main page -->
  <main class="page">