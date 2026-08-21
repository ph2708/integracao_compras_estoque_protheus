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
                dt = datetime.date(y, m, d)
                return dt.strftime('%Y-%m-%d')
        if '-' in val_str:
            parts = val_str.split('-')
            if len(parts) == 3 and len(parts[0]) == 4:
                y, m, d = int(parts[0]), int(parts[1]), int(parts[2])
                dt = datetime.date(y, m, d)
                return dt.strftime('%Y-%m-%d')
        return None
    except:
        return None

def parse_float(val):
    if val is None:
        return 0.0
    val_str = str(val).strip()
    if not val_str or val_str.upper() in ['NONE', '#N/D', 'NULL', '']:
        return 0.0
    if ',' in val_str:
        val_str = val_str.replace('.', '').replace(',', '.')
    try:
        return float(val_str)
    except:
        return 0.0

def read_csv_rows(file_path):
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
        except Exception:
            continue
    return []

def load_rows_from_file(file_path, original_ext=None):
    """
    Carrega as linhas de arquivos .csv ou .xlsx com fallback automático inteligente
    """
    ext = original_ext if original_ext else os.path.splitext(file_path)[1].lower()
    if not ext.startswith('.'):
        ext = '.' + ext if ext else ''

    if ext in ['.csv', '.txt']:
        rows = read_csv_rows(file_path)
        if rows:
            return rows

    # Tenta abrir como Excel (.xlsx)
    try:
        wb = openpyxl.load_workbook(file_path, read_only=True, data_only=True)
        sheet = wb.active
        rows = []
        for row in sheet.iter_rows(values_only=True):
            if row:
                rows.append([cell for cell in row])
        wb.close()
        print(f"Lido Excel .xlsx ({len(rows)} linhas)")
        return rows
    except Exception as err:
        print(f"Formato não-Excel ou arquivo temporário. Tentando fallback para leitor CSV...")
        rows = read_csv_rows(file_path)
        if rows:
            return rows
        raise err

def fetch_protheus_enrichment_data(codigos_produtos, ops, pvs):
    """
    Auto-enriquece em LOTE todos os campos não-obrigatórios que vierem em branco na planilha (Descrição Curta, Descrição Longa SB5010, Produto Pai, Cliente C2_OBS, Último Preço e Fornecedor da SC7010)
    """
    product_map = {}
    order_map = {}
    purchase_map = {}

    try:
        import pymssql
        host = os.getenv('DB_PROTHEUS_HOST', '177.221.240.40')
        port = int(os.getenv('DB_PROTHEUS_PORT', 14333))
        database = os.getenv('DB_PROTHEUS_DATABASE', 'MP_12')
        user = os.getenv('DB_PROTHEUS_USERNAME', 'ConsultaProtheus')
        password = os.getenv('DB_PROTHEUS_PASSWORD', '')

        conn = pymssql.connect(
            server=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset='ISO-8859-1'
        )
        cursor = conn.cursor(as_dict=True)

        cods_unicos = list(set([c.strip() for c in codigos_produtos if c and c.strip()]))
        op_nums = list(set([str(op)[:6] for op in ops if op and len(str(op)) >= 6]))
        pv_nums = list(set([str(pv).strip() for pv in pvs if pv and str(pv).strip()]))

        # 1. Consulta Descrição Curta (B1_DESC) e Descrição Longa (B5_CEME da SB5010)
        if cods_unicos:
            for chunk_idx in range(0, len(cods_unicos), 200):
                chunk = cods_unicos[chunk_idx:chunk_idx+200]
                placeholders = "', '".join(chunk)
                sql_prod = f"""
                SELECT 
                    RTRIM(B.B1_COD) AS B1_COD,
                    RTRIM(B.B1_DESC) AS B1_DESC,
                    RTRIM(B5.B5_CEME) AS B5_CEME
                FROM SB1010 B WITH (NOLOCK)
                LEFT JOIN SB5010 B5 WITH (NOLOCK) ON RTRIM(B.B1_COD) = RTRIM(B5.B5_COD) AND B5.D_E_L_E_T_ = ' '
                WHERE B.D_E_L_E_T_ = ' ' AND RTRIM(B.B1_COD) IN ('{placeholders}')
                """
                cursor.execute(sql_prod)
                for r in cursor.fetchall():
                    c_cod = (r.get('B1_COD') or '').strip()
                    c_desc = (r.get('B1_DESC') or '').strip()
                    c_longa = (r.get('B5_CEME') or c_desc).strip()
                    product_map[c_cod] = {
                        'descricao': c_desc,
                        'descricao_longa': c_longa
                    }

        # 2. Consulta Produto Pai Concatenado e Cliente C2_OBS na SC2010 + SB1010 (Priorizando Nomes Reais de Clientes sobre '0000 - ESTOQUE')
        where_clauses = []
        if op_nums:
            ops_formatted = "', '".join(op_nums[:200])
            where_clauses.append(f"RTRIM(S.C2_NUM) IN ('{ops_formatted}')")
        if pv_nums:
            pvs_formatted = "', '".join(pv_nums[:200])
            where_clauses.append(f"RTRIM(S.C2_PEDIDO) IN ('{pvs_formatted}')")

        if where_clauses:
            sql_orders = f"""
            SELECT DISTINCT
                RTRIM(S.C2_NUM) AS C2_NUM,
                RTRIM(S.C2_PEDIDO) AS C2_PEDIDO,
                RTRIM(S.C2_OBS) AS C2_OBS,
                RTRIM(S.C2_PRODUTO) + ' - ' + RTRIM(ISNULL(B.B1_DESC, '')) AS PRODUTO_PAI,
                CASE WHEN RTRIM(S.C2_OBS) LIKE '%ESTOQUE%' THEN 1 ELSE 0 END AS IS_ESTOQUE
            FROM SC2010 S WITH (NOLOCK)
            LEFT JOIN SB1010 B WITH (NOLOCK) ON RTRIM(S.C2_PRODUTO) = RTRIM(B.B1_COD) AND B.D_E_L_E_T_ = ' '
            WHERE S.D_E_L_E_T_ = ' ' AND ({' OR '.join(where_clauses)})
            ORDER BY IS_ESTOQUE ASC
            """
            cursor.execute(sql_orders)
            for r in cursor.fetchall():
                num = (r.get('C2_NUM') or '').strip()
                ped = (r.get('C2_PEDIDO') or '').strip()
                obs = (r.get('C2_OBS') or '').strip()
                pai = (r.get('PRODUTO_PAI') or '').strip()

                val_data = {'produto_pai': pai, 'cliente_obs': obs}
                if num:
                    if 'OP_' + num not in order_map or ('ESTOQUE' in order_map['OP_' + num].get('cliente_obs', '') and 'ESTOQUE' not in obs):
                        order_map['OP_' + num] = val_data
                if ped:
                    if 'PV_' + ped not in order_map or ('ESTOQUE' in order_map['PV_' + ped].get('cliente_obs', '') and 'ESTOQUE' not in obs):
                        order_map['PV_' + ped] = val_data

        # 3. Consulta Último Preço (C7_PRECO) e Fornecedor (C7_FORNECE) na SC7010
        if cods_unicos:
            for chunk_idx in range(0, len(cods_unicos), 200):
                chunk = cods_unicos[chunk_idx:chunk_idx+200]
                placeholders = "', '".join(chunk)
                sql_sc7 = f"""
                WITH RankedSC7 AS (
                    SELECT 
                        RTRIM(C7_PRODUTO) AS C7_PRODUTO,
                        C7_PRECO,
                        RTRIM(C7_FORNECE) AS C7_FORNECE,
                        ROW_NUMBER() OVER(PARTITION BY RTRIM(C7_PRODUTO) ORDER BY C7_EMISSAO DESC, R_E_C_N_O_ DESC) as rn
                    FROM SC7010 WITH (NOLOCK)
                    WHERE D_E_L_E_T_ = ' ' 
                      AND RTRIM(C7_PRODUTO) IN ('{placeholders}')
                      AND C7_PRECO > 0
                )
                SELECT C7_PRODUTO, C7_PRECO, C7_FORNECE
                FROM RankedSC7
                WHERE rn = 1
                """
                cursor.execute(sql_sc7)
                for r in cursor.fetchall():
                    c_cod = (r.get('C7_PRODUTO') or '').strip()
                    c_preco = float(r.get('C7_PRECO') or 0.0)
                    c_forn = (r.get('C7_FORNECE') or '').strip()
                    purchase_map[c_cod] = {'preco': c_preco, 'fornecedor': c_forn}

        conn.close()
        print(f"⚡ Enriquecimento Protheus: {len(product_map)} produtos, {len(order_map)} OPs/PVs e {len(purchase_map)} preços consultados.")
    except Exception as e:
        print(f"Aviso ao enriquecer dados no Protheus: {e}")

    return product_map, order_map, purchase_map

