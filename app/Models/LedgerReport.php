<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class LedgerReport extends Model
{
    public function getAccountLedger(int $accountId, ?string $from = null, ?string $to = null): array
    {
        // جلب بيانات الحساب
        $stmt = $this->db->prepare("SELECT id, code, name_ar, account_type, sub_type, opening_debit, opening_credit FROM chart_of_accounts WHERE id = :id");
        $stmt->execute([':id' => $accountId]);
        $account = $stmt->fetch();

        if (!$account) {
            throw new Exception("الحساب غير موجود: account_id={$accountId}");
        }

        // حساب رصيد ما قبل الفترة (opening + كل الحركات قبل from)
        $openingNet = (float)$account['opening_debit'] - (float)$account['opening_credit'];

        if ($from) {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(jl.debit),0)  AS d,
                    COALESCE(SUM(jl.credit),0) AS c
                FROM journal_entry_lines jl
                INNER JOIN journal_entries je ON je.id = jl.journal_entry_id
                WHERE je.status='posted'
                  AND jl.account_id = :aid
                  AND je.entry_date < :from
            ");
            $stmt->execute([':aid' => $accountId, ':from' => $from]);
            $pre = $stmt->fetch();
            $openingNet += ((float)$pre['d'] - (float)$pre['c']);
        }

        // الحركات داخل الفترة
        $where = ["je.status='posted'", "jl.account_id = :aid"];
        $params = [':aid' => $accountId];

        if ($from) {
            $where[] = "je.entry_date >= :from";
            $params[':from'] = $from;
        }
        if ($to) {
            $where[] = "je.entry_date <= :to";
            $params[':to'] = $to;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $stmt = $this->db->prepare("
            SELECT
                je.id AS journal_entry_id,
                je.entry_no,
                je.entry_date,
                je.description AS entry_description,
                jl.id AS line_id,
                jl.line_no,
                jl.debit,
                jl.credit,
                jl.description AS line_description
            FROM journal_entry_lines jl
            INNER JOIN journal_entries je ON je.id = jl.journal_entry_id
            $whereSql
            ORDER BY je.entry_date ASC, je.id ASC, jl.line_no ASC
        ");
        $stmt->execute($params);
        $lines = $stmt->fetchAll();

        // حساب الرصيد الجاري (running balance)
        $running = $openingNet;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as &$l) {
            $d = (float)$l['debit'];
            $c = (float)$l['credit'];
            $totalDebit += $d;
            $totalCredit += $c;
            $running += ($d - $c);
            $l['running_balance'] = $running; // موجب = مدين، سالب = دائن
        }
        unset($l);

        return [
            'account' => $account,
            'filters' => ['from' => $from, 'to' => $to],
            'opening_balance' => $openingNet,
            'totals' => [
                'period_debit'  => $totalDebit,
                'period_credit' => $totalCredit,
                'closing_balance' => $running,
            ],
            'lines' => $lines,
        ];
    }
}