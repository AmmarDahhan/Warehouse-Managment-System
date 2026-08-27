<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CalendarNoteController;
use App\Http\Controllers\CustodyController;
use App\Http\Controllers\CustodyReturnController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EntryNoteController;
use App\Http\Controllers\ExitNoteController;
use App\Http\Controllers\InstallationReportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ReceivingNoteController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScrapNoteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseKeeperController;
use App\Models\EntryNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/products',[ProductController::class,'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{id}/details', [ProductController::class, 'details']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //ENTRY NOTE

    Route::get('/allEntryNote',[EntryNoteController::class,'index']);
    Route::get('/EntryNote/{id}/details',[EntryNoteController::class,'show']);
    Route::post('/entryNote',[EntryNoteController::class,'store']);

    //EXIT NOTE
    Route::get('/allExitNote',[ExitNoteController::class,'index']);
    Route::get('/ExitNote/{id}/details',[ExitNoteController::class,'show']);
    Route::post('/exitNote',[ExitNoteController::class,'store']);

    //    WAREHOUSE
    Route::post('/warehouses/store', [WarehouseController::class, 'store']);
    Route::put('/warehouses/update/{id}', [WarehouseController::class, 'update']);
    Route::delete('/warehouses/destroy/{id}', [WarehouseController::class, 'destroy']);
    Route::get('/warehouses/index', [WarehouseController::class, 'index']);
    Route::get('warehouses/show/{id}', [WarehouseController::class, 'show']);


    //    PRODUCTS
    Route::post('/products/store', [ProductController::class, 'store']);
    Route::post('products/update/{id}', [ProductController::class, 'update']);
    Route::delete('products/delete/{id}', [ProductController::class, 'destroy']);
    Route::get('/products',[ProductController::class,'index']);
    Route::get('products/show/{id}', [ProductController::class, 'show']);

    //  LOCATIONS
    // مسار لإنشاء موقع جديد
    Route::post('/locations', [LocationController::class, 'store']);
    // مسار لعرض جميع المواقع
    Route::get('/locations', [LocationController::class, 'index']);

    Route::get('/locations/show/{id}', [LocationController::class, 'show']);

    // مسار للحصول على مواقع منتج معين
    Route::get('/locations/product-locations', [LocationController::class, 'getProductLocations']);

