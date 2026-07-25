<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Label</title>
    <style>
        @page {
            size: 40mm 30mm;
            margin: 0;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Courier New', 'Consolas', monospace;
            color: #000;
            background: #fff;
        }
        .label {
            width: 40mm;
            height: 30mm;
            padding: 1mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            page-break-after: always;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .label:last-child { page-break-after: auto; }
        .barcode {
            width: 38mm;
            height: 13mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .barcode svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .code {
            font-size: 8pt;
            font-weight: 700;
            margin-top: 0.6mm;
            text-align: center;
            line-height: 1;
            letter-spacing: 0.2px;
        }
        .name {
            font-size: 5pt;
            text-align: center;
            line-height: 1.1;
            margin-top: 0.4mm;
            max-height: 6mm;
            overflow: hidden;
            word-break: break-word;
        }
    </style>
</head>
<body>
    @foreach ($labels as $label)
        <div class="label">
            <div class="barcode">{!! $label['svg'] !!}</div>
            <div class="code">{{ $label['code'] }}</div>
            <div class="name">{{ $label['name'] }}</div>
        </div>
    @endforeach
</body>
</html>
