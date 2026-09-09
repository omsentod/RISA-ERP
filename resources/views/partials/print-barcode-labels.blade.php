<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Label Stiker Thermal</title>
    <style>
        {!! file_get_contents(public_path('assets/css/print-barcode-labels.css')) !!}
    </style>
</head>
<body>
    @foreach ($labels as $label)
        <div class="sheet">
            <div class="label" data-product-code="{{ $label['code'] }}">
                
                <!-- GROUP 1: Product Title (Reserved space at top for pre-printed OSFIX logo on paper) -->
                @php
                    $cleanText = strip_tags(str_replace('&nbsp;', ' ', $label['name']));
                    $textLen = strlen($cleanText);
                    $fontClass = match(true) {
                        $textLen > 50 => 'product-title-xxs',
                        $textLen > 38 => 'product-title-xs',
                        $textLen > 25 => 'product-title-sm',
                        default => '',
                    };
                @endphp
                <div class="group-1">
                    <div class="logo-spacer"></div>
                    <div class="v-text product-title draggable-title {{ $fontClass }}">
                        <div class="title-controls no-print" contenteditable="false">
                            <span class="btn-resize" onclick="resizeTitle(event, this, 1)" title="Perbesar Teks">A+</span>
                            <span class="btn-resize" onclick="resizeTitle(event, this, -1)" title="Perkecil Teks">A-</span>
                            <span class="font-size-indicator" contenteditable="true" title="Ketik angka & Enter" style="font-size:10px; margin:0 4px; line-height:1.8; font-family:monospace; color:#333; cursor:text; padding:0 2px; border:1px dashed transparent;">--px</span>
                            <span class="btn-resize" onclick="resizeBox(event, this, 1)" title="Perpanjang Kotak">↕+</span>
                            <span class="btn-resize" onclick="resizeBox(event, this, -1)" title="Perpendek Kotak">↕-</span>
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

                <!-- GROUP 4: Barcode & Code -->
                <div class="group-4">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let isDragging = false;
            let currentEl = null;
            let startX, startY;
            let initialX = 0, initialY = 0;

            const titles = document.querySelectorAll('.draggable-title');
            titles.forEach(title => {
                // Inisialisasi indikator font awal
                const indicator = title.querySelector('.font-size-indicator');
                if (indicator) {
                    const style = window.getComputedStyle(title);
                    indicator.innerText = Math.round(parseFloat(style.fontSize)) + 'px';
                }

                title.addEventListener('mousedown', function(e) {
                    // Jangan drag jika yang diklik adalah tombol resize, input teks, atau indikator font
                    if(e.target.classList.contains('btn-resize') || e.target.classList.contains('font-size-indicator')) return;

                    isDragging = true;
                    currentEl = this;
                    startX = e.clientX;
                    startY = e.clientY;
                    
                    // Ambil transform sebelumnya
                    const style = window.getComputedStyle(currentEl);
                    const matrix = new DOMMatrixReadOnly(style.transform === 'none' ? 'matrix(1, 0, 0, 1, 0, 0)' : style.transform);
                    initialX = matrix.m41 || 0;
                    initialY = matrix.m42 || 0;
                    
                    currentEl.style.zIndex = '1000';
                    currentEl.classList.add('dragging');
                    e.preventDefault(); // Mencegah blokir seleksi teks bawaan
                });
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging || !currentEl) return;
                
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                
                const newX = initialX + dx;
                const newY = initialY + dy;
                
                // Set posisi baru
                currentEl.style.transform = `translate(${newX}px, ${newY}px)`;
            });

            document.addEventListener('mouseup', function() {
                if (currentEl) {
                    currentEl.style.zIndex = 'auto';
                    currentEl.classList.remove('dragging');
                    
                    // Sinkronisasi pergeseran (transform) ke label dengan produk yang sama
                    const label = currentEl.closest('.label');
                    if (label) {
                        const code = label.getAttribute('data-product-code');
                        const transform = currentEl.style.transform;
                        document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                            if (el !== currentEl) el.style.transform = transform;
                        });
                    }
                }
                isDragging = false;
                currentEl = null;
            });
            
            // Mencegah spasi dan enter diubah menjadi DOM baru di editable field, dan sinkronisasi perubahan teks
            const editables = document.querySelectorAll('.editable-field');
            editables.forEach(field => {
                field.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
                field.addEventListener('input', function(e) {
                    const label = this.closest('.label');
                    if (!label) return;
                    const code = label.getAttribute('data-product-code');
                    const type = this.getAttribute('data-field-type');
                    const text = this.innerText;
                    
                    // Sinkronisasi teks ke label dengan produk dan tipe field yang sama
                    document.querySelectorAll(`.label[data-product-code="${code}"] .editable-field[data-field-type="${type}"]`).forEach(el => {
                        if (el !== this) {
                            el.innerText = text;
                        }
                    });
                });
            });
            
            // Event listener untuk input font-size manual
            const sizeIndicators = document.querySelectorAll('.font-size-indicator');
            sizeIndicators.forEach(ind => {
                ind.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur(); // Memicu event blur saat ditekan enter
                    }
                });
                
                ind.addEventListener('blur', function(e) {
                    let val = parseFloat(this.innerText);
                    
                    if (!isNaN(val) && val > 0) {
                        const container = this.closest('.draggable-title');
                        const updateTitleStyle = (el) => {
                            el.style.fontSize = val + 'px';
                            el.style.lineHeight = 'normal';
                            el.classList.remove('product-title-sm', 'product-title-xs', 'product-title-xxs');
                            const bind = el.querySelector('.font-size-indicator');
                            if (bind && bind !== this) bind.innerText = val + 'px';
                        };
                        
                        updateTitleStyle(container);
                        
                        // Sinkronisasi ke produk yang sama
                        const label = container.closest('.label');
                        if (label) {
                            const code = label.getAttribute('data-product-code');
                            document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                                if (el !== container) updateTitleStyle(el);
                            });
                        }
                    } else {
                        // Jika input tidak valid, kembalikan ke ukuran saat ini
                        const style = window.getComputedStyle(this.closest('.draggable-title'));
                        this.innerText = Math.round(parseFloat(style.fontSize)) + 'px';
                    }
                    
                    if (!this.innerText.endsWith('px')) {
                        this.innerText += 'px';
                    }
                });
            });
        });

        // Fungsi resize font (sinkronisasi antar produk sama)
        window.resizeTitle = function(e, btn, direction) {
            e.stopPropagation(); // Jangan memicu event drag
            e.preventDefault();
            
            const container = btn.closest('.draggable-title');
            const style = window.getComputedStyle(container);
            let currentSize = parseFloat(style.fontSize);
            currentSize += (direction * 1);
            
            const updateTitleStyle = (el) => {
                el.style.fontSize = currentSize + 'px';
                el.style.lineHeight = 'normal';
                el.classList.remove('product-title-sm', 'product-title-xs', 'product-title-xxs');
                
                const ind = el.querySelector('.font-size-indicator');
                if (ind) ind.innerText = currentSize + 'px';
            };
            
            updateTitleStyle(container);
            
            // Sinkronisasi ke produk yang sama
            const label = container.closest('.label');
            if (label) {
                const code = label.getAttribute('data-product-code');
                document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                    if (el !== container) updateTitleStyle(el);
                });
            }
        };

        // Fungsi perpanjang/perpendek kotak teks ke atas/bawah (tanpa batas maksimal)
        window.resizeBox = function(e, btn, direction) {
            e.stopPropagation();
            e.preventDefault();
            
            const container = btn.closest('.draggable-title');
            const style = window.getComputedStyle(container);
            
            // Hapus batas max-height agar bisa membesar tanpa batas
            container.style.maxHeight = 'none';
            container.style.flexShrink = '0';
            
            let currentHeight = parseFloat(style.height);
            
            if (isNaN(currentHeight) || currentHeight === 0) {
                currentHeight = 113.38;
            }
            
            // Tambah / kurang sekitar 10px per klik
            currentHeight += (direction * 10);
            container.style.height = currentHeight + 'px';
            
            // Sinkronisasi ke produk yang sama
            const label = container.closest('.label');
            if (label) {
                const code = label.getAttribute('data-product-code');
                document.querySelectorAll(`.label[data-product-code="${code}"] .draggable-title`).forEach(el => {
                    if (el !== container) {
                        el.style.maxHeight = 'none';
                        el.style.flexShrink = '0';
                        el.style.height = currentHeight + 'px';
                    }
                });
            }
        };
    </script>
</body>
</html>
