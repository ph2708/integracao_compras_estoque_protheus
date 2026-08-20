import os, pymssql
conn = pymssql.connect(server='177.221.240.40', port=14333, user='ConsultaProtheus', password='C*n$ult@#M@q#', database='MP_12', charset='ISO-8859-1')
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
