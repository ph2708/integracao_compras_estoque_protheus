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
    Consulta componentes/matérias-primas requisitados da OP na SD4010 + SC2010
    EXCLUINDO produtos do tipo PI (Produto Intermediário) e PA (Produto Acabado) na SB1010 (B1_TIPO NOT IN ('PI', 'PA'))
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
           OR RTRIM(D.D4_OP) LIKE RTRIM(S.C2_NUM) + '%'
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
            RTRIM(C7_PRODUTO) AS C7_PRODUTO,
            C7_PRECO,
            RTRIM(C7_FORNECE) AS C7_FORNECE,
            ROW_NUMBER() OVER(PARTITION BY RTRIM(C7_PRODUTO) ORDER BY C7_EMISSAO DESC, R_E_C_N_O_ DESC) as rn
        FROM SC7010 WITH (NOLOCK)
        WHERE D_E_L_E_T_ = ' ' 
          AND RTRIM(C7_PRODUTO) IN ({placeholders})
          AND C7_PRECO > 0
    )
    SELECT C7_PRODUTO, C7_PRECO, C7_FORNECE
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
        fornece = (r.get('C7_FORNECE') or '').strip()
        result[cod] = {
            'preco': preco,
            'fornecedor': fornece
        }
    return result

def get_ultimo_preco_fornecedor(codigo_produto):
    res = get_ultimos_precos_batch([codigo_produto])
    return res.get(codigo_produto.strip(), {'preco': 0.0, 'fornecedor': ''})

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
        elif command in ['get_ultimo_preco', 'ultimo_preco']:
            cod = sys.argv[2] if len(sys.argv) > 2 else ''
            print(json.dumps({'success': True, 'data': get_ultimo_preco_fornecedor(cod)}))
        elif command in ['get_precos_batch', 'ultimos_precos_batch']:
            cods = json.loads(sys.argv[2]) if len(sys.argv) > 2 else []
            print(json.dumps({'success': True, 'data': get_ultimos_precos_batch(cods)}))
        else:
            print(json.dumps({'success': False, 'message': f'Comando desconhecido: {command}'}))
    except Exception as e:
        print(json.dumps({'success': False, 'message': str(e)}))
