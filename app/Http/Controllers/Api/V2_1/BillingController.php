<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Http\Controllers\Api\BaseController;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * BillingController
 *
 * Handles all billing-related operations including plans, charges,
 * invoices, payments, and billing summaries.
 *
 * Endpoints: 21 total
 * - Plans: 2 endpoints
 * - Charges: 5 endpoints
 * - Invoices: 6 endpoints
 * - Payments: 6 endpoints
 * - Summary: 2 endpoints
 */
class BillingController extends BaseController
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    // =========================================================================
    // BILLING PLANS (2 endpoints)
    // =========================================================================

    /**
     * GET /billing_plans
     * List all billing plans
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by', 'plan_name'),
                'sort_order' => $request->input('sort_order', 'asc'),
                'per_page' => $request->input('per_page', 20),
            ];

            $plans = $this->billingService->listPlans($filters);

            return $this->paginatedResponse($plans, 'Billing plans retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list billing plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve billing plans', 500);
        }
    }

    /**
     * GET /billing_plans/{planId}
     * Get specific billing plan
     */
    public function show(string $planId): JsonResponse
    {
        try {
            $plan = $this->billingService->getPlan($planId);

            return $this->successResponse($plan, 'Billing plan retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get billing plan', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve billing plan', 500);
        }
    }

    // =========================================================================
    // BILLING CHARGES (5 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/billing_charges
     * List billing charges for account
     */
    public function indexCharges(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'charge_type' => $request->input('charge_type'),
                'active_only' => $request->boolean('active_only'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 20),
            ];

            $charges = $this->billingService->listCharges($accountId, $filters);

            return $this->paginatedResponse($charges, 'Billing charges retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list billing charges', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve billing charges', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/billing_charges
     * Create billing charge
     */
    public function storeCharge(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'charge_type' => 'required|string|in:seat,envelope,storage,api,custom',
                'charge_name' => 'required|string|max:255',
                'unit_price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
                'incremental_quantity' => 'nullable|integer|min:0',
                'blocked' => 'nullable|boolean',
                'chargeable_items' => 'nullable|array',
                'discount_information' => 'nullable|array',
                'discount_information.percentage' => 'nullable|numeric|min:0|max:100',
                'discount_information.fixed_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $charge = $this->billingService->createCharge($accountId, $request->all());

            return $this->createdResponse($charge, 'Billing charge created successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create billing charge', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create billing charge', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_charges/{chargeId}
     * Get specific billing charge
     */
    public function showCharge(int $accountId, int $chargeId): JsonResponse
    {
        try {
            $charge = $this->billingService->getCharge($accountId, $chargeId);

            return $this->successResponse($charge, 'Billing charge retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get billing charge', [
                'account_id' => $accountId,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve billing charge', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/billing_charges/{chargeId}
     * Update billing charge
     */
    public function updateCharge(Request $request, int $accountId, int $chargeId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'charge_name' => 'sometimes|string|max:255',
                'unit_price' => 'sometimes|numeric|min:0',
                'quantity' => 'sometimes|integer|min:0',
                'incremental_quantity' => 'sometimes|integer|min:0',
                'blocked' => 'sometimes|boolean',
                'chargeable_items' => 'sometimes|array',
                'discount_information' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $charge = $this->billingService->getCharge($accountId, $chargeId);
            $charge->update($request->all());

            return $this->successResponse($charge->fresh(), 'Billing charge updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to update billing charge', [
                'account_id' => $accountId,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update billing charge', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/billing_charges/{chargeId}
     * Delete billing charge
     */
    public function destroyCharge(int $accountId, int $chargeId): JsonResponse
    {
        try {
            $this->billingService->deleteCharge($accountId, $chargeId);

            return $this->noContentResponse('Billing charge deleted successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Failed to delete billing charge', [
                'account_id' => $accountId,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete billing charge', 500);
        }
    }

    // =========================================================================
    // BILLING INVOICES (6 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/billing_invoices
     * List invoices for account
     */
    public function indexInvoices(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'unpaid_only' => $request->boolean('unpaid_only'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'sort_by' => $request->input('sort_by', 'invoice_date'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 20),
            ];

            $invoices = $this->billingService->listInvoices($accountId, $filters);

            return $this->paginatedResponse($invoices, 'Invoices retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list invoices', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve invoices', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/billing_invoices
     * Create invoice
     */
    public function storeInvoice(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_date' => 'required|date',
                'due_date' => 'nullable|date|after:invoice_date',
                'amount' => 'required|numeric|min:0',
                'tax_exempt_amount' => 'nullable|numeric|min:0',
                'non_tax_exempt_amount' => 'nullable|numeric|min:0',
                'currency_code' => 'nullable|string|size:3',
                'pdf_available' => 'nullable|boolean',
                'items' => 'nullable|array',
                'items.*.charge_type' => 'required|string',
                'items.*.charge_name' => 'required|string',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.quantity' => 'nullable|integer|min:0',
                'items.*.subtotal' => 'nullable|numeric|min:0',
                'items.*.tax' => 'nullable|numeric|min:0',
                'items.*.total' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $invoice = $this->billingService->createInvoice($accountId, $request->all());

            return $this->createdResponse($invoice, 'Invoice created successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create invoice', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create invoice', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_invoices/{invoiceId}
     * Get specific invoice
     */
    public function showInvoice(int $accountId, string $invoiceId): JsonResponse
    {
        try {
            $invoice = $this->billingService->getInvoice($accountId, $invoiceId);

            return $this->successResponse($invoice, 'Invoice retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get invoice', [
                'account_id' => $accountId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve invoice', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_invoices/past_due
     * Get past due invoices
     */
    public function pastDueInvoices(int $accountId): JsonResponse
    {
        try {
            $invoices = $this->billingService->getPastDueInvoices($accountId);

            return $this->successResponse($invoices, 'Past due invoices retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get past due invoices', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve past due invoices', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_invoices/{invoiceId}/pdf
     * Download invoice PDF
     */
    public function downloadInvoicePdf(int $accountId, string $invoiceId): JsonResponse
    {
        try {
            $invoice = $this->billingService->getInvoice($accountId, $invoiceId);

            if (!$invoice->pdf_available) {
                return $this->errorResponse('PDF is not available for this invoice', 404);
            }

            // Placeholder implementation
            // In production, this would generate or retrieve the actual PDF
            $pdfUrl = route('api.v2_1.billing.invoices.pdf.download', [
                'accountId' => $accountId,
                'invoiceId' => $invoiceId,
            ]);

            return $this->successResponse([
                'invoice_id' => $invoice->invoice_id,
                'pdf_url' => $pdfUrl,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ], 'Invoice PDF URL generated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'account_id' => $accountId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to generate invoice PDF', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/billing_invoices/{invoiceId}
     * Update invoice
     */
    public function updateInvoice(Request $request, int $accountId, string $invoiceId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'due_date' => 'sometimes|date',
                'amount' => 'sometimes|numeric|min:0',
                'tax_exempt_amount' => 'sometimes|numeric|min:0',
                'non_tax_exempt_amount' => 'sometimes|numeric|min:0',
                'pdf_available' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $invoice = $this->billingService->getInvoice($accountId, $invoiceId);
            $invoice->update($request->all());

            return $this->successResponse($invoice->fresh(['items', 'payments']), 'Invoice updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to update invoice', [
                'account_id' => $accountId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update invoice', 500);
        }
    }

    // =========================================================================
    // BILLING PAYMENTS (6 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/billing_payments
     * List payments for account
     */
    public function indexPayments(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'invoice_id' => $request->input('invoice_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'sort_by' => $request->input('sort_by', 'payment_date'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 20),
            ];

            $payments = $this->billingService->listPayments($accountId, $filters);

            return $this->paginatedResponse($payments, 'Payments retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list payments', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve payments', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/billing_payments
     * Make payment
     */
    public function storePayment(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'nullable|string|exists:billing_invoices,invoice_id',
                'payment_date' => 'nullable|date',
                'payment_amount' => 'required|numeric|min:0.01',
                'payment_method' => 'nullable|string|in:credit_card,ach,wire',
                'transaction_id' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $payment = $this->billingService->makePayment($accountId, $request->all());

            return $this->createdResponse($payment, 'Payment created successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create payment', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create payment', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_payments/{paymentId}
     * Get specific payment
     */
    public function showPayment(int $accountId, string $paymentId): JsonResponse
    {
        try {
            $payment = $this->billingService->getPayment($accountId, $paymentId);

            return $this->successResponse($payment, 'Payment retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get payment', [
                'account_id' => $accountId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve payment', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/billing_payments/{paymentId}
     * Update payment
     */
    public function updatePayment(Request $request, int $accountId, string $paymentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_amount' => 'sometimes|numeric|min:0.01',
                'payment_method' => 'sometimes|string|in:credit_card,ach,wire',
                'transaction_id' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $payment = $this->billingService->getPayment($accountId, $paymentId);

            if ($payment->isCompleted()) {
                return $this->errorResponse('Cannot update completed payment', 400);
            }

            $payment->update($request->all());

            return $this->successResponse($payment->fresh('invoice'), 'Payment updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to update payment', [
                'account_id' => $accountId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update payment', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/billing_payments/{paymentId}/process
     * Process payment (mark as completed)
     */
    public function processPayment(int $accountId, string $paymentId): JsonResponse
    {
        try {
            $payment = $this->billingService->processPayment($accountId, $paymentId);

            return $this->successResponse($payment, 'Payment processed successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Failed to process payment', [
                'account_id' => $accountId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process payment', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/billing_payments/{paymentId}
     * Delete payment
     */
    public function destroyPayment(int $accountId, string $paymentId): JsonResponse
    {
        try {
            $payment = $this->billingService->getPayment($accountId, $paymentId);

            if ($payment->isCompleted()) {
                return $this->errorResponse('Cannot delete completed payment', 400);
            }

            $payment->delete();

            return $this->noContentResponse('Payment deleted successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to delete payment', [
                'account_id' => $accountId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete payment', 500);
        }
    }

    // =========================================================================
    // BILLING SUMMARY & USAGE (2 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/billing_summary
     * Get billing summary for account
     */
    public function getBillingSummary(int $accountId): JsonResponse
    {
        try {
            $summary = $this->billingService->getBillingSummary($accountId);

            return $this->successResponse($summary, 'Billing summary retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get billing summary', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve billing summary', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/billing_usage
     * Get billing usage statistics
     */
    public function getUsage(int $accountId): JsonResponse
    {
        try {
            // Placeholder implementation
            // In production, this would calculate actual usage metrics
            $usage = [
                'current_period' => [
                    'start_date' => now()->startOfMonth()->toIso8601String(),
                    'end_date' => now()->endOfMonth()->toIso8601String(),
                    'envelopes_sent' => 0, // Would query actual usage
                    'api_calls' => 0,
                    'storage_used_mb' => 0,
                ],
                'plan_limits' => [
                    'envelopes_included' => 100,
                    'api_calls_included' => 10000,
                    'storage_included_gb' => 50,
                ],
                'overages' => [
                    'envelopes' => 0,
                    'api_calls' => 0,
                    'storage_gb' => 0,
                ],
            ];

            return $this->successResponse($usage, 'Billing usage retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get billing usage', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve billing usage', 500);
        }
    }
}
