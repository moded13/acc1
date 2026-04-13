<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Item;
use Exception;

class ItemsController extends Controller
{
    public function index(): void
    {
        try {
            $model = new Item();
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
            $model = new Item();
            $id = $model->create($data);
            $this->json(['status' => 'success', 'message' => 'تم إنشاء الصنف', 'data' => ['id' => $id]], 201);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
