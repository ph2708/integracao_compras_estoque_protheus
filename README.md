# Sistema PCP & Compras (Laravel + Protheus TOTVS)

Este projeto consiste em um sistema de Gestão de Estoque (PCP) e Compras integrado com o banco de dados TOTVS Protheus (SQL Server).

---

## 🛠️ Tecnologias & Ambiente
- **Framework**: Laravel 12 (PHP 8.4)
- **Banco da Aplicação**: MySQL 8.0 (Executado localmente via Docker Container)
- **Banco Integrado (TOTVS Protheus)**: SQL Server (Consulta Remota)
- **Gerenciador de Versões**: `asdf`

---

## 🐳 Como Subir o Ambiente Local

### 1. Iniciar o Banco MySQL via Docker
```bash
docker compose up -d
```

### 2. Configurar Variáveis de Ambiente
Copie o arquivo `.env.example` para `.env` e ajuste as credenciais se necessário:
```bash
cp .env.example .env
```

### 3. Rodar as Migrations no MySQL Local
```bash
php artisan migrate
```

### 4. Iniciar o Servidor de Desenvolvimento
```bash
php artisan serve
```

---

## 📊 Mapeamento de Tabelas de Banco de Dados

### 1. Banco da Aplicação (`MySQL` Local)
Tabelas locais gerenciadas pelo Laravel via Eloquent e Migrations:

- **`estoque_items`** (Painel de Estoque):
  - `id`: Identificador único.
  - `codigo_produto`: Código do item/produto no sistema.
  - `descricao`: Descrição técnica ou nome do item.
  - `op`: Número da Ordem de Produção relacionada.
  - `pedido`: Número do Pedido vinculado.
  - `status`: Status no PCP/Almoxarifado (`FALTA`, `SEPARADO`, `RETIRADO`, `FABRICA`, `FABRICAR INTERNO KANBAN`). Padrão: `FALTA`.
  - `observacao_estoque`: Campo de observação exclusivo visível apenas para a equipe de estoque.
  - `created_at` / `updated_at`: Registros de criação e atualização.

- **`compras_items`** (Painel de Compras):
  - `id`: Identificador único.
  - `estoque_item_id`: Chave estrangeira referenciando `estoque_items.id`.
  - `codigo_fornecedor`: Código do fornecedor retornado da consulta ao Protheus.
  - `condicao_pagamento`: Condição comercial / prazo de pagamento.
  - `status_pagamento`: Estado do pagamento (`PAGAMENTO ANTECIPADO`, `FATURADO`, `PAGO`, `PENDENTE`). Padrão: `PENDENTE`.
  - `created_at` / `updated_at`: Registros de criação e atualização.

---

### 2. Banco TOTVS Protheus (`SQL Server` Remoto - `MP_12`)
Tabelas consultadas em modo **Somente Leitura** (`protheus` connection):

- **`SC2010`** - Ordens de Produção / Pedidos de Produção
  - `C2_PEDIDO`: Código / Número do Pedido.
  - `C2_OBS`: Observações da Ordem / Pedido.
- **`SB1010`** - Cadastros de Produtos
  - `B1_DESC`: Descrição principal do Produto.
- **`SB5010`** - Dados Adicionais do Produto
  - `B5_CEME`: Complemento / Descrição Detalhada da Engenharia de Produto.
- **`SD4010`** - Requisitados / Empenhos / OPs
  - `D4_OP`: Código / Identificador da Ordem de Produção (OP).
- **`SA2010`** - Cadastro de Fornecedores
  - `A2_COD`: Código do Fornecedor.
  - `A2_LOJA`: Loja do Fornecedor.
  - `A2_NOME`: Razão Social / Nome Fantasia do Fornecedor.
- **`SC7010`** - Pedidos de Compra
  - `C7_NUM`: Número do Pedido de Compra.
  - `C7_FORNECE`: Código do Fornecedor vinculado ao Pedido de Compra.
  - `C7_COND`: Condição de Pagamento do Pedido de Compra.

*(Nota: Caso novas tabelas do Protheus venham a ser consultadas, elas serão registradas nesta seção).*
