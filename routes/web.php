<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OpFechamentoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirecionamento da raiz
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Rotas de Autenticação (Públicas)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rotas Protegidas por Autenticação (Requer Login)
Route::middleware(['auth'])->group(function () {

    // Dashboard Geral
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Painel de Estoque (PCP)
    Route::get('/estoque', [EstoqueController::class, 'index'])->name('estoque.index');
    Route::post('/estoque/consultar-pedido', [EstoqueController::class, 'consultarPedido'])->name('estoque.consultar-pedido');
    Route::post('/estoque/store-batch', [EstoqueController::class, 'storeBatch'])->name('estoque.store-batch');
    Route::post('/estoque/update-batch', [EstoqueController::class, 'updateBatch'])->name('estoque.update-batch');
    Route::post('/estoque', [EstoqueController::class, 'store'])->name('estoque.store');
    Route::put('/estoque/{id}', [EstoqueController::class, 'update'])->name('estoque.update');

    // Painel de Compras
    Route::get('/compras', [ComprasController::class, 'index'])->name('compras.index');
    Route::post('/compras/consultar-protheus', [ComprasController::class, 'consultarProtheus'])->name('compras.consultar-protheus');
    Route::post('/compras/update-batch', [ComprasController::class, 'updateBatch'])->name('compras.update-batch');
    Route::put('/compras/{id}', [ComprasController::class, 'update'])->name('compras.update');

    // Fechamento de Ordem de Produção (OP)
    Route::get('/fechamento-op', [OpFechamentoController::class, 'index'])->name('fechamento-op.index');
    Route::post('/fechamento-op/fechar-lote', [OpFechamentoController::class, 'fecharOpsLote'])->name('fechamento-op.fechar-lote');
    Route::post('/fechamento-op/{op}/fechar', [OpFechamentoController::class, 'fecharOp'])->name('fechamento-op.fechar');

    // Gestão de Base de Dados e Usuários (Apenas Administradores)
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::get('/importar', [ImportController::class, 'index'])->name('importar.index');
        Route::post('/importar', [ImportController::class, 'import'])->name('importar.process');
        Route::get('/importar/modelo', [ImportController::class, 'downloadModelo'])->name('importar.modelo');
        Route::get('/importar/exportar', [ImportController::class, 'export'])->name('importar.export');
        Route::post('/importar/limpar', [ImportController::class, 'clearBase'])->name('importar.clear');
    });
});
