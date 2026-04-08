<?php
namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;

class Receipt extends Model
{
    public function list(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, c.name AS customer_name
            FROM receipts r
            LEFT JOIN customers c ON c.id = r.customer_id
            ORDER BY r.receipt_date DESC, r.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, c.name AS customer_name
            FROM receipts r
            LEFT JOIN customers c ON c.id = r.customer_id
            WHERE r.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $customerId  = (int)($data['customer_id']  ?? 0) ?: null;
        $amount      = (float)($data['amount']      ?? 0);
        $method      = in_array($data['method'] ?? '', ['cash','bank']) ? $data['method'] : 'cash';
        $receiptDate = $data['receipt_date'] ?? date('Y-m-d');
        $notes       = $data['notes']        ?? null;

        if ($amount <= 0) throw new Exception('المبلغ يجب أن يكون أكبر من صفر');

        $receiptNo = $this->generateReceiptNo();

        $stmt = $this->db->prepare("
            INSERT INTO receipts (receipt_no, receipt_date, customer_id, amount, method, status, notes, created_at, updated_at)
            VALUES (:receipt_no, :receipt_date, :customer_id, :amount, :method, 'draft', :notes, NOW(), NOW())
        ");
        $stmt->execute([
            ':receipt_no'   => $receiptNo,
            ':receipt_date' => $receiptDate,
            ':customer_id'  => $customerId,
            ':amount'       => $amount,
            ':method'       => $method,
            ':notes'        => $notes,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function markPosted(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE receipts SET status='posted', updated_at=NOW() WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    protected function generateReceiptNo(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM receipts WHERE YEAR(receipt_date) = :year");
        $stmt->execute([':year' => $year]);
        $seq = (int)$stmt->fetch()['cnt'] + 1;
        return sprintf('REC-%s-%05d', $year, $seq);
    }
}
