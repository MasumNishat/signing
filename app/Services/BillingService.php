<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Models\BillingCharge;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Models\BillingPayment;
use App\Models\BillingPlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BillingService
 *
 * Handles business logic for billing operations including plans, charges,
 * invoices, and payments.
 */
class BillingService
{
    /**
     * List billing plans
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listPlans(array $filters = []): LengthAwarePaginator
    {
        $query = BillingPlan::query();

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'plan_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get billing plan by ID
     *
     * @param string $planId
     * @return BillingPlan
     * @throws ResourceNotFoundException
     */
    public function getPlan(string $planId): BillingPlan
    {
        $plan = BillingPlan::where('plan_id', $planId)->first();

        if (!$plan) {
            throw new ResourceNotFoundException('Billing plan not found');
        }

        return $plan;
    }

    /**
     * List account charges
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listCharges(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = BillingCharge::where('account_id', $accountId);

        // Filter by charge type
        if (!empty($filters['charge_type'])) {
            $query->ofType($filters['charge_type']);
        }

        // Filter active only
        if (!empty($filters['active_only'])) {
            $query->active();
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get charge by ID
     *
     * @param int $accountId
     * @param int $chargeId
     * @return BillingCharge
     * @throws ResourceNotFoundException
     */
    public function getCharge(int $accountId, int $chargeId): BillingCharge
    {
        $charge = BillingCharge::where('id', $chargeId)
            ->where('account_id', $accountId)
            ->first();

        if (!$charge) {
            throw new ResourceNotFoundException('Billing charge not found');
        }

        return $charge;
    }

