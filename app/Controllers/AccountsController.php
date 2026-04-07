<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Account;

class AccountsController extends Controller
{
    /**
     * GET /api/accounts
     */
    public function index(): void
    {
        $model = new Account();
        $accounts = $model->getFlatActive();

        $this->json([
            'status' => 'success',
            'data'   => $accounts
        ]);
    }

    /**
     * POST /api/accounts
     */
    public function store(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $this->json(['status'=>'error','message'=>'صيغة البيانات يجب أن تكون JSON'], 400);
        }

        $code = trim((string)($data['code'] ?? ''));
        $name_ar = trim((string)($data['name_ar'] ?? ''));
        $account_type = trim((string)($data['account_type'] ?? ''));

        if ($code === '') $this->json(['status'=>'error','message'=>'code مطلوب'], 422);
        if ($name_ar === '') $this->json(['status'=>'error','message'=>'name_ar مطلوب'], 422);
        if ($account_type === '') $this->json(['status'=>'error','message'=>'account_type مطلوب'], 422);

        // parent_id
        $parent_id = $data['parent_id'] ?? null;
        if ($parent_id === '' || $parent_id === 0) $parent_id = null;
        if ($parent_id !== null) $parent_id = (int)$parent_id;

        // افتراضي: إذا الحساب بدون parent => حساب رئيسي (غير postable)
        $is_postable = $data['is_postable'] ?? null;
        if ($is_postable === null) {
            $is_postable = ($parent_id === null) ? 0 : 1;
        } else {
            $is_postable = (int)$is_postable;
        }

        $payload = [
            'code' => $code,
            'name_ar' => $name_ar,
            'name_en' => $data['name_en'] ?? null,
            'account_type' => $account_type,
            'sub_type' => $data['sub_type'] ?? null,
            'parent_id' => $parent_id,
            'is_postable' => $is_postable,
            'opening_debit' => $data['opening_debit'] ?? 0,
            'opening_credit' => $data['opening_credit'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'notes' => $data['notes'] ?? null,
        ];

        $model = new Account();
        $newId = $model->createAccount($payload);

        $this->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحساب بنجاح',
            'account_id' => $newId
        ], 201);
    }
}