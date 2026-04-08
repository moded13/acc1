<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Account;

class ChartOfAccountsController extends Controller
{
    public function tree(): void
    {
        $model = new Account();
        $tree = $model->getTree();

        $this->json([
            'status' => 'success',
            'data'   => $tree,
        ]);
    }
}