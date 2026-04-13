<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\api\Voucher\CreditNoteController;
use App\Http\Controllers\v1\api\Voucher\DebitNoteController;
use App\Http\Controllers\v1\api\Voucher\DeliveryNoteController;
use App\Http\Controllers\v1\api\Voucher\InvoiceController;
use App\Http\Controllers\v1\api\Voucher\PurchaseBillController;
use App\Http\Controllers\v1\api\Voucher\PurchaseOrderController;
use App\Http\Controllers\v1\api\Voucher\ReceiptNoteController;
use App\Http\Controllers\v1\api\Voucher\SaleOrderController;

Route::middleware(['ability:vouchers.*'])->group(function () {
    // Sale Order Routes
    Route::prefix('sale-orders')->group(function () {
        Route::get('/', [SaleOrderController::class, 'index']);
        Route::post('/', [SaleOrderController::class, 'store']);
        Route::get('{id}', [SaleOrderController::class, 'show']);
        Route::put('{id}', [SaleOrderController::class, 'update']);
        Route::delete('{id}', [SaleOrderController::class, 'destroy']);
        Route::post('{id}/restore', [SaleOrderController::class, 'restore']);
        Route::delete('{id}/force', [SaleOrderController::class, 'forceDelete']);
        Route::post('{id}/review', [SaleOrderController::class, 'review']);
        Route::post('{id}/approve', [SaleOrderController::class, 'approve']);
        Route::post('{id}/allocate', [SaleOrderController::class, 'allocate']);
        Route::post('{id}/fulfill', [SaleOrderController::class, 'fulfill']);
    });
    // Invoice Routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('{id}', [InvoiceController::class, 'show']);
        Route::put('{id}', [InvoiceController::class, 'update']);
        Route::delete('{id}', [InvoiceController::class, 'destroy']);
        Route::post('{id}/restore', [InvoiceController::class, 'restore']);
        Route::delete('{id}/force', [InvoiceController::class, 'forceDelete']);
        Route::post('{id}/review', [InvoiceController::class, 'review']);
        Route::post('{id}/approve', [InvoiceController::class, 'approve']);
    });
    // Goods Delivery Note Routes
    Route::prefix('delivery-notes')->group(function () {
        Route::get('/', [DeliveryNoteController::class, 'index']);
        Route::post('/', [DeliveryNoteController::class, 'store']);
        Route::get('{id}', [DeliveryNoteController::class, 'show']);
        Route::put('{id}', [DeliveryNoteController::class, 'update']);
        Route::delete('{id}', [DeliveryNoteController::class, 'destroy']);
        Route::post('{id}/restore', [DeliveryNoteController::class, 'restore']);
        Route::delete('{id}/force', [DeliveryNoteController::class, 'forceDelete']);
        Route::post('{id}/review', [DeliveryNoteController::class, 'review']);
        Route::post('{id}/approve', [DeliveryNoteController::class, 'approve']);
    });
    // Credit Note (Sale Return) Routes
    Route::prefix('credit-notes')->group(function () {
        Route::get('/', [CreditNoteController::class, 'index']);
        Route::post('/', [CreditNoteController::class, 'store']);
        Route::get('{id}', [CreditNoteController::class, 'show']);
        Route::put('{id}', [CreditNoteController::class, 'update']);
        Route::delete('{id}', [CreditNoteController::class, 'destroy']);
        Route::post('{id}/restore', [CreditNoteController::class, 'restore']);
        Route::delete('{id}/force', [CreditNoteController::class, 'forceDelete']);
        Route::post('{id}/review', [CreditNoteController::class, 'review']);
        Route::post('{id}/approve', [CreditNoteController::class, 'approve']);
    });
    // Purchase Order Routes
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::get('{id}', [PurchaseOrderController::class, 'show']);
        Route::put('{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('{id}', [PurchaseOrderController::class, 'destroy']);
        Route::post('{id}/restore', [PurchaseOrderController::class, 'restore']);
        Route::delete('{id}/force', [PurchaseOrderController::class, 'forceDelete']);
        Route::post('{id}/review', [PurchaseOrderController::class, 'review']);
        Route::post('{id}/approve', [PurchaseOrderController::class, 'approve']);
    });
    // Purchase Bill Routes
    Route::prefix('purchase-bills')->group(function () {
        Route::get('/', [PurchaseBillController::class, 'index']);
        Route::post('/', [PurchaseBillController::class, 'store']);
        Route::get('{id}', [PurchaseBillController::class, 'show']);
        Route::put('{id}', [PurchaseBillController::class, 'update']);
        Route::delete('{id}', [PurchaseBillController::class, 'destroy']);
        Route::post('{id}/restore', [PurchaseBillController::class, 'restore']);
        Route::delete('{id}/force', [PurchaseBillController::class, 'forceDelete']);
        Route::post('{id}/review', [PurchaseBillController::class, 'review']);
        Route::post('{id}/approve', [PurchaseBillController::class, 'approve']);
    });
    // Goods Receipt Note Routes
    Route::prefix('receipt-notes')->group(function () {
        Route::get('/', [ReceiptNoteController::class, 'index']);
        Route::post('/', [ReceiptNoteController::class, 'store']);
        Route::get('{id}', [ReceiptNoteController::class, 'show']);
        Route::put('{id}', [ReceiptNoteController::class, 'update']);
        Route::delete('{id}', [ReceiptNoteController::class, 'destroy']);
        Route::post('{id}/restore', [ReceiptNoteController::class, 'restore']);
        Route::delete('{id}/force', [ReceiptNoteController::class, 'forceDelete']);
        Route::post('{id}/review', [ReceiptNoteController::class, 'review']);
        Route::post('{id}/approve', [ReceiptNoteController::class, 'approve']);
    });
    // Debit Note (Purchase return) Routes
    Route::prefix('debit-notes')->group(function () {
        Route::get('/', [DebitNoteController::class, 'index']);
        Route::post('/', [DebitNoteController::class, 'store']);
        Route::get('{id}', [DebitNoteController::class, 'show']);
        Route::put('{id}', [DebitNoteController::class, 'update']);
        Route::delete('{id}', [DebitNoteController::class, 'destroy']);
        Route::post('{id}/restore', [DebitNoteController::class, 'restore']);
        Route::delete('{id}/force', [DebitNoteController::class, 'forceDelete']);
        Route::post('{id}/review', [DebitNoteController::class, 'review']);
        Route::post('{id}/approve', [DebitNoteController::class, 'approve']);
    });
});