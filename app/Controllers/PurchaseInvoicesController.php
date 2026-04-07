<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PurchaseInvoice;
use App\Services\PostingService;
use Exception;

class PurchaseInvoicesController extends Controller
{
    public function index(): void
    {
        try {
            $model = new PurchaseInvoice();
            $this->json(['status' => 'success', 'data' => $model->list()]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['status' => 'error', 'message' => 'صيغة JSON غير صحيحة'], 400);
        }
        $header = $data['header'] ?? $data;
        $lines  = $data['lines']  ?? [];
        try {
            $model = new PurchaseInvoice();
            $id = $model->create($header, $lines);
            $this->json(['status' => 'success', 'message' => 'تم إنشاء فاتورة المشتريات', 'data' => ['id' => $id]], 201);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function post(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['status' => 'error', 'message' => 'id مطلوب'], 400);
        }
        try {
            $service = new PostingService();
            $jeId = $service->postPurchaseInvoice($id);
            $this->json(['status' => 'success', 'message' => 'تم ترحيل الفاتورة', 'data' => ['journal_entry_id' => $jeId]]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
