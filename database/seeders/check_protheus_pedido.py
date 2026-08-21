import os
import pymssql

host = os.getenv('DB_PROTHEUS_HOST', '177.221.240.40')
port = int(os.getenv('DB_PROTHEUS_PORT', 14333))
database = os.getenv('DB_PROTHEUS_DATABASE', 'MP_12')
user = os.getenv('DB_PROTHEUS_USERNAME', 'ConsultaProtheus')
password = os.getenv('DB_PROTHEUS_PASSWORD', '')

conn = pymssql.connect(server=host, port=port, user=user, password=password, database=database, charset='ISO-8859-1')
cursor = conn.cursor(as_dict=True)
cursor.execute("SELECT TOP 1 * FROM SC2010 WHERE D_E_L_E_T_ = ' '")
row = cursor.fetchone()
print("Colunas da SC2010:")
print(list(row.keys()))

cursor.execute("SELECT C2_NUM, C2_PEDIDO, C2_OBS FROM SC2010 WHERE D_E_L_E_T_ = ' ' AND (C2_PEDIDO LIKE '%006882%' OR C2_NUM LIKE '%018699%')")
print("\nRegistros para 006882:")
for r in cursor.fetchall():
    print(r)
conn.close()
