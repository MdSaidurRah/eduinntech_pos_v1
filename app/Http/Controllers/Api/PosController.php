<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Order\PosRequest;
use App\Models\Order;
use App\Models\OrderItemTax;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\Settings;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;

class PosController extends ApiBaseController
{
    public function posProducts()
    {
        $request = request();
        $allProducs = [];
        $warehouse = warehouse();
        $warehouseId = $warehouse->id;
        $limit = (int) $request->input('limit', 48);
        $offset = (int) $request->input('offset', 0);

        if ($limit < 1 || $limit > 100) {
            $limit = 48;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        $productsQuery = Product::select(
            'products.id',
            'products.name',
            'products.image',
            'products.product_type',
            'products.unit_id',
            'product_details.sales_price',
            'product_details.sales_tax_type',
            'product_details.tax_id',
            'product_details.current_stock',
            'taxes.rate',
            'units.short_name as unit_short_name',
            'units.name as unit_name'
        )
            ->join('product_details', 'product_details.product_id', '=', 'products.id')
            ->leftJoin('taxes', 'taxes.id', '=', 'product_details.tax_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->where('product_details.warehouse_id', '=', $warehouseId)
            ->where(function ($query) {
                $query->where(function ($qry) {
                    $qry->where('products.product_type', '!=', 'service')
                        ->where('product_details.current_stock', '>', 0);
                })->orWhere('products.product_type', '=', 'service');
            });

        if ($warehouse->products_visibility == 'warehouse') {
            $productsQuery->where('products.warehouse_id', '=', $warehouse->id);
        }

        if ($request->has('category_id') && $request->category_id != "") {
            $categoryId = $this->getIdFromHash($request->category_id);
            $productsQuery->where('products.category_id', '=', $categoryId);
        }

        if ($request->has('brand_id') && $request->brand_id != "") {
            $brandId = $this->getIdFromHash($request->brand_id);
            $productsQuery->where('products.brand_id', '=', $brandId);
        }

        $totalProducts = (clone $productsQuery)->count();
        $products = $productsQuery
            ->orderBy('products.name')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $productImagePath = Common::getFolderPath('productImagePath');
        $defaultImage = asset('images/product.png');

        foreach ($products as $product) {
            $unitPrice = $product->sales_price;
            $singleUnitPrice = $unitPrice;
            $taxRate = $product->rate != '' ? $product->rate : 0;

            if ($taxRate != 0) {
                if ($product->sales_tax_type == 'inclusive') {
                    $subTotal = $singleUnitPrice;
                    $singleUnitPrice = ($singleUnitPrice * 100) / (100 + $taxRate);
                    $taxAmount = ($singleUnitPrice) * ($taxRate / 100);
                } else {
                    $taxAmount = ($singleUnitPrice * ($taxRate / 100));
                    $subTotal = $singleUnitPrice + $taxAmount;
                }
            } else {
                $taxAmount = 0;
                $subTotal = $singleUnitPrice;
            }

            $allProducs[] = [
                'item_id' => '',
                'xid' => Common::getHashFromId($product->id),
                'name' => $product->name,
                'image' => $product->image,
                'image_url' => $product->image == null ? $defaultImage : Common::getFileUrl($productImagePath, $product->image),
                'discount_rate' => 0,
                'total_discount' => 0,
                'x_tax_id' => $product->tax_id ? Common::getHashFromId($product->tax_id) : null,
                'tax_type' => $product->sales_tax_type,
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'x_unit_id' => $product->getRawOriginal('unit_id') ? Common::getHashFromId($product->getRawOriginal('unit_id')) : null,
                'unit' => [
                    'name' => $product->unit_name,
                    'short_name' => $product->unit_short_name,
                ],
                'unit_price' => $unitPrice,
                'single_unit_price' => $singleUnitPrice,
                'subtotal' => $subTotal,
                'quantity' => 1,
                'stock_quantity' => $product->current_stock,
                'unit_short_name' => $product->unit_short_name,
                'product_type' => $product->product_type,
            ];
        }

        return ApiResponse::make('Data fetched', [
            'products' => $allProducs,
            'has_more' => ($offset + $limit) < $totalProducts,
            'total' => $totalProducts,
        ]);
    }

    public function addPosPayment(PosRequest $request)
    {
        return ApiResponse::make('Success');
    }

    public function savePosPayments()
    {

        $request = request();
        $loggedInUser = user();
        $warehouse = warehouse();
        $orderDetails = $request->details;
        $orderType = $request->order_type;
        $isQuote = $orderType == 'quotations';
        $isDraft = $orderType == 'pos-drafts';
        $posDefaultStatus = $isQuote || $isDraft ? 'pending' : $warehouse->default_pos_order_status;

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        $paymentTotal = collect($allPayments)->sum(function ($item) {
            return (float) ($item['amount'] ?? 0);
        });

        $customerId = null;
        if (isset($orderDetails['user_id']) && $orderDetails['user_id'] != '') {
            $customerId = $this->getIdFromHash($orderDetails['user_id']);
        }
        $customer = $customerId ? User::find($customerId) : null;
        $isWalkInCustomer = $customer && (int) $customer->is_walkin_customer === 1;

        if (!$isQuote && !$isDraft && $isWalkInCustomer && $paymentTotal <= 0) {
            throw new ApiException('Walk In Customer cannot complete order with 0 payment. Please add a payment first.');
        }

        if (count($allPayments) > 0 && !$isDraft) {
            if ($paymentTotal > $orderDetails['subtotal']) {
                throw new ApiException('Paid amount should be less than or equal to Grand Total');
            }
        }

        if (!$isQuote && !$isDraft) {
            $this->assertPosProductStock($request->input('product_items', []), $warehouse->id);
        }

        $order = null;
        $oldOrderId = "";
        $draftUniqueId = $request->input('draft_xid');

        if ($isDraft && $draftUniqueId) {
            $order = Order::where('unique_id', $draftUniqueId)
                ->where('order_type', 'pos-drafts')
                ->first();
            if ($order) {
                $this->deleteDraftItems($order);
                $oldOrderId = "";
            }
        }

        if (!$order) {
            $order = new Order();
            $order->unique_id = Common::generateOrderUniqueId();
        }

        if ($isQuote) {
            $order->order_type = 'quotations';
        } elseif ($isDraft) {
            $order->order_type = 'pos-drafts';
        } else {
            $order->order_type = 'sales';
        }
        $order->invoice_type = "pos";
        $order->invoice_number = $order->invoice_number ?: "";
        $order->order_date = Carbon::now();
        $order->warehouse_id = $warehouse->id;
        $order->user_id = isset($orderDetails['user_id']) ? $orderDetails['user_id'] : null;
        $order->tax_id = isset($orderDetails['tax_id']) ? $orderDetails['tax_id'] : null;
        $order->tax_rate = $orderDetails['tax_rate'];
        $order->tax_amount = $orderDetails['tax_amount'];
        $order->discount = $orderDetails['discount'];
        $order->shipping = $orderDetails['shipping'];
        $order->subtotal = 0;
        $order->total = $orderDetails['subtotal'];
        $order->paid_amount = 0;
        $order->due_amount = $order->total;
        $order->order_status = $posDefaultStatus;
        $order->staff_user_id = $loggedInUser->id;
        $order->save();

        if ($order->invoice_number == "" || $isDraft) {
            $order->invoice_number = Common::getTransactionNumber($order->order_type, $order->id);
            $order->save();
        }

        Common::storeAndUpdateOrder($order, $oldOrderId);

        if (!$isDraft) {
            Common::updateWarehouseHistory('order', $order, "add_edit");
        }

        $allPayments = $isDraft ? [] : $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        foreach ($allPayments as $allPayment) {
            // Save Order Payment
            if ($allPayment['amount'] > 0 && $allPayment['payment_mode_id'] != '') {
                $payment = new Payment();
                $payment->warehouse_id = $warehouse->id;
                $payment->payment_type = "in";
                $payment->date = Carbon::now();
                $payment->amount = $allPayment['amount'];
                $payment->paid_amount = $allPayment['amount'];
                $payment->payment_mode_id = $allPayment['payment_mode_id'];
                $payment->notes = $allPayment['notes'];
                $payment->user_id = $order->user_id;
                $payment->save();

                // Generate and save payment number
                $paymentType = 'payment-' . $payment->payment_type;
                $payment->payment_number = Common::getTransactionNumber($paymentType, $payment->id);
                $payment->save();

                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $order->id;
                $orderPayment->payment_id = $payment->id;
                $orderPayment->amount = $allPayment['amount'];
                $orderPayment->save();
            }
        }

        Common::updateOrderAmount($order->id);

        if (!$isDraft && !$isQuote && $draftUniqueId) {
            $draft = Order::where('unique_id', $draftUniqueId)
                ->where('order_type', 'pos-drafts')
                ->first();
            if ($draft) {
                $this->deleteDraftOrder($draft);
            }
        }

        $savedOrder = Order::select('id', 'unique_id', 'invoice_number', 'user_id', 'staff_user_id', 'order_date', 'discount', 'shipping', 'tax_amount', 'subtotal', 'total', 'paid_amount', 'due_amount', 'total_items', 'total_quantity', 'order_type')
            ->with(['user:id,name,email', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax', 'items.product:id,name', 'items.unit:id,name,short_name', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
            ->find($order->id);

        $totalMrp = 0;
        $totalTax = 0;
        foreach ($savedOrder->items as $orderItem) {
            $totalMrp += ($orderItem->quantity * $orderItem->mrp);
            $totalTax += $orderItem->total_tax;
        }

        $savingOnMrp = $totalMrp - $savedOrder->total;
        $saving_percentage = $totalMrp > 0 ? number_format((float)($savingOnMrp / $totalMrp * 100), 2, '.', '') : 0;

        $savedOrder->saving_on_mrp = $savingOnMrp;
        $savedOrder->saving_percentage = $saving_percentage;
        $savedOrder->total_tax_on_items = $totalTax + $savedOrder->tax_amount;

        return ApiResponse::make('POS Data Saved', [
            'order' => $savedOrder,
        ]);
    }

    public function posDrafts()
    {
        $warehouse = warehouse();

        $drafts = Order::select(
            'id',
            'unique_id',
            'invoice_number',
            'user_id',
            'order_date',
            'total',
            'total_items',
            'total_quantity',
            'order_type'
        )
            ->with(['user:id,name'])
            ->where('order_type', 'pos-drafts')
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::make('Data fetched', [
            'drafts' => $drafts,
        ]);
    }

    public function showPosDraft($uniqueId)
    {
        $warehouse = warehouse();

        $order = Order::with([
            'user:id,name',
            'items',
            'items.product',
            'items.unit',
            'tax',
        ])
            ->where('unique_id', $uniqueId)
            ->where('order_type', 'pos-drafts')
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (!$order) {
            throw new ApiException('Draft not found');
        }

        $products = [];
        $sn = 1;
        foreach ($order->items as $item) {
            $product = $item->product;
            $unit = $item->unit;
            $productDetails = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('warehouse_id', $order->warehouse_id)
                ->where('product_id', $item->product_id)
                ->first();

            $products[] = [
                'item_id' => '',
                'xid' => $product ? $product->xid : null,
                'name' => $product ? $product->name : '',
                'image' => $product ? $product->image : null,
                'image_url' => $product ? $product->image_url : null,
                'discount_rate' => $item->discount_rate,
                'total_discount' => $item->total_discount,
                'x_tax_id' => $item->x_tax_id,
                'tax_type' => $item->tax_type,
                'tax_rate' => $item->tax_rate,
                'total_tax' => $item->total_tax,
                'tax_amount' => $item->total_tax,
                'x_unit_id' => $item->x_unit_id,
                'unit' => $unit,
                'unit_price' => $item->unit_price,
                'single_unit_price' => $item->single_unit_price,
                'subtotal' => $item->subtotal,
                'quantity' => $item->quantity,
                'stock_quantity' => $productDetails ? $productDetails->current_stock : 0,
                'unit_short_name' => $unit ? $unit->short_name : '',
                'product_type' => $product ? $product->product_type : 'single',
                'sn' => $sn,
            ];
            $sn++;
        }

        return ApiResponse::make('Data fetched', [
            'order' => $order,
            'products' => $products,
        ]);
    }

    public function deletePosDraft($uniqueId)
    {
        $warehouse = warehouse();

        $order = Order::where('unique_id', $uniqueId)
            ->where('order_type', 'pos-drafts')
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (!$order) {
            throw new ApiException('Draft not found');
        }

        $this->deleteDraftOrder($order);

        return ApiResponse::make('Draft deleted');
    }

    public function checkPosStock()
    {
        $warehouse = warehouse();
        $this->assertPosProductStock(request()->input('product_items', []), $warehouse->id);

        return ApiResponse::make('Stock available');
    }

    private function getPosStockIssues($productItems, $warehouseId)
    {
        $issues = [];

        if (!is_array($productItems)) {
            return $issues;
        }

        foreach ($productItems as $productItem) {
            $productItem = (object) $productItem;

            if (isset($productItem->product_type) && $productItem->product_type == 'service') {
                continue;
            }

            if (empty($productItem->xid)) {
                continue;
            }

            $productId = $this->getIdFromHash($productItem->xid);
            $details = ProductDetails::withoutGlobalScope('current_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->first();

            $stock = $details ? (float) $details->current_stock : 0;
            $qty = (float) ($productItem->quantity ?? 0);

            if ($qty > $stock) {
                $issues[] = [
                    'xid' => $productItem->xid,
                    'name' => $productItem->name ?? 'Product',
                    'stock' => $stock,
                    'quantity' => $qty,
                ];
            }
        }

        return $issues;
    }

    private function assertPosProductStock($productItems, $warehouseId)
    {
        $issues = $this->getPosStockIssues($productItems, $warehouseId);

        if (count($issues) > 0) {
            $parts = [];
            foreach ($issues as $issue) {
                $parts[] = $issue['name'] . ' (available: ' . $issue['stock'] . ', required: ' . $issue['quantity'] . ')';
            }

            throw new ApiException('Insufficient stock: ' . implode(', ', $parts));
        }
    }

    private function deleteDraftItems($order)
    {
        $itemIds = $order->items()->pluck('id')->toArray();
        if (count($itemIds) > 0) {
            OrderItemTax::whereIn('order_item_id', $itemIds)->delete();
        }
        $order->items()->delete();
    }

    private function deleteDraftOrder($order)
    {
        $this->deleteDraftItems($order);
        $order->delete();
    }
}
