import sys
import pymssql
import os

host = os.getenv('DB_PROTHEUS_HOST', '177.221.240.40')
port = int(os.getenv('DB_PROTHEUS_PORT', 14333))
database = os.getenv('DB_PROTHEUS_DATABASE', 'MP_12')
user = os.getenv('DB_PROTHEUS_USERNAME', 'ConsultaProtheus')
password = os.getenv('DB_PROTHEUS_PASSWORD', 'C*n$ult@#M@q#')

conn = pymssql.connect(server=host, port=port, user=user, password=password, database=database, charset='ISO-8859-1')
cursor = conn.cursor(as_dict=True)

term = sys.argv[1] if len(sys.argv) > 1 else '006882'

print("=== SC2010 (Ordens de Producao) ===")
cursor.execute(f"SELECT C2_NUM, C2_ITEM, C2_SEQUEN, C2_PEDIDO, C2_CLI, C2_LOJA, C2_OBS, C2_PRODUTO FROM SC2010 WHERE D_E_L_E_T_ = ' ' AND (C2_PEDIDO LIKE '%{term}%' OR C2_NUM LIKE '%{term}%')")
sc2010_rows = cursor.fetchall()
for r in sc2010_rows:
    print(r)

print("\n=== SC5010 (Pedidos de Venda) ===")
cursor.execute(f"SELECT C5_NUM, C5_CLIENTE, C5_LOJACLI, C5_NOMECLI FROM SC5010 WHERE D_E_L_E_T_ = ' ' AND C5_NUM LIKE '%{term}%'")
sc5010_rows = cursor.fetchall()
for r in sc5010_rows:
    print(r)

print("\n=== SA1010 (Clientes) ===")
if sc5010_rows:
    cli_cod = sc5010_rows[0]['C5_CLIENTE']
    cli_loj = sc5010_rows[0]['C5_LOJACLI']
    cursor.execute(f"SELECT A1_COD, A1_LOJA, A1_NOME, A1_NREDUC FROM SA1010 WHERE D_E_L_E_T_ = ' ' AND A1_COD = '{cli_cod}' AND A1_LOJA = '{cli_loj}'")
    for r in cursor.fetchall():
        print(r)
elif sc2010_rows:
    cli_cod = sc2010_rows[0]['C2_CLI']
    cli_loj = sc2010_rows[0]['C2_LOJA']
    cursor.execute(f"SELECT A1_COD, A1_LOJA, A1_NOME, A1_NREDUC FROM SA1010 WHERE D_E_L_E_T_ = ' ' AND A1_COD = '{cli_cod}' AND A1_LOJA = '{cli_loj}'")
    for r in cursor.fetchall():
        print(r)

conn.close()
