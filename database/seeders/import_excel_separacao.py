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
                d, m, y = int(parts[0]), int(parts[1]), int(parts[2])
                return f"{y:04d}-{m:02d}-{d:02d}"
        if '-' in val_str:
            parts = val_str.split('-')
            if len(parts) == 3 and len(parts[0]) == 4:
                int(parts[0]); int(parts[1]); int(parts[2])
                return val_str
        return None
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
            if any(k in row_text for k in ['ORDEM PRODUCAO', 'ORDEM PRODUÇÃO', 'ORD PRODUCAO', 'ORD PRODUÇÃO', 'CODIGO PRODUTO', 'CÓDIGO PRODUTO', 'PV', 'CÓDIGO', 'CODIGO']):
                is_header = False
                for idx, cell in enumerate(row):
                    if cell is not None:
                        col_name = str(cell).strip().upper()
                        col_map[col_name] = idx
                print(f"Cabeçalho mapeado com {len(col_map)} colunas.")
            continue

        def get_col_val(col_names, default=None):
            for name in col_names:
                name_upper = name.upper()
                if name_upper in col_map:
                    idx = col_map[name_upper]
                    if idx < len(row) and row[idx] is not None:
                        return row[idx]
            return default

        raw_op = get_col_val(['ORDEM PRODUCAO', 'ORDEM PRODUÇÃO', 'ORD PRODUCAO', 'ORD PRODUÇÃO'])
        raw_pv = get_col_val(['PV', 'PEDIDO DE VENDA', 'PEDIDO'])
        raw_cod = get_col_val(['CODIGO PRODUTO', 'CÓDIGO PRODUTO', 'CÓDIGO', 'CODIGO'])

        op = str(raw_op).strip() if raw_op is not None and str(raw_op).strip() != 'None' else None
        pedido = str(raw_pv).strip() if raw_pv is not None and str(raw_pv).strip() != 'None' else None
        codigo_produto = str(raw_cod).strip() if raw_cod is not None and str(raw_cod).strip() != 'None' else None

        if not codigo_produto or codigo_produto == '':
            continue

        raw_desc = get_col_val(['DESCRIÇÃO COMPONENTE', 'DESCRICAO COMPONENTE', 'DESCRIÇÃO MATERIAL', 'DESCRICAO MATERIAL', 'DESCRIÇÃO', 'DESCRICAO'])
        descricao = str(raw_desc).strip() if raw_desc is not None and str(raw_desc).strip() != 'None' else ''

        raw_desc_longa = get_col_val(['DESCRIÇÃO LONGA (B5_CEME)', 'DESCRIÇÃO LONGA', 'DESCRICAO LONGA', 'DESC. LONGA'])
        descricao_longa = str(raw_desc_longa).strip() if raw_desc_longa is not None and str(raw_desc_longa).strip() != 'None' else descricao

        raw_pai = get_col_val(['PRODUTO PAI CONCATENADO', 'PRODUTO PAI'])
        produto_pai = str(raw_pai).strip() if raw_pai is not None and str(raw_pai).strip() != 'None' else None

        raw_status = get_col_val(['STATUS PCP', 'STATUS PCP/ALMOX', 'STATUS ALMOX', 'STATUS PCP ATUAL'])
        status_pcp = str(raw_status).strip().upper() if raw_status is not None else 'FALTA'
        if status_pcp not in ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN']:
            if 'KANBAN' in status_pcp:
                status_pcp = 'FABRICAR INTERNO KANBAN'
            elif 'FABRICA' in status_pcp:
                status_pcp = 'FABRICA'
            else:
                status_pcp = 'FALTA'

        qtd_estoque = parse_float(get_col_val(['QTD EM ESTOQUE', 'SALDO ATUAL', 'SALDO', 'QTD. EMPENHO']))
        raw_obs_est = get_col_val(['OBSERVAÇÃO ESTOQUE', 'OBSERVACAO ESTOQUE', 'LOG', 'OBSERVAÇÃO', 'OBSERVACAO'])
        obs_estoque = str(raw_obs_est).strip() if raw_obs_est is not None and str(raw_obs_est).strip() != 'None' else None

        raw_sc = get_col_val(['SOLICITAÇÃO DE COMPRA', 'SOLICITACAO DE COMPRA', 'SC'])
        solicitacao_compra = str(raw_sc).strip() if raw_sc is not None and str(raw_sc).strip() != 'None' else None

        qtd_comprado = parse_float(get_col_val(['COMPRADO', 'QTD COMPRADO', 'QTD']))
        quantidade_op = max(1.0, qtd_estoque + qtd_comprado)
        valor_unitario = parse_float(get_col_val(['VALOR UNITARIO COMPRA', 'VALOR UNITÁRIO COMPRA', 'VALOR UNITÁRIO', 'VALOR UNITARIO']))

        raw_pagto = get_col_val(['TIPO PAGAMENTO/FATURADO', 'TIPO PAGAMENTO', 'SITUACAO'])
        status_pagamento_raw = str(raw_pagto).strip().upper() if raw_pagto is not None else 'PENDENTE'
        status_pagamento = status_pagamento_raw if status_pagamento_raw in ['PENDENTE', 'PAGAMENTO ANTECIPADO', 'FATURADO', 'PAGO'] else 'PENDENTE'

        raw_forn = get_col_val(['FORNECEDOR SELECIONADO', 'FORNECEDOR'])
        fornecedor = str(raw_forn).strip() if raw_forn is not None and str(raw_forn).strip() != 'None' else None

        raw_pc = get_col_val(['PEDIDO COMPRA (PROTHEUS)', 'PEDIDO COMPRA'])
        pedido_compra = str(raw_pc).strip() if raw_pc is not None and str(raw_pc).strip() != 'None' else None

        raw_ipi = get_col_val(['IPI COMPRA', 'IPI COMPRA (%)', 'IPI'])
        ipi = parse_float(raw_ipi)

        data_pc = parse_date(get_col_val(['DATA EMISSÃO PC', 'DATA EMISSAO PC', 'DATA PC']))
        data_pagamento = parse_date(get_col_val(['DATA PREVISÃO PGTO', 'DATA PREVISAO PGTO', 'DATA PGTO', 'DT PAG.']))

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
