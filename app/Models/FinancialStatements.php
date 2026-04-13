<?php
namespace App\Models;

use App\Core\Model;

class FinancialStatements extends Model
{
    /**
     * قائمة الدخل للفترة: from..to
     * - الإيرادات: credit - debit
     * - المصروفات: debit - credit
     */
    public function incomeStatement(string $from, string $to): array
    {
        // الإيرادات
        $rev = $this->sumByAccountType('revenue', $from, $to);

        // المصروفات
        $exp = $this->sumByAccountType('expense', $from, $to);

        $totalRevenue  = 0.0;
        $totalExpenses = 0.0;

        foreach ($rev as $r) $totalRevenue  += (float)$r['amount'];
        foreach ($exp as $r) $totalExpenses += (float)$r['amount'];

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => [
                'lines' => $rev,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'lines' => $exp,
                'total' => $totalExpenses,
            ],
            'net_profit' => $totalRevenue - $totalExpenses,
        ];
    }

    /**
     * الميزانية العمومية حتى تاريخ as_of
     * أصول / خصوم / حقوق ملكية
     */
    public function balanceSheet(string $asOf): array
    {
        $assets     = $this->closingByTypeUpTo('asset', $asOf);
        $liability  = $this->closingByTypeUpTo('liability', $asOf);
        $equity     = $this->closingByTypeUpTo('equity', $asOf);

        $totalAssets = 0.0;
        $totalLiab   = 0.0;
        $totalEquity = 0.0;

        foreach ($assets as $a) $totalAssets += (float)$a['closing'];
        foreach ($liability as $l) $totalLiab += (float)$l['closing'];
        foreach ($equity as $e) $totalEquity += (float)$e['closing'];

        return [
            'as_of' => $asOf,
            'assets' => ['lines' => $assets, 'total' => $totalAssets],
            'liabilities' => ['lines' => $liability, 'total' => $totalLiab],
            'equity' => ['lines' => $equity, 'total' => $totalEquity],
            'check' => [
                'assets_minus_liab_equity' => $totalAssets - ($totalLiab + $totalEquity),
            ],
        ];
    }

    private function sumByAccountType(string $type, string $from, string $to): array
    {
        // amount تعريفه يختلف:
        // revenue: credit - debit (طبيعة دائن)
        // expense: debit - credit (طبيعة مدين)
        $expr = ($type === 'revenue')
            ? "COALESCE(SUM(jl.credit - jl.debit),0)"
            : "COALESCE(SUM(jl.debit - jl.credit),0)";

        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.code,
                a.name_ar,
                $expr AS amount
            FROM chart_of_accounts a
            LEFT JOIN journal_entry_lines jl ON jl.account_id = a.id
            LEFT JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE a.account_type = :type
              AND je.status = 'posted'
              AND je.entry_date BETWEEN :from AND :to
            GROUP BY a.id, a.code, a.name_ar
            HAVING amount <> 0
            ORDER BY a.code ASC
        ");
        $stmt->execute([':type'=>$type, ':from'=>$from, ':to'=>$to]);
        return $stmt->fetchAll();
    }

    private function closingByTypeUpTo(string $type, string $asOf): array
    {
        // closing = opening_net + movements_net_up_to
        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.code,
                a.name_ar,
                (
                    (a.opening_debit - a.opening_credit) +
                    COALESCE(SUM(jl.debit - jl.credit),0)
                ) AS closing
            FROM chart_of_accounts a
            LEFT JOIN journal_entry_lines jl ON jl.account_id = a.id
            LEFT JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE a.account_type = :type
              AND (je.status = 'posted' OR je.status IS NULL)
              AND (je.entry_date <= :asOf OR je.entry_date IS NULL)
            GROUP BY a.id, a.code, a.name_ar, a.opening_debit, a.opening_credit
            HAVING closing <> 0
            ORDER BY a.code ASC
        ");
        $stmt->execute([':type'=>$type, ':asOf'=>$asOf]);
        return $stmt->fetchAll();
    }
}