import { ref } from 'vue';

export function useOffline() {
    const syncedAt = ref(null);
    const pendingDrafts = ref([]);

    // Check if running in browser
    if (typeof window === 'undefined') {
        return { syncedAt, pendingDrafts };
    }

    // Listen for online/offline events
    const isOnline = ref(navigator.onLine);
    
    const updateOnlineStatus = (event) => {
        isOnline.value = event.target.online;
    };

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    // Save draft to IndexedDB
    const saveDraft = (draft) => {
        pendingDrafts.value = [...pendingDrafts.value, { ...draft, savedAt: new Date() }];
        
        // Store in IndexedDB
        const request = indexedDB.open('KebunTebuDB', 1);
        
        request.onsuccess = (event) => {
            const db = event.target.result;
            const transaction = db.transaction(['drafts'], 'readwrite');
            const store = transaction.objectStore('drafts');
            store.add({ ...draft, savedAt: new Date().toISOString(), id: Date.now() });
            
            transaction.oncomplete = () => {
                console.log('Draft saved to IndexedDB');
            };
        };

        request.onerror = (event) => {
            console.error('IndexedDB error:', event.target.error);
        };
    };

    // Sync drafts when coming back online
    const syncDrafts = async () => {
        if (!isOnline.value) return;

        if (pendingDrafts.value.length === 0) return;

        isOnline.value = false; // Set to syncing state
        
        const drafts = [...pendingDrafts.value];
        pendingDrafts.value = [];

        for (const draft of drafts) {
            try {
                // Post to the sync endpoint
                const response = await fetch('/reports/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ drafts }),
                });

                if (response.ok) {
                    console.log('Draft synced successfully');
                } else {
                    // Re-queue the draft if sync failed
                    pendingDrafts.value = [...pendingDrafts.value, draft];
                }
            } catch (error) {
                // Re-queue the draft if request failed
                pendingDrafts.value = [...pendingDrafts.value, draft];
            }
        }

        isOnline.value = true;
        syncedAt.value = new Date();
    };

    // Initialize on mount - check for pending drafts
    if (isOnline.value) {
        // Check IndexedDB for pending drafts
        const request = indexedDB.open('KebunTebuDB', 1);
        
        request.onsuccess = (event) => {
            const db = event.target.result;
            
            if (db.objectStoreNames.contains('drafts')) {
                const transaction = db.transaction('drafts', 'readonly');
                const store = transaction.objectStore('drafts');
                const allRequests = store.getAll();
                
                allRequests.onsuccess = (event) => {
                    pendingDrafts.value = event.target.result.map(draft => ({
                        ...draft,
                        savedAt: new Date(draft.savedAt),
                    }));
                };
            }
        };
    }

    return { syncedAt, pendingDrafts, isOnline, saveDraft, syncDrafts };
}