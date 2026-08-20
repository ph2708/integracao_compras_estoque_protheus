import openpyxl
import pymysql
import os
import datetime

# Script para importar os 279 itens da planilha Separação.xlsx para o MySQL
BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
EXCEL_PATH = os.path.join(BASE_DIR, 'Separação.xlsx')

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

def import_excel():
    if not os.path.exists(EXCEL_PATH):
        print(f"Aviso: Planilha não encontrada no caminho {EXCEL_PATH}")
        return

    print(f"Lendo arquivo Excel: {EXCEL_PATH}...")
    wb = openpyxl.load_workbook(EXCEL_PATH, data_only=True)
    sheet = wb.active
    rows = list(sheet.iter_rows(values_only=True))

    header_idx = None
    for idx, r in enumerate(rows):
        if r and 'ORDEM PRODUCAO' in [str(cell) for cell in r if cell]:
            header_idx = idx
            break

    if header_idx is None:
        print("Erro: Cabeçalho 'ORDEM PRODUCAO' não encontrado no Excel.")
        return

    data_rows = rows[header_idx + 1:]
    print(f"Total de linhas de dados encontradas: {len(data_rows)}")

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

    # Zerar tabelas de estoque e compras
    cursor.execute("SET FOREIGN_KEY_CHECKS=0;")
    cursor.execute("TRUNCATE TABLE compras_items;")
    cursor.execute("TRUNCATE TABLE estoque_items;")
    cursor.execute("SET FOREIGN_KEY_CHECKS=1;")
    print("Tabelas estoque_items e compras_items zeradas com sucesso!")

    count = 0
    now_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    for r in data_rows:
        if not any(r):
            continue

        op = str(r[1]).strip() if r[1] is not None else None
        pedido = str(r[2]).strip() if r[2] is not None else None
        codigo_produto = str(r[3]).strip() if r[3] is not None else None

        if not codigo_produto or codigo_produto == 'None':
            continue

        descricao = str(r[4]).strip() if r[4] is not None else ''
        status_pcp = str(r[5]).strip().upper() if r[5] is not None else 'FALTA'
        if status_pcp not in ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN']:
            if 'KANBAN' in status_pcp:
                status_pcp = 'FABRICAR INTERNO KANBAN'
            elif 'FABRICA' in status_pcp:
                status_pcp = 'FABRICA'
            else:
                status_pcp = 'FALTA'

        try:
            qtd_estoque = float(r[6]) if r[6] is not None else 0.0
        except:
            qtd_estoque = 0.0

        obs_estoque = str(r[7]).strip() if r[7] is not None and str(r[7]).strip() != 'None' else None
        solicitacao_compra = str(r[8]).strip() if r[8] is not None and str(r[8]).strip() != 'None' else None

        try:
            qtd_comprado = float(r[9]) if r[9] is not None else 0.0
        except:
            qtd_comprado = 0.0

        quantidade_op = max(1.0, qtd_estoque + qtd_comprado)

        try:
            valor_unitario = float(r[10]) if r[10] is not None else 0.0
        except:
            valor_unitario = 0.0

        status_pagamento = str(r[12]).strip().upper() if r[12] is not None else 'PENDENTE'
        fornecedor = str(r[13]).strip() if r[13] is not None and str(r[13]).strip() != 'None' else None
        pedido_compra = str(r[14]).strip() if r[14] is not None and str(r[14]).strip() != 'None' else None

        try:
            ipi = float(r[15]) if r[15] is not None else 0.0
        except:
            ipi = 0.0

        data_pc = parse_date(r[16])
        data_pagamento = parse_date(r[17])

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

    conn.close()
    print(f"✅ Importação finalizada com sucesso! Total de {count} itens importados para o MySQL.")

if __name__ == '__main__':
    import_excel()
