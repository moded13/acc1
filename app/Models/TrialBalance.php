<?php
namespace App\Models;

use App\Core\Model;

class TrialBalance extends Model
{
    public function get(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = [];
        $params = [];

        if ($dateFrom) {
            $where[] = "je.entry_date >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $where[] = "je.entry_date <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "
            SELECT
                a.id,
                a.code,
                a.name_ar,
                a.account_type,
                a.parent_id,
                a.opening_debit,
                a.opening_credit,

                COALESCE(SUM(jl.debit), 0)  AS period_debit,
                COALESCE(SUM(jl.credit), 0) AS period_credit

            FROM chart_of_accounts a
            LEFT JOIN journal_entry_lines jl
                ON jl.account_id = a.id
            LEFT JOIN journal_entries je
                ON je.id = jl.journal_entry_id
                AND je.status = 'posted'
            $whereSql
            GROUP BY
                a.id, a.code, a.name_ar, a.account_type, a.parent_id, a.opening_debit, a.opening_credit
            ORDER BY a.code ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $openingNet = (float)$r['opening_debit'] - (float)$r['opening_credit'];
            $periodNet  = (float)$r['period_debit']  - (float)$r['period_credit'];
            $closingNet = $openingNet + $periodNet;

            $r['closing_debit']  = $closingNet >= 0 ? $closingNet : 0;
            $r['closing_credit'] = $closingNet < 0  ? abs($closingNet) : 0;
        }
        unset($r);

        return $rows;
    }
}