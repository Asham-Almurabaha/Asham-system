<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantors\Http\Controllers\GuarantorController;

Route::middleware('api')->group(function () {
    Route::apiResource('guarantors', GuarantorController::class);
});
