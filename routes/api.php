<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Userlog\UserLogController;
use App\Http\Controllers\Counter\CounterController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Inventorytype\InventoryTypeController;
use App\Http\Controllers\InventoryCategory\InventoryCategoryController;
use App\Http\Controllers\Userlevel\UserLevelController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Approvalitems\ApprovalItemsController;
use App\Http\Controllers\Approvalscheme\ApprovalSchemeController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Customerscontact\CustomerContactController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\productsn\ProductsnController;
use App\Http\Controllers\survey\SurveyController;
use App\Http\Controllers\salescontract\SalescontractController;
use App\Http\Controllers\salesretur\SalesreturController;
use App\Http\Controllers\suppliers\SupplierController;

// Endpoint: POST /api/login
Route::post('/login', [LoginController::class, 'login'])->name('api.login');

// Endpoint: Notification API
Route::prefix('notification')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/{id}', [NotificationController::class, 'show']);
    Route::post('/', [NotificationController::class, 'store']);
    Route::put('/{id}', [NotificationController::class, 'update']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});

// Endpoint: Users CRUD API
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);           // data()
    Route::get('/levels', [UserController::class, 'levels']);     // data_level()
    Route::get('/{username}', [UserController::class, 'show']);   // edit() / data_header()
    Route::post('/', [UserController::class, 'store']);           // simpan()
    Route::put('/{username}', [UserController::class, 'update']); // update()
});

// Endpoint: User Log API
Route::prefix('userlog')->group(function () {
    Route::get('/', [UserLogController::class, 'index']);
});

// Endpoint: User Level API
Route::prefix('userlevel')->group(function () {
    Route::get('/', [UserLevelController::class, 'index']);
    Route::get('/support-data', [UserLevelController::class, 'supportData']);
    Route::get('/{id}', [UserLevelController::class, 'show']);
    Route::post('/', [UserLevelController::class, 'store']);
    Route::put('/{id}', [UserLevelController::class, 'update']);
    Route::delete('/{id}', [UserLevelController::class, 'destroy']);
});

// Endpoint: Counter API
Route::prefix('counter')->group(function () {
    Route::get('/', [CounterController::class, 'index']);
    Route::get('/{id_counter}/{periode}', [CounterController::class, 'show']);
    Route::put('/{id_counter}/{periode}', [CounterController::class, 'update']);
});

// Endpoint: Setting API
Route::prefix('setting')->group(function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::get('/{id}', [SettingController::class, 'show']);
    Route::put('/{id}', [SettingController::class, 'update']);
});

// Endpoint: Inventory Category API
Route::prefix('inventorycategory')->group(function () {
    Route::get('/', [InventoryCategoryController::class, 'index']);
    Route::get('/{id}', [InventoryCategoryController::class, 'show']);
    Route::post('/', [InventoryCategoryController::class, 'store']);
    Route::put('/{id}', [InventoryCategoryController::class, 'update']);
});

// Endpoint: Inventory Type API
Route::prefix('inventorytype')->group(function () {
    Route::get('/', [InventoryTypeController::class, 'index']);
    Route::get('/{id}', [InventoryTypeController::class, 'show']);
    Route::post('/', [InventoryTypeController::class, 'store']);
    Route::put('/{id}', [InventoryTypeController::class, 'update']);
});

// Endpoint: Approval Items API
Route::prefix('approvalitems')->group(function () {
    Route::get('/', [ApprovalItemsController::class, 'index']);
    Route::get('/support-data', [ApprovalItemsController::class, 'supportData']);
    Route::get('/{id}', [ApprovalItemsController::class, 'show']);
    Route::post('/', [ApprovalItemsController::class, 'store']);
    Route::put('/{id}', [ApprovalItemsController::class, 'update']);
});

// Endpoint: Approval Scheme API
Route::prefix('approvalscheme')->group(function () {
    Route::get('/', [ApprovalSchemeController::class, 'index']);
    Route::get('/support-data', [ApprovalSchemeController::class, 'supportData']);
    Route::get('/{id}', [ApprovalSchemeController::class, 'show']);
    Route::post('/', [ApprovalSchemeController::class, 'store']);
    Route::put('/{id}', [ApprovalSchemeController::class, 'update']);
    Route::delete('/{id}', [ApprovalSchemeController::class, 'destroy']);
});

// Endpoint: Employee API
Route::prefix('employee')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/support-data', [EmployeeController::class, 'supportData']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::put('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
});

