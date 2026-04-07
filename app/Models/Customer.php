<?php
namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;

class Customer extends Model
{
    public function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM customers";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if ($code === '') throw new Exception('كود العميل مطلوب');
        if ($name === '') throw new Exception('اسم العميل مطلوب');

        $stmt = $this->db->prepare("SELECT id FROM customers WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        if ($stmt->fetch()) throw new Exception('كود العميل موجود مسبقاً');

        $stmt = $this->db->prepare("
            INSERT INTO customers (code, name, phone, email, address, tax_no, is_active, created_at, updated_at)
            VALUES (:code, :name, :phone, :email, :address, :tax_no, :is_active, NOW(), NOW())
        ");
        $stmt->execute([
            ':code'      => $code,
            ':name'      => $name,
            ':phone'     => $data['phone']     ?? null,
            ':email'     => $data['email']     ?? null,
            ':address'   => $data['address']   ?? null,
            ':tax_no'    => $data['tax_no']    ?? null,
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);

        return (int)$this->db->lastInsertId();
    }
}
