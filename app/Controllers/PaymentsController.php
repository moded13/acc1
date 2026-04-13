<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payment;
use App\Services\PostingService;
use Exception;

class PaymentsController extends Controller
{
    public function index(): void
    {
        try {
            $model = new Payment();
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
        try {
            $model = new Payment();
            $id = $model->create($data);
            $this->json(['status' => 'success', 'message' => 'تم إنشاء سند الدفع', 'data' => ['id' => $id]], 201);
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
            $jeId = $service->postPayment($id);
            $this->json(['status' => 'success', 'message' => 'تم ترحيل سند الدفع', 'data' => ['journal_entry_id' => $jeId]]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