// Endpoint: Customers API
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::get('/support-data', [CustomerController::class, 'supportData']);
    Route::get('/kabupaten', [CustomerController::class, 'getKabupaten']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});

// Endpoint: Customer Contacts API
Route::prefix('customerscontact')->group(function () {
    Route::get('/', [CustomerContactController::class, 'index']);
    Route::get('/{id}', [CustomerContactController::class, 'show']);
    Route::post('/', [CustomerContactController::class, 'store']);
    Route::put('/{id}', [CustomerContactController::class, 'update']);
    Route::delete('/{id}', [CustomerContactController::class, 'destroy']);
});

// Endpoint: Product API
Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/support-data', [ProductController::class, 'supportData']);
    Route::get('/sub-kategori', [ProductController::class, 'getSubKategori']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/', [ProductController::class, 'store']);
    Route::post('/{id}', [ProductController::class, 'update']); // Use POST because of form-data file uploads (method spoofing can also be used _method=PUT)
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

// Endpoint: Product Category API
Route::prefix('productcategory')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductCategory\ProductCategoryController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\ProductCategory\ProductCategoryController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\ProductCategory\ProductCategoryController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\ProductCategory\ProductCategoryController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ProductCategory\ProductCategoryController::class, 'destroy']);
});

// Endpoint: Product Sub Category API
Route::prefix('productsubcategory')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'supportData']);
    Route::get('/{id}', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ProductSubCategory\ProductSubCategoryController::class, 'destroy']);
});

// Endpoint: Product Unit API
Route::prefix('productunit')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductUnit\ProductUnitController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\ProductUnit\ProductUnitController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\ProductUnit\ProductUnitController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\ProductUnit\ProductUnitController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ProductUnit\ProductUnitController::class, 'destroy']);
});

// Endpoint: Product Brand API
Route::prefix('productbrand')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductBrand\ProductBrandController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\ProductBrand\ProductBrandController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\ProductBrand\ProductBrandController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\ProductBrand\ProductBrandController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ProductBrand\ProductBrandController::class, 'destroy']);
});

// Endpoint: Product SN API
Route::prefix('productsn')->group(function () {
    Route::get('/', [\App\Http\Controllers\productsn\ProductsnController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\productsn\ProductsnController::class, 'supportData']);
    Route::get('/{id}', [\App\Http\Controllers\productsn\ProductsnController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\productsn\ProductsnController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\productsn\ProductsnController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\productsn\ProductsnController::class, 'destroy']);
});

// Endpoint: Product Price API
Route::prefix('productprice')->group(function () {
    Route::get('/', [\App\Http\Controllers\productprice\ProductpriceController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\productprice\ProductpriceController::class, 'supportData']);
    Route::get('/{id}', [\App\Http\Controllers\productprice\ProductpriceController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\productprice\ProductpriceController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\productprice\ProductpriceController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\productprice\ProductpriceController::class, 'destroy']);
});

// Endpoint: Product Price Marketing API
Route::prefix('productpricemkt')->group(function () {
    Route::get('/', [\App\Http\Controllers\productpricemkt\ProductpricemktController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\productpricemkt\ProductpricemktController::class, 'show']);
    Route::post('/keranjang', [\App\Http\Controllers\productpricemkt\ProductpricemktController::class, 'tambahKeranjang']);
});

// Endpoint: Product Price Agent API
Route::prefix('productpriceagent')->group(function () {
    Route::get('/', [\App\Http\Controllers\productpriceagent\ProductpriceagentController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\productpriceagent\ProductpriceagentController::class, 'show']);
    Route::post('/keranjang', [\App\Http\Controllers\productpriceagent\ProductpriceagentController::class, 'tambahKeranjang']);
});

// Endpoint: Product Price Log API
Route::prefix('productpricelog')->group(function () {
    Route::get('/', [\App\Http\Controllers\productpricelog\ProductpricelogController::class, 'index']);
});

// Endpoint: Brosur API
Route::prefix('brosur')->group(function () {
    Route::get('/', [\App\Http\Controllers\brosur\BrosurController::class, 'index']);
    Route::get('/generate', [\App\Http\Controllers\brosur\BrosurController::class, 'generate']);
});

// Endpoint: Product Price Request API
Route::prefix('productpricereq')->group(function () {
    Route::get('/', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'supportData']);
    Route::get('/{id}', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'update']);
    Route::patch('/{id}/status', [\App\Http\Controllers\productpricereq\ProductpricereqController::class, 'updateStatus']);
});

