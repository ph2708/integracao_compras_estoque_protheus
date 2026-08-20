import sys
import openpyxl
import pymysql
import os
import datetime
import csv

# Script otimizado para memória para importar planilhas Excel (.xlsx) e arquivos CSV/Texto no MySQL
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

def parse_float(val):
    if val is None:
        return 0.0
    val_str = str(val).strip()
    if not val_str or val_str.upper() in ['NONE', '#N/D', 'NULL', '']:
        return 0.0
    # Trata formato de moeda/número brasileiro com vírgula (ex: 220,97 -> 220.97 ou 1.585,40 -> 1585.40)
    if ',' in val_str:
        val_str = val_str.replace('.', '').replace(',', '.')
    try:
        return float(val_str)
    except:
        return 0.0

def load_rows_from_file(file_path):
    """
    Carrega as linhas de arquivos .csv ou .xlsx/.xls com detecção automática de delimitadores e codificação UTF-8/ISO-8859-1
    """
    ext = os.path.splitext(file_path)[1].lower()
    
    if ext in ['.csv', '.txt']:
        for encoding in ['utf-8-sig', 'utf-8', 'iso-8859-1', 'cp1252']:
            try:
                with open(file_path, 'r', encoding=encoding) as f:
                    sample = f.read(4096)
                    f.seek(0)
                    delimiter = ';' if ';' in sample else ','
                    reader = csv.reader(f, delimiter=delimiter)
                    rows = [row for row in reader if row]
                    if rows:
                        print(f"Lido CSV com codificação {encoding} e delimitador '{delimiter}' ({len(rows)} linhas)")
                        return rows
            except Exception as e:
                continue
        return []
    else:
        wb = openpyxl.load_workbook(file_path, read_only=True, data_only=True)
        sheet = wb.active
        rows = []
        for row in sheet.iter_rows(values_only=True):
            if row:
                rows.append([cell for cell in row])
        wb.close()
        print(f"Lido Excel .xlsx ({len(rows)} linhas)")
        return rows

