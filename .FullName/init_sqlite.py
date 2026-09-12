import sqlite3
import os
import re

# Database and Schema Paths
SCHEMA_FILE = 'c:/Users/VINCENT/.gemini/antigravity/scratch/Hwange_Diocese_RMS_App/schema.sql'
SQLITE_FILE = 'c:/Users/VINCENT/.gemini/antigravity/scratch/Hwange_Diocese_RMS_App/database.sqlite'

def migrate_schema():
    if not os.path.exists(SCHEMA_FILE):
        print(f"Error: {SCHEMA_FILE} not found.")
        return

    # Delete existing SQLite file
    if os.path.exists(SQLITE_FILE):
        os.remove(SQLITE_FILE)

    # Read original MySQL schema
    with open(SCHEMA_FILE, 'r') as f:
        sql = f.read()

    # Basic Conversions for SQLite compatibility
    sql = re.sub(r'INT\s+AUTO_INCREMENT\s+PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'AUTO_INCREMENT', 'AUTOINCREMENT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'INT\s+PRIMARY KEY\s+AUTOINCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', sql, flags=re.IGNORECASE)
    sql = re.sub(r'ENUM\(.*?\)', 'TEXT', sql, flags=re.IGNORECASE) # SQLite uses TEXT
    sql = re.sub(r'ENGINE=.*', ';', sql, flags=re.IGNORECASE)
    sql = re.sub(r'USE\s+.*;', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'CREATE DATABASE\s+.*;', '', sql, flags=re.IGNORECASE)
    
    # Initialize SQLite
    conn = sqlite3.connect(SQLITE_FILE)
    cursor = conn.cursor()
    
    try:
        # Execute the cleaned SQL
        # Split by semicolon to execute one by one (simplified)
        for statement in sql.split(';'):
            if statement.strip():
                cursor.execute(statement)
        
        conn.commit()
        print(f"Success: {SQLITE_FILE} created and initialized.")
    except Exception as e:
        print(f"Error during migration: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    migrate_schema()
