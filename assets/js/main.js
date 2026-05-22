/**
 * Hwange Diocese Records Management System
 * Main JavaScript Controller - Modernized with Universal Autocomplete
 * Optimized with robust readyState checks to prevent DOMContentLoaded execution race conditions.
 */

// Global helper functions (exposed on window object)
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('ion-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.name = 'eye-off-outline';
        btn.setAttribute('title', 'Hide Password');
    } else {
        input.type = 'password';
        icon.name = 'eye-outline';
        btn.setAttribute('title', 'Show Password');
    }
}

function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'information-circle-outline';
    if (type === 'success') icon = 'checkmark-circle-outline';
    if (type === 'error') icon = 'alert-circle-outline';

    toast.innerHTML = `
        <ion-icon name="${icon}"></ion-icon>
        <div class="toast-content">${message}</div>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

window.togglePasswordVisibility = togglePasswordVisibility;
window.showToast = showToast;

// Unified controller initialization
function initHwangeRMS() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const appLayout = document.getElementById('app-layout');

    // Load sidebar state from localStorage
    const savedState = localStorage.getItem('sidebar-collapsed');
    if (savedState === 'true' && appLayout) {
        appLayout.classList.add('collapsed');
    }

    // Toggle Sidebar
    if (sidebarToggle && appLayout) {
        const toggleIcon = sidebarToggle.querySelector('ion-icon');
        
        const updateToggleIcon = (isCollapsed) => {
            if (toggleIcon) {
                toggleIcon.name = isCollapsed ? 'chevron-forward-outline' : 'menu-outline';
                sidebarToggle.setAttribute('title', isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
            }
        };

        // Initial icon state
        updateToggleIcon(appLayout.classList.contains('collapsed'));

        sidebarToggle.addEventListener('click', () => {
            appLayout.classList.toggle('collapsed');
            const isCollapsed = appLayout.classList.contains('collapsed');
            
            // Update icon
            updateToggleIcon(isCollapsed);
            
            // Save state
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });
    }

    // --- Universal Autocomplete System ---
    const setupAutocomplete = (input, resultsBox, type = 'all') => {
        if (!input || !resultsBox) return;
        let timer = null;

        const performSearch = () => {
            const query = input.value.trim();
            if (query.length < 2) {
                resultsBox.classList.remove('show');
                return;
            }

            const isInSubfolder = window.location.pathname.includes('/dashboard/') || 
                                   window.location.pathname.includes('/sacraments/') || 
                                   window.location.pathname.includes('/admin/') || 
                                   window.location.pathname.includes('/parishioners/') || 
                                   window.location.pathname.includes('/profile/') || 
                                   window.location.pathname.includes('/auth/');
            
            const actionPath = isInSubfolder ? '../actions/search_suggestions.php' : 'actions/search_suggestions.php';

            fetch(`${actionPath}?q=${encodeURIComponent(query)}&type=${type}`)
                .then(r => r.json())
                .then(data => {
                    resultsBox.innerHTML = '';
                    if (data.length === 0) {
                        resultsBox.innerHTML = '<div style="padding: 1rem; color: var(--text-muted); font-size: 0.8rem;">No suggestions found.</div>';
                    } else {
                        data.forEach(res => {
                            const item = document.createElement('a');
                            item.href = res.url;
                            item.className = 'search-result-item';
                            item.style.textDecoration = 'none';
                            item.innerHTML = `
                                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.03);">
                                    <ion-icon name="${res.icon}" style="font-size: 1.2rem; color: var(--accent);"></ion-icon>
                                    <div style="flex: 1;">
                                        <span style="display: block; font-weight: 700; color: white; font-size: 0.9rem;">${res.title}</span>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                            <span style="background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; font-size: 0.6rem; color: var(--accent);">${res.category}</span>
                                            <span>${res.sub}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            resultsBox.appendChild(item);
                        });
                    }
                    resultsBox.classList.add('show');
                });
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(performSearch, 300);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') resultsBox.classList.remove('show');
            if (e.key === 'Enter' && resultsBox.classList.contains('show')) {
                const firstResult = resultsBox.querySelector('.search-result-item');
                if (firstResult) {
                    e.preventDefault();
                    window.location.href = firstResult.href;
                }
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!resultsBox.contains(e.target) && e.target !== input) {
                resultsBox.classList.remove('show');
            }
        });
    };

    // Initialize Global Search
    const globalSearchInput = document.getElementById('global-search-query');
    const globalSearchResults = document.getElementById('global-search-results');
    if (globalSearchInput && globalSearchResults) {
        setupAutocomplete(globalSearchInput, globalSearchResults, 'all');
        
        // Shortcut Alt+S
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                globalSearchInput.focus();
            }
        });
    }

    // Initialize Page-Level Search Inputs
    const pageSearchInputs = document.querySelectorAll('input[name="search"]');
    pageSearchInputs.forEach(input => {
        const resultsBox = document.createElement('div');
        resultsBox.className = 'global-results-popup';
        resultsBox.style.width = '100%';
        resultsBox.style.top = '100%';
        resultsBox.style.left = '0';
        resultsBox.style.marginTop = '8px';
        
        const container = input.closest('div[style*="position: relative"]') || input.parentElement;
        if (container) {
            if (container.style.position !== 'absolute') container.style.position = 'relative';
            container.appendChild(resultsBox);
        }
        
        let searchType = 'all';
        if (window.location.pathname.includes('parishioners')) searchType = 'person';
        if (window.location.pathname.includes('baptism')) searchType = 'baptism';
        if (window.location.pathname.includes('marriage')) searchType = 'marriage';
        
        setupAutocomplete(input, resultsBox, searchType);
    });

    // Generic Search Submission Handler (Fix for unresponsive buttons)
    document.addEventListener('click', (e) => {
        // 1. Icon Clicks (magnifying glass)
        const icon = e.target.closest('ion-icon[name="search-outline"]');
        if (icon) {
            const form = icon.closest('form');
            if (form) {
                e.preventDefault();
                form.submit();
            } else {
                const input = icon.parentElement.querySelector('input');
                if (input) input.focus();
            }
        }
        
        // 2. Explicit Search Button Clicks
        const btn = e.target.closest('.btn-primary, .btn-secondary');
        if (btn) {
            const text = btn.innerText.trim().toLowerCase();
            const hasSearchIcon = btn.querySelector('ion-icon[name="search-outline"]');
            if (text === 'search' || hasSearchIcon) {
                const form = btn.closest('form');
                if (form) {
                    e.preventDefault();
                    form.submit();
                }
            }
        }
    });

    // --- Dropdown Management (Consolidated) ---
    const dropdowns = [
        { btn: 'new-entry-btn', menu: 'new-entry-dropdown' },
        { btn: 'profile-toggle', menu: 'profile-dropdown' },
        { btn: 'bell-toggle', menu: 'notification-dropdown' }
    ];

    dropdowns.forEach(dd => {
        const btn = document.getElementById(dd.btn);
        const menu = document.getElementById(dd.menu);

        if (btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close all other dropdowns
                dropdowns.forEach(other => {
                    if (other.menu !== dd.menu) {
                        const otherMenu = document.getElementById(other.menu);
                        if (otherMenu) otherMenu.classList.remove('show');
                    }
                });
                menu.classList.toggle('show');
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!menu.contains(e.target) && !btn.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });

    // Global Escape Key Listener
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Close all search results
            document.querySelectorAll('.global-results-popup, .global-search-results').forEach(el => {
                el.classList.remove('show');
            });
            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu, .show, .search-select-results').forEach(el => {
                el.classList.remove('show');
            });
            // Close info modal if present
            const infoModal = document.getElementById('infoModal');
            if (infoModal) infoModal.classList.remove('active');
            
            // Close GMC
            const gmcOverlay = document.getElementById('gmc-overlay');
            if (gmcOverlay) gmcOverlay.classList.remove('active');
        }
    });

    // Auto-hide error messages after 5 seconds
    const errorMsg = document.getElementById('login-error') || document.getElementById('error-msg');
    if (errorMsg) {
        setTimeout(() => {
            errorMsg.style.display = 'none';
        }, 5000);
    }

    // --- Searchable Select Component Logic ---
    const initSearchableSelects = () => {
        const searchableInputs = document.querySelectorAll('.searchable-input');
        
        searchableInputs.forEach(input => {
            if (input.dataset.initialized) return;
            input.dataset.initialized = 'true';

            const container = input.closest('.searchable-select-container');
            const resultsDiv = container.querySelector('.search-select-results');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            let options = [];
            try {
                options = JSON.parse(input.dataset.options || '[]');
            } catch (e) {
                console.error('SearchableSelect: Failed to parse options JSON', e);
            }
            let selectedIndex = -1;

            const showResults = (filter = '') => {
                const filtered = options.filter(opt => 
                    opt.text.toLowerCase().includes(filter.toLowerCase())
                );
                
                if (filtered.length > 0) {
                    resultsDiv.innerHTML = filtered.map((opt, i) => `
                        <div class="search-select-item" data-value="${opt.value}" data-text="${opt.text.replace(/"/g, '&quot;')}">
                            ${opt.text}
                        </div>
                    `).join('');
                    resultsDiv.classList.add('show');
                } else {
                    resultsDiv.classList.remove('show');
                }
                selectedIndex = -1;
            };

            input.addEventListener('input', (e) => {
                showResults(e.target.value);
                if (e.target.value === '') hiddenInput.value = '';
            });

            input.addEventListener('focus', () => {
                showResults(''); 
            });

            input.addEventListener('keydown', (e) => {
                const items = resultsDiv.querySelectorAll('.search-select-item');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(-1, selectedIndex - 1);
                    updateSelection(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        selectItem(items[selectedIndex]);
                    } else if (items.length > 0) {
                        selectItem(items[0]);
                    }
                }
            });

            const updateSelection = (items) => {
                items.forEach((item, i) => {
                    item.classList.toggle('selected', i === selectedIndex);
                    if (i === selectedIndex) item.scrollIntoView({ block: 'nearest' });
                });
            };

            const selectItem = (item) => {
                input.value = item.dataset.text;
                hiddenInput.value = item.dataset.value;
                resultsDiv.classList.remove('show');
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            };

            resultsDiv.addEventListener('click', (e) => {
                const item = e.target.closest('.search-select-item');
                if (item) selectItem(item);
            });

            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) {
                    resultsDiv.classList.remove('show');
                }
            });
        });
    };

    initSearchableSelects();
    window.initSearchableSelects = initSearchableSelects;

    // --- Global Mission Command (GMC) Controller ---
    const overlay = document.getElementById('gmc-overlay');
    const gmcInput = document.getElementById('gmc-input');
    const gmcResults = document.getElementById('gmc-results');
    let gmcSelectedIndex = -1;

    if (overlay && gmcInput) {
        const toggleGMC = (show) => {
            if (show) {
                overlay.classList.add('active');
                gmcInput.focus();
                gmcInput.value = '';
                renderDefaultCommands();
            } else {
                overlay.classList.remove('active');
            }
        };

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === '/')) {
                e.preventDefault();
                toggleGMC(true);
            }
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) toggleGMC(false);
        });

        const defaultCommands = [
            { title: 'New Baptism Record', meta: 'Registry Entry', icon: 'water-outline', url: 'sacraments/baptism_add.php' },
            { title: 'New Marriage Record', meta: 'Registry Entry', icon: 'heart-outline', url: 'sacraments/marriage_add.php' },
            { title: 'View Diocesan Reports', meta: 'Analytics', icon: 'bar-chart-outline', url: 'dashboard/reports.php' },
            { title: 'Archival Audit Logs', meta: 'System Security', icon: 'list-outline', url: 'dashboard/audit_logs.php' },
            { title: 'Manage Parish Missions', meta: 'Administration', icon: 'admin/parishes.php' }
        ];

        const renderResults = (items) => {
            gmcResults.innerHTML = '';
            if (items.length === 0) {
                gmcResults.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-muted);">No archives found matching your query.</div>';
                return;
            }

            items.forEach((item, index) => {
                const div = document.createElement('a');
                
                let finalUrl = item.url;
                const pathParts = window.location.pathname.split('/');
                const isInSubdir = pathParts.includes('dashboard') || pathParts.includes('sacraments') || pathParts.includes('admin') || pathParts.includes('parishioners');
                
                if (isInSubdir && !finalUrl.startsWith('http') && !finalUrl.startsWith('../')) {
                    finalUrl = '../' + finalUrl;
                }
                
                div.href = finalUrl;
                div.className = 'gmc-result-item';
                div.classList.toggle('selected', index === gmcSelectedIndex);
                
                div.innerHTML = `
                    <div class="gmc-result-icon"><ion-icon name="${item.icon || 'document-outline'}"></ion-icon></div>
                    <div class="gmc-result-info">
                        <span class="gmc-result-title">${item.title}</span>
                        <span class="gmc-result-meta">${item.meta}</span>
                    </div>
                `;
                gmcResults.appendChild(div);
            });
        };

        const renderDefaultCommands = () => renderResults(defaultCommands);

        let searchTimeout;
        gmcInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const q = gmcInput.value.trim();
            if (q.length < 2) {
                renderDefaultCommands();
                return;
            }

            searchTimeout = setTimeout(() => {
                const base = window.location.pathname.includes('/dashboard/') || window.location.pathname.includes('/sacraments/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/parishioners/') ? '../' : '';
                fetch(`${base}actions/global_search.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        const formatted = data.map(d => ({
                            title: d.title,
                            meta: d.meta,
                            icon: d.type === 'person' ? 'person-outline' : (d.type === 'baptism' ? 'water-outline' : 'heart-outline'),
                            url: d.url
                        }));
                        renderResults(formatted);
                    });
            }, 200);
        });

        gmcInput.addEventListener('keydown', (e) => {
            const items = gmcResults.querySelectorAll('.gmc-result-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                gmcSelectedIndex = Math.min(gmcSelectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                gmcSelectedIndex = Math.max(0, gmcSelectedIndex - 1);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                if (gmcSelectedIndex >= 0 && items[gmcSelectedIndex]) items[gmcSelectedIndex].click();
            }
        });

        const updateSelection = (items) => {
            items.forEach((item, i) => {
                item.classList.toggle('selected', i === gmcSelectedIndex);
                if (i === gmcSelectedIndex) item.scrollIntoView({ block: 'nearest' });
            });
        };
    }
}

// Robust conditional execution check
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHwangeRMS);
} else {
    initHwangeRMS();
}