def import_excel(target_path=None, mode='truncate'):
    excel_file = target_path if target_path and os.path.exists(target_path) else DEFAULT_EXCEL_PATH

    if not os.path.exists(excel_file):
        print(f"Aviso: Planilha não encontrada no caminho {excel_file}")
        return

    print(f"Processando arquivo: {excel_file}...")
    rows = load_rows_from_file(excel_file)
    if not rows:
        print("Aviso: Nenhuma linha foi encontrada no arquivo enviado.")
        return

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

    for row in rows:
        if not row:
            continue

        row_str = [str(cell).strip().upper() for cell in row if cell is not None]
        if is_header:
            row_text = ' '.join(row_str)
            if any(k in row_text for k in ['ORDEM PRODUCAO', 'ORDEM PRODUÇÃO', 'CODIGO PRODUTO', 'CÓDIGO PRODUTO', 'PV']):
                is_header = False
                for idx, cell in enumerate(row):
                    if cell is not None:
                        col_name = str(cell).strip().upper()
                        col_map[col_name] = idx
                print(f"Cabeçalho mapeado com {len(col_map)} colunas: {list(col_map.keys())}")
            continue

        # Mapeamento dinâmico de colunas ou por índice fallback
        idx_op = col_map.get('ORDEM PRODUCAO', col_map.get('ORDEM PRODUÇÃO', 0))
        idx_pv = col_map.get('PV', 1)
        idx_cod = col_map.get('CODIGO PRODUTO', col_map.get('CÓDIGO PRODUTO', 2))
        idx_desc = col_map.get('DESCRIÇÃO COMPONENTE', col_map.get('DESCRICAO COMPONENTE', col_map.get('DESCRIÇÃO', 3)))
        idx_desc_longa = col_map.get('DESCRIÇÃO LONGA (B5_CEME)', col_map.get('DESCRIÇÃO LONGA', col_map.get('DESCRICAO LONGA', None)))
        idx_pai = col_map.get('PRODUTO PAI CONCATENADO', col_map.get('PRODUTO PAI', None))
        idx_status = col_map.get('STATUS PCP', 5)
        idx_qtd_est = col_map.get('QTD EM ESTOQUE', 6)
        idx_obs_est = col_map.get('OBSERVAÇÃO ESTOQUE', col_map.get('OBSERVACAO ESTOQUE', 7))
        idx_sc = col_map.get('SOLICITAÇÃO DE COMPRA', col_map.get('SOLICITACAO DE COMPRA', 8))
        idx_comprado = col_map.get('COMPRADO', 9)
        idx_val_unit = col_map.get('VALOR UNITARIO COMPRA', col_map.get('VALOR UNITÁRIO COMPRA', 10))
        idx_pagto = col_map.get('TIPO PAGAMENTO/FATURADO', 11)
        idx_forn = col_map.get('FORNECEDOR SELECIONADO', 12)
        idx_pc = col_map.get('PEDIDO COMPRA (PROTHEUS)', col_map.get('PEDIDO COMPRA', 13))
        idx_ipi = col_map.get('IPI COMPRA', 14)
        idx_d_pc = col_map.get('DATA EMISSÃO PC', col_map.get('DATA EMISSAO PC', 15))
        idx_d_pg = col_map.get('DATA PREVISÃO PGTO', col_map.get('DATA PREVISAO PGTO', 16))

        op = str(row[idx_op]).strip() if len(row) > idx_op and row[idx_op] is not None else None
        pedido = str(row[idx_pv]).strip() if len(row) > idx_pv and row[idx_pv] is not None else None
        codigo_produto = str(row[idx_cod]).strip() if len(row) > idx_cod and row[idx_cod] is not None else None

        if not codigo_produto or codigo_produto == 'None' or codigo_produto == '':
            continue

        descricao = str(row[idx_desc]).strip() if len(row) > idx_desc and row[idx_desc] is not None else ''
        descricao_longa = str(row[idx_desc_longa]).strip() if idx_desc_longa is not None and len(row) > idx_desc_longa and row[idx_desc_longa] is not None and str(row[idx_desc_longa]).strip() != 'None' else descricao
        produto_pai = str(row[idx_pai]).strip() if idx_pai is not None and len(row) > idx_pai and row[idx_pai] is not None and str(row[idx_pai]).strip() != 'None' else None

        status_pcp = str(row[idx_status]).strip().upper() if len(row) > idx_status and row[idx_status] is not None else 'FALTA'
        if status_pcp not in ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN']:
            if 'KANBAN' in status_pcp:
                status_pcp = 'FABRICAR INTERNO KANBAN'
            elif 'FABRICA' in status_pcp:
                status_pcp = 'FABRICA'
            else:
                status_pcp = 'FALTA'

        qtd_estoque = parse_float(row[idx_qtd_est]) if len(row) > idx_qtd_est else 0.0
        obs_estoque = str(row[idx_obs_est]).strip() if len(row) > idx_obs_est and row[idx_obs_est] is not None and str(row[idx_obs_est]).strip() != 'None' else None
        solicitacao_compra = str(row[idx_sc]).strip() if len(row) > idx_sc and row[idx_sc] is not None and str(row[idx_sc]).strip() != 'None' else None
        qtd_comprado = parse_float(row[idx_comprado]) if len(row) > idx_comprado else 0.0

        quantidade_op = max(1.0, qtd_estoque + qtd_comprado)
        valor_unitario = parse_float(row[idx_val_unit]) if len(row) > idx_val_unit else 0.0

        status_pagamento_raw = str(row[idx_pagto]).strip().upper() if len(row) > idx_pagto and row[idx_pagto] is not None else 'PENDENTE'
        status_pagamento = status_pagamento_raw if status_pagamento_raw in ['PENDENTE', 'PAGAMENTO ANTECIPADO', 'FATURADO', 'PAGO'] else 'PENDENTE'

        fornecedor = str(row[idx_forn]).strip() if len(row) > idx_forn and row[idx_forn] is not None and str(row[idx_forn]).strip() != 'None' else None
        pedido_compra = str(row[idx_pc]).strip() if len(row) > idx_pc and row[idx_pc] is not None and str(row[idx_pc]).strip() != 'None' else None
        ipi = parse_float(row[idx_ipi]) if len(row) > idx_ipi else 0.0

        data_pc = parse_date(row[idx_d_pc]) if len(row) > idx_d_pc else None
        data_pagamento = parse_date(row[idx_d_pg]) if len(row) > idx_d_pg else None

        val_total = (valor_unitario * qtd_comprado) + (valor_unitario * qtd_comprado * (ipi / 100))

        # Inserir no Estoque
        sql_est = """
        INSERT INTO estoque_items (codigo_produto, descricao, descricao_longa, produto_pai, op, pedido, cliente_obs, quantidade, quantidade_estoque, status, observacao_estoque, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(sql_est, (codigo_produto, descricao, descricao_longa, produto_pai, op, pedido, None, quantidade_op, qtd_estoque, status_pcp, obs_estoque, now_str, now_str))
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
    target = sys.argv[1] if len(sys.argv) > 1 else None
    mode = sys.argv[2] if len(sys.argv) > 2 else 'truncate'
    import_excel(target, mode)