// Endpoint: CSR API
Route::prefix('csr')->group(function () {
    Route::get('/', [\App\Http\Controllers\csr\CsrController::class, 'index']);
    Route::get('/form-options', [\App\Http\Controllers\csr\CsrController::class, 'getFormOptions']);
    Route::get('/check-sn', [\App\Http\Controllers\csr\CsrController::class, 'checkSerialNumber']);
    Route::get('/barcode-data', [\App\Http\Controllers\csr\CsrController::class, 'getBarcodeData']);
    Route::get('/{id}', [\App\Http\Controllers\csr\CsrController::class, 'show'])->where('id', '.*');
    Route::post('/', [\App\Http\Controllers\csr\CsrController::class, 'store']);
    Route::post('/{id}/confirm', [\App\Http\Controllers\csr\CsrController::class, 'confirm'])->where('id', '.*');
    Route::post('/{id}/cancel', [\App\Http\Controllers\csr\CsrController::class, 'cancel'])->where('id', '.*');
    Route::post('/{id}', [\App\Http\Controllers\csr\CsrController::class, 'update'])->where('id', '.*'); 
});

// Endpoint: CST API
Route::prefix('cst')->group(function () {
    Route::get('/', [\App\Http\Controllers\cst\CstController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\cst\CstController::class, 'show'])->where('id', '.*');
    Route::post('/{id}', [\App\Http\Controllers\cst\CstController::class, 'update'])->where('id', '.*');
});

// Endpoint: LKT API
Route::prefix('lkt')->group(function () {
    Route::get('/', [\App\Http\Controllers\lkt\LktController::class, 'index']);
    Route::get('/teknisi-options', [\App\Http\Controllers\lkt\LktController::class, 'getTeknisiOptions']);
    Route::get('/{id}', [\App\Http\Controllers\lkt\LktController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\lkt\LktController::class, 'store']);
    Route::post('/{id}', [\App\Http\Controllers\lkt\LktController::class, 'update']);
    Route::post('/{id}/done', [\App\Http\Controllers\lkt\LktController::class, 'done']);
    Route::post('/{id}/cancel', [\App\Http\Controllers\lkt\LktController::class, 'cancel']);
    
    // Realisasi (Visit) routes
    Route::post('/{id}/realisasi', [\App\Http\Controllers\lkt\LktController::class, 'storeRealisasi']);
    Route::post('/realisasi/{id}', [\App\Http\Controllers\lkt\LktController::class, 'updateRealisasi']);
    Route::post('/realisasi/{id}/confirm', [\App\Http\Controllers\lkt\LktController::class, 'confirmRealisasi']);
    Route::post('/realisasi/{id}/close', [\App\Http\Controllers\lkt\LktController::class, 'closeRealisasi']);
    Route::post('/realisasi/{id}/cancel', [\App\Http\Controllers\lkt\LktController::class, 'cancelRealisasi']);
    Route::post('/realisasi/{id}/reject', [\App\Http\Controllers\lkt\LktController::class, 'rejectLktRealisasi']);
});

// Endpoint: Quotations API
Route::prefix('quotations')->group(function () {
    Route::get('/', [\App\Http\Controllers\Quotations\QuotationController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\Quotations\QuotationController::class, 'supportData']);
    Route::get('/{id}', [\App\Http\Controllers\Quotations\QuotationController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Quotations\QuotationController::class, 'store']);
    Route::post('/{id}', [\App\Http\Controllers\Quotations\QuotationController::class, 'update']);
    Route::post('/{id}/confirm-to-so', [\App\Http\Controllers\Quotations\QuotationController::class, 'confirmToSO']);
});

// Endpoint: Sales Order (SO) API
Route::prefix('so')->group(function () {
    Route::get('/', [\App\Http\Controllers\So\SoController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\So\SoController::class, 'show']);
    Route::post('/{id}', [\App\Http\Controllers\So\SoController::class, 'update']);
    Route::post('/confirm', [\App\Http\Controllers\So\SoController::class, 'confirm']);
    Route::post('/check-payment', [\App\Http\Controllers\So\SoController::class, 'checkPayment']);
    Route::post('/cancel', [\App\Http\Controllers\So\SoController::class, 'cancel']);
});

