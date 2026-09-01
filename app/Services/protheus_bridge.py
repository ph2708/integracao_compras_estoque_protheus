import sys
import json
import pymssql
import os

def load_env_file():
    env_path = os.path.join(os.path.dirname(__file__), '../../.env')
    if os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    k, v = line.split('=', 1)
                    k = k.strip()
                    v = v.strip().strip("'\"")
                    if k not in os.environ:
                        os.environ[k] = v

load_env_file()

DB_HOST = os.getenv('DB_PROTHEUS_HOST', '177.221.240.40')
DB_PORT = int(os.getenv('DB_PROTHEUS_PORT', 14333))
DB_NAME = os.getenv('DB_PROTHEUS_DATABASE', 'MP_12')
DB_USER = os.getenv('DB_PROTHEUS_USERNAME', 'ConsultaProtheus')
DB_PASS = os.getenv('DB_PROTHEUS_PASSWORD', '').strip("'\"")

def get_connection():
    return pymssql.connect(
        server=DB_HOST,
        port=DB_PORT,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME,
        charset='ISO-8859-1'
    )

def list_filiais():
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT DISTINCT RTRIM(C2_FILIAL) AS C2_FILIAL 
    FROM SC2010 
    WHERE D_E_L_E_T_ = ' ' AND C2_FILIAL IS NOT NULL AND RTRIM(C2_FILIAL) != ''
    ORDER BY C2_FILIAL
    """
    cursor.execute(sql)
    rows = cursor.fetchall()
    conn.close()
    return [r['C2_FILIAL'] for r in rows if r['C2_FILIAL']]

def list_pedidos(filial=None):
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT DISTINCT TOP 100 RTRIM(C2_PEDIDO) AS C2_PEDIDO 
    FROM SC2010 
    WHERE D_E_L_E_T_ = ' ' AND C2_PEDIDO IS NOT NULL AND RTRIM(C2_PEDIDO) != ''
    """
    params = []
    if filial and filial != 'null':
        filiais_list = [f.strip() for f in filial.split(',') if f and f.strip()]
        if len(filiais_list) == 1:
            sql += " AND RTRIM(C2_FILIAL) = %s"
            params.append(filiais_list[0])
        elif len(filiais_list) > 1:
            placeholders = ', '.join(['%s'] * len(filiais_list))
            sql += f" AND RTRIM(C2_FILIAL) IN ({placeholders})"
            params.extend(filiais_list)

    sql += " ORDER BY C2_PEDIDO DESC"
    cursor.execute(sql, params)
    rows = cursor.fetchall()
    conn.close()
    return [r['C2_PEDIDO'] for r in rows if r['C2_PEDIDO']]

