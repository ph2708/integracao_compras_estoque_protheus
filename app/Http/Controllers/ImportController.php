<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    /**
     * Tela de Gestão de Base de Dados (Importação, Exportação e Limpeza) - Apenas Admin
     */
    public function index()
    {
        $totalEstoque = EstoqueItem::count();
        $totalCompras = CompraItem::count();

        return view('importar.index', compact('totalEstoque', 'totalCompras'));
    }

    /**
     * Gera e realiza o download de uma planilha modelo preenchida com cabeçalhos e exemplos
     */
    public function downloadModelo(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modelo_importacao_estoque_compras.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Bom para UTF-8 no Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Cabeçalho da planilha
            fputcsv($handle, [
                'ORDEM PRODUCAO',
                'PV',
                'CODIGO PRODUTO',
                'DESCRIÇÃO COMPONENTE',
                'PRODUTO PAI CONCATENADO',
                'STATUS PCP',
                'QTD EM ESTOQUE',
                'OBSERVAÇÃO ESTOQUE',
                'SOLICITAÇÃO DE COMPRA',
                'COMPRADO',
                'VALOR UNITARIO COMPRA',
                'TIPO PAGAMENTO/FATURADO',
                'FORNECEDOR SELECIONADO',
                'PEDIDO COMPRA (PROTHEUS)',
                'IPI COMPRA',
                'DATA EMISSÃO PC',
                'DATA PREVISÃO PGTO'
            ], ';');

            // Linha Exemplo 1
            fputcsv($handle, [
                '01872201001',
                '006614',
                '61645000102',
                'CABO COBRE FLEXIVEL 750V 1.5MM2',
                '951010010 - QUADRO DE COMANDO QTA 100A',
                'RETIRADO',
                '17.5',
                'Material separado na prateleira B2',
                '',
                '0',
                '10.50',
                'PENDENTE',
                '003825 (FORNECEDOR EXEMPLO)',
                '',
                '0',
                '2026-08-20',
                '2026-08-25'
            ], ';');

            // Linha Exemplo 2
            fputcsv($handle, [
                '01872201002',
                '006614',
                '105049500',
                'CONTATOR FORCA TRIPOLAR 160A ABB',
                '951010010 - QUADRO DE COMANDO QTA 100A',
                'FALTA',
                '0',
                'Aguardando cotação de compras',
                'SC-55401',
                '2',
                '271.00',
                'FATURADO',
                '016117 (GW METAL)',
                '014297',
                '5',
                '2026-08-15',
                '2026-08-30'
            ], ';');

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Exporta todos os dados atuais do banco de dados para um arquivo CSV formatado
     */
    public function export(): StreamedResponse
    {
        $filename = 'exportacao_base_pcp_compras_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Adiciona BOM UTF-8 para compatibilidade perfeita com Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Cabeçalho idêntico ao modelo de importação
            fputcsv($handle, [
                'ORDEM PRODUCAO',
                'PV',
                'CODIGO PRODUTO',
                'DESCRIÇÃO COMPONENTE',
                'PRODUTO PAI CONCATENADO',
                'STATUS PCP',
                'QTD EM ESTOQUE',
                'OBSERVAÇÃO ESTOQUE',
                'SOLICITAÇÃO DE COMPRA',
                'COMPRADO',
                'VALOR UNITARIO COMPRA',
                'TIPO PAGAMENTO/FATURADO',
                'FORNECEDOR SELECIONADO',
                'PEDIDO COMPRA (PROTHEUS)',
                'IPI COMPRA',
                'DATA EMISSÃO PC',
                'DATA PREVISÃO PGTO'
            ], ';');

            // Stream em chunks de 500 registros
            EstoqueItem::with('compraItem')->chunk(500, function ($items) use ($handle) {
                foreach ($items as $item) {
                    $compra = $item->compraItem;
                    $qtdComprar = max(0, $item->quantidade - $item->quantidade_estoque);

                    fputcsv($handle, [
                        $item->op ?? '',
                        $item->pedido ?? '',
                        $item->codigo_produto ?? '',
                        $item->descricao ?? '',
                        $item->produto_pai ?? '',
                        $item->status ?? 'FALTA',
                        $item->quantidade_estoque ?? 0,
                        $item->observacao_estoque ?? '',
                        $compra->solicitacao_compra ?? '',
                        $qtdComprar,
                        $compra->valor_unitario ?? 0,
                        $compra->status_pagamento ?? 'PENDENTE',
                        $compra->codigo_fornecedor ?? '',
                        $compra->pedido_compra ?? '',
                        $compra->ipi ?? 0,
                        $compra->data_pc ? (is_string($compra->data_pc) ? $compra->data_pc : $compra->data_pc->format('Y-m-d')) : '',
                        $compra->data_pagamento ? (is_string($compra->data_pagamento) ? $compra->data_pagamento : $compra->data_pagamento->format('Y-m-d')) : ''
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Limpa completamente a base de dados de Estoque PCP e Compras
     */
    public function clearBase()
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CompraItem::truncate();
        EstoqueItem::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->back()->with('success', '🗑️ Toda a base de dados de Estoque PCP e Compras foi limpa com sucesso!');
    }

    /**
     * Processa o upload da planilha (.xlsx ou .csv) e importa os dados para o MySQL
     */
    public function import(Request $request)
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '512M');

        $request->validate([
            'arquivo' => 'required|file|max:20480',
            'modo' => 'required|in:truncate,append'
        ]);

        $file = $request->file('arquivo');
        $fullPath = $file->getRealPath();
        $originalExt = strtolower($file->getClientOriginalExtension());

        if (!$fullPath || !file_exists($fullPath)) {
            return redirect()->back()->with('error', 'O arquivo temporário de upload não pôde ser lido pelo servidor.');
        }

        // Executar o script python otimizado import_excel_separacao.py
        $scriptPath = base_path('database/seeders/import_excel_separacao.py');
        if (file_exists($scriptPath)) {
            $cmd = "python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($fullPath) . " " . escapeshellarg($request->modo) . " " . escapeshellarg($originalExt) . " 2>&1";
            $output = shell_exec($cmd);

            // Verifica se a saída do script Python contem a confirmação de importação
            if (str_contains($output, 'Importação finalizada com sucesso!')) {
                preg_match('/Total de (\d+) itens importados/', $output, $matches);
                $totalImportados = $matches[1] ?? 'vários';
                return redirect()->back()->with('success', "✅ Planilha importada com sucesso! {$totalImportados} itens foram carregados no banco MySQL.");
            } else {
                return redirect()->back()->with('error', 'Ocorreu uma falha ao processar o arquivo: ' . nl2br(e($output)));
            }
        }

        return redirect()->back()->with('error', 'Ocorreu um erro: script de importação não encontrado.');
    }
}
