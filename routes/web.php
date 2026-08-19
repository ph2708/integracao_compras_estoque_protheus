<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

// Dashboard & Indicadores
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Painel de Estoque (PCP)
Route::post('estoque/consultar-pedido', [EstoqueController::class, 'consultarPedido'])->name('estoque.consultar-pedido');
Route::post('estoque/store-batch', [EstoqueController::class, 'storeBatch'])->name('estoque.store-batch');
Route::resource('estoque', EstoqueController::class)->only(['index', 'store', 'update']);

// Painel de Compras
Route::resource('compras', ComprasController::class)->only(['index', 'update']);
Route::post('compras/consultar-protheus', [ComprasController::class, 'consultarProtheus'])->name('compras.consultar-protheus');
