<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Supplier;
use Exception;

class SuppliersController extends Controller
{
    public function index(): void
    {
        try {
            $model = new Supplier();
            $this->json(['status' => 'success', 'data' => $model->getAll()]);
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
            $model = new Supplier();
            $id = $model->create($data);
            $this->json(['status' => 'success', 'message' => 'تم إنشاء المورد', 'data' => ['id' => $id]], 201);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
