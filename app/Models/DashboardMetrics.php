<?php
namespace App\Models;

use App\Core\Model;

class DashboardMetrics extends Model
{
    public function get(): array
    {
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt
            FROM journal_entries
            WHERE status = 'posted'
              AND entry_date BETWEEN :from AND :to
        ");
        $stmt->execute([':from' => $monthStart, ':to' => $monthEnd]);
        $entriesCount = (int)($stmt->fetch()['cnt'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(jl.debit),0)  AS total_debit,
                COALESCE(SUM(jl.credit),0) AS total_credit
            FROM journal_entry_lines jl
            INNER JOIN journal_entries je ON je.id = jl.journal_entry_id
            WHERE je.status = 'posted'
              AND je.entry_date BETWEEN :from AND :to
        ");
        $stmt->execute([':from' => $monthStart, ':to' => $monthEnd]);
        $totals = $stmt->fetch();

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(jl.debit - jl.credit),0) AS net_cashflow
            FROM journal_entry_lines jl
            INNER JOIN journal_entries je ON je.id = jl.journal_entry_id
            INNER JOIN chart_of_accounts a ON a.id = jl.account_id
            WHERE je.status = 'posted'
              AND je.entry_date BETWEEN :from AND :to
              AND a.sub_type IN ('cash','bank')
        ");
        $stmt->execute([':from' => $monthStart, ':to' => $monthEnd]);
        $netCashflow = (float)($stmt->fetch()['net_cashflow'] ?? 0);

        return [
            'period' => ['from' => $monthStart, 'to' => $monthEnd],
            'counts' => ['journal_entries_month' => $entriesCount],
            'totals' => [
                'month_debit'  => (float)($totals['total_debit'] ?? 0),
                'month_credit' => (float)($totals['total_credit'] ?? 0),
            ],
            'cashflow' => ['net_cashflow_month' => $netCashflow],
        ];
    }
}