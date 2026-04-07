<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;
use Exception;

class CustomersController extends Controller
{
    public function index(): void
    {
        try {
            $model = new Customer();
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
            $model = new Customer();
            $id = $model->create($data);
            $this->json(['status' => 'success', 'message' => 'تم إنشاء العميل', 'data' => ['id' => $id]], 201);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
