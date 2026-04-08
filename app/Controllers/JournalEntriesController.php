<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\JournalEntry;
use Exception;

class JournalEntriesController extends Controller
{
    public function index(): void
    {
        try {
            $model = new JournalEntry();
            $entries = $model->listEntries(100);

            $this->json([
                'status' => 'success',
                'data'   => $entries,
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'فشل في جلب القيود',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $this->json(['status'=>'error','message'=>'صيغة البيانات يجب أن تكون JSON'], 400);
        }

        $entryDate   = $data['entry_date']  ?? null;
        $description = $data['description'] ?? null;
        $lines       = $data['lines']       ?? [];

        if (!$entryDate) {
            $this->json(['status'=>'error','message'=>'entry_date مطلوب'], 422);
        }
        if (empty($lines)) {
            $this->json(['status'=>'error','message'=>'lines مطلوبة'], 422);
        }

        try {
            $model = new JournalEntry();
            $entryId = $model->createWithLines($entryDate, $description, $lines);

            $this->json([
                'status'   => 'success',
                'message'  => 'تم إنشاء القيد بنجاح.',
                'entry_id' => $entryId,
            ], 201);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}