import sys
import json
import pymssql
import os

DB_HOST = os.getenv('DB_PROTHEUS_HOST', '177.221.240.40')
DB_PORT = int(os.getenv('DB_PROTHEUS_PORT', 14333))
DB_NAME = os.getenv('DB_PROTHEUS_DATABASE', 'MP_12')
DB_USER = os.getenv('DB_PROTHEUS_USERNAME', 'ConsultaProtheus')
DB_PASS = os.getenv('DB_PROTHEUS_PASSWORD', 'C*n$ult@#M@q#')

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
    SELECT DISTINCT TOP 50 RTRIM(C2_PEDIDO) AS C2_PEDIDO 
    FROM SC2010 
    WHERE D_E_L_E_T_ = ' ' AND C2_PEDIDO IS NOT NULL AND RTRIM(C2_PEDIDO) != ''
    """
    params = []
    if filial:
        sql += " AND RTRIM(C2_FILIAL) = %s"
        params.append(filial)

    sql += " ORDER BY C2_PEDIDO DESC"
    cursor.execute(sql, params)
    rows = cursor.fetchall()
    conn.close()
    return [r['C2_PEDIDO'] for r in rows if r['C2_PEDIDO']]

def get_pedido_items(c2_pedido, filial=None):
    """
    Opção A: Consulta Matérias-Primas / Componentes Requisitados da OP na SD4010 + SC2010
    """
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT DISTINCT TOP 100
        RTRIM(S.C2_FILIAL) AS C2_FILIAL,
        RTRIM(S.C2_PEDIDO) AS C2_PEDIDO,
        RTRIM(D.D4_OP) AS D4_OP,
        RTRIM(D.D4_COD) AS C2_PRODUTO,
        RTRIM(B.B1_DESC) AS B1_DESC,
        RTRIM(B5.B5_CEME) AS B5_CEME,
        RTRIM(S.C2_OBS) AS C2_OBS,
        D.D4_QTDEORI AS QUANTIDADE
    FROM SD4010 D
    INNER JOIN SC2010 S ON RTRIM(D.D4_OP) LIKE RTRIM(S.C2_NUM) + '%' AND S.D_E_L_E_T_ = ' '
    LEFT JOIN SB1010 B ON RTRIM(D.D4_COD) = RTRIM(B.B1_COD) AND B.D_E_L_E_T_ = ' '
    LEFT JOIN SB5010 B5 ON RTRIM(D.D4_COD) = RTRIM(B5.B5_COD) AND B5.D_E_L_E_T_ = ' '
    WHERE D.D_E_L_E_T_ = ' ' AND (RTRIM(S.C2_PEDIDO) = %s OR RTRIM(S.C2_OBS) LIKE %s)
    """
    search_obs = f"%{c2_pedido}%"
    params = [c2_pedido, search_obs]

    if filial:
        sql += " AND RTRIM(S.C2_FILIAL) = %s"
        params.append(filial)

    sql += " ORDER BY RTRIM(D.D4_OP), RTRIM(D.D4_COD)"
    cursor.execute(sql, params)
    rows = cursor.fetchall()
    conn.close()

    formatted_rows = []
    for r in rows:
        desc = r.get('B5_CEME') or r.get('B1_DESC') or ''
        formatted_rows.append({
            'filial': r.get('C2_FILIAL') or '',
            'pedido': r.get('C2_PEDIDO') or '',
            'op': r.get('D4_OP') or '',
            'codigo_produto': r.get('C2_PRODUTO') or '',
            'descricao': desc.strip(),
            'cliente_obs': (r.get('C2_OBS') or '').strip(),
            'quantidade': float(r.get('QUANTIDADE') or 1)
        })

    return formatted_rows

def get_fornecedor_compra(pedido, produto=None):
    conn = get_connection()
    cursor = conn.cursor(as_dict=True)
    sql = """
    SELECT TOP 1 
        RTRIM(C.C7_NUM) AS C7_NUM,
        RTRIM(C.C7_FORNECE) AS C7_FORNECE,
        RTRIM(C.C7_LOJA) AS C7_LOJA,
        RTRIM(C.C7_COND) AS C7_COND,
        RTRIM(A.A2_NOME) AS A2_NOME,
        RTRIM(A.A2_NREDUZ) AS A2_NREDUZ,
        COALESCE(RTRIM(E.E4_DESCRI), RTRIM(C.C7_COND)) AS CONDICAO_PAGAMENTO_DESC
    FROM SC7010 C
    LEFT JOIN SA2010 A ON RTRIM(C.C7_FORNECE) = RTRIM(A.A2_COD) AND RTRIM(C.C7_LOJA) = RTRIM(A.A2_LOJA) AND A.D_E_L_E_T_ = ' '
    LEFT JOIN SE4010 E ON RTRIM(C.C7_COND) = RTRIM(E.E4_CODIGO) AND E.D_E_L_E_T_ = ' '
    WHERE C.D_E_L_E_T_ = ' ' AND (RTRIM(C.C7_NUM) = %s OR RTRIM(C.C7_FORNECE) = %s OR RTRIM(C.C7_PRODUTO) = %s)
    """
    cursor.execute(sql, (pedido, pedido, produto or pedido))
    row = cursor.fetchone()
    conn.close()

    if row:
        forn_nome = (row.get('A2_NOME') or row.get('A2_NREDUZ') or '').strip()
        forn_cod = (row.get('C7_FORNECE') or '').strip()
        forn_full = f"{forn_cod} ({forn_nome})" if forn_nome else forn_cod

        return {
            'pedido_compra': row.get('C7_NUM') or '',
            'codigo_fornecedor': forn_full,
            'condicao_pagamento': row.get('CONDICAO_PAGAMENTO_DESC') or row.get('C7_COND') or ''
        }
    return None

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Nenhum comando fornecido"}))
        sys.exit(1)

    cmd = sys.argv[1]
    try:
        if cmd == 'list_filiais':
            res = list_filiais()
            print(json.dumps({"success": True, "data": res}))
        elif cmd == 'list_pedidos':
            filial = sys.argv[2] if len(sys.argv) > 2 and sys.argv[2] != 'null' else None
            res = list_pedidos(filial)
            print(json.dumps({"success": True, "data": res}))
        elif cmd == 'get_pedido_items' and len(sys.argv) > 2:
            c2_pedido = sys.argv[2]
            filial = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != 'null' else None
            res = get_pedido_items(c2_pedido, filial)
            print(json.dumps({"success": True, "data": res}))
        elif cmd == 'get_fornecedor' and len(sys.argv) > 2:
            pedido = sys.argv[2]
            produto = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] != 'null' else None
            res = get_fornecedor_compra(pedido, produto)
            print(json.dumps({"success": True, "data": res}))
        else:
            print(json.dumps({"error": "Comando invalido"}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
