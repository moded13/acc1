<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\TrialBalance;
use App\Models\DashboardMetrics;
use App\Models\LedgerReport;
use App\Models\FinancialStatements;
use Exception;

class ReportsController extends Controller
{
    public function trialBalance(): void
    {
        try {
            $from = $_GET['from'] ?? null;
            $to   = $_GET['to'] ?? null;

            $model = new TrialBalance();
            $data = $model->get($from, $to);

            $this->json([
                'status' => 'success',
                'filters' => ['from' => $from, 'to' => $to],
                'data' => $data,
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'فشل في إنشاء ميزان المراجعة',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardMetrics(): void
    {
        try {
            $model = new DashboardMetrics();
            $data = $model->get();

            $this->json([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'فشل في جلب مؤشرات لوحة التحكم',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reports/ledger?account_id=ID&from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function ledger(): void
    {
        try {
            $accountId = (int)($_GET['account_id'] ?? 0);
            if ($accountId <= 0) {
                $this->json(['status'=>'error','message'=>'account_id مطلوب'], 422);
            }

            $from = $_GET['from'] ?? null;
            $to   = $_GET['to'] ?? null;

            $model = new LedgerReport();
            $data = $model->getAccountLedger($accountId, $from, $to);

            $this->json([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'فشل في إنشاء كشف الحساب',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reports/income-statement?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function incomeStatement(): void
    {
        try {
            $from = $_GET['from'] ?? null;
            $to   = $_GET['to'] ?? null;

            if (!$from || !$to) {
                $this->json(['status'=>'error','message'=>'from و to مطلوبان'], 422);
            }

            $model = new FinancialStatements();
            $data = $model->incomeStatement($from, $to);

            $this->json(['status'=>'success','data'=>$data]);
        } catch (Exception $e) {
            $this->json([
                'status'=>'error',
                'message'=>'فشل في إنشاء قائمة الدخل',
                'error'=>$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reports/balance-sheet?as_of=YYYY-MM-DD
     */
    public function balanceSheet(): void
    {
        try {
            $asOf = $_GET['as_of'] ?? null;
            if (!$asOf) {
                $this->json(['status'=>'error','message'=>'as_of مطلوب'], 422);
            }

            $model = new FinancialStatements();
            $data = $model->balanceSheet($asOf);

            $this->json(['status'=>'success','data'=>$data]);
        } catch (Exception $e) {
            $this->json([
                'status'=>'error',
                'message'=>'فشل في إنشاء الميزانية العمومية',
                'error'=>$e->getMessage(),
            ], 500);
        }
    }
}