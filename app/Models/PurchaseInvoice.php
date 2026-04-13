<?php
namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Receipt;
use App\Models\Payment;

class PurchaseInvoice extends Model
{
    public function list(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT pi.*, s.name AS supplier_name
            FROM purchase_invoices pi
            LEFT JOIN suppliers s ON s.id = pi.supplier_id
            ORDER BY pi.invoice_date DESC, pi.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pi.*, s.name AS supplier_name
            FROM purchase_invoices pi
            LEFT JOIN suppliers s ON s.id = pi.supplier_id
            WHERE pi.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getLines(int $invoiceId): array
    {
        $stmt = $this->db->prepare("
            SELECT pil.*, i.name AS item_name
            FROM purchase_invoice_lines pil
            LEFT JOIN items i ON i.id = pil.item_id
            WHERE pil.purchase_invoice_id = :id
            ORDER BY pil.line_no ASC
        ");
        $stmt->execute([':id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a draft purchase invoice with lines.
     * $lines: array of [ item_id?, description, qty, unit_cost, vat_rate? ]
     */
    public function create(array $header, array $lines): int
    {
        if (empty($lines)) throw new Exception('يجب إدخال سطر واحد على الأقل.');

        $supplierId  = (int)($header['supplier_id']  ?? 0);
        $invoiceDate = $header['invoice_date'] ?? date('Y-m-d');
        $notes       = $header['notes']        ?? null;
        $defaultVat  = (float)($header['vat_rate'] ?? 0.16);

        if ($supplierId <= 0) throw new Exception('المورد مطلوب');

        $subtotal = 0.0;
        $vatTotal = 0.0;
        $prepared = [];

        foreach ($lines as $idx => $l) {
            $qty      = (float)($l['qty']       ?? 1);
            $unitCost = (float)($l['unit_cost']  ?? 0);
            $vatRate  = (float)($l['vat_rate']   ?? $defaultVat);
            $desc     = trim($l['description']   ?? '');

            $lineBase  = $qty * $unitCost;
            $vatAmount = round($lineBase * $vatRate, 4);
            $lineTotal = $lineBase + $vatAmount;

            $subtotal += $lineBase;
            $vatTotal += $vatAmount;

            $prepared[] = [
                'item_id'     => (int)($l['item_id'] ?? 0) ?: null,
                'description' => $desc,
                'qty'         => $qty,
                'unit_cost'   => $unitCost,
                'vat_rate'    => $vatRate,
                'vat_amount'  => $vatAmount,
                'line_total'  => $lineTotal,
                'line_no'     => $idx + 1,
            ];
        }

        $total = $subtotal + $vatTotal;
        $invoiceNo = $this->generateInvoiceNo();

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO purchase_invoices
                    (invoice_no, invoice_date, supplier_id, status, subtotal, vat_total, total, notes, created_at, updated_at)
                VALUES
                    (:invoice_no, :invoice_date, :supplier_id, 'draft', :subtotal, :vat_total, :total, :notes, NOW(), NOW())
            ");
            $stmt->execute([
                ':invoice_no'   => $invoiceNo,
                ':invoice_date' => $invoiceDate,
                ':supplier_id'  => $supplierId,
                ':subtotal'     => $subtotal,
                ':vat_total'    => $vatTotal,
                ':total'        => $total,
                ':notes'        => $notes,
            ]);

            $invoiceId = (int)$this->db->lastInsertId();

            $lineStmt = $this->db->prepare("
                INSERT INTO purchase_invoice_lines
                    (purchase_invoice_id, item_id, description, qty, unit_cost, vat_rate, vat_amount, line_total, line_no)
                VALUES
                    (:invoice_id, :item_id, :description, :qty, :unit_cost, :vat_rate, :vat_amount, :line_total, :line_no)
            ");

            foreach ($prepared as $p) {
                $lineStmt->execute([
                    ':invoice_id'  => $invoiceId,
                    ':item_id'     => $p['item_id'],
                    ':description' => $p['description'],
                    ':qty'         => $p['qty'],
                    ':unit_cost'   => $p['unit_cost'],
                    ':vat_rate'    => $p['vat_rate'],
                    ':vat_amount'  => $p['vat_amount'],
                    ':line_total'  => $p['line_total'],
                    ':line_no'     => $p['line_no'],
                ]);
            }

            $this->db->commit();
            return $invoiceId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function markPosted(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE purchase_invoices SET status='posted', updated_at=NOW() WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    protected function generateInvoiceNo(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM purchase_invoices WHERE YEAR(invoice_date) = :year");
        $stmt->execute([':year' => $year]);
        $seq = (int)$stmt->fetch()['cnt'] + 1;
        return sprintf('PI-%s-%05d', $year, $seq);
    }
}
