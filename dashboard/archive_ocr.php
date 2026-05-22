<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Historical Digital Archive & OCR Helper
 * 
 * This tool enables archivists to digitize old physical registers
 * using side-by-side transcription and OCR assistance.
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protect the page - Archivist level
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Archive Helper - Hwange Diocese RMS</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.1">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Inter:wght@400;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
    <style>
        .archive-layout { display: grid; grid-template-columns: 1fr 1fr; height: calc(100vh - 120px); gap: 1rem; padding: 1rem; }
        
        .viewer-panel { 
            background: #020617; 
            border-radius: 20px; 
            position: relative; 
            overflow: hidden; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: inset 0 0 50px rgba(0,0,0,0.5);
            cursor: grab;
        }
        .viewer-panel:active { cursor: grabbing; }
        
        .entry-panel { background: rgba(30, 41, 59, 0.4); border-radius: 20px; padding: 2rem; overflow-y: auto; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px); }
        
        .upload-overlay { text-align: center; color: var(--text-muted); cursor: pointer; padding: 40px; transition: all 0.3s ease; }
        .upload-overlay:hover { color: var(--accent); transform: scale(1.05); }
        .upload-overlay ion-icon { font-size: 4rem; margin-bottom: 1.5rem; filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.3)); }
        
        #ocr-image { 
            max-width: none; 
            max-height: none; 
            display: none; 
            transition: transform 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transform-origin: center center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        
        .ocr-progress { position: absolute; bottom: 0; left: 0; height: 6px; background: linear-gradient(90deg, var(--accent) 0%, #fbbf24 100%); transition: width 0.3s; z-index: 100; border-radius: 0 3px 3px 0; }
        
        .viewer-controls { 
            position: absolute; 
            bottom: 20px; 
            left: 50%; 
            transform: translateX(-50%); 
            display: flex; 
            gap: 10px; 
            background: rgba(15, 23, 42, 0.8); 
            padding: 8px; 
            border-radius: 12px; 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 10;
        }
        .control-btn { width: 40px; height: 40px; border-radius: 8px; border: none; background: rgba(255,255,255,0.05); color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
        .control-btn:hover { background: var(--accent); color: #000; }
        
        .suggestion-chip { 
            display: inline-block; 
            padding: 6px 12px; 
            background: rgba(56, 189, 248, 0.1); 
            border: 1px solid rgba(56, 189, 248, 0.3); 
            border-radius: 8px; 
            font-size: 0.8rem; 
            color: var(--accent); 
            cursor: pointer; 
            margin: 4px; 
            transition: all 0.2s;
        }
        .suggestion-chip:hover { background: var(--accent); color: #000; }
        
        .rotating { animation: rotate 2s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-layout" id="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php 
            $header_title = "Archives OCR Helper";
            $header_subtitle = "Digitizing the history of Hwange Diocese through AI-assisted transcription.";
            $additional_header_actions = '
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="runOCR()" id="ocr-btn" disabled style="background: var(--accent); color: #000; font-weight: 800; border: none;">
                        <ion-icon name="sparkles-outline"></ion-icon> Run OCR Assistant
                    </button>
                </div>';
            include '../includes/header.php'; 
            ?>

            <div class="archive-layout">
                <!-- Archive Image Panel -->
                <div class="viewer-panel" id="viewer" onwheel="handleZoom(event)" onmousedown="startDrag(event)" onmousemove="doDrag(event)" onmouseup="stopDrag(event)" onmouseleave="stopDrag(event)">
                    <div class="helper-badge">SCRIPTORIUM LIGHTBOX</div>
                    <div class="ocr-progress" id="ocr-progress-bar" style="width: 0%;"></div>
                    
                    <div class="upload-overlay" id="upload-prompt" onclick="document.getElementById('file-upload').click()">
                        <ion-icon name="cloud-upload-outline"></ion-icon>
                        <p style="font-weight: 700; font-size: 1.2rem; margin-bottom: 8px;">Upload Historical Scan</p>
                        <small style="opacity: 0.6;">Recommended: High-Contrast 300DPI Grayscale</small>
                    </div>
                    
                    <input type="file" id="file-upload" style="display: none;" accept="image/*" onchange="previewImage(event)">
                    <img id="ocr-image" src="" alt="Historical Register" draggable="false">

                    <div class="viewer-controls" id="viewer-controls" style="display: none;">
                        <button class="control-btn" onclick="zoomIn()"><ion-icon name="add-outline"></ion-icon></button>
                        <button class="control-btn" onclick="zoomOut()"><ion-icon name="remove-outline"></ion-icon></button>
                        <button class="control-btn" onclick="resetZoom()"><ion-icon name="refresh-outline"></ion-icon></button>
                    </div>
                </div>

                <!-- Transcription Panel -->
                <div class="entry-panel">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
                        <div>
                            <h3 style="font-family: 'Outfit', sans-serif; margin: 0; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="create-outline" style="color: var(--accent);"></ion-icon>
                                Digital Transcription
                            </h3>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Convert sacred ink to canonical data.</p>
                        </div>
                        <span id="ocr-status" style="font-size: 0.65rem; padding: 4px 10px; border-radius: 6px; background: rgba(255,255,255,0.05); color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Idle</span>
                    </div>

                    <!-- AI SUGGESTIONS AREA -->
                    <div id="ai-suggestions-box" style="margin-bottom: 1.5rem; display: none;">
                        <label style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 800; display: block; margin-bottom: 10px;">AI Scanned Suggestions:</label>
                        <div id="suggestions-container" style="display: flex; flex-wrap: wrap;"></div>
                    </div>
                    
                    <form id="transcription-form">
                        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Historical Sacrament Type</label>
                                <select name="sacrament_type" class="input-field" style="width: 100%; border-color: var(--accent);" required>
                                    <option value="baptism">Baptism Register</option>
                                    <option value="marriage" disabled>Marriage Register (Coming Soon)</option>
                                    <option value="confirmation" disabled>Confirmation Register (Coming Soon)</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Parish / Mission of Origin</label>
                                <select name="parish_id" id="t-parish" class="input-field" style="width: 100%;" required>
                                    <option value="">Select Parish...</option>
                                    <?php 
                                    $parishes = db_fetchAll("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name");
                                    foreach($parishes as $p) echo "<option value='{$p['parish_id']}'>".h($p['parish_name'])."</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Baptismal Name(s)</label>
                                <input type="text" name="first_name" id="t-name" class="input-field" placeholder="Extracted Name" required>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Date of Baptism</label>
                                <input type="date" name="baptism_date" id="t-date" class="input-field" required>
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Father's Full Name</label>
                                <input type="text" name="father_name" id="t-father" class="input-field" placeholder="...">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Mother's Full Name</label>
                                <input type="text" name="mother_name" id="t-mother" class="input-field" placeholder="...">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Godparents / Witnesses</label>
                                <input type="text" name="godparents" id="t-godparents" class="input-field" placeholder="...">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; display: block;">Minister / Priest</label>
                                <input type="text" name="minister" id="t-minister" class="input-field" placeholder="...">
                            </div>
                        </div>

                        <div style="margin-top: 2.5rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(56, 189, 248, 0.05) 0%, rgba(15, 23, 42, 0.8) 100%); border-radius: 15px; border: 1px solid rgba(56, 189, 248, 0.2);">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <ion-icon name="shield-checkmark-outline" style="color: var(--accent); font-size: 1.2rem;"></ion-icon>
                                <h4 style="color: white; font-size: 0.9rem; margin: 0;">Canonical Integrity Check</h4>
                            </div>
                            <p style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.6;">
                                By committing this record, you affirm that the transcription accurately represents the historical register. AI suggestions are for assistance only.
                            </p>
                        </div>

                        <button type="submit" class="btn btn-primary" id="save-btn" style="width: 100%; margin-top: 2rem; padding: 1.25rem; background: var(--accent); color: #000; font-weight: 800; border: none; box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);">
                            Commit to Canonical Archive
                        </button>
                    </form>
                    <button class="btn btn-secondary" style="width: 100%; margin-top: 0.75rem; background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1);" onclick="location.reload()">Clear All & Reset</button>
                </div>
            </div>
            
            <?php include '../includes/privacy_footer.php'; ?>

        </main>
    </div>

    <script>
        let scale = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;
        const img = document.getElementById('ocr-image');

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                img.src = reader.result;
                img.style.display = 'block';
                document.getElementById('upload-prompt').style.display = 'none';
                document.getElementById('ocr-btn').disabled = false;
                document.getElementById('viewer-controls').style.display = 'flex';
                resetZoom();
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // --- Lightbox Zoom & Pan Logic ---
        function handleZoom(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            scale = Math.min(Math.max(.125, scale * delta), 4);
            updateTransform();
        }

        function zoomIn() { scale *= 1.2; updateTransform(); }
        function zoomOut() { scale /= 1.2; updateTransform(); }
        function resetZoom() { scale = 1; translateX = 0; translateY = 0; updateTransform(); }

        function updateTransform() {
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        }

        function startDrag(e) {
            if (e.button !== 0) return;
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        }

        function doDrag(e) {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateTransform();
        }

        function stopDrag() { isDragging = false; }

        // --- OCR Logic ---
        async function runOCR() {
            const btn = document.getElementById('ocr-btn');
            const progress = document.getElementById('ocr-progress-bar');
            const status = document.getElementById('ocr-status');
            
            btn.innerHTML = '<ion-icon name="sync-outline" class="rotating"></ion-icon> Extracting Text...';
            btn.disabled = true;
            status.innerText = 'Analyzing...';
            status.style.color = '#fbbf24';

            try {
                const worker = await Tesseract.createWorker({
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            progress.style.width = (m.progress * 100) + '%';
                        }
                    }
                });
                
                await worker.loadLanguage('eng');
                await worker.initialize('eng');
                const { data: { text } } = await worker.recognize(img.src);
                await worker.terminate();

                processOCRResults(text);
                
                btn.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> Scan Complete';
                status.innerText = 'Completed';
                status.style.color = '#10b981';
                setTimeout(() => {
                    progress.style.width = '0%';
                }, 1000);
            } catch (err) {
                console.error(err);
                btn.innerHTML = '<ion-icon name="alert-circle-outline"></ion-icon> OCR Failed';
                status.innerText = 'Error';
                status.style.color = '#ef4444';
            }
        }

        function processOCRResults(text) {
            const container = document.getElementById('suggestions-container');
            const box = document.getElementById('ai-suggestions-box');
            container.innerHTML = '';
            box.style.display = 'block';

            // Clean text and extract potential names/words
            const words = text.split(/[\s\n,]+/).filter(w => w.length > 3);
            const uniqueWords = [...new Set(words)];

            uniqueWords.forEach(word => {
                const chip = document.createElement('span');
                chip.className = 'suggestion-chip';
                chip.innerText = word;
                chip.onclick = () => fillActiveField(word);
                container.appendChild(chip);
            });
        }

        let activeInput = document.getElementById('t-name');
        document.querySelectorAll('.input-field').forEach(input => {
            input.onfocus = () => activeInput = input;
        });

        function fillActiveField(val) {
            if (activeInput) {
                activeInput.value = val;
                activeInput.focus();
            }
        }

        // --- Save Logic ---
        document.getElementById('transcription-form').onsubmit = async function(e) {
            e.preventDefault();
            const btn = document.getElementById('save-btn');
            const formData = new FormData(this);
            
            btn.innerHTML = '<ion-icon name="sync-outline" class="rotating"></ion-icon> Committing...';
            btn.disabled = true;

            try {
                const response = await fetch('../actions/save_archived_record.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Record committed to Canonical Archive successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast(result.error || 'Failed to save record.', 'error');
                    btn.innerHTML = 'Commit to Canonical Archive';
                    btn.disabled = false;
                }
            } catch (err) {
                showToast('Network error during archival commit.', 'error');
                btn.innerHTML = 'Commit to Canonical Archive';
                btn.disabled = false;
            }
        };
    </script>
    <script src="../assets/js/main.js?v=1.6.2"></script>
</body>
</html>
