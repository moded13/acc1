<?php
namespace App\Services;

use App\Config\Database;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Receipt;
use App\Models\Payment;
use Exception;
use PDO;

/**
 * PostingService
 *
 * Handles posting of commercial documents to the General Ledger.
 * Each post() method:
 *   1. Validates the document is in 'draft' status
 *   2. Creates a balanced journal_entry + lines
 *   3. Creates inventory movements (for invoices with items)
 *   4. Marks the document as 'posted'
 *   5. Links the journal entry via source_type / source_id
 */
class PostingService
{
    private PDO $db;

    /** @var array<string,int> cached settings */
    private array $settings = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->loadSettings();
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Post a Sales Invoice.
     *   DR  Accounts Receivable   (total incl. VAT)
     *   CR  Sales Revenue         (subtotal)
     *   CR  VAT Output            (vat_total)
     * Also creates inventory OUT movements per line.
     */
    public function postSalesInvoice(int $invoiceId): int
    {
        $model = new SalesInvoice();
        $inv   = $model->findById($invoiceId);

        if (!$inv) throw new Exception("فاتورة مبيعات غير موجودة: {$invoiceId}");
        if ($inv['status'] !== 'draft') throw new Exception("الفاتورة ليست في حالة مسودة (draft).");

        $total    = (float)$inv['total'];
        $subtotal = (float)$inv['subtotal'];
        $vatTotal = (float)$inv['vat_total'];

        $arId     = $this->setting('account_ar');
        $salesId  = $this->setting('account_sales');
        $vatOutId = $this->setting('account_vat_output');

        $lines = [];
        $lines[] = ['account_id' => $arId,     'debit'  => $total,    'credit' => 0,        'description' => 'مدين - ذمم عملاء'];
        $lines[] = ['account_id' => $salesId,  'debit'  => 0,         'credit' => $subtotal, 'description' => 'دائن - إيراد مبيعات'];
        if ($vatTotal > 0) {
            $lines[] = ['account_id' => $vatOutId, 'debit' => 0,      'credit' => $vatTotal, 'description' => 'دائن - ضريبة مبيعات مخرجات'];
        }

        $desc = "ترحيل فاتورة مبيعات {$inv['invoice_no']}";
        $jeId = $this->createJournalEntry($inv['invoice_date'], $desc, $lines, 'sales_invoice', $invoiceId);

        // Inventory movements OUT
        $invLines = $model->getLines($invoiceId);
        foreach ($invLines as $line) {
            if (!empty($line['item_id'])) {
                $this->createInventoryMovement(
                    (int)$line['item_id'],
                    $inv['invoice_date'],
                    'out',
                    (float)$line['qty'],
                    (float)$line['unit_price'],
                    'sales_invoice',
                    $invoiceId
                );
            }
        }

        $model->markPosted($invoiceId);
        return $jeId;
    }

