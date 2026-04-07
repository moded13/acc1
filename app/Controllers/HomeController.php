<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $apiUrl = '/20/accounting/acc1/public/api/journal-entries';

        echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
<meta charset='UTF-8'>
<title>نظام acc1 المحاسبي</title>
<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
<link href=\"https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">
<style>
    body{margin:0;padding:40px 16px;font-family:'Cairo',Tahoma,Arial;background:#f3f5fb}
    .wrapper{max-width:1100px;margin:0 auto}
    .card{background:#fff;border-radius:18px;padding:28px;box-shadow:0 18px 45px rgba(15,23,42,.08)}
    h1{margin:0 0 8px;color:#1f3c88;font-weight:800}
    p{margin:0 0 10px;color:#6b7280}
    code{display:inline-block;background:#020617;color:#e5e7eb;padding:10px 14px;border-radius:999px}
    a{color:#2563eb;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<div class='wrapper'>
  <div class='card'>
    <h1>نظام acc1 المحاسبي</h1>
    <p>الواجهة تعمل. جرّب API القيود:</p>
    <p><code>{$apiUrl}</code></p>
    <p><a href='/20/accounting/acc1/public/dashboard'>اذهب إلى لوحة التحكم</a></p>
  </div>
</div>
</body>
</html>";
    }
}