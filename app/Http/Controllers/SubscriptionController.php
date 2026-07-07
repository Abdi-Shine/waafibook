<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Services\StorageUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function plansIndex()
    {
        // "Free Trial" is auto-assigned on company registration (see
        // RegisteredUserController), not something a customer picks here.
        $plans = SubscriptionPlan::query()->where('name', '!=', 'Free Trial')->get();
        return view('admin.subscribers.plans.index', compact('plans'));
    }

    public function plansStore(Request $request)
    {
        // Tenant selects a plan → create/update their subscription
        if ($request->filled('plan_id')) {
            $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
            ]);

            $plan    = SubscriptionPlan::findOrFail($request->plan_id);
            $company = auth()->user()->company;

            if (!$company) {
                return redirect()->back()->with('error', 'No company is associated with your account.');
            }

            // Derive the expiry date from the plan's billing cycle
            $expiresAt = match ($plan->billing_cycle) {
                'yearly'    => now()->addYear(),
                'quarterly' => now()->addMonths(3),
                '7days'     => now()->addDays(7),
                default     => now()->addMonth(),
            };

            Subscription::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status'               => 'active',
                    'start_date'           => now()->toDateString(),
                    'expiry_date'          => $expiresAt->toDateString(),
                ]
            );

            return redirect()->route('subscribers.subscriptions.index')
                ->with('success', 'You are now subscribed to the ' . $plan->name . ' plan.');
        }

        // Host creating a new plan definition (used from host plans page)
        $request->validate([
            'name'          => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'max_users'     => 'required|integer|min:1',
        ]);

        SubscriptionPlan::query()->create([
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'billing_cycle'    => $request->billing_cycle,
            'max_users'        => $request->max_users,
            'storage_limit_gb' => $request->storage_limit_gb ?? 2,
            'features'         => $request->features ? array_map('trim', explode(',', $request->features)) : [],
            'status'           => $request->status ?? 'active',
            'is_popular'       => $request->has('is_popular'),
        ]);

        return redirect()->back()->with('success', 'Subscription plan created successfully.');
    }

    public function plansUpdate(Request $request, $id)
    {
        $plan = SubscriptionPlan::query()->findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'max_users'     => 'required|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $plan->update([
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'billing_cycle'    => $request->billing_cycle,
            'max_users'        => $request->max_users,
            'storage_limit_gb' => $request->storage_limit_gb,
            'features'         => $request->features ? array_map('trim', explode(',', $request->features)) : [],
            'status'           => $request->status ?? 'active',
            'is_popular'       => $request->has('is_popular'),
        ]);

        return redirect()->back()->with('success', 'Subscription plan updated successfully.');
    }

    public function plansDestroy($id)
    {
        $plan = SubscriptionPlan::query()->findOrFail($id);

        $hasActiveSubscribers = Subscription::where('subscription_plan_id', $id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveSubscribers) {
            return redirect()->back()->with('error', 'Cannot delete "' . $plan->name . '" — companies are actively subscribed to it. Deactivate the plan instead.');
        }

        $plan->delete();
        return redirect()->back()->with('success', 'Subscription plan deleted successfully.');
    }

    /**
     * Show the payment checkout page for a selected plan.
     */
    public function checkout($id)
    {
        $plan    = SubscriptionPlan::where('status', 'active')->findOrFail($id);
        $company = Company::find(auth()->user()->company_id);

        $currentSubscription = Subscription::where('company_id', auth()->user()->company_id)
            ->with('plan')
            ->latest()
            ->first();

        return view('frontend.subscribers.checkout', compact('plan', 'company', 'currentSubscription'));
    }

    /**
     * Process the payment and activate the subscription.
     */
    public function processPayment(Request $request, $id)
    {
        $plan    = SubscriptionPlan::where('status', 'active')->findOrFail($id);
        $company = Company::find(auth()->user()->company_id);

        if (!$company) {
            return redirect()->back()->with('error', 'No company is associated with your account.');
        }

        $request->validate([
            'payment_method'   => 'required|in:evc_mobile,bank_transfer',
            'transaction_ref'  => 'required|string|max:100',
            'phone'            => 'required_if:payment_method,evc_mobile|nullable|string|max:20',
        ]);

        $expiresAt = match ($plan->billing_cycle) {
            'yearly'    => now()->addYear(),
            'quarterly' => now()->addMonths(3),
            '7days'     => now()->addDays(7),
            default     => now()->addMonth(),
        };

        DB::transaction(function () use ($request, $plan, $company, $expiresAt) {
            // Create or update subscription as pending_payment — will be activated by super admin approval
            $subscription = Subscription::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status'               => 'pending_payment',
                    'start_date'           => now()->toDateString(),
                    'expiry_date'          => $expiresAt->toDateString(),
                    'payment_method'       => $request->payment_method,
                ]
            );

            // Record the payment as pending — super admin will approve/reject
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'amount'          => $plan->price,
                'payment_date'    => now()->toDateString(),
                'payment_method'  => $request->payment_method,
                'transaction_id'  => $request->transaction_ref,
                'status'          => 'pending',
                'notes'           => $request->phone ? 'Phone: ' . $request->phone : null,
            ]);
        });

        return redirect()->route('subscribers.subscriptions.index')
            ->with('success', 'Your payment request has been submitted and is awaiting approval from the administrator.');
    }

    public function subscriptionsIndex()
    {
        $companyId     = auth()->user()->company_id;
        $subscriptions = Subscription::query()
            ->with(['company', 'plan', 'payments'])
            ->where('company_id', $companyId)
            ->get();

        $activePlanId = $subscriptions->first()?->subscription_plan_id;

        $plans = SubscriptionPlan::query()
            ->where('status', 'active')
            ->where('name', '!=', 'Free Trial')
            ->orderBy('price')
            ->get();

        $usedUsers    = StorageUsageService::usedUsers($companyId);
        $maxUsers     = StorageUsageService::maxUsers($companyId);
        $usedStorageGB = StorageUsageService::usedGB($companyId);
        $maxStorageGB  = StorageUsageService::limitGB($companyId);

        return view('admin.subscribers.subscriptions.index', compact(
            'subscriptions', 'plans', 'activePlanId',
            'usedUsers', 'maxUsers', 'usedStorageGB', 'maxStorageGB'
        ));
    }
}
