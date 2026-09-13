<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cetak Label Stiker Thermal</title>
    <style>
        {!! file_get_contents(public_path('assets/css/print-barcode-labels.css')) !!}
    </style>
</head>
<body>

    <!-- ================= FLOATING CONTROL PANEL (STUDIO CETAK LABEL - SIDEBAR KANAN) ================= -->
    <div class="ctrl-panel-wrapper no-print">
        <div id="ctrlPanel" class="ctrl-panel">
            <div class="ctrl-header">
                <div class="ctrl-brand">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    <span>Studio Label</span>
                </div>
                <button type="button" class="ctrl-btn ctrl-btn-ghost" onclick="togglePanelCollapse()" title="Sembunyikan / Tampilkan Panel" style="padding: 3px 6px;">
                    <svg id="collapseIcon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>

            <div class="ctrl-body">
                <div class="ctrl-group">
                    <div class="ctrl-section-title">Preset Desain</div>
                    <select id="presetSelect" class="ctrl-select" title="Pilih Preset Desain" onchange="onPresetSelectChange(this.value)">
                        <option value="Standar">Preset Standar</option>
                    </select>
                    <div class="ctrl-row">
                        <button type="button" class="ctrl-btn" onclick="saveCurrentPreset()" title="Simpan tata letak saat ini ke preset aktif" style="flex: 1;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <span>Simpan</span>
                        </button>
                        <button type="button" class="ctrl-btn" onclick="createNewPreset()" title="Buat preset baru" style="flex: 1;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Baru</span>
                        </button>
                    </div>
                </div>

                <div class="ctrl-divider"></div>

                <div class="ctrl-group">
                    <div class="ctrl-section-title">Aksi Tata Letak</div>
                    <button type="button" class="ctrl-btn" onclick="applyToAllLabels()" title="Terapkan layout label yang dipilih ke seluruh label di halaman" style="width: 100%;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1"/><rect x="8" y="2" width="13" height="13" rx="2"/></svg>
                        <span>Terapkan ke Semua</span>
                    </button>

                    <label class="ctrl-switch-label" title="Jika aktif, setiap pergeseran atau perubahan pada satu label langsung menggerakkan semua label secara realtime">
                        <span>Sinkron Otomatis</span>
                        <input type="checkbox" id="liveSyncToggle" class="ctrl-switch-input" onchange="toggleLiveSync(this.checked)">
                        <span class="ctrl-switch-slider"></span>
                    </label>
                </div>

                <div class="ctrl-divider"></div>

                <div class="ctrl-group">
                    <button type="button" class="ctrl-btn ctrl-btn-primary" onclick="window.print()" title="Cetak label ke printer thermal" style="width: 100%;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        <span>Cetak Sekarang</span>
                    </button>

                    <button type="button" class="ctrl-btn ctrl-btn-ghost" onclick="resetToFactoryDefault()" title="Kembalikan semua tata letak ke setelan bawaan sistem" style="width: 100%;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <span>Reset ke Bawaan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification (Non-print) -->
        <div id="ctrlToast" class="ctrl-toast no-print">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span id="ctrlToastMsg"></span>
        </div>
    </div>

    <!-- ================= DAFTAR LABEL STIKER ================= -->
    @foreach ($labels as $label)
        @php
            $cleanText = strip_tags(str_replace('&nbsp;', ' ', $label['name']));
            $textLen = strlen($cleanText);
            $fontClass = match(true) {
                $textLen > 50 => 'product-title-xxs',
                $textLen > 38 => 'product-title-xs',
                $textLen > 25 => 'product-title-sm',
                default => '',
            };

            $serverLayout = $label['label_layout'] ?? null;
            $initialTitleStyle = '';
            if (!empty($serverLayout['title']['transform']) && $serverLayout['title']['transform'] !== 'none') {
                $initialTitleStyle .= 'transform:' . $serverLayout['title']['transform'] . ';';
            }
            if (!empty($serverLayout['title']['fontSize'])) {
                $initialTitleStyle .= 'font-size:' . $serverLayout['title']['fontSize'] . ';line-height:normal;';
            }
            if (!empty($serverLayout['title']['height'])) {
                $initialTitleStyle .= 'height:' . $serverLayout['title']['height'] . ';max-height:none;flex-shrink:0;';
            }

            $initialBarcodeStyle = '';
            if (!empty($serverLayout['barcode']['transform']) && $serverLayout['barcode']['transform'] !== 'none') {
                $initialBarcodeStyle .= 'transform:' . $serverLayout['barcode']['transform'] . ';';
            }
        @endphp
        <div class="sheet">
            <div class="label" data-product-code="{{ $label['code'] }}" data-server-layout="{{ json_encode($serverLayout) }}">
                
                <!-- GROUP 1: Product Title (Reserved space at top for pre-printed OSFIX logo on paper) -->
                <div class="group-1">
                    <div class="logo-spacer"></div>
                    <div class="v-text product-title draggable-title {{ empty($serverLayout['title']['fontSize']) ? $fontClass : '' }}" style="{{ $initialTitleStyle }}">
                        <div class="title-controls no-print" contenteditable="false">
                            <span class="btn-resize" onclick="resizeTitle(event, this, 1)" title="Perbesar Teks (Font +)">A+</span>
                            <span class="btn-resize" onclick="resizeTitle(event, this, -1)" title="Perkecil Teks (Font -)">A-</span>
                            <span class="font-size-indicator" contenteditable="true" title="Ketik angka & Enter" style="font-size:10px; margin:0 4px; line-height:1.8; font-family:monospace; color:#333; cursor:text; padding:0 2px; border:1px dashed transparent;">{{ !empty($serverLayout['title']['fontSize']) ? $serverLayout['title']['fontSize'] : '--px' }}</span>
                            <span class="btn-resize" onclick="resizeBox(event, this, 1)" title="Perpanjang Kotak (Tinggi +)">↕+</span>
                            <span class="btn-resize" onclick="resizeBox(event, this, -1)" title="Perpendek Kotak (Tinggi -)">↕-</span>
                        </div>
                        <span class="title-text">{!! $label['name'] !!}</span>
                    </div>
                </div>
                
                <!-- GROUP 2: Caution Note -->
                <div class="group-2">
                    <div class="v-text caution-text">Caution : Only Use by<br>Orthopaedic Surgeon</div>
                </div>
                
                <!-- GROUP 3: NIE, REF, LOT, QTY -->
                <div class="group-3">
                    @if (!empty($label['nie_number']) && $label['nie_number'] !== '-')
                        <div class="v-text nie-text"><span class="lbl">NIE</span> <span class="val">{{ trim(preg_replace('/AKD\s*/i', '', $label['nie_number'])) }}</span></div>
                    @endif
                    <div class="v-text ref-text"><span class="lbl">REF</span> <span class="val">{{ $label['code'] }}</span></div>
                    <div class="v-text lot-text"><span class="lbl">LOT</span> <span class="val editable-field" contenteditable="true" data-field-type="lot">{{ $label['lot'] ?? '012606110' }}</span></div>
                    <div class="v-text qty-text"><span class="lbl">QTY</span> <span class="val">{{ $label['quantity'] ?? 1 }}</span></div>
                </div>

                <!-- GROUP 4: Barcode & Code (Draggable) -->
                <div class="group-4 draggable-barcode" style="{{ $initialBarcodeStyle }}" title="Klik dan geser untuk memindahkan posisi barcode">
                    <div class="barcode-wrapper">
                        <div class="barcode-svg-container">
                            {!! $label['svg'] !!}
                        </div>
                    </div>
                    <div class="v-text barcode-num">{{ $label['code'] }}</div>
                </div>

                <!-- GROUP 4B: Material Info & Current Year Month -->
                <div class="group-mat">
                    <div class="v-text mat-text">
                        Mat 316L<br>
                        <span class="editable-field" contenteditable="true" data-field-type="mat">{{ $label['year_month'] ?? now()->format('Y m') }}</span>
                    </div>
                </div>

                <!-- GROUP 5: Medical Symbols, ISO Badges & Produksi Info (Leftmost) -->
                <div class="group-5">
                    @if (!empty($symbols))
                        <div class="symbols-block">
                            <img src="{{ $symbols }}" alt="Medical Symbols & ISO Badges">
                        </div>
                    @endif
                    
                    <div class="v-text produksi-block">
                        Produksi<br>
                        PT RISA Implantama<br>
                        Surabaya - Jawa Timur<br>
                        KEMENKES RI - AKD
                    </div>
                </div>
                
            </div>
        </div>
    @endforeach

    <!-- ================= LOGIKA JAVASCRIPT ================= -->
    <script>
        const PRESET_STORAGE_KEY = 'risa_label_studio_presets';
        const PRODUCT_PRESETS_KEY = 'risa_label_studio_product_presets';
        const ACTIVE_PRESET_KEY = 'risa_label_studio_active_preset';
        const LIVE_SYNC_KEY = 'risa_label_studio_live_sync';

        // Default live sync: false agar admin leluasa mengatur tiap produk secara independen
        let liveSyncEnabled = localStorage.getItem(LIVE_SYNC_KEY) === 'true'; 
        let lastInteractedLabel = null;
        let isDragging = false;
        let currentEl = null;
        let startX, startY;
        let initialX = 0, initialY = 0;

        // Default Global Presets
        const defaultPresets = {
            'Standar': {
                title: {
                    transform: 'matrix(1, 0, 0, 1, 0, 0)',
                    fontSize: '',
                    height: ''
                },
                barcode: {
                    transform: 'matrix(1, 0, 0, 1, 0, 0)'
                }
            }
        };

        function getStoredPresets() {
            try {
                const data = localStorage.getItem(PRESET_STORAGE_KEY);
                if (data) {
                    const parsed = JSON.parse(data);
                    if (parsed && typeof parsed === 'object') {
                        return Object.assign({}, defaultPresets, parsed);
                    }
                }
            } catch (e) {
                console.warn('Gagal membaca presets dari storage:', e);
            }
            return Object.assign({}, defaultPresets);
        }

        function saveStoredPresets(presets) {
            try {
                localStorage.setItem(PRESET_STORAGE_KEY, JSON.stringify(presets));
            } catch (e) {
                console.error('Gagal menyimpan presets ke storage:', e);
            }
        }

        // Penyimpanan Preset Khusus per Kode Produk (Per-SKU)
        function getStoredProductPresets() {
            try {
                const data = localStorage.getItem(PRODUCT_PRESETS_KEY);
                if (data) {
                    const parsed = JSON.parse(data);
                    if (parsed && typeof parsed === 'object') return parsed;
                }
            } catch (e) {
                console.warn('Gagal membaca product presets:', e);
            }
            return {};
        }

        function saveStoredProductPresets(presets) {
            try {
                localStorage.setItem(PRODUCT_PRESETS_KEY, JSON.stringify(presets));
            } catch (e) {
                console.error('Gagal menyimpan product presets:', e);
            }
        }

        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('ctrlToast');
            const msgEl = document.getElementById('ctrlToastMsg');
            if (!toast || !msgEl) return;

            msgEl.textContent = message;
            toast.className = 'ctrl-toast no-print show ' + (isSuccess ? 'success' : 'info');

            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }

        // Terapkan layout preset ke elemen target
        function applyLayoutToLabel(label, layout) {
            if (!label || !layout) return;

            const titleEl = label.querySelector('.draggable-title');
            if (titleEl && layout.title) {
                if (layout.title.transform) titleEl.style.transform = layout.title.transform;
                if (layout.title.fontSize) {
                    titleEl.style.fontSize = layout.title.fontSize;
                    titleEl.style.lineHeight = 'normal';
                    titleEl.classList.remove('product-title-sm', 'product-title-xs', 'product-title-xxs');
                    const ind = titleEl.querySelector('.font-size-indicator');
                    if (ind) ind.innerText = layout.title.fontSize;
                }
                if (layout.title.height) {
                    titleEl.style.maxHeight = 'none';
                    titleEl.style.flexShrink = '0';
                    titleEl.style.height = layout.title.height;
                }
            }

            const barcodeEl = label.querySelector('.draggable-barcode');
            if (barcodeEl && layout.barcode) {
                if (layout.barcode.transform) barcodeEl.style.transform = layout.barcode.transform;
            }
        }

        // Ambil layout terkini dari label
        function getLabelCurrentLayout(label) {
            if (!label) label = document.querySelector('.label');
            if (!label) return null;

            const titleEl = label.querySelector('.draggable-title');
            const barcodeEl = label.querySelector('.draggable-barcode');

            return {
                title: {
                    transform: titleEl ? titleEl.style.transform || 'none' : 'none',
                    fontSize: titleEl ? titleEl.style.fontSize || '' : '',
                    height: titleEl ? titleEl.style.height || '' : ''
                },
                barcode: {
                    transform: barcodeEl ? barcodeEl.style.transform || 'none' : 'none'
                }
            };
        }

        // Simpan preset: simpan konfigurasi masing-masing produk ke database MySQL via API & cache lokal
        window.saveCurrentPreset = function() {
            const productPresets = getStoredProductPresets();
            const uniqueCodes = new Set();
            const layoutsToSave = {};

            // 1. Rekam konfigurasi layout untuk setiap kode produk unik yang ada di halaman
            document.querySelectorAll('.label').forEach(label => {
                const code = label.getAttribute('data-product-code');
                if (code && !uniqueCodes.has(code)) {
                    uniqueCodes.add(code);
                    const layout = getLabelCurrentLayout(label);
                    if (layout) {
                        productPresets[code] = layout;
                        layoutsToSave[code] = layout;
                    }
                }
            });

            // Simpan ke cache browser lokal
            saveStoredProductPresets(productPresets);

            // 2. Simpan juga template global sebagai fallback
            const select = document.getElementById('presetSelect');
            const presetName = select ? select.value : 'Standar';
            const sourceLabel = lastInteractedLabel || document.querySelector('.label');
            const globalLayout = getLabelCurrentLayout(sourceLabel);
            if (globalLayout) {
                const presets = getStoredPresets();
                presets[presetName] = globalLayout;
                saveStoredPresets(presets);
                localStorage.setItem(ACTIVE_PRESET_KEY, presetName);
            }

            // 3. Kirim ke Database Server MySQL
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            if (csrfToken && Object.keys(layoutsToSave).length > 0) {
                showToast(`Menyimpan ${uniqueCodes.size} preset ke database...`, true);
                fetch('/admin/products/save-label-layout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        layouts: layoutsToSave
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        showToast(`Preset tersimpan di Database (${uniqueCodes.size} produk)`);
                    } else {
                        showToast(`Preset tersimpan di browser (${uniqueCodes.size} produk)`);
                    }
                })
                .catch(err => {
                    console.warn('Gagal menyimpan ke server, tersimpan di browser:', err);
                    showToast(`Preset tersimpan di browser (${uniqueCodes.size} produk)`);
                });
            } else {
                showToast(`Preset untuk ${uniqueCodes.size} produk berhasil disimpan`);
            }
        };

        // Buat preset baru
        window.createNewPreset = function() {
            const name = prompt('Masukkan nama preset baru:');
            if (!name || !name.trim()) return;

            const trimmedName = name.trim();
            const presets = getStoredPresets();

            const sourceLabel = lastInteractedLabel || document.querySelector('.label');
            const layout = getLabelCurrentLayout(sourceLabel) || defaultPresets['Standar'];

            presets[trimmedName] = layout;
            saveStoredPresets(presets);

            // Update select options
            refreshPresetSelect(trimmedName);
            showToast('Preset baru "' + trimmedName + '" berhasil dibuat & disimpan');
        };

        // Handler perubahan dropdown preset
        window.onPresetSelectChange = function(name) {
            const presets = getStoredPresets();
            const layout = presets[name];
            if (!layout) return;

            localStorage.setItem(ACTIVE_PRESET_KEY, name);
            document.querySelectorAll('.label').forEach(label => {
                applyLayoutToLabel(label, layout);
            });

            showToast('Preset "' + name + '" diterapkan ke semua label');
        };

        // Terapkan ke semua label (Canva-style)
        window.applyToAllLabels = function() {
            const sourceLabel = lastInteractedLabel || document.querySelector('.label');
            const layout = getLabelCurrentLayout(sourceLabel);
            if (!layout) return;

            document.querySelectorAll('.label').forEach(label => {
                applyLayoutToLabel(label, layout);
            });

            showToast('Tata letak diterapkan ke seluruh label');
        };

        // Toggle Live Sync
        window.toggleLiveSync = function(checked) {
            liveSyncEnabled = checked;
            localStorage.setItem(LIVE_SYNC_KEY, checked ? 'true' : 'false');

            if (checked) {
                window.applyToAllLabels();
                showToast('Sinkronisasi otomatis diaktifkan (semua produk serempak)');
            } else {
                showToast('Mode mandiri per produk diaktifkan', false);
            }
        };

        // Reset ke pengaturan bawaan pabrik
        window.resetToFactoryDefault = function() {
            if (!confirm('Kembalikan semua tata letak ke setelan bawaan sistem? Catatan: Preset per produk yang sudah disimpan juga akan direset.')) {
                return;
            }

            // Hapus preset per produk yang tersimpan
            localStorage.removeItem(PRODUCT_PRESETS_KEY);

            document.querySelectorAll('.draggable-title').forEach(el => {
                el.style.transform = '';
                el.style.fontSize = '';
                el.style.lineHeight = '';
                el.style.height = '';
                el.style.maxHeight = '';
                el.style.flexShrink = '';

                const ind = el.querySelector('.font-size-indicator');
                if (ind) {
                    const style = window.getComputedStyle(el);
                    ind.innerText = Math.round(parseFloat(style.fontSize)) + 'px';
                }
            });

            document.querySelectorAll('.draggable-barcode').forEach(el => {
                el.style.transform = '';
            });

            showToast('Semua tata letak dikembalikan ke setelan awal');
        };

        function refreshPresetSelect(selectedName) {
            const select = document.getElementById('presetSelect');
            if (!select) return;

            const presets = getStoredPresets();
            select.innerHTML = '';

            Object.keys(presets).forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (name === selectedName) opt.selected = true;
                select.appendChild(opt);
            });
        }

        // Terapkan preset ke seluruh halaman: memprioritaskan database server, lalu cache lokal per-SKU
        function applyPresetsToPage() {
            const productPresets = getStoredProductPresets();
            const savedActivePreset = localStorage.getItem(ACTIVE_PRESET_KEY) || 'Standar';
            const presets = getStoredPresets();
            const fallbackLayout = presets[savedActivePreset] || defaultPresets['Standar'];

            document.querySelectorAll('.label').forEach(label => {
                const code = label.getAttribute('data-product-code');
                const rawServer = label.getAttribute('data-server-layout');
                let serverLayout = null;
                try {
                    if (rawServer && rawServer !== 'null') serverLayout = JSON.parse(rawServer);
                } catch(e) {}

                // Prioritas 1: Layout dari Database Server (MySQL)
                if (serverLayout && (serverLayout.title || serverLayout.barcode)) {
                    applyLayoutToLabel(label, serverLayout);
                    // Sinkronkan juga ke cache lokal
                    if (code) productPresets[code] = serverLayout;
                } 
                // Prioritas 2: Layout dari cache lokal per-kode produk
                else if (code && productPresets[code]) {
                    applyLayoutToLabel(label, productPresets[code]);
                } 
                // Prioritas 3: Fallback template global
                else if (fallbackLayout) {
                    applyLayoutToLabel(label, fallbackLayout);
                }
            });

            saveStoredProductPresets(productPresets);
        }

        // ================= EVENT LISTENER INIT =================
        document.addEventListener('DOMContentLoaded', function() {
            // Setup Live Sync switch
            const syncToggle = document.getElementById('liveSyncToggle');
            if (syncToggle) {
                syncToggle.checked = liveSyncEnabled;
            }

            // Setup Presets dropdown
            const savedActivePreset = localStorage.getItem(ACTIVE_PRESET_KEY) || 'Standar';
            refreshPresetSelect(savedActivePreset);

            // Terapkan preset otomatis per produk
            applyPresetsToPage();

            // Inisialisasi indikator font awal untuk semua judul
            document.querySelectorAll('.draggable-title').forEach(title => {
                const indicator = title.querySelector('.font-size-indicator');
                if (indicator) {
                    const style = window.getComputedStyle(title);
                    indicator.innerText = Math.round(parseFloat(style.fontSize)) + 'px';
                }
            });

            // Setup Drag untuk Judul dan Barcode
            const setupDragListeners = (elements, selectorClass) => {
                elements.forEach(el => {
                    el.addEventListener('mousedown', function(e) {
                        if (e.target.classList.contains('btn-resize') || e.target.classList.contains('font-size-indicator')) {
                            return;
                        }

                        isDragging = true;
                        currentEl = this;
                        lastInteractedLabel = currentEl.closest('.label');
                        startX = e.clientX;
                        startY = e.clientY;

                        const style = window.getComputedStyle(currentEl);
                        const matrix = new DOMMatrixReadOnly(style.transform === 'none' ? 'matrix(1, 0, 0, 1, 0, 0)' : style.transform);
                        initialX = matrix.m41 || 0;
                        initialY = matrix.m42 || 0;

                        currentEl.style.zIndex = '1000';
                        currentEl.classList.add('dragging');
                        e.preventDefault();
                    });
                });
            };

            setupDragListeners(document.querySelectorAll('.draggable-title'), '.draggable-title');
            setupDragListeners(document.querySelectorAll('.draggable-barcode'), '.draggable-barcode');

            document.addEventListener('mousemove', function(e) {
                if (!isDragging || !currentEl) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                const newX = initialX + dx;
                const newY = initialY + dy;

                currentEl.style.transform = `translate(${newX}px, ${newY}px)`;
            });

            document.addEventListener('mouseup', function() {
                if (currentEl) {
                    currentEl.style.zIndex = 'auto';
                    currentEl.classList.remove('dragging');

                    const isTitle = currentEl.classList.contains('draggable-title');
                    const selector = isTitle ? '.draggable-title' : '.draggable-barcode';
                    const transform = currentEl.style.transform;

                    if (liveSyncEnabled) {
                        // Sinkronkan ke seluruh label
                        document.querySelectorAll(selector).forEach(el => {
                            if (el !== currentEl) el.style.transform = transform;
                        });
                    } else {
                        // Sinkronkan hanya ke lembar duplikat produk yang sama
                        const label = currentEl.closest('.label');
                        if (label) {
                            const code = label.getAttribute('data-product-code');
                            document.querySelectorAll(`.label[data-product-code="${code}"] ${selector}`).forEach(el => {
                                if (el !== currentEl) el.style.transform = transform;
                            });
                        }
                    }
                }
                isDragging = false;
                currentEl = null;
            });

            // Editable field sync
            const editables = document.querySelectorAll('.editable-field');
            editables.forEach(field => {
                field.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') e.preventDefault();
                });
                field.addEventListener('input', function(e) {
                    const label = this.closest('.label');
                    if (!label) return;
                    lastInteractedLabel = label;
                    const code = label.getAttribute('data-product-code');
                    const type = this.getAttribute('data-field-type');
                    const text = this.innerText;

                    // Sinkronisasi teks ke produk yang sama
                    document.querySelectorAll(`.label[data-product-code="${code}"] .editable-field[data-field-type="${type}"]`).forEach(el => {
                        if (el !== this) el.innerText = text;
                    });
                });
            });

            // Input font size manual
            const sizeIndicators = document.querySelectorAll('.font-size-indicator');
            sizeIndicators.forEach(ind => {
                ind.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                });

                ind.addEventListener('blur', function() {
                    let val = parseFloat(this.innerText);
                    const container = this.closest('.draggable-title');
                    if (!container) return;
                    const label = container.closest('.label');
                    lastInteractedLabel = label;

                    if (!isNaN(val) && val > 0) {
                        const fontSizeStr = val + 'px';
                        const updateEl = (el) => {
                            el.style.fontSize = fontSizeStr;
                            el.style.lineHeight = 'normal';
                            el.classList.remove('product-title-sm', 'product-title-xs', 'product-title-xxs');
                            const bind = el.querySelector('.font-size-indicator');
                            if (bind) bind.innerText = fontSizeStr;
                        };

                        updateEl(container);

                        if (liveSyncEnabled) {
                            document.querySelectorAll('.draggable-title').forEach(el => {
                                if (el !== container) updateEl(el);
                            });
                        } else if (label) {
                            const code = label.getAttribute('data-product-code');
                            document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                                if (el !== container) updateEl(el);
                            });
                        }
                    } else {
                        const style = window.getComputedStyle(container);
                        this.innerText = Math.round(parseFloat(style.fontSize)) + 'px';
                    }
                });
            });
        });

        // Resize Font (A+ / A-)
        window.resizeTitle = function(e, btn, direction) {
            e.stopPropagation();
            e.preventDefault();

            const container = btn.closest('.draggable-title');
            if (!container) return;
            const label = container.closest('.label');
            lastInteractedLabel = label;

            const style = window.getComputedStyle(container);
            let currentSize = parseFloat(style.fontSize);
            currentSize += (direction * 1);
            if (currentSize < 6) currentSize = 6;
            const sizeStr = currentSize + 'px';

            const updateEl = (el) => {
                el.style.fontSize = sizeStr;
                el.style.lineHeight = 'normal';
                el.classList.remove('product-title-sm', 'product-title-xs', 'product-title-xxs');
                const ind = el.querySelector('.font-size-indicator');
                if (ind) ind.innerText = sizeStr;
            };

            updateEl(container);

            if (liveSyncEnabled) {
                document.querySelectorAll('.draggable-title').forEach(el => {
                    if (el !== container) updateEl(el);
                });
            } else if (label) {
                const code = label.getAttribute('data-product-code');
                document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                    if (el !== container) updateEl(el);
                });
            }
        };

        // Resize Box Height (↕+ / ↕-)
        window.resizeBox = function(e, btn, direction) {
            e.stopPropagation();
            e.preventDefault();

            const container = btn.closest('.draggable-title');
            if (!container) return;
            const label = container.closest('.label');
            lastInteractedLabel = label;

            const style = window.getComputedStyle(container);
            container.style.maxHeight = 'none';
            container.style.flexShrink = '0';

            let currentHeight = parseFloat(style.height);
            if (isNaN(currentHeight) || currentHeight === 0) currentHeight = 113.38;
            currentHeight += (direction * 10);
            if (currentHeight < 20) currentHeight = 20;
            const heightStr = currentHeight + 'px';

            container.style.height = heightStr;

            if (liveSyncEnabled) {
                document.querySelectorAll('.draggable-title').forEach(el => {
                    if (el !== container) {
                        el.style.maxHeight = 'none';
                        el.style.flexShrink = '0';
                        el.style.height = heightStr;
                    }
                });
            } else if (label) {
                const code = label.getAttribute('data-product-code');
                document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                    if (el !== container) {
                        el.style.maxHeight = 'none';
                        el.style.flexShrink = '0';
                        el.style.height = heightStr;
                    }
                });
            }
        };

        // Toggle Minimize / Collapse Panel
        window.togglePanelCollapse = function() {
            const panel = document.getElementById('ctrlPanel');
            const icon = document.getElementById('collapseIcon');
            if (!panel) return;

            panel.classList.toggle('collapsed');
            const isCollapsed = panel.classList.contains('collapsed');
            if (icon) {
                icon.innerHTML = isCollapsed
                    ? '<polyline points="18 15 12 9 6 15"/>'
                    : '<polyline points="6 9 12 15 18 9"/>';
            }
        };
    </script>
</body>
</html>
