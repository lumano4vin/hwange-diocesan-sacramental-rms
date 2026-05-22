import os
import mysql.connector

# Load environment variables manually
env_vars = {}
env_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), '.env')
if os.path.exists(env_path):
    with open(env_path, 'r') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if '=' in line:
                key, val = line.split('=', 1)
                env_vars[key.strip()] = val.strip().strip('"\'')

host = env_vars.get('DB_HOST')
port = env_vars.get('DB_PORT', '3306')
db = env_vars.get('DB_NAME', 'defaultdb')
user = env_vars.get('DB_USER')
password = env_vars.get('DB_PASSWORD')

print(f"Connecting to {host}:{port}/{db} as {user}...")

try:
    conn = mysql.connector.connect(
        host=host,
        port=int(port),
        user=user,
        password=password,
        database=db,
        ssl_disabled=True # Disable certificate check
    )
    cursor = conn.cursor()
    cursor.execute("SELECT user_id, username, role, full_name FROM users")
    users = cursor.fetchall()
    print("Users found in cloud MySQL:")
    for u in users:
        print(f"ID: {u[0]}, Username: {u[1]}, Role: {u[2]}, Name: {u[3]}")
    cursor.close()
    conn.close()
except Exception as e:
    print(f"Error checking MySQL users: {e}")
