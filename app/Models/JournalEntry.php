<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

class JournalEntry extends Model
{
    public function createWithLines(string $entryDate, ?string $description, array $lines): int
    {
        if (empty($lines)) {
            throw new Exception('يجب إدخال سطر واحد على الأقل للقيد.');
        }

        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit  += (float)($line['debit']  ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new Exception('القيد غير متوازن: مجموع المدين لا يساوي مجموع الدائن.');
        }

        // =========================
        // منع الترحيل على حساب غير قابل للترحيل (is_postable=0)
        // =========================
        $ids = [];
        foreach ($lines as $l) {
            $ids[] = (int)($l['account_id'] ?? 0);
        }
        $ids = array_values(array_unique(array_filter($ids, fn($x) => $x > 0)));

        if (empty($ids)) {
            throw new Exception('يجب تحديد account_id صحيح في أسطر القيد.');
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, is_postable FROM chart_of_accounts WHERE id IN ($in)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['id']] = (int)($r['is_postable'] ?? 1);
        }

        foreach ($ids as $id) {
            if (!isset($map[$id])) {
                throw new Exception("الحساب غير موجود: {$id}");
            }
            if ($map[$id] !== 1) {
                throw new Exception("لا يمكن الترحيل على حساب رئيسي (غير قابل للترحيل): {$id}");
            }
        }

        try {
            $this->db->beginTransaction();

            $entryNo = $this->generateEntryNumber();
            $stmt = $this->db->prepare("
                INSERT INTO journal_entries (entry_no, entry_date, description, status, created_at, updated_at)
                VALUES (:entry_no, :entry_date, :description, 'posted', NOW(), NOW())
            ");
            $stmt->execute([
                ':entry_no'    => $entryNo,
                ':entry_date'  => $entryDate,
                ':description' => $description,
            ]);

            $entryId = (int)$this->db->lastInsertId();

            $lineNo = 1;
            $lineStmt = $this->db->prepare("
                INSERT INTO journal_entry_lines
                    (journal_entry_id, account_id, line_no, debit, credit, description, created_at, updated_at)
                VALUES
                    (:entry_id, :account_id, :line_no, :debit, :credit, :description, NOW(), NOW())
            ");

            foreach ($lines as $line) {
                $lineStmt->execute([
                    ':entry_id'    => $entryId,
                    ':account_id'  => (int)$line['account_id'],
                    ':line_no'     => $lineNo++,
                    ':debit'       => (float)($line['debit']  ?? 0),
                    ':credit'      => (float)($line['credit'] ?? 0),
                    ':description' => $line['description'] ?? null,
                ]);
            }

            $this->db->commit();
            return $entryId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listEntries(int $limit = 50): array
    {
        $sql = "
            SELECT
                je.id,
                je.entry_no,
                je.entry_date,
                je.description,
                je.status,
                COALESCE(SUM(jl.debit),0)  AS total_debit,
                COALESCE(SUM(jl.credit),0) AS total_credit
            FROM journal_entries je
            LEFT JOIN journal_entry_lines jl ON jl.journal_entry_id = je.id
            GROUP BY je.id, je.entry_no, je.entry_date, je.description, je.status
            ORDER BY je.entry_date DESC, je.id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getEntryWithLines(int $entryId): array
    {
        $stmt = $this->db->prepare("
            SELECT je.*
            FROM journal_entries je
            WHERE je.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $entryId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            throw new Exception('القيد غير موجود');
        }

        $stmt = $this->db->prepare("
            SELECT
                jl.*,
                a.code AS account_code,
                a.name_ar AS account_name
            FROM journal_entry_lines jl
            INNER JOIN chart_of_accounts a ON a.id = jl.account_id
            WHERE jl.journal_entry_id = :id
            ORDER BY jl.line_no ASC, jl.id ASC
        ");
        $stmt->execute([':id' => $entryId]);
        $lines = $stmt->fetchAll();

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $l) {
            $totalDebit += (float)$l['debit'];
            $totalCredit += (float)$l['credit'];
        }

        return [
            'entry' => $entry,
            'lines' => $lines,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
            ]
        ];
    }

    protected function generateEntryNumber(): string
    {
        $year = date('Y');

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt
            FROM journal_entries
            WHERE YEAR(entry_date) = :year
        ");
        $stmt->execute([':year' => $year]);

        $row = $stmt->fetch();
        $seq = (int)($row['cnt'] ?? 0) + 1;

        return sprintf('%s-%05d', $year, $seq);
    }
}