def get_pedido_items(c2_pedido, filial=None):
    """
    Consulta componentes/matérias-primas requisitados da OP na SD4010 + SC2010.
    Garante vinculo exato de Produto Pai com SC2010 (D4_OP = C2_NUM + C2_ITEM + C2_SEQUEN).
    EXCLUINDO produtos do tipo PI (Produto Intermediário) e PA (Produto Acabado) na SB1010.
    Deduplicado por Filial, OP e Código de Produto.
    """
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT DISTINCT
        RTRIM(S.C2_FILIAL) AS C2_FILIAL,
        RTRIM(S.C2_PEDIDO) AS C2_PEDIDO,
        RTRIM(D.D4_OP) AS D4_OP,
        RTRIM(D.D4_COD) AS C2_PRODUTO,
        RTRIM(B.B1_DESC) AS B1_DESC,
        RTRIM(B5.B5_CEME) AS B5_CEME,
        RTRIM(S.C2_OBS) AS C2_OBS,
        RTRIM(B.B1_TIPO) AS B1_TIPO,
        D.D4_QTDEORI AS QUANTIDADE,
        RTRIM(S.C2_PRODUTO) + ' - ' + RTRIM(ISNULL(B_PAI.B1_DESC, '')) AS PRODUTO_PAI
    FROM SD4010 D WITH (NOLOCK)
    INNER JOIN SC2010 S WITH (NOLOCK) 
        ON RTRIM(D.D4_FILIAL) = RTRIM(S.C2_FILIAL)
       AND (
           RTRIM(D.D4_OP) = RTRIM(S.C2_NUM) + RTRIM(S.C2_ITEM) + RTRIM(S.C2_SEQUEN)
           OR (LEN(RTRIM(D.D4_OP)) = 6 AND RTRIM(D.D4_OP) = RTRIM(S.C2_NUM))
       )
       AND S.D_E_L_E_T_ = ' '
    LEFT JOIN SB1010 B WITH (NOLOCK) ON RTRIM(D.D4_COD) = RTRIM(B.B1_COD) AND B.D_E_L_E_T_ = ' '
    LEFT JOIN SB5010 B5 WITH (NOLOCK) ON RTRIM(D.D4_COD) = RTRIM(B5.B5_COD) AND B5.D_E_L_E_T_ = ' '
    LEFT JOIN SB1010 B_PAI WITH (NOLOCK) ON RTRIM(S.C2_PRODUTO) = RTRIM(B_PAI.B1_COD) AND B_PAI.D_E_L_E_T_ = ' '
    WHERE D.D_E_L_E_T_ = ' ' 
      AND (RTRIM(S.C2_PEDIDO) = %s OR RTRIM(S.C2_OBS) LIKE %s)
      AND (B.B1_TIPO IS NULL OR RTRIM(B.B1_TIPO) NOT IN ('PI', 'PA'))
    """
    search_obs = f"%{c2_pedido}%"
    params = [c2_pedido, search_obs]

    if filial and filial != 'null':
        filiais_list = [f.strip() for f in filial.split(',') if f and f.strip()]
        if len(filiais_list) == 1:
            sql += " AND RTRIM(S.C2_FILIAL) = %s"
            params.append(filiais_list[0])
        elif len(filiais_list) > 1:
            placeholders = ', '.join(['%s'] * len(filiais_list))
            sql += f" AND RTRIM(S.C2_FILIAL) IN ({placeholders})"
            params.extend(filiais_list)

    sql += " ORDER BY RTRIM(D.D4_OP), RTRIM(D.D4_COD)"
    cursor.execute(sql, params)
    rows = cursor.fetchall()
    conn.close()

    unique_items = {}
    for r in rows:
        b1_tipo = (r.get('B1_TIPO') or '').strip().upper()
        if b1_tipo in ['PI', 'PA']:
            continue

        filial_val = (r.get('C2_FILIAL') or '').strip()
        op_val = (r.get('D4_OP') or '').strip()
        cod_val = (r.get('C2_PRODUTO') or '').strip()

        if not cod_val:
            continue

        descCurta = (r.get('B1_DESC') or '').strip()
        descLonga = (r.get('B5_CEME') or descCurta).strip()

        key = (filial_val, op_val, cod_val)

        if key not in unique_items:
            unique_items[key] = {
                'filial': filial_val,
                'pedido': (r.get('C2_PEDIDO') or '').strip(),
                'op': op_val,
                'codigo_produto': cod_val,
                'descricao': descCurta,
                'descricao_longa': descLonga,
                'produto_pai': (r.get('PRODUTO_PAI') or '').strip(),
                'cliente_obs': (r.get('C2_OBS') or '').strip(),
                'quantidade': float(r.get('QUANTIDADE') or 1.0)
            }

    return list(unique_items.values())

def get_ultimos_precos_batch(codigos_produtos):
    if not codigos_produtos:
        return {}

    conn = get_connection()
    cursor = conn.cursor(as_dict=True)

    codigos_unicos = list(set([str(c).strip() for c in codigos_produtos if c and str(c).strip()]))
    if not codigos_unicos:
        conn.close()
        return {}

    placeholders = ', '.join(['%s'] * len(codigos_unicos))
    sql = f"""
    WITH RankedSC7 AS (
        SELECT 
            RTRIM(C7.C7_PRODUTO) AS C7_PRODUTO,
            C7.C7_PRECO,
            RTRIM(C7.C7_FORNECE) AS C7_FORNECE,
            RTRIM(A2.A2_NOME) AS A2_NOME,
            ROW_NUMBER() OVER(PARTITION BY RTRIM(C7.C7_PRODUTO) ORDER BY C7.C7_EMISSAO DESC, C7.R_E_C_N_O_ DESC) as rn
        FROM SC7010 C7 WITH (NOLOCK)
        LEFT JOIN SA2010 A2 WITH (NOLOCK) ON RTRIM(C7.C7_FORNECE) = RTRIM(A2.A2_COD) AND A2.D_E_L_E_T_ = ' '
        WHERE C7.D_E_L_E_T_ = ' ' 
          AND RTRIM(C7.C7_PRODUTO) IN ({placeholders})
          AND C7.C7_PRECO > 0
    )
    SELECT C7_PRODUTO, C7_PRECO, C7_FORNECE, A2_NOME
    FROM RankedSC7
    WHERE rn = 1
    """

    cursor.execute(sql, codigos_unicos)
    rows = cursor.fetchall()
    conn.close()

    result = {}
    for r in rows:
        cod = (r.get('C7_PRODUTO') or '').strip()
        preco = float(r.get('C7_PRECO') or 0.0)
        fornece_cod = (r.get('C7_FORNECE') or '').strip()
        fornece_nome = (r.get('A2_NOME') or '').strip()

        fornece_full = f"{fornece_cod} - {fornece_nome}" if (fornece_cod and fornece_nome) else fornece_cod

        result[cod] = {
            'preco': preco,
            'valor_unitario': preco,
            'fornecedor': fornece_full,
            'codigo_fornecedor': fornece_full
        }
    return result

def get_ultimo_preco_fornecedor(codigo_produto):
    res = get_ultimos_precos_batch([codigo_produto])
    return res.get(codigo_produto.strip(), {'preco': 0.0, 'valor_unitario': 0.0, 'fornecedor': '', 'codigo_fornecedor': ''})

def get_apontamentos_montagem(filial=None, data_de=None, data_ate=None):
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    
    where_clauses = ["H6.D_E_L_E_T_ = ' '"]
    params = []

    if filial and filial != 'null':
        where_clauses.append("H6.H6_FILIAL = %s")
        params.append(str(filial).strip())

    if data_de and data_de != 'null':
        dt_de = str(data_de).replace('-', '').strip()
        if len(dt_de) == 8:
            where_clauses.append("H6.H6_DATAINI >= %s")
            params.append(dt_de)

    if data_ate and data_ate != 'null':
        dt_ate = str(data_ate).replace('-', '').strip()
        if len(dt_ate) == 8:
            where_clauses.append("H6.H6_DATAINI <= %s")
            params.append(dt_ate)

    where_sql = " AND ".join(where_clauses)

    sql = f"""
    SELECT TOP 1500
        RTRIM(H6.H6_FILIAL) AS FILIAL,
        RTRIM(H6.H6_OP) AS OP,
        RTRIM(C2.C2_PEDIDO) AS PEDIDO,
        RTRIM(C2.C2_OBS) AS CLIENTE_OBS,
        RTRIM(H6.H6_OPERAC) AS OPERAC,
        RTRIM(H6.H6_RECURSO) AS COD_RECURSO,
        RTRIM(H1.H1_DESCRI) AS NOME_RECURSO,
        RTRIM(H6.H6_DATAINI) AS DATA_INI,
        RTRIM(H6.H6_HORAINI) AS HORA_INI,
        RTRIM(H6.H6_DATAFIN) AS DATA_FIN,
        RTRIM(H6.H6_HORAFIN) AS HORA_FIN,
        RTRIM(H6.H6_TEMPO) AS TEMPO,
        H6.H6_QTDPROD,
        RTRIM(H6.H6_PT) AS PT
    FROM SH6010 H6 WITH (NOLOCK)
    LEFT JOIN SH1010 H1 WITH (NOLOCK) ON H1.H1_FILIAL = H6.H6_FILIAL AND H1.H1_CODIGO = H6.H6_RECURSO AND H1.D_E_L_E_T_ = ' '
    LEFT JOIN SC2010 C2 WITH (NOLOCK) ON C2.C2_FILIAL = H6.H6_FILIAL AND C2.C2_NUM = SUBSTRING(H6.H6_OP, 1, 6) AND C2.D_E_L_E_T_ = ' '
    WHERE {where_sql}
    ORDER BY H6.H6_DATAINI DESC, H6.H6_HORAINI DESC
    """
    cursor.execute(sql, tuple(params))
    rows = cursor.fetchall()
    conn.close()

    items = []
    for r in rows:
        items.append({
            'filial': (r.get('FILIAL') or '').strip(),
            'op': (r.get('OP') or '').strip(),
            'pedido': (r.get('PEDIDO') or '').strip(),
            'cliente': (r.get('CLIENTE_OBS') or '').strip(),
            'operacao': (r.get('OPERAC') or '').strip(),
            'cod_recurso': (r.get('COD_RECURSO') or '').strip(),
            'nome_recurso': (r.get('NOME_RECURSO') or '').strip(),
            'data_ini': (r.get('DATA_INI') or '').strip(),
            'hora_ini': (r.get('HORA_INI') or '').strip(),
            'data_fin': (r.get('DATA_FIN') or '').strip(),
            'hora_fin': (r.get('HORA_FIN') or '').strip(),
            'tempo': (r.get('TEMPO') or '000:00').strip(),
            'qtd_prod': float(r.get('H6_QTDPROD') or 0.0),
            'pt': (r.get('PT') or '').strip()
        })
    return items

def get_valores_brutos_pvs(pvs_list):
    if not pvs_list:
        return {}

    conn = get_connection()
    cursor = conn.cursor(as_dict=True)

    pvs_unicos = list(set([str(p).strip() for p in pvs_list if p and str(p).strip()]))
    if not pvs_unicos:
        conn.close()
        return {}

    placeholders = ', '.join(['%s'] * len(pvs_unicos))
    sql = f"""
    SELECT 
        RTRIM(C6_NUM) AS PV,
        SUM(C6_VALOR) AS TOTAL_VALOR_BRUTO
    FROM SC6010 WITH (NOLOCK)
    WHERE D_E_L_E_T_ = ' ' AND RTRIM(C6_NUM) IN ({placeholders})
    GROUP BY RTRIM(C6_NUM)
    """

    cursor.execute(sql, pvs_unicos)
    rows = cursor.fetchall()
    conn.close()

    result = {}
    for r in rows:
        pv_num = (r.get('PV') or '').strip()
        val_bruto = float(r.get('TOTAL_VALOR_BRUTO') or 0.0)
        if pv_num:
            result[pv_num] = val_bruto
    return result

def get_produto_info(codigo_produto):
    if not codigo_produto:
        return None
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT 
        RTRIM(B.B1_COD) AS CODIGO, 
        RTRIM(B.B1_DESC) AS DESC_CURTA, 
        RTRIM(ISNULL(B5.B5_CEME, '')) AS DESC_LONGA
    FROM SB1010 B WITH (NOLOCK)
    LEFT JOIN SB5010 B5 WITH (NOLOCK) ON RTRIM(B.B1_COD) = RTRIM(B5.B5_COD) AND B5.D_E_L_E_T_ = ' '
    WHERE B.D_E_L_E_T_ = ' ' AND RTRIM(B.B1_COD) = %s
    """
    cursor.execute(sql, [str(codigo_produto).strip()])
    row = cursor.fetchone()
    conn.close()
    if row:
        return {
            'codigo_produto': row.get('CODIGO'),
            'descricao': row.get('DESC_CURTA'),
            'descricao_longa': row.get('DESC_LONGA')
        }
    return None

