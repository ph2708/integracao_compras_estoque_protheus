<?php

namespace App\Services;

use Exception;

class ProtheusService
{
    protected string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = app_path('Services/protheus_bridge.py');
    }

    /**
     * Check connection to Protheus via python bridge
     */
    public function checkConnection(): bool
    {
        try {
            $filiais = $this->listarFiliais();
            return !empty($filiais);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get list of all distinct Filiais (C2_FILIAL) from SC2010
     */
    public function listarFiliais(): array
    {
        try {
            $command = "python3 " . escapeshellarg($this->scriptPath) . " list_filiais";
            $output = shell_exec($command);
            
            $json = json_decode($output, true);
            if (isset($json['success']) && $json['success']) {
                return $json['data'] ?? [];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getFiliais(): array
    {
        return $this->listarFiliais();
    }

    /**
     * Get list of unique orders (C2_PEDIDO) from Protheus table SC2010
     */
    public function listarPedidosProtheus(?string $filial = null): array
    {
        try {
            $filialArg = $filial ? escapeshellarg($filial) : 'null';
            $command = "python3 " . escapeshellarg($this->scriptPath) . " list_pedidos " . $filialArg;
            $output = shell_exec($command);
            
            $json = json_decode($output, true);
            if (isset($json['success']) && $json['success']) {
                return $json['data'] ?? [];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTodosPedidosVenda(?string $filial = null): array
    {
        return $this->listarPedidosProtheus($filial);
    }

    /**
     * Consult ALL items for a given Pedido (C2_PEDIDO) and Filial in Protheus
     */
    public function getPedidoItems(string $c2Pedido, ?string $filial = null): array
    {
        try {
            $filialArg = $filial ? escapeshellarg($filial) : 'null';
            $command = "python3 " . escapeshellarg($this->scriptPath) . " get_pedido_items " . escapeshellarg(trim($c2Pedido)) . " " . $filialArg;
            $output = shell_exec($command);
            
            $json = json_decode($output, true);
            if (isset($json['success']) && !empty($json['data'])) {
                return $json['data'];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getItensPorPedido(string $c2Pedido, ?string $filial = null): array
    {
        return $this->getPedidoItems($c2Pedido, $filial);
    }

    /**
     * Consult Supplier and Purchase Order info from Protheus (SC7010, SA2010, SE4010)
     */
    public function getFornecedorEPedidoCompra(string $c7Num, ?string $produto = null): ?object
    {
        try {
            $prodArg = $produto ? escapeshellarg(trim($produto)) : 'null';
            $command = "python3 " . escapeshellarg($this->scriptPath) . " get_fornecedor " . escapeshellarg(trim($c7Num)) . " " . $prodArg;
            $output = shell_exec($command);
            
            $json = json_decode($output, true);
            if (isset($json['success']) && !empty($json['data'])) {
                return (object) $json['data'];
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getDadosPedidoCompra(string $c7Num, ?string $produto = null): ?object
    {
        return $this->getFornecedorEPedidoCompra($c7Num, $produto);
    }
}
