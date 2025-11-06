import mysql.connector
from mysql.connector import Error

class DatabaseConfig:
    MYSQL_HOST = 'localhost'
    MYSQL_USER = 'root'
    MYSQL_PASSWORD = '7470'  # Tu contraseña de MySQL
    MYSQL_DB = 'biblioteca_itca'
    MYSQL_PORT = 3306

def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host=DatabaseConfig.MYSQL_HOST,
            user=DatabaseConfig.MYSQL_USER,
            password=DatabaseConfig.MYSQL_PASSWORD,
            database=DatabaseConfig.MYSQL_DB,
            port=DatabaseConfig.MYSQL_PORT
        )
        return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None