<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class Account extends Model
{
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM chart_of_accounts ORDER BY code ASC");
        return $stmt->fetchAll();
    }

    public function getFlatActive(): array
    {
        $stmt = $this->db->query("
            SELECT id, code, name_ar, account_type, sub_type, parent_id, is_postable, is_active
            FROM chart_of_accounts
            WHERE is_active = 1
            ORDER BY code ASC
        ");
        return $stmt->fetchAll();
    }

    public function createAccount(array $data): int
    {
        // تحقق: code لا يتكرر
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $data['code']]);
        $exists = $stmt->fetch();
        if ($exists) {
            throw new Exception('الكود موجود مسبقاً، اختر كود آخر.');
        }

        // تحقق: parent موجود إن تم اختياره
        if (!empty($data['parent_id'])) {
            $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$data['parent_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('الحساب الأب غير موجود.');
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO chart_of_accounts
              (code, name_ar, name_en, account_type, sub_type, parent_id, is_postable, opening_debit, opening_credit, is_active, notes, created_at, updated_at)
            VALUES
              (:code, :name_ar, :name_en, :account_type, :sub_type, :parent_id, :is_postable, :opening_debit, :opening_credit, :is_active, :notes, NOW(), NOW())
        ");

        $stmt->execute([
            ':code'          => $data['code'],
            ':name_ar'       => $data['name_ar'],
            ':name_en'       => $data['name_en'] ?? null,
            ':account_type'  => $data['account_type'],
            ':sub_type'      => $data['sub_type'] ?? null,
            ':parent_id'     => $data['parent_id'] ?: null,
            ':is_postable'   => (int)($data['is_postable'] ?? 1),
            ':opening_debit' => (float)($data['opening_debit'] ?? 0),
            ':opening_credit'=> (float)($data['opening_credit'] ?? 0),
            ':is_active'     => (int)($data['is_active'] ?? 1),
            ':notes'         => $data['notes'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }
}