def import_excel(target_path=None, mode='truncate', original_ext=None):
    excel_file = target_path if target_path and os.path.exists(target_path) else DEFAULT_EXCEL_PATH

    if not os.path.exists(excel_file):
        print(f"Aviso: Planilha não encontrada no caminho {excel_file}")
        return

    print(f"Processando arquivo: {excel_file}...")
    rows = load_rows_from_file(excel_file, original_ext)
    if not rows:
        print("Aviso: Nenhuma linha foi encontrada no arquivo enviado.")
        return

    # Pré-mapeamento de cabeçalho para coletar OPs e PVs
    is_header = True
    col_map = {}
    cods_to_query = []
    ops_to_query = []
    pvs_to_query = []

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
            continue

        def temp_get_col_val(col_names):
            for name in col_names:
                name_upper = name.upper()
                if name_upper in col_map:
                    idx = col_map[name_upper]
                    if idx < len(row) and row[idx] is not None:
                        return row[idx]
            return None

        cod_val = temp_get_col_val(['CODIGO PRODUTO', 'CÓDIGO PRODUTO', 'CÓDIGO', 'CODIGO'])
        op_val = temp_get_col_val(['ORDEM PRODUCAO', 'ORDEM PRODUÇÃO', 'ORD PRODUCAO', 'ORD PRODUÇÃO'])
        pv_val = temp_get_col_val(['PV', 'PEDIDO DE VENDA', 'PEDIDO'])

        if cod_val: cods_to_query.append(str(cod_val).strip())
        if op_val: ops_to_query.append(str(op_val).strip())
        if pv_val: pvs_to_query.append(str(pv_val).strip())

    # Pre-busca enriquecimento do Protheus
    product_map, order_map, purchase_map = fetch_protheus_enrichment_data(cods_to_query, ops_to_query, pvs_to_query)

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

    for row in rows:
        if not row:
            continue

        row_str = [str(cell).strip().upper() for cell in row if cell is not None]
        if is_header:
            row_text = ' '.join(row_str)
            if any(k in row_text for k in ['ORDEM PRODUCAO', 'ORDEM PRODUÇÃO', 'ORD PRODUCAO', 'ORD PRODUÇÃO', 'CODIGO PRODUTO', 'CÓDIGO PRODUTO', 'PV', 'CÓDIGO', 'CODIGO']):
                is_header = False
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

        # ⚡ Auto-preenchimento inteligente de campos em branco no Protheus!
        protheus_prod = product_map.get(codigo_produto, {})
        protheus_ord = {}
        if op and len(op) >= 6:
            protheus_ord = order_map.get('OP_' + op[:6], {})
        if not protheus_ord and pedido:
            protheus_ord = order_map.get('PV_' + pedido, {})
        protheus_pur = purchase_map.get(codigo_produto, {})

        raw_desc = get_col_val(['DESCRIÇÃO COMPONENTE', 'DESCRICAO COMPONENTE', 'DESCRIÇÃO MATERIAL', 'DESCRICAO MATERIAL', 'DESCRIÇÃO', 'DESCRICAO'])
        descricao = str(raw_desc).strip() if raw_desc is not None and str(raw_desc).strip() != 'None' and str(raw_desc).strip() != '' else protheus_prod.get('descricao', '')

        raw_desc_longa = get_col_val(['DESCRIÇÃO LONGA (B5_CEME)', 'DESCRIÇÃO LONGA', 'DESCRICAO LONGA', 'DESC. LONGA'])
        descricao_longa = str(raw_desc_longa).strip() if raw_desc_longa is not None and str(raw_desc_longa).strip() != 'None' and str(raw_desc_longa).strip() != '' else protheus_prod.get('descricao_longa', descricao)

        raw_pai = get_col_val(['PRODUTO PAI CONCATENADO', 'PRODUTO PAI'])
        produto_pai = str(raw_pai).strip() if raw_pai is not None and str(raw_pai).strip() != 'None' and str(raw_pai).strip() != '' else protheus_ord.get('produto_pai', None)

        raw_cliente = get_col_val(['CLIENTE', 'NOME CLIENTE', 'CLIENTE (C2_OBS)'])
        cliente_obs = str(raw_cliente).strip() if raw_cliente is not None and str(raw_cliente).strip() != 'None' and str(raw_cliente).strip() != '' else protheus_ord.get('cliente_obs', None)

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
        if valor_unitario == 0.0 and protheus_pur.get('preco'):
            valor_unitario = protheus_pur.get('preco')

        raw_forn = get_col_val(['FORNECEDOR SELECIONADO', 'FORNECEDOR'])
        fornecedor = str(raw_forn).strip() if raw_forn is not None and str(raw_forn).strip() != 'None' and str(raw_forn).strip() != '' else protheus_pur.get('fornecedor', None)

        raw_pagto = get_col_val(['TIPO PAGAMENTO/FATURADO', 'TIPO PAGAMENTO', 'SITUACAO'])
        status_pagamento_raw = str(raw_pagto).strip().upper() if raw_pagto is not None else 'PENDENTE'
        status_pagamento = status_pagamento_raw if status_pagamento_raw in ['PENDENTE', 'PAGAMENTO ANTECIPADO', 'FATURADO', 'PAGO'] else 'PENDENTE'

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
        cursor.execute(sql_est, (codigo_produto, descricao, descricao_longa, produto_pai, op, pedido, cliente_obs, quantidade_op, qtd_estoque, status_pcp, obs_estoque, now_str, now_str))
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
    orig_ext = sys.argv[3] if len(sys.argv) > 3 else None
    import_excel(target, mode, orig_ext)