    /**
     * Post a Purchase Invoice.
     *   DR  Inventory / Purchases  (subtotal)
     *   DR  VAT Input              (vat_total)
     *   CR  Accounts Payable       (total incl. VAT)
     * Also creates inventory IN movements per line.
     */
    public function postPurchaseInvoice(int $invoiceId): int
    {
        $model = new PurchaseInvoice();
        $inv   = $model->findById($invoiceId);

        if (!$inv) throw new Exception("فاتورة مشتريات غير موجودة: {$invoiceId}");
        if ($inv['status'] !== 'draft') throw new Exception("الفاتورة ليست في حالة مسودة (draft).");

        $total    = (float)$inv['total'];
        $subtotal = (float)$inv['subtotal'];
        $vatTotal = (float)$inv['vat_total'];

        $apId       = $this->setting('account_ap');
        $purchasesId = $this->setting('account_purchases');
        $vatInId    = $this->setting('account_vat_input');

        $lines = [];
        $lines[] = ['account_id' => $purchasesId, 'debit' => $subtotal, 'credit' => 0,      'description' => 'مدين - مشتريات/مخزون'];
        if ($vatTotal > 0) {
            $lines[] = ['account_id' => $vatInId,  'debit' => $vatTotal, 'credit' => 0,     'description' => 'مدين - ضريبة مشتريات مدخلات'];
        }
        $lines[] = ['account_id' => $apId,         'debit' => 0,         'credit' => $total, 'description' => 'دائن - ذمم موردين'];

        $desc = "ترحيل فاتورة مشتريات {$inv['invoice_no']}";
        $jeId = $this->createJournalEntry($inv['invoice_date'], $desc, $lines, 'purchase_invoice', $invoiceId);

        // Inventory movements IN
        $invLines = $model->getLines($invoiceId);
        foreach ($invLines as $line) {
            if (!empty($line['item_id'])) {
                $this->createInventoryMovement(
                    (int)$line['item_id'],
                    $inv['invoice_date'],
                    'in',
                    (float)$line['qty'],
                    (float)$line['unit_cost'],
                    'purchase_invoice',
                    $invoiceId
                );
            }
        }

        $model->markPosted($invoiceId);
        return $jeId;
    }

    /**
     * Post a Receipt (customer payment received).
     *   DR  Cash / Bank            (amount)
     *   CR  Accounts Receivable    (amount)
     */
    public function postReceipt(int $receiptId): int
    {
        $model   = new Receipt();
        $receipt = $model->findById($receiptId);

        if (!$receipt) throw new Exception("سند القبض غير موجود: {$receiptId}");
        if ($receipt['status'] !== 'draft') throw new Exception("سند القبض ليس في حالة مسودة (draft).");

        $amount = (float)$receipt['amount'];
        $arId   = $this->setting('account_ar');

        $cashBankId = ($receipt['method'] === 'bank')
            ? $this->setting('account_bank')
            : $this->setting('account_cash');

        $lines = [
            ['account_id' => $cashBankId, 'debit'  => $amount, 'credit' => 0,      'description' => 'مدين - نقد/بنك'],
            ['account_id' => $arId,       'debit'  => 0,       'credit' => $amount, 'description' => 'دائن - ذمم عملاء'],
        ];

        $desc = "ترحيل سند قبض {$receipt['receipt_no']}";
        $jeId = $this->createJournalEntry($receipt['receipt_date'], $desc, $lines, 'receipt', $receiptId);

        $model->markPosted($receiptId);
        return $jeId;
    }

    /**
     * Post a Payment (supplier payment made).
     *   DR  Accounts Payable       (amount)
     *   CR  Cash / Bank            (amount)
     */
    public function postPayment(int $paymentId): int
    {
        $model   = new Payment();
        $payment = $model->findById($paymentId);

        if (!$payment) throw new Exception("سند الدفع غير موجود: {$paymentId}");
        if ($payment['status'] !== 'draft') throw new Exception("سند الدفع ليس في حالة مسودة (draft).");

        $amount = (float)$payment['amount'];
        $apId   = $this->setting('account_ap');

        $cashBankId = ($payment['method'] === 'bank')
            ? $this->setting('account_bank')
            : $this->setting('account_cash');

        $lines = [
            ['account_id' => $apId,       'debit'  => $amount, 'credit' => 0,      'description' => 'مدين - ذمم موردين'],
            ['account_id' => $cashBankId, 'debit'  => 0,       'credit' => $amount, 'description' => 'دائن - نقد/بنك'],
        ];

        $desc = "ترحيل سند دفع {$payment['payment_no']}";
        $jeId = $this->createJournalEntry($payment['payment_date'], $desc, $lines, 'payment', $paymentId);

        $model->markPosted($paymentId);
        return $jeId;
    }

    // ----------------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------------

