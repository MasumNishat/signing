<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\BillingCharge;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Models\BillingPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BillingTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_billing_plans()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_plans");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function test_can_get_specific_billing_plan()
    {
        $this->createAndAuthenticateUser();

        $plan = BillingPlan::first();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_plans/{$plan->id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_list_billing_charges()
    {
        $this->createAndAuthenticateUser();

        BillingCharge::create([
            'account_id' => $this->account->id,
            'charge_name' => 'Test Charge',
            'charge_amount' => 25.00,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_charges");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_billing_charge()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_charges", [
            'charge_name' => 'Additional Envelopes',
            'charge_amount' => 50.00,
            'charge_type' => 'one_time',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('billing_charges', [
            'account_id' => $this->account->id,
            'charge_name' => 'Additional Envelopes',
        ]);
    }

    /** @test */
    public function test_can_get_specific_charge()
    {
        $this->createAndAuthenticateUser();

        $charge = BillingCharge::create([
            'account_id' => $this->account->id,
            'charge_name' => 'Test Charge',
            'charge_amount' => 25.00,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_charges/{$charge->charge_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_delete_billing_charge()
    {
        $this->createAndAuthenticateUser();

        $charge = BillingCharge::create([
            'account_id' => $this->account->id,
            'charge_name' => 'To Delete',
            'charge_amount' => 10.00,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/billing_charges/{$charge->charge_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('billing_charges', [
            'id' => $charge->id,
        ]);
    }

    /** @test */
    public function test_can_list_invoices()
    {
        $this->createAndAuthenticateUser();

        BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-001',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_invoices");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_invoice()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_invoices", [
            'invoice_number' => 'INV-TEST',
            'amount' => 150.00,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('billing_invoices', [
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-TEST',
        ]);
    }

    /** @test */
    public function test_can_get_specific_invoice()
    {
        $this->createAndAuthenticateUser();

        $invoice = BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-002',
            'amount' => 200.00,
            'balance' => 200.00,
            'due_date' => now()->addDays(30),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_invoices/{$invoice->invoice_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_get_past_due_invoices()
    {
        $this->createAndAuthenticateUser();

        BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-PAST',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->subDays(10),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_invoices_past_due");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_list_payments()
    {
        $this->createAndAuthenticateUser();

        BillingPayment::create([
            'account_id' => $this->account->id,
            'payment_amount' => 50.00,
            'payment_method' => 'credit_card',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_payments");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_payment()
    {
        $this->createAndAuthenticateUser();

        $invoice = BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-PAY',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_payments", [
            'invoice_id' => $invoice->invoice_id,
            'payment_amount' => 50.00,
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('billing_payments', [
            'account_id' => $this->account->id,
            'payment_amount' => 50.00,
        ]);
    }

    /** @test */
    public function test_can_get_specific_payment()
    {
        $this->createAndAuthenticateUser();

        $payment = BillingPayment::create([
            'account_id' => $this->account->id,
            'payment_amount' => 75.00,
            'payment_method' => 'credit_card',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_payments/{$payment->payment_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_process_payment()
    {
        $this->createAndAuthenticateUser();

        $invoice = BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-PROCESS',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        $payment = BillingPayment::create([
            'account_id' => $this->account->id,
            'invoice_id' => $invoice->id,
            'payment_amount' => 100.00,
            'payment_method' => 'credit_card',
            'payment_status' => 'pending',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_payments/{$payment->payment_id}/process");

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $payment->refresh();
        $this->assertEquals('completed', $payment->payment_status);
    }

    /** @test */
    public function test_validates_payment_amount()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_payments", [
            'payment_amount' => -10.00, // Invalid
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['payment_amount']);
    }

    /** @test */
    public function test_can_get_billing_summary()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing_summary");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertArrayHasKey('current_period_charges', $response->json('data'));
        $this->assertArrayHasKey('total_balance', $response->json('data'));
    }

    /** @test */
    public function test_payment_reduces_invoice_balance()
    {
        $this->createAndAuthenticateUser();

        $invoice = BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-BALANCE',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_payments", [
            'invoice_id' => $invoice->invoice_id,
            'payment_amount' => 60.00,
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(201);

        $invoice->refresh();
        $this->assertEquals(40.00, $invoice->balance);
    }
}