    /**
     * Create billing charge
     *
     * @param int $accountId
     * @param array $data
     * @return BillingCharge
     * @throws ValidationException
     */
    public function createCharge(int $accountId, array $data): BillingCharge
    {
        DB::beginTransaction();
        try {
            // Validate
            if (empty($data['charge_type'])) {
                throw new ValidationException('charge_type is required');
            }

            if (empty($data['charge_name'])) {
                throw new ValidationException('charge_name is required');
            }

            // Create charge
            $charge = BillingCharge::create([
                'account_id' => $accountId,
                'charge_type' => $data['charge_type'],
                'charge_name' => $data['charge_name'],
                'unit_price' => $data['unit_price'] ?? null,
                'quantity' => $data['quantity'] ?? 0,
                'incremental_quantity' => $data['incremental_quantity'] ?? 0,
                'blocked' => $data['blocked'] ?? false,
                'chargeable_items' => $data['chargeable_items'] ?? null,
                'discount_information' => $data['discount_information'] ?? null,
            ]);

            DB::commit();

            Log::info('Billing charge created', [
                'account_id' => $accountId,
                'charge_id' => $charge->id,
            ]);

            return $charge;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create billing charge', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete charge
     *
     * @param int $accountId
     * @param int $chargeId
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteCharge(int $accountId, int $chargeId): bool
    {
        $charge = $this->getCharge($accountId, $chargeId);

        DB::beginTransaction();
        try {
            $charge->delete();

            DB::commit();

            Log::info('Billing charge deleted', [
                'account_id' => $accountId,
                'charge_id' => $chargeId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete billing charge', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete charge');
        }
    }

    /**
     * List invoices
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listInvoices(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = BillingInvoice::where('account_id', $accountId);

        // Filter unpaid only
        if (!empty($filters['unpaid_only'])) {
            $query->unpaid();
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'invoice_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load
        $query->withCount('items')->withCount('payments');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get invoice by ID
     *
     * @param int $accountId
     * @param string $invoiceId
     * @return BillingInvoice
     * @throws ResourceNotFoundException
     */
    public function getInvoice(int $accountId, string $invoiceId): BillingInvoice
    {
        $invoice = BillingInvoice::where('invoice_id', $invoiceId)
            ->where('account_id', $accountId)
            ->with(['items', 'payments'])
            ->first();

        if (!$invoice) {
            throw new ResourceNotFoundException('Invoice not found');
        }

        return $invoice;
    }

    /**
     * Create invoice
     *
     * @param int $accountId
     * @param array $data
     * @return BillingInvoice
     * @throws ValidationException
     */
    public function createInvoice(int $accountId, array $data): BillingInvoice
    {
        DB::beginTransaction();
        try {
            // Validate
            if (empty($data['invoice_date'])) {
                throw new ValidationException('invoice_date is required');
            }

            if (empty($data['amount'])) {
                throw new ValidationException('amount is required');
            }

            // Create invoice
            $invoice = BillingInvoice::create([
                'account_id' => $accountId,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'amount' => $data['amount'],
                'balance' => $data['amount'], // Initial balance equals amount
                'tax_exempt_amount' => $data['tax_exempt_amount'] ?? 0,
                'non_tax_exempt_amount' => $data['non_tax_exempt_amount'] ?? 0,
                'currency_code' => $data['currency_code'] ?? 'USD',
                'pdf_available' => $data['pdf_available'] ?? false,
            ]);

            // Add invoice items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    BillingInvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'charge_type' => $itemData['charge_type'],
                        'charge_name' => $itemData['charge_name'],
                        'unit_price' => $itemData['unit_price'] ?? null,
                        'quantity' => $itemData['quantity'] ?? 0,
                        'subtotal' => $itemData['subtotal'] ?? null,
                        'tax' => $itemData['tax'] ?? 0,
                        'total' => $itemData['total'] ?? null,
                    ]);
                }
            }

            DB::commit();

            Log::info('Invoice created', [
                'account_id' => $accountId,
                'invoice_id' => $invoice->invoice_id,
            ]);

            return $invoice->fresh(['items']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get past due invoices
     *
     * @param int $accountId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPastDueInvoices(int $accountId)
    {
        return BillingInvoice::forAccount($accountId)
            ->overdue()
            ->with(['items', 'payments'])
            ->get();
    }

    /**
     * List payments
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listPayments(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = BillingPayment::where('account_id', $accountId);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        // Filter by invoice
        if (!empty($filters['invoice_id'])) {
            $invoice = BillingInvoice::where('invoice_id', $filters['invoice_id'])
                ->where('account_id', $accountId)
                ->first();

            if ($invoice) {
                $query->forInvoice($invoice->id);
            }
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'payment_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load
        $query->with('invoice');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Get payment by ID
     *
     * @param int $accountId
     * @param string $paymentId
     * @return BillingPayment
     * @throws ResourceNotFoundException
     */
    public function getPayment(int $accountId, string $paymentId): BillingPayment
    {
        $payment = BillingPayment::where('payment_id', $paymentId)
            ->where('account_id', $accountId)
            ->with('invoice')
            ->first();

        if (!$payment) {
            throw new ResourceNotFoundException('Payment not found');
        }

        return $payment;
    }

    /**
     * Make payment
     *
     * @param int $accountId
     * @param array $data
     * @return BillingPayment
     * @throws ValidationException
     * @throws ResourceNotFoundException
     */
    public function makePayment(int $accountId, array $data): BillingPayment
    {
        DB::beginTransaction();
        try {
            // Validate
            if (empty($data['payment_amount'])) {
                throw new ValidationException('payment_amount is required');
            }

            // Validate invoice if provided
            $invoiceInternalId = null;
            if (!empty($data['invoice_id'])) {
                $invoice = BillingInvoice::where('invoice_id', $data['invoice_id'])
                    ->where('account_id', $accountId)
                    ->first();

                if (!$invoice) {
                    throw new ResourceNotFoundException('Invoice not found');
                }

                $invoiceInternalId = $invoice->id;
            }

            // Create payment
            $payment = BillingPayment::create([
                'account_id' => $accountId,
                'invoice_id' => $invoiceInternalId,
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_amount' => $data['payment_amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'status' => BillingPayment::STATUS_PENDING,
                'transaction_id' => $data['transaction_id'] ?? null,
            ]);

            DB::commit();

            Log::info('Payment created', [
                'account_id' => $accountId,
                'payment_id' => $payment->payment_id,
            ]);

            return $payment->fresh('invoice');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payment', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process payment (mark as completed)
     *
     * @param int $accountId
     * @param string $paymentId
     * @return BillingPayment
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function processPayment(int $accountId, string $paymentId): BillingPayment
    {
        $payment = $this->getPayment($accountId, $paymentId);

        if ($payment->isCompleted()) {
            throw new BusinessLogicException('Payment is already completed');
        }

        DB::beginTransaction();
        try {
            $payment->markAsCompleted();

            // Recalculate invoice balance if payment is linked to invoice
            if ($payment->invoice_id) {
                $payment->invoice->recalculateBalance();
            }

            DB::commit();

            Log::info('Payment processed', [
                'account_id' => $accountId,
                'payment_id' => $paymentId,
            ]);

            return $payment->fresh('invoice');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to process payment');
        }
    }

    /**
     * Get billing summary for account
     *
     * @param int $accountId
     * @return array
     */
    public function getBillingSummary(int $accountId): array
    {
        $totalInvoiced = BillingInvoice::forAccount($accountId)->sum('amount');
        $totalPaid = BillingPayment::forAccount($accountId)
            ->completed()
            ->sum('payment_amount');
        $totalOutstanding = BillingInvoice::forAccount($accountId)->sum('balance');
        $overdueAmount = BillingInvoice::forAccount($accountId)->overdue()->sum('balance');

        $recentInvoices = BillingInvoice::forAccount($accountId)
            ->orderBy('invoice_date', 'desc')
            ->limit(5)
            ->get();

        $recentPayments = BillingPayment::forAccount($accountId)
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'overdue_amount' => $overdueAmount,
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $recentPayments,
        ];
    }
}