    /**
     * Create a balanced journal entry with source linkage.
     * Uses the same is_postable guard as JournalEntry::createWithLines().
     */
    private function createJournalEntry(
        string $date,
        string $description,
        array  $lines,
        string $sourceType,
        int    $sourceId
    ): int {
        // Validate balance
        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $l) {
            $totalDebit  += (float)($l['debit']  ?? 0);
            $totalCredit += (float)($l['credit'] ?? 0);
        }
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception('القيد غير متوازن أثناء الترحيل التلقائي.');
        }

        // is_postable guard
        $ids = array_values(array_unique(array_map(fn($l) => (int)$l['account_id'], $lines)));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, is_postable FROM chart_of_accounts WHERE id IN ($in)");
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int)$r['id']] = (int)$r['is_postable'];
        }
        foreach ($ids as $id) {
            if (!isset($map[$id])) throw new Exception("الحساب غير موجود: {$id}");
            if ($map[$id] !== 1) throw new Exception("الحساب غير قابل للترحيل: {$id}");
        }

        try {
            $this->db->beginTransaction();

            // Generate entry number
            $year  = date('Y', strtotime($date));
            $stmt  = $this->db->prepare("SELECT COUNT(*) AS cnt FROM journal_entries WHERE YEAR(entry_date) = ?");
            $stmt->execute([$year]);
            $seq   = (int)$stmt->fetch()['cnt'] + 1;
            $entryNo = sprintf('%s-%05d', $year, $seq);

            $stmt = $this->db->prepare("
                INSERT INTO journal_entries
                    (entry_no, entry_date, description, status, source_type, source_id, created_at, updated_at)
                VALUES
                    (:entry_no, :entry_date, :description, 'posted', :source_type, :source_id, NOW(), NOW())
            ");
            $stmt->execute([
                ':entry_no'    => $entryNo,
                ':entry_date'  => $date,
                ':description' => $description,
                ':source_type' => $sourceType,
                ':source_id'   => $sourceId,
            ]);
            $jeId = (int)$this->db->lastInsertId();

            $lineStmt = $this->db->prepare("
                INSERT INTO journal_entry_lines
                    (journal_entry_id, account_id, line_no, debit, credit, description, created_at, updated_at)
                VALUES
                    (:je_id, :account_id, :line_no, :debit, :credit, :description, NOW(), NOW())
            ");
            $lineNo = 1;
            foreach ($lines as $l) {
                $lineStmt->execute([
                    ':je_id'       => $jeId,
                    ':account_id'  => (int)$l['account_id'],
                    ':line_no'     => $lineNo++,
                    ':debit'       => (float)($l['debit']  ?? 0),
                    ':credit'      => (float)($l['credit'] ?? 0),
                    ':description' => $l['description'] ?? null,
                ]);
            }

            $this->db->commit();
            return $jeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function createInventoryMovement(
        int    $itemId,
        string $date,
        string $type,
        float  $qty,
        float  $unitCost,
        string $sourceType,
        int    $sourceId
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO inventory_movements
                (item_id, movement_date, movement_type, qty, unit_cost, source_type, source_id, created_at)
            VALUES
                (:item_id, :movement_date, :movement_type, :qty, :unit_cost, :source_type, :source_id, NOW())
        ");
        $stmt->execute([
            ':item_id'       => $itemId,
            ':movement_date' => $date,
            ':movement_type' => $type,
            ':qty'           => $qty,
            ':unit_cost'     => $unitCost,
            ':source_type'   => $sourceType,
            ':source_id'     => $sourceId,
        ]);
    }

    private function loadSettings(): void
    {
        $rows = $this->db->query("SELECT setting_key, setting_val FROM settings")->fetchAll();
        foreach ($rows as $r) {
            $this->settings[$r['setting_key']] = $r['setting_val'];
        }
    }

    private function setting(string $key): int
    {
        $val = (int)($this->settings[$key] ?? 0);
        if ($val <= 0) {
            throw new Exception("إعداد حساب التحكم غير مضبوط: {$key}. يرجى ضبط الإعدادات أولاً.");
        }
        return $val;
    }
}
