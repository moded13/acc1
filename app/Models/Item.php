<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class Item extends Model
{
    public function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM items";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sku  = trim($data['sku']  ?? '');
        $name = trim($data['name'] ?? '');

        if ($sku  === '') throw new Exception('SKU/كود الصنف مطلوب');
        if ($name === '') throw new Exception('اسم الصنف مطلوب');

        $stmt = $this->db->prepare("SELECT id FROM items WHERE sku = :sku LIMIT 1");
        $stmt->execute([':sku' => $sku]);
        if ($stmt->fetch()) throw new Exception('كود الصنف (SKU) موجود مسبقاً');

        $stmt = $this->db->prepare("
            INSERT INTO items (sku, name, unit, cost_price, sale_price, vat_rate, is_active, created_at, updated_at)
            VALUES (:sku, :name, :unit, :cost_price, :sale_price, :vat_rate, :is_active, NOW(), NOW())
        ");
        $stmt->execute([
            ':sku'        => $sku,
            ':name'       => $name,
            ':unit'       => $data['unit']       ?? 'وحدة',
            ':cost_price' => (float)($data['cost_price'] ?? 0),
            ':sale_price' => (float)($data['sale_price'] ?? 0),
            ':vat_rate'   => (float)($data['vat_rate']   ?? 0.16),
            ':is_active'  => (int)($data['is_active']    ?? 1),
        ]);

        return (int)$this->db->lastInsertId();
    }
}
