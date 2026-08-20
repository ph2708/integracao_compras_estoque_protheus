import sys
import openpyxl
import pymysql
import os
import datetime

# Script otimizado para memória (read_only=True) para importar Separação.xlsx ou arquivo enviado no MySQL
BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DEFAULT_EXCEL_PATH = os.path.join(BASE_DIR, 'Separação.xlsx')

DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE', 'pcp_compras')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')

def parse_date(val):
    if not val:
        return None
    if isinstance(val, (datetime.date, datetime.datetime)):
        return val.strftime('%Y-%m-%d')
    try:
        val_str = str(val).strip()
        if '/' in val_str:
            parts = val_str.split('/')
            if len(parts) == 3:
                return f"{parts[2]}-{parts[1]:0>2}-{parts[0]:0>2}"
        return val_str
    except:
        return None

def import_excel(target_path=None, mode='truncate'):
    excel_file = target_path if target_path and os.path.exists(target_path) else DEFAULT_EXCEL_PATH

    if not os.path.exists(excel_file):
        print(f"Aviso: Planilha não encontrada no caminho {excel_file}")
        return

    print(f"Lendo arquivo Excel: {excel_file}...")
    wb = openpyxl.load_workbook(excel_file, read_only=True, data_only=True)
    sheet = wb.active

    conn = pymysql.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USERNAME,
        password=DB_PASSWORD,
        database=DB_DATABASE,
        autocommit=True,
        charset='utf8mb4'
    )
    cursor = conn.cursor()

    if mode == 'truncate':
        cursor.execute("SET FOREIGN_KEY_CHECKS=0;")
        cursor.execute("TRUNCATE TABLE compras_items;")
        cursor.execute("TRUNCATE TABLE estoque_items;")
        cursor.execute("SET FOREIGN_KEY_CHECKS=1;")
        print("Tabelas estoque_items e compras_items zeradas com sucesso!")

    count = 0
    now_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    is_header = True
    col_map = {}

    for row in sheet.iter_rows(values_only=True):
        if not row:
            continue

        row_str = [str(cell).strip().upper() for cell in row if cell is not None]
        if is_header:
            if 'ORDEM PRODUCAO' in row_str or 'CODIGO PRODUTO' in row_str or 'PV' in row_str:
                is_header = False
                for idx, cell in enumerate(row):
                    if cell:
                        col_name = str(cell).strip().upper()
                        col_map[col_name] = idx
            continue

        # Mapeamento dinâmico de colunas ou por índice fallback
        idx_op = col_map.get('ORDEM PRODUCAO', 1)
        idx_pv = col_map.get('PV', 2)
        idx_cod = col_map.get('CODIGO PRODUTO', 3)
        idx_desc = col_map.get('DESCRIÇÃO COMPONENTE', 4)
        idx_pai = col_map.get('PRODUTO PAI CONCATENADO', col_map.get('PRODUTO PAI', None))
        idx_status = col_map.get('STATUS PCP', 5)
        idx_qtd_est = col_map.get('QTD EM ESTOQUE', 6)
        idx_obs_est = col_map.get('OBSERVAÇÃO ESTOQUE', 7)
        idx_sc = col_map.get('SOLICITAÇÃO DE COMPRA', 8)
        idx_comprado = col_map.get('COMPRADO', 9)
        idx_val_unit = col_map.get('VALOR UNITARIO COMPRA', 10)
        idx_pagto = col_map.get('TIPO PAGAMENTO/FATURADO', 12)
        idx_forn = col_map.get('FORNECEDOR SELECIONADO', 13)
        idx_pc = col_map.get('PEDIDO COMPRA (PROTHEUS)', 14)
        idx_ipi = col_map.get('IPI COMPRA', 15)
        idx_d_pc = col_map.get('DATA EMISSÃO PC', 16)
        idx_d_pg = col_map.get('DATA PREVISÃO PGTO', 17)

        op = str(row[idx_op]).strip() if len(row) > idx_op and row[idx_op] is not None else None
        pedido = str(row[idx_pv]).strip() if len(row) > idx_pv and row[idx_pv] is not None else None
        codigo_produto = str(row[idx_cod]).strip() if len(row) > idx_cod and row[idx_cod] is not None else None

        if not codigo_produto or codigo_produto == 'None':
            continue

        descricao = str(row[idx_desc]).strip() if len(row) > idx_desc and row[idx_desc] is not None else ''
        produto_pai = str(row[idx_pai]).strip() if idx_pai is not None and len(row) > idx_pai and row[idx_pai] is not None and str(row[idx_pai]).strip() != 'None' else None

        status_pcp = str(row[idx_status]).strip().upper() if len(row) > idx_status and row[idx_status] is not None else 'FALTA'
        if status_pcp not in ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN']:
            if 'KANBAN' in status_pcp:
                status_pcp = 'FABRICAR INTERNO KANBAN'
            elif 'FABRICA' in status_pcp:
                status_pcp = 'FABRICA'
            else:
                status_pcp = 'FALTA'

        try:
            qtd_estoque = float(row[idx_qtd_est]) if len(row) > idx_qtd_est and row[idx_qtd_est] is not None else 0.0
        except:
            qtd_estoque = 0.0

        obs_estoque = str(row[idx_obs_est]).strip() if len(row) > idx_obs_est and row[idx_obs_est] is not None and str(row[idx_obs_est]).strip() != 'None' else None
        solicitacao_compra = str(row[idx_sc]).strip() if len(row) > idx_sc and row[idx_sc] is not None and str(row[idx_sc]).strip() != 'None' else None

        try:
            qtd_comprado = float(row[idx_comprado]) if len(row) > idx_comprado and row[idx_comprado] is not None else 0.0
        except:
            qtd_comprado = 0.0

        quantidade_op = max(1.0, qtd_estoque + qtd_comprado)

        try:
            valor_unitario = float(row[idx_val_unit]) if len(row) > idx_val_unit and row[idx_val_unit] is not None else 0.0
        except:
            valor_unitario = 0.0

        status_pagamento = str(row[idx_pagto]).strip().upper() if len(row) > idx_pagto and row[idx_pagto] is not None else 'PENDENTE'
        fornecedor = str(row[idx_forn]).strip() if len(row) > idx_forn and row[idx_forn] is not None and str(row[idx_forn]).strip() != 'None' else None
        pedido_compra = str(row[idx_pc]).strip() if len(row) > idx_pc and row[idx_pc] is not None and str(row[idx_pc]).strip() != 'None' else None

        try:
            ipi = float(row[idx_ipi]) if len(row) > idx_ipi and row[idx_ipi] is not None else 0.0
        except:
            ipi = 0.0

        data_pc = parse_date(row[idx_d_pc]) if len(row) > idx_d_pc else None
        data_pagamento = parse_date(row[idx_d_pg]) if len(row) > idx_d_pg else None

        val_total = (valor_unitario * qtd_comprado) + (valor_unitario * qtd_comprado * (ipi / 100))

        # Inserir no Estoque
        sql_est = """
        INSERT INTO estoque_items (codigo_produto, descricao, produto_pai, op, pedido, cliente_obs, quantidade, quantidade_estoque, status, observacao_estoque, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(sql_est, (codigo_produto, descricao, produto_pai, op, pedido, None, quantidade_op, qtd_estoque, status_pcp, obs_estoque, now_str, now_str))
        estoque_id = cursor.lastrowid

        # Inserir em Compras
        sql_compra = """
        INSERT INTO compras_items (estoque_item_id, pedido_compra, codigo_fornecedor, valor_unitario, ipi, data_pc, data_pagamento, frete, solicitacao_compra, condicao_pagamento, valor_total, status_pagamento, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(sql_compra, (estoque_id, pedido_compra, fornecedor, valor_unitario, ipi, data_pc, data_pagamento, 0.0, solicitacao_compra, None, val_total, status_pagamento, now_str, now_str))

        count += 1

    wb.close()
    conn.close()
    print(f"✅ Importação finalizada com sucesso! Total de {count} itens importados para o MySQL.")

if __name__ == '__main__':
    target = sys.argv[1] if len(sys.argv) > 1 else None
    mode = sys.argv[2] if len(sys.argv) > 2 else 'truncate'
    import_excel(target, mode)
