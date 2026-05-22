/**
 * Hwange Diocese Records Management System
 * Offline-First Background Sync Engine
 */

class OfflineSyncEngine {
    constructor() {
        this.isSyncing = false;
        this.syncInterval = 30000; // Check every 30 seconds
        this.localFetchUrl = '/api/get_pending_sync.php';
        this.localMarkUrl = '/api/mark_synced.php';
        // In the PHP backend, MASTER_SYNC_URL is defined, but JS needs it. 
        // We will fetch it from the local API or inject it. 
        // For security, the local PHP endpoint should do the POST, or JS can do it if CORS allows.
        // It's safer to have JS do the POST so the user's browser (with internet) pushes it.
        
        // Wait, if the local server doesn't have internet, but the user's browser does? 
        // No, the local server IS the user's laptop. 
        // So JS can trigger a local PHP script that does the pushing, or JS can push directly.
        // Pushing directly from JS is fine, but we need the master URL.
        // Actually, let's just make a PHP script `api/push_to_master.php` that does the cURL request.
        // That avoids CORS issues entirely!
    }

    init() {
        console.log("☁️ Offline-First Sync Engine Initialized");
        
        // Listen for online events
        window.addEventListener('online', () => this.triggerSync());
        
        // Periodic check
        setInterval(() => {
            if (navigator.onLine) {
                this.triggerSync();
            }
        }, this.syncInterval);

        // Initial check
        if (navigator.onLine) {
            this.triggerSync();
        }
    }

    async triggerSync() {
        if (this.isSyncing) return;
        this.isSyncing = true;
        
        try {
            // 1. Fetch pending records from local SQLite
            let res = await fetch(this.localFetchUrl);
            let data = await res.json();
            
            if (data.status === 'success' && data.has_pending) {
                console.log("☁️ Found pending records to sync. Connecting to Master Server...");
                
                // 2. We will call a local PHP proxy to push to master to avoid CORS
                let pushRes = await fetch('/api/push_to_master.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data.payload)
                });
                
                let pushData = await pushRes.json();
                
                if (pushData.status === 'success') {
                    console.log("☁️ Sync Successful! Records merged to Master Database.");
                    this.updateUI(true);
                } else {
                    console.error("☁️ Master Server rejected sync:", pushData);
                    this.updateUI(false);
                }
            }
        } catch (e) {
            console.error("☁️ Sync Engine Error:", e);
        } finally {
            this.isSyncing = false;
        }
    }
    
    updateUI(success) {
        let indicator = document.getElementById('sync-indicator');
        if (indicator) {
            indicator.style.color = success ? '#10b981' : '#ef4444'; // Green if success, Red if fail
            indicator.title = success ? 'Cloud Synced' : 'Sync Failed';
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    window.SyncEngine = new OfflineSyncEngine();
    window.SyncEngine.init();
});
