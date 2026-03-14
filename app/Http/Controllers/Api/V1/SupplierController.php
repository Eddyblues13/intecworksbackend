<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaterialOrder;
use App\Models\MaterialRequest;
use App\Models\SupplierProduct;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // ═══════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Stats
        $incomingRequests = MaterialRequest::where('status', 'pending')->count();
        $activeOrders     = MaterialOrder::where('supplier_id', $user->id)
            ->whereNotIn('status', ['delivered', 'confirmed', 'cancelled'])
            ->count();
        $completedOrders  = MaterialOrder::where('supplier_id', $user->id)
            ->whereIn('status', ['delivered', 'confirmed'])
            ->count();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]
        );

        // Subscription
        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        // Verification document (for supplier profile status)
        $verificationDoc = \App\Models\VerificationDocument::where('user_id', $user->id)->latest()->first();

        // Build profile object matching Flutter SupplierProfile entity
        $profile = [
            'id'                     => (string) $user->id,
            'userId'                 => (string) $user->id,
            'govIdUrl'               => $verificationDoc?->government_id_url,
            'businessRegUrl'         => $verificationDoc?->business_reg_url ?? $verificationDoc?->trade_cert_url,
            'verificationStatus'     => $verificationDoc?->status ?? 'draft',
            'verificationNotes'      => $verificationDoc?->rejection_reason,
            'productCategories'      => [],
            'deliveryAreas'          => [],
            'complianceScore'        => $completedOrders > 0
                ? round(($completedOrders / max($completedOrders + $activeOrders, 1)) * 100, 1)
                : 100.0,
            'performanceScore'       => 0.0,
            'subscriptionStatus'     => $sub ? 'active' : ($user->account_status === 'subscription_required' ? 'none' : 'expired'),
            'subscriptionExpiresAt'  => $sub?->expires_at?->toIso8601String(),
            'approvedAt'             => $verificationDoc?->reviewed_at?->toIso8601String(),
        ];

        // Recent incoming requests (pending material requests)
        $pendingRequests = MaterialRequest::where('status', 'pending')
            ->with('serviceJob')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($r) {
                $arr = $r->toApiArray();
                $arr['materialRequestId'] = $arr['id'] ?? '';
                $arr['supplierQuoteId']   = '';
                $arr['supplierId']        = '';
                $arr['clientId']          = '';
                $arr['jobId']             = $arr['serviceJobId'] ?? '';
                $arr['fundedAmount']      = 0.0;
                $arr['deliveryAddress']   = null;
                return $arr;
            });

        // Recent active orders
        $activeOrdersList = MaterialOrder::where('supplier_id', $request->user()->id)
            ->whereNotIn('status', ['delivered', 'confirmed', 'cancelled'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($o) {
                $arr = $o->toApiArray();
                $arr['supplierQuoteId'] = $arr['supplierQuoteId'] ?? '';
                $arr['supplierId']      = $arr['supplierId'] ?? '';
                $arr['clientId']        = '';
                $arr['jobId']           = '';
                $arr['fundedAmount']    = $arr['totalAmount'] ?? 0.0;
                $arr['deliveryAddress'] = null;
                return $arr;
            });

        return response()->json([
            'profile'             => $profile,
            'pendingRequests'     => $pendingRequests,
            'activeOrders'        => $activeOrdersList,
            'pendingRequestCount' => $incomingRequests,
            'activeOrderCount'    => $activeOrders,
            'completedOrderCount' => $completedOrders,
            'walletBalance'       => (float) $wallet->total_earned,
        ]);
    }

    // ═══════════════════════════════════════════════════
    //  MATERIAL REQUESTS (incoming)
    // ═══════════════════════════════════════════════════

    public function materialRequests(Request $request): JsonResponse
    {
        $query = MaterialRequest::query()->with('serviceJob');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(20);

        return response()->json(
            $requests->getCollection()->map(fn ($r) => $r->toApiArray())
        );
    }

    public function materialRequestDetail(MaterialRequest $materialRequest): JsonResponse
    {
        $materialRequest->load('serviceJob');
        return response()->json($materialRequest->toApiArray());
    }

    // ═══════════════════════════════════════════════════
    //  QUOTES (supplier submits to a material request)
    // ═══════════════════════════════════════════════════

    public function submitQuote(MaterialRequest $materialRequest, Request $request): JsonResponse
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string',
            'items.*.qty'    => 'required|integer|min:1',
            'items.*.price'  => 'required|numeric|min:0',
            'deliveryNotes'  => 'nullable|string',
            'totalAmount'    => 'required|numeric|min:0',
        ]);

        $user = $request->user();

        // Create order as "quote_pending"
        $order = MaterialOrder::create([
            'material_request_id' => $materialRequest->id,
            'supplier_id'         => $user->id,
            'total_amount'        => $request->totalAmount,
            'status'              => 'quote_pending',
            'quote_items'         => $request->items,
            'delivery_notes'      => $request->deliveryNotes,
        ]);

        return response()->json([
            'message' => 'Quote submitted successfully.',
            'order'   => $order->toApiArray(),
        ], 201);
    }

    public function myQuotes(Request $request): JsonResponse
    {
        $orders = MaterialOrder::where('supplier_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(
            $orders->getCollection()->map(fn ($o) => $o->toApiArray())
        );
    }

    public function quoteDetail(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        if ((int) $materialOrder->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your quote.'], 403);
        }
        return response()->json($materialOrder->toApiArray());
    }

    // ═══════════════════════════════════════════════════
    //  ORDERS
    // ═══════════════════════════════════════════════════

    public function orders(Request $request): JsonResponse
    {
        $query = MaterialOrder::where('supplier_id', $request->user()->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20);

        return response()->json(
            $orders->getCollection()->map(fn ($o) => $o->toApiArray())
        );
    }

    public function orderDetail(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        if ((int) $materialOrder->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your order.'], 403);
        }
        return response()->json($materialOrder->toApiArray());
    }

    public function markOutForDelivery(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        if ((int) $materialOrder->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your order.'], 403);
        }
        $materialOrder->update(['status' => 'out_for_delivery']);
        return response()->json(['message' => 'Marked as out for delivery.', 'order' => $materialOrder->fresh()->toApiArray()]);
    }

    public function uploadDeliveryProof(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'notes'  => 'nullable|string',
        ]);

        if ((int) $materialOrder->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your order.'], 403);
        }

        $materialOrder->update([
            'delivery_proof_images' => $request->images,
            'delivery_notes'        => $request->notes ?? $materialOrder->delivery_notes,
        ]);

        return response()->json(['message' => 'Delivery proof uploaded.', 'order' => $materialOrder->fresh()->toApiArray()]);
    }

    public function markDelivered(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        if ((int) $materialOrder->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your order.'], 403);
        }
        $materialOrder->update([
            'status'       => 'delivered',
            'delivered_at' => Carbon::now(),
        ]);
        return response()->json(['message' => 'Order marked as delivered.', 'order' => $materialOrder->fresh()->toApiArray()]);
    }

    // ═══════════════════════════════════════════════════
    //  PRODUCT CATALOG
    // ═══════════════════════════════════════════════════

    public function products(Request $request): JsonResponse
    {
        $products = SupplierProduct::where('supplier_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(
            $products->getCollection()->map(fn ($p) => $p->toApiArray())
        );
    }

    public function productDetail(SupplierProduct $supplierProduct, Request $request): JsonResponse
    {
        if ((int) $supplierProduct->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your product.'], 403);
        }
        return response()->json($supplierProduct->toApiArray());
    }

    public function createProduct(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'unit'          => 'nullable|string|max:50',
            'category'      => 'nullable|string|max:100',
            'images'        => 'nullable|array',
            'stockQuantity' => 'nullable|integer|min:0',
        ]);

        $product = SupplierProduct::create([
            'supplier_id'    => $request->user()->id,
            'name'           => $request->name,
            'description'    => $request->description,
            'price'          => $request->price,
            'unit'           => $request->unit ?? 'piece',
            'category'       => $request->category,
            'images'         => $request->images,
            'in_stock'       => true,
            'stock_quantity' => $request->stockQuantity ?? 0,
        ]);

        return response()->json(['message' => 'Product created.', 'product' => $product->toApiArray()], 201);
    }

    public function updateProduct(SupplierProduct $supplierProduct, Request $request): JsonResponse
    {
        if ((int) $supplierProduct->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your product.'], 403);
        }

        $supplierProduct->update($request->only([
            'name', 'description', 'price', 'unit', 'category',
            'images', 'in_stock', 'stock_quantity',
        ]));

        return response()->json(['message' => 'Product updated.', 'product' => $supplierProduct->fresh()->toApiArray()]);
    }

    public function deleteProduct(SupplierProduct $supplierProduct, Request $request): JsonResponse
    {
        if ((int) $supplierProduct->supplier_id !== $request->user()->id) {
            return response()->json(['message' => 'Not your product.'], 403);
        }
        $supplierProduct->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    // ═══════════════════════════════════════════════════
    //  WALLET / EARNINGS
    // ═══════════════════════════════════════════════════

    public function wallet(Request $request): JsonResponse
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]
        );
        return response()->json($wallet->toApiArray());
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if (!$wallet) {
            return response()->json([]);
        }

        $txns = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return response()->json(
            $txns->getCollection()->map(fn ($t) => $t->toApiArray())
        );
    }

    // ═══════════════════════════════════════════════════
    //  PROFILE
    // ═══════════════════════════════════════════════════

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        $verificationDoc = \App\Models\VerificationDocument::where('user_id', $user->id)->latest()->first();

        $completedOrders = MaterialOrder::where('supplier_id', $user->id)
            ->whereIn('status', ['delivered', 'confirmed'])
            ->count();
        $activeOrders = MaterialOrder::where('supplier_id', $user->id)
            ->whereNotIn('status', ['delivered', 'confirmed', 'cancelled'])
            ->count();

        return response()->json([
            'id'                     => (string) $user->id,
            'userId'                 => (string) $user->id,
            'govIdUrl'               => $verificationDoc?->government_id_url,
            'businessRegUrl'         => $verificationDoc?->business_reg_url ?? $verificationDoc?->trade_cert_url,
            'verificationStatus'     => $verificationDoc?->status ?? 'draft',
            'verificationNotes'      => $verificationDoc?->rejection_reason,
            'productCategories'      => [],
            'deliveryAreas'          => [],
            'complianceScore'        => $completedOrders > 0
                ? round(($completedOrders / max($completedOrders + $activeOrders, 1)) * 100, 1)
                : 100.0,
            'performanceScore'       => 0.0,
            'subscriptionStatus'     => $sub ? 'active' : ($user->account_status === 'subscription_required' ? 'none' : 'expired'),
            'subscriptionExpiresAt'  => $sub?->expires_at?->toIso8601String(),
            'approvedAt'             => $verificationDoc?->reviewed_at?->toIso8601String(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'fullName'  => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'location'  => 'nullable|string|max:255',
            'avatarUrl' => 'nullable|string',
        ]);

        if ($request->fullName) $user->full_name = $request->fullName;
        if ($request->phone)    $user->phone     = $request->phone;
        if ($request->location) $user->location  = $request->location;
        if ($request->avatarUrl) $user->avatar_url = $request->avatarUrl;
        $user->save();

        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        $verificationDoc = \App\Models\VerificationDocument::where('user_id', $user->id)->latest()->first();

        return response()->json([
            'id'                     => (string) $user->id,
            'userId'                 => (string) $user->id,
            'govIdUrl'               => $verificationDoc?->government_id_url,
            'businessRegUrl'         => $verificationDoc?->business_reg_url ?? $verificationDoc?->trade_cert_url,
            'verificationStatus'     => $verificationDoc?->status ?? 'draft',
            'verificationNotes'      => $verificationDoc?->rejection_reason,
            'productCategories'      => [],
            'deliveryAreas'          => [],
            'complianceScore'        => 100.0,
            'performanceScore'       => 0.0,
            'subscriptionStatus'     => $sub ? 'active' : 'none',
            'subscriptionExpiresAt'  => $sub?->expires_at?->toIso8601String(),
            'approvedAt'             => $verificationDoc?->reviewed_at?->toIso8601String(),
        ]);
    }

    public function complianceScore(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalOrders     = MaterialOrder::where('supplier_id', $user->id)->count();
        $deliveredOnTime = MaterialOrder::where('supplier_id', $user->id)
            ->whereIn('status', ['delivered', 'confirmed'])
            ->count();
        $cancelledOrders = MaterialOrder::where('supplier_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $score = $totalOrders > 0
            ? round(($deliveredOnTime / $totalOrders) * 100, 1)
            : 100.0;

        return response()->json([
            'complianceScore' => $score,
            'totalOrders'     => $totalOrders,
            'deliveredOnTime' => $deliveredOnTime,
            'cancelledOrders' => $cancelledOrders,
        ]);
    }
}
