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
    for row in sheet.iter_rows(values_only=True):
        if not row:
            continue

        row_str = [str(cell) for cell in row if cell is not None]
        if is_header:
            if 'ORDEM PRODUCAO' in row_str or 'CODIGO PRODUTO' in row_str:
                is_header = False
            continue

        op = str(row[1]).strip() if len(row) > 1 and row[1] is not None else None
        pedido = str(row[2]).strip() if len(row) > 2 and row[2] is not None else None
        codigo_produto = str(row[3]).strip() if len(row) > 3 and row[3] is not None else None

        if not codigo_produto or codigo_produto == 'None':
            continue

        descricao = str(row[4]).strip() if len(row) > 4 and row[4] is not None else ''
        status_pcp = str(row[5]).strip().upper() if len(row) > 5 and row[5] is not None else 'FALTA'
        if status_pcp not in ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN']:
            if 'KANBAN' in status_pcp:
                status_pcp = 'FABRICAR INTERNO KANBAN'
            elif 'FABRICA' in status_pcp:
                status_pcp = 'FABRICA'
            else:
                status_pcp = 'FALTA'

        try:
            qtd_estoque = float(row[6]) if len(row) > 6 and row[6] is not None else 0.0
        except:
            qtd_estoque = 0.0

        obs_estoque = str(row[7]).strip() if len(row) > 7 and row[7] is not None and str(row[7]).strip() != 'None' else None
        solicitacao_compra = str(row[8]).strip() if len(row) > 8 and row[8] is not None and str(row[8]).strip() != 'None' else None

        try:
            qtd_comprado = float(row[9]) if len(row) > 9 and row[9] is not None else 0.0
        except:
            qtd_comprado = 0.0

        quantidade_op = max(1.0, qtd_estoque + qtd_comprado)

        try:
            valor_unitario = float(row[10]) if len(row) > 10 and row[10] is not None else 0.0
        except:
            valor_unitario = 0.0

        status_pagamento = str(row[12]).strip().upper() if len(row) > 12 and row[12] is not None else 'PENDENTE'
        fornecedor = str(row[13]).strip() if len(row) > 13 and row[13] is not None and str(row[13]).strip() != 'None' else None
        pedido_compra = str(row[14]).strip() if len(row) > 14 and row[14] is not None and str(row[14]).strip() != 'None' else None

        try:
            ipi = float(row[15]) if len(row) > 15 and row[15] is not None else 0.0
        except:
            ipi = 0.0

        data_pc = parse_date(row[16]) if len(row) > 16 else None
        data_pagamento = parse_date(row[17]) if len(row) > 17 else None

        val_total = (valor_unitario * qtd_comprado) + (valor_unitario * qtd_comprado * (ipi / 100))

        # Inserir no Estoque
        sql_est = """
        INSERT INTO estoque_items (codigo_produto, descricao, op, pedido, cliente_obs, quantidade, quantidade_estoque, status, observacao_estoque, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(sql_est, (codigo_produto, descricao, op, pedido, None, quantidade_op, qtd_estoque, status_pcp, obs_estoque, now_str, now_str))
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
