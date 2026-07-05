<?php

namespace App\Http\Controllers;

use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosReceiptController extends Controller
{
    protected function getAuthenticatedUser()
    {
        foreach (['branch_manager', 'web'] as $guard) {
            if ($user = auth($guard)->user()) {
                return $user;
            }
        }
        return auth()->user();
    }

    protected function authorizeAccess(PosTransaction $transaction)
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Authorization check: only allow authorized user types
        if (!in_array($user->user_type, ['branch_manager', 'director', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        // Multi-tenant check: if branch manager, verify they manage this branch
        if ($user->user_type === 'branch_manager') {
            $hasAccess = DB::table('branch_managers')
                ->where('user_id', $user->id)
                ->where('branch_id', $transaction->branch_id)
                ->where('is_active', 1)
                ->exists();

            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this branch receipt.');
            }
        }
    }

    public function print(PosTransaction $transaction)
    {
        $this->authorizeAccess($transaction);

        $receipt = $transaction->receipt;
        if (!$receipt) {
            $receipt = $transaction->generateReceipt();
        }

        $data = $receipt->receipt_data;

        return view('receipts.pos-receipt', [
            'receipt' => $receipt,
            'transaction' => $data['transaction'],
            'customer' => $data['customer'],
            'items' => $data['items'],
            'staff' => $data['staff'],
            'branch' => $data['branch']
        ]);
    }

    public function view(PosTransaction $transaction)
    {
        return $this->print($transaction);
    }

    public function download(PosTransaction $transaction)
    {
        return $this->print($transaction);
    }
}
