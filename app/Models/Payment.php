<?php
namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;

class Payment extends Model
{
    public function list(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, s.name AS supplier_name
            FROM payments p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, s.name AS supplier_name
            FROM payments p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $supplierId  = (int)($data['supplier_id']  ?? 0) ?: null;
        $amount      = (float)($data['amount']      ?? 0);
        $method      = in_array($data['method'] ?? '', ['cash','bank']) ? $data['method'] : 'cash';
        $paymentDate = $data['payment_date'] ?? date('Y-m-d');
        $notes       = $data['notes']        ?? null;

        if ($amount <= 0) throw new Exception('المبلغ يجب أن يكون أكبر من صفر');

        $paymentNo = $this->generatePaymentNo();

        $stmt = $this->db->prepare("
            INSERT INTO payments (payment_no, payment_date, supplier_id, amount, method, status, notes, created_at, updated_at)
            VALUES (:payment_no, :payment_date, :supplier_id, :amount, :method, 'draft', :notes, NOW(), NOW())
        ");
        $stmt->execute([
            ':payment_no'   => $paymentNo,
            ':payment_date' => $paymentDate,
            ':supplier_id'  => $supplierId,
            ':amount'       => $amount,
            ':method'       => $method,
            ':notes'        => $notes,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function markPosted(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE payments SET status='posted', updated_at=NOW() WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    protected function generatePaymentNo(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM payments WHERE YEAR(payment_date) = :year");
        $stmt->execute([':year' => $year]);
        $seq = (int)$stmt->fetch()['cnt'] + 1;
        return sprintf('PAY-%s-%05d', $year, $seq);
    }
}
