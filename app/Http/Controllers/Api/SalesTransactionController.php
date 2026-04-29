<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesTransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = SalesTransaction::query()
            ->with('customer:id,fname,lname,email')
            ->when($request->search, function ($q) use ($request) {
                $term = $request->search;
                $q->where(function ($qq) use ($term) {
                    $qq->where('transaction_no', 'like', "%{$term}%")
                       ->orWhere('customer_name', 'like', "%{$term}%")
                       ->orWhere('customer_email', 'like', "%{$term}%");
                });
            })
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->order_status, fn($q) => $q->where('order_status', $request->order_status));

        return response()->json($query->latest('transacted_at')->latest('updated_at')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $validated = $this->normalizeCustomer($validated);
        $validated['transaction_no'] = $validated['transaction_no'] ?: $this->generateTransactionNo();
        $validated['grand_total'] = $this->calculateGrandTotal($validated);
        $validated['user_id'] = $request->user()?->id;

        $transaction = SalesTransaction::create($validated);

        return response()->json([
            'message' => 'Sales transaction created successfully',
            'data' => $transaction->load('customer:id,fname,lname,email'),
        ], 201);
    }

    public function show(SalesTransaction $salesTransaction)
    {
        return response()->json([
            'data' => $salesTransaction->load('customer:id,fname,lname,email'),
        ]);
    }

    public function update(Request $request, SalesTransaction $salesTransaction)
    {
        $validated = $this->validatedPayload($request, $salesTransaction->id);
        $validated = $this->normalizeCustomer($validated);
        $validated['transaction_no'] = $validated['transaction_no'] ?: $salesTransaction->transaction_no;
        $validated['grand_total'] = $this->calculateGrandTotal($validated);

        $salesTransaction->update($validated);

        return response()->json([
            'message' => 'Sales transaction updated successfully',
            'data' => $salesTransaction->fresh()->load('customer:id,fname,lname,email'),
        ]);
    }

    public function destroy(SalesTransaction $salesTransaction)
    {
        $salesTransaction->delete();

        return response()->json(['message' => 'Sales transaction deleted successfully']);
    }

    private function validatedPayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'transaction_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('sales_transactions', 'transaction_no')->ignore($ignoreId),
            ],
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'max:50'],
            'order_status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'transacted_at' => ['nullable', 'date'],
        ]);
    }

    private function normalizeCustomer(array $payload): array
    {
        if (!empty($payload['customer_id'])) {
            $customer = User::find($payload['customer_id']);
            if ($customer) {
                $payload['customer_name'] = $payload['customer_name'] ?: $customer->full_name;
                $payload['customer_email'] = $payload['customer_email'] ?: $customer->email;
            }
        }

        return $payload;
    }

    private function calculateGrandTotal(array $payload): float
    {
        return max(0, (float) ($payload['subtotal'] ?? 0)
            - (float) ($payload['discount_total'] ?? 0)
            + (float) ($payload['tax_total'] ?? 0)
            + (float) ($payload['shipping_total'] ?? 0));
    }

    private function generateTransactionNo(): string
    {
        $prefix = 'ST-' . now()->format('Ymd') . '-';
        $next = SalesTransaction::where('transaction_no', 'like', $prefix . '%')->count() + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