// Endpoint: Delivery Order (DO) API
Route::prefix('do')->group(function () {
    Route::get('/', [\App\Http\Controllers\Do\DoController::class, 'index']);
    Route::get('/sn/{id_product}', [\App\Http\Controllers\Do\DoController::class, 'getAvailableSn']);
    Route::get('/{id}', [\App\Http\Controllers\Do\DoController::class, 'show']);
    Route::post('/confirm', [\App\Http\Controllers\Do\DoController::class, 'confirm']);
    Route::post('/check-payment', [\App\Http\Controllers\Do\DoController::class, 'cekPayment']);
    Route::post('/check-availability', [\App\Http\Controllers\Do\DoController::class, 'cekAvailability']);
    Route::post('/delivered', [\App\Http\Controllers\Do\DoController::class, 'delivered']);
    Route::post('/cancel', [\App\Http\Controllers\Do\DoController::class, 'cancel']);
    Route::post('/split', [\App\Http\Controllers\Do\DoController::class, 'split']);
    Route::post('/revisi', [\App\Http\Controllers\Do\DoController::class, 'revisi']);
    Route::post('/{id}', [\App\Http\Controllers\Do\DoController::class, 'update']);
});

// Endpoint: Customer Invoice API
Route::prefix('customerinvoice')->group(function () {
    Route::get('/', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'index']);
    Route::get('/support-data', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'supportData']);
    Route::get('/ar-pelunasan', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'getArPelunasan']);
    Route::get('/retur', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'getRetur']);
    Route::get('/kb-masuk', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'getKbMasuk']);
    Route::get('/giro', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'getDataGiro']);
    Route::get('/{id}', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'show']);
    Route::post('/invoice-detail', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'storeInvoiceDetail']);
    Route::post('/ganti-status', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'gantiStatus']);
    Route::post('/back-status', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'backStatus']);
    Route::post('/posting', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'posting']);
    Route::post('/unposting', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'unposting']);
    Route::post('/ar-pelunasan', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'simpanArPelunasan']);
    Route::post('/update-code-pi', [\App\Http\Controllers\customerinvoice\CustomerInvoiceController::class, 'updateCodePi']);
});

// Endpoint: Survey API
Route::prefix('survey')->group(function () {
    Route::get('/', [SurveyController::class, 'index']);
    Route::get('/support-data', [SurveyController::class, 'supportData']);
    Route::get('/{id}', [SurveyController::class, 'show']);
    Route::post('/', [SurveyController::class, 'store']);
    Route::put('/{id}', [SurveyController::class, 'update']);
    Route::post('/{id}/update-afs', [SurveyController::class, 'updateAfs']);
    Route::post('/{id}/update-gudang', [SurveyController::class, 'updateGudang']);
    Route::post('/{id}/update-progress', [SurveyController::class, 'updateProgress']);
    Route::post('/{id}/cancel', [SurveyController::class, 'cancel']);
    Route::post('/{id}/confirm', [SurveyController::class, 'confirm']);
    Route::post('/{id}/approve-afs', [SurveyController::class, 'approveAfs']);
    Route::post('/{id}/approve-gudang', [SurveyController::class, 'approveGudang']);
    Route::post('/{id}/upload', [SurveyController::class, 'upload']);
    Route::post('/{id}/selesai', [SurveyController::class, 'selesai']);
});

// Endpoint: Sales Contract API
Route::prefix('salescontract')->group(function () {
    Route::get('/', [SalescontractController::class, 'index']);
    Route::get('/create/{id_so}', [SalescontractController::class, 'createData']);
    Route::get('/{id}', [SalescontractController::class, 'show']);
    Route::post('/', [SalescontractController::class, 'store']);
    Route::put('/{id}', [SalescontractController::class, 'update']);
});

// Endpoint: Sales Retur API
Route::prefix('salesretur')->group(function () {
    Route::get('/', [SalesreturController::class, 'index']);
    Route::get('/support-data', [SalesreturController::class, 'supportData']);
    Route::post('/get-do', [SalesreturController::class, 'getDo']);
    Route::post('/get-do-detail', [SalesreturController::class, 'getDoDetail']);
    Route::post('/get-so', [SalesreturController::class, 'getSo']);
    Route::get('/{id}', [SalesreturController::class, 'show']);
    Route::post('/', [SalesreturController::class, 'store']);
    Route::put('/{id}', [SalesreturController::class, 'update']);
    Route::post('/{id}/confirm', [SalesreturController::class, 'confirm']);
    Route::post('/{id}/cancel', [SalesreturController::class, 'cancel']);
});

// Endpoint: Suppliers API
Route::prefix('suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index']);
    Route::get('/support-data', [SupplierController::class, 'supportData']);
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::post('/{id}', [SupplierController::class, 'update']);
    Route::post('/search', [SupplierController::class, 'cariSupplier']);
});
