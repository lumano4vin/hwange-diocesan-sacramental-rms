import sqlite3
import json
import urllib.request
import urllib.error
import os

db_file = 'database.sqlite'
master_sync_url = os.environ.get('MASTER_SYNC_URL', 'http://127.0.0.1:8001/api/sync.php')
api_key = os.environ.get('SYNC_API_KEY', 'hwange_sync_key_123')

def fetch_drafts(cursor, table):
    cursor.execute(f"SELECT * FROM {table} WHERE status = 'Draft'")
    columns = [col[0] for col in cursor.description]
    return [dict(zip(columns, row)) for row in cursor.fetchall()]

def sync():
    print("--- Synchronizing Canonical Data ---")
    print(f"Target Cloud Master: {master_sync_url}\n")
    
    conn = sqlite3.connect(db_file)
    cursor = conn.cursor()
    
    try:
        print("[SYNC] Gathering pending draft records...")
        payload = {
            'baptisms': fetch_drafts(cursor, 'baptisms'),
            'marriages': fetch_drafts(cursor, 'marriages'),
            'confirmations': fetch_drafts(cursor, 'confirmations'),
            'deaths': fetch_drafts(cursor, 'deaths')
        }
        
        total_records = sum(len(v) for v in payload.values())
        
        if total_records > 0:
            print(f"Found {total_records} records to push to the central vault.")
            
            data = json.dumps(payload).encode('utf-8')
            req = urllib.request.Request(master_sync_url, data=data)
            req.add_header('Content-Type', 'application/json')
            req.add_header('Authorization', f'Bearer {api_key}')
            
            try:
                response = urllib.request.urlopen(req)
                if response.getcode() in [200, 201]:
                    print("[SUCCESS] Records successfully pushed to cloud.")
                else:
                    print(f"[ERROR] Cloud server responded with code {response.getcode()}.")
            except urllib.error.URLError as e:
                print(f"[ERROR] Failed to reach cloud server: {e.reason}")
        else:
            print("[SYNC] No pending records to push. Local node is up to date.")
            
        print("\n[SUCCESS] Canonical synchronization cycle complete.")
        
    except Exception as e:
        print(f"\n[ERROR] Synchronization failed: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    sync()