// مسار للبحث عن المواقع المتاحة (POST لأنها تستقبل بيانات في الـ body)
    Route::post('/locations/search-available', [LocationController::class, 'searchAvailableLocations']);

    //MATERIAL REQUEST
    Route::post('/MRequest',[MaterialRequestController::class,'store']);
    Route::get('/allRequestMaterial',[MaterialRequestController::class,'index']);
    Route::get('/pendingRequestMaterial',[MaterialRequestController::class,'pendingRequests']);
    Route::get('/M-Request/show/{id}',[MaterialRequestController::class,'showRequest']);
    Route::put('/materialRequests/{id}/edit', [MaterialRequestController::class, 'editRequest']);
    Route::put('/materialRequests/{id}/reject', [MaterialRequestController::class, 'rejectRequest']);
    Route::put('/materialRequests/{id}/approve', [MaterialRequestController::class, 'approveRequest']);
    Route::get('/material-requests/user/{userId}', [MaterialRequestController::class, 'userRequests']);


    //CUSTODY
    // إنشاء عهدة بشكل يدوي
    Route::post('/custody/store', [CustodyController::class, 'store']);
    // عرض كل عهد شخص ما
    Route::get('/custody/allForUser',[CustodyController::class,'showAllForUser']);
    //عرض عهدة محددة
    Route::get('/custody/specific/{custody}', [CustodyController::class, 'showSpecific']);
    // عرض كل العهد في جدول العهد
    Route::get('/custody/showAll', [CustodyController::class, 'showAll']);
    // عرض كل العهد الموجودة في غرفة ما
    Route::get('/rooms/{roomId}/custodies', [CustodyController::class, 'showRoomCustodies']);
    // جلب غرف شخص ما
    Route::get('/users/{user}/rooms', [CustodyController::class, 'getSpecificUserRooms']);
    // اسناد أغراض إلى غرف
    Route::post('/custody-items/assign-rooms-bulk', [CustodyController::class, 'assignRoomsToCustodyItems']);


    // return Custody
    // إنشاء طلب إرجاع جديد
    Route::post('/custody-returns', [CustodyReturnController::class, 'createReturnRequest']);
    Route::post('/custody-returns/items/{custodyReturnItemId}/process', [CustodyReturnController::class, 'processCustodyReturnItem']);
    Route::get('/custody-returns', [CustodyReturnController::class, 'index']);
    Route::get('/custody-returns/pending', [CustodyReturnController::class, 'pendingReturnRequests']);
    Route::get('/custody-returns/{id}', [CustodyReturnController::class, 'show']);
    Route::get('/my-custody-returns', [CustodyReturnController::class, 'myReturnRequests']);




    //BUILDINGS
    Route::post('/buildings/store', [BuildingController::class, 'store']);
    Route::put('buildings/update/{id}', [BuildingController::class, 'update']);
    Route::delete('buildings/delete/{id}', [BuildingController::class, 'destroy']);
    Route::get('/buildings',[BuildingController::class,'index']);
    Route::get('buildings/show/{id}', [BuildingController::class, 'show']);

    //ROOMS
    Route::post('/rooms/store', [RoomController::class, 'store']);
    Route::put('rooms/update/{id}', [RoomController::class, 'update']);
    Route::delete('rooms/delete/{id}', [RoomController::class, 'destroy']);
    Route::get('/rooms',[RoomController::class,'index']);
    Route::get('rooms/show/{id}', [RoomController::class, 'show']);

    //DEPARTMENTS
    Route::post('/departments/store', [DepartmentController::class, 'store']);
    Route::put('departments/update/{id}', [DepartmentController::class, 'update']);
    Route::delete('departments/delete/{id}', [DepartmentController::class, 'destroy']);
    Route::get('/departments',[DepartmentController::class,'index']);
    Route::get('departments/show/{id}', [DepartmentController::class, 'show']);


    //CALENDAR
    Route::get('/calendar', [CalendarNoteController::class, 'indexFilter']);
    Route::post('/calendar/store', [CalendarNoteController::class, 'store']);
    Route::get('/calendar/show/{date}', [CalendarNoteController::class, 'show']);
    Route::put('/calendar/update/{date}', [CalendarNoteController::class, 'update']);
    Route::delete('/calendar/destroy/{date}', [CalendarNoteController::class, 'destroy']);

    //Scrap Note
    Route::get('/allScrapNote',[ScrapNoteController::class,'index']);
    Route::get('/scrapNote/{id}/details',[ScrapNoteController::class,'show']);
    Route::post('/scrapNote/store', [ScrapNoteController::class, 'store']);
    Route::put('scrapNotes/{id}/approve', [ScrapNoteController::class, 'approve']);
    Route::put('scrapNotes/{id}/reject', [ScrapNoteController::class, 'reject']);
    Route::get('/scrapNotes/pending', [ScrapNoteController::class, 'pendingScrapNotes']);

    // InstallationReport
    Route::get('/allInstallationReport',[InstallationReportController::class,'index']);
    Route::put('/installationReport/{id}/approve',[InstallationReportController::class,'approve']);
    Route::put('/installationReport/{id}/reject',[InstallationReportController::class,'reject']);
    Route::get('/InstallationReport/{id}/details',[InstallationReportController::class,'show']);
    Route::post('/InstallationReport/store', [InstallationReportController::class, 'store']);
    
    // Installation Reports - Manager Functions
    Route::get('/pending-installation-reports', [InstallationReportController::class, 'pendingReports']);
    Route::get('/installation-reports/dashboard-stats', [InstallationReportController::class, 'dashboardStats']);

    // product Movement

    Route::get('entryNote/unassigned', [EntryNoteController::class, 'getAllUnassignedItems']);

    Route::get('/unassigned-items', [LocationController::class, 'unassignedItems']);
    Route::post('/assign-location', [LocationController::class, 'assignLocation']);


    //NOTIFICATIONS
    Route::get('/allNotification-S',[NotificationController::class,'index']);
    Route::get('/Notification/{id}',[NotificationController::class,'show']);
    Route::get('/markAsRead-allNotification-S',[NotificationController::class,'markAllAsRead']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('me', [UserController::class, 'showActive'])->middleware('auth:sanctum');
    });


    Route::put('/warehouse-keepers/{id}', [WarehouseKeeperController::class, 'update']);
    Route::put('/warehouse-keepers/update-password/{id}', [WarehouseKeeperController::class, 'updatePassword']);
    Route::get('/warehouse-keeper/me', [WarehouseKeeperController::class, 'show']);

    // MANAGERS
    Route::put('/managers/{id}', [ManagerController::class, 'update']);
    Route::put('/managers/update-password/{id}', [ManagerController::class, 'updatePassword']);
    Route::get('/manager/me', [ManagerController::class, 'show']);

    //SUPPLIERS
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers/store', [SupplierController::class, 'store']);
    Route::get('supplier/show/{id}', [SupplierController::class, 'show']);
    Route::put('supplier/update/{id}', [SupplierController::class, 'update']);

    //PurchaseRequests
    Route::get('/purchase-requests', [PurchaseRequestController::class, 'index']);
    Route::post('/purchase-requests/store', [PurchaseRequestController::class, 'store']);
    Route::get('/purchase-requests/show/{id}', [PurchaseRequestController::class, 'show']);
    Route::put('/purchase-requests/update/{id}', [PurchaseRequestController::class, 'update']);
    Route::put('/purchase-requests/{id}/approve', [PurchaseRequestController::class, 'approve']);
    Route::put('/purchase-requests/{id}/reject', [PurchaseRequestController::class, 'reject']);
    Route::get('/purchase-requests/my-requests', [PurchaseRequestController::class, 'myRequests']);

    //ReceivingNote
    Route::get('/allReceivingNote', [ReceivingNoteController::class, 'index']);
    Route::get('/receivingNote/{id}', [ReceivingNoteController::class, 'show']);
    Route::post('/receivingNote/store', [ReceivingNoteController::class, 'store']);

    // product Movement

    Route::get('product-movements/{productId}/byMonth', [ProductMovementController::class, 'getMovementsByMonth']);
    Route::get('product-movements/{productId}', [ProductMovementController::class, 'showProductMovement']);
    Route::get('products/monthlyBalances', [ProductMovementController::class, 'getMonthlyProductBalances']);
    Route::get('warehouses/{warehouseId}/product-balances', [ProductMovementController::class, 'getWarehouseProductBalances']);


    Route::get('/managers',[DepartmentController::class,'indexManager']);


});