if __name__ == '__main__':
    command = sys.argv[1] if len(sys.argv) > 1 else ''

    try:
        if command in ['list_filiais', 'filiais']:
            print(json.dumps({'success': True, 'data': list_filiais()}))
        elif command in ['list_pedidos', 'pedidos']:
            fil = sys.argv[2] if len(sys.argv) > 2 and sys.argv[2] != 'null' else None
            print(json.dumps({'success': True, 'data': list_pedidos(fil)}))
        elif command in ['get_pedido_items', 'pedido_itens']:
            ped = sys.argv[2] if len(sys.argv) > 2 else ''
            fil = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != 'null' else None
            print(json.dumps({'success': True, 'data': get_pedido_items(ped, fil)}))
        elif command in ['get_produto_info', 'produto_info']:
            cod = sys.argv[2] if len(sys.argv) > 2 else ''
            print(json.dumps({'success': True, 'data': get_produto_info(cod)}))
        elif command in ['get_ultimo_preco', 'ultimo_preco']:
            cod = sys.argv[2] if len(sys.argv) > 2 else ''
            print(json.dumps({'success': True, 'data': get_ultimo_preco_fornecedor(cod)}))
        elif command in ['get_precos_batch', 'ultimos_precos_batch']:
            cods = json.loads(sys.argv[2]) if len(sys.argv) > 2 else []
            print(json.dumps({'success': True, 'data': get_ultimos_precos_batch(cods)}))
        elif command in ['get_apontamentos_montagem', 'apontamentos_montagem']:
            fil = sys.argv[2] if len(sys.argv) > 2 and sys.argv[2] != 'null' else None
            dt_de = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != 'null' else None
            dt_ate = sys.argv[4] if len(sys.argv) > 4 and sys.argv[4] != 'null' else None
            print(json.dumps({'success': True, 'data': get_apontamentos_montagem(fil, dt_de, dt_ate)}))
        elif command in ['get_valores_brutos_pvs', 'valores_brutos']:
            pvs = json.loads(sys.argv[2]) if len(sys.argv) > 2 else []
            print(json.dumps({'success': True, 'data': get_valores_brutos_pvs(pvs)}))
        else:
            print(json.dumps({'success': False, 'message': f'Comando desconhecido: {command}'}))
    except Exception as e:
        print(json.dumps({'success': False, 'message': str(e)}))
