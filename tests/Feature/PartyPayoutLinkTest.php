<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function makeAdminForPayoutTest(): User
{
    $company = Company::create(['name' => 'Payout Test Co ' . uniqid()]);

    return User::withoutGlobalScopes()->create([
        'name'     => 'Admin',
        'email'    => 'payout-admin-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'role'     => 'admin',
        'company_id' => $company->id,
    ]);
}

test('payment-in page auto-opens the modal and pre-selects the customer from ?customer_id=', function () {
    $admin = makeAdminForPayoutTest();
    $customer = Customer::create([
        'company_id' => $admin->company_id,
        'customer_code' => 'CUST-001',
        'name' => 'Ali Shire',
        'amount_balance' => 130,
    ]);

    $response = $this->actingAs($admin)->get('/payment-in?customer_id=' . $customer->id);

    $response->assertOk();
    $response->assertSee('showModal = true', false);
    $response->assertSee('data-balance="130.00" selected', false);
});

test('payment-out page auto-opens the modal and pre-selects the supplier from ?vendor_id=', function () {
    $admin = makeAdminForPayoutTest();
    $supplier = Supplier::create([
        'company_id' => $admin->company_id,
        'supplier_code' => 'SUP-001',
        'name' => 'Acme Supplies',
        'amount_balance' => 75,
    ]);

    $response = $this->actingAs($admin)->get('/payment-out?vendor_id=' . $supplier->id);

    $response->assertOk();
    $response->assertSee('showModal = true', false);
    $response->assertSee('data-balance="75.00" selected', false);
});
