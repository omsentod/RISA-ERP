import os
import re
import json
import pandas as pd
import barcode
from barcode.writer import SVGWriter, ImageWriter

def clean_svg_for_inline(svg_str, code=None):
    # Remove XML declaration and DOCTYPE which are not needed for inline HTML SVGs
    svg_str = re.sub(r'<\?xml[^>]*\?>', '', svg_str)
    svg_str = re.sub(r'<!DOCTYPE[^>]*>', '', svg_str)
    svg_str = svg_str.strip()
    
    # Extract width and height in mm (e.g. width="34.080mm" height="17.000mm")
    width_match = re.search(r'width="([\d.]+)mm"', svg_str)
    height_match = re.search(r'height="([\d.]+)mm"', svg_str)
    
    if width_match and height_match:
        w = float(width_match.group(1))
        h = float(height_match.group(1))
        
        # High resolution fallback dimensions for raster canvas parsing in browser (10x scaled)
        pixel_width = w * 10
        pixel_height = h * 10
        data_code_attr = f' data-code="{code}"' if code else ''
        
        # Replace the opening <svg ...> tag with a clean responsive one having viewBox and explicit dimensions
        svg_str = re.sub(
            r'<svg[^>]*>',
            f'<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{pixel_width}" height="{pixel_height}" preserveAspectRatio="xMidYMid meet"{data_code_attr}>',
            svg_str,
            count=1
        )
    elif code:
        # If no width/height match, still inject into opening <svg> tag if possible
        svg_str = re.sub(
            r'<svg([^>]*)>',
            f'<svg\\1 data-code="{code}">',
            svg_str,
            count=1
        )
    
    # Remove absolute unit suffixes (e.g., mm) from all coordinates and sizes
    # inside the SVG so that the vectors are unitless and scale relative to the viewBox.
    svg_str = re.sub(r'([\d.]+)mm', r'\1', svg_str)
    return svg_str

def make_safe_filename(kode):
    # Replace spaces, dots, slashes, backslashes, and other special chars with underscores
    safe = re.sub(r'[\s./\\:*"<>|?#&%=+]', '_', str(kode).strip())
    # Remove consecutive underscores
    safe = re.sub(r'_{2,}', '_', safe)
    return safe.strip('_')

import shutil

def main():
    # Setup paths
    base_dir = os.path.dirname(os.path.abspath(__file__))
    excel_path = os.path.join(base_dir, "BARCODE.xlsx")
    barcodes_dir = os.path.join(base_dir, "barcodes")
    js_output_path = os.path.join(base_dir, "barcodes_data.js")
    
    # Check Laravel root and public barcode-generator path
    laravel_root = os.path.dirname(base_dir) if os.path.basename(base_dir) == "barcode-generator" else os.path.dirname(os.path.dirname(base_dir))
    public_bg_dir = os.path.join(laravel_root, "public", "barcode-generator")
    
    if not os.path.exists(excel_path) and os.path.exists(os.path.join(public_bg_dir, "BARCODE.xlsx")):
        excel_path = os.path.join(public_bg_dir, "BARCODE.xlsx")
    
    # Ensure barcodes output directory exists
    os.makedirs(barcodes_dir, exist_ok=True)
    
    print(f"Reading data from: {excel_path}")
    if not os.path.exists(excel_path):
        print(f"Error: {excel_path} not found!")
        return
        
    df = pd.read_excel(excel_path)
    
    # Drop rows where Kode or Nama Produk is null
    df = df.dropna(subset=['Kode'])
    
    print(f"Found {len(df)} rows with valid codes.")
    
    # Initialize the code128 writer
    CODE128 = barcode.get_barcode_class('code128')
    
    data_list = []
    success_count = 0
    
    for idx, row in df.iterrows():
        raw_kode = str(row['Kode']).strip()
        spesifikasi = str(row['Spesifikasi']).strip() if pd.notna(row['Spesifikasi']) else ""
        nama_produk = str(row['Nama Produk']).strip() if pd.notna(row['Nama Produk']) else ""
        
        # Generate safe filename
        safe_name = make_safe_filename(raw_kode)
        svg_file_path = os.path.join(barcodes_dir, f"{safe_name}") # save() adds .svg automatically
        
        try:
            # Writer options for maximum compatibility with 1D laser barcode gun scanners (e.g. iware)
            writer_opts = {
                "write_text": False,
                "module_width": 0.35,
                "module_height": 18.0,
                "quiet_zone": 10.0,
                "dpi": 300
            }

            # Generate barcode SVG object
            my_barcode = CODE128(raw_kode, writer=SVGWriter())
            
            # Save individual SVG file
            saved_path = my_barcode.save(svg_file_path, options=writer_opts)
            
            # Read, clean and overwrite the saved file to ensure it's also responsive and without mm unit bugs
            try:
                with open(saved_path, "r", encoding="utf-8") as svg_file:
                    file_content = svg_file.read()
                cleaned_file_content = clean_svg_for_inline(file_content, raw_kode)
                with open(saved_path, "w", encoding="utf-8") as svg_file:
                    svg_file.write(cleaned_file_content)
            except Exception as file_err:
                print(f"Warning: Could not clean saved file {saved_path}: {file_err}")
                
            # Save individual PNG file (options: write_text=False, format=PNG)
            try:
                my_barcode_png = CODE128(raw_kode, writer=ImageWriter())
                my_barcode_png.save(svg_file_path, options=writer_opts)
            except Exception as png_err:
                print(f"Warning: Could not save PNG file for {safe_name}: {png_err}")
            
            # Get SVG as string for inlining in dashboard
            svg_bytes = my_barcode.render(writer_options=writer_opts)
            svg_str = svg_bytes.decode("utf-8")
            
            # Clean SVG string
            inline_svg = clean_svg_for_inline(svg_str, raw_kode)
            
            # Save info to our list
            data_list.append({
                "id": idx,
                "spesifikasi": spesifikasi,
                "kode": raw_kode,
                "nama_produk": nama_produk,
                "svg": inline_svg,
                "filename": f"barcodes/{safe_name}.svg"
            })
            
            success_count += 1
            if success_count % 100 == 0:
                print(f"Generated {success_count} barcodes...")
                
        except Exception as e:
            print(f"Error generating barcode for row {idx} [Code: {raw_kode}]: {e}")
            
    # Write to barcodes_data.js
    print(f"Writing javascript data file to: {js_output_path}")
    js_content = f"// Automatically generated barcode data. Do not edit manually.\nconst barcodeData = {json.dumps(data_list, indent=2)};\n"
    with open(js_output_path, "w", encoding="utf-8") as f:
        f.write(js_content)

    # Sync automatically to public/barcode-generator/ directory if distinct
    if os.path.exists(public_bg_dir) and os.path.abspath(public_bg_dir) != os.path.abspath(base_dir):
        print(f"Syncing generated files to web directory: {public_bg_dir}")
        try:
            pub_barcodes_dir = os.path.join(public_bg_dir, "barcodes")
            os.makedirs(pub_barcodes_dir, exist_ok=True)
            pub_js_output = os.path.join(public_bg_dir, "barcodes_data.js")
            with open(pub_js_output, "w", encoding="utf-8") as f:
                f.write(js_content)
            
            for item in os.listdir(barcodes_dir):
                s = os.path.join(barcodes_dir, item)
                d = os.path.join(pub_barcodes_dir, item)
                if os.path.isfile(s):
                    shutil.copy2(s, d)
        except Exception as sync_err:
            print(f"Warning: Failed to sync to public directory: {sync_err}")
        
    print(f"\nCompleted successfully! Generated {success_count} / {len(df)} barcodes.")
    print(f"Individual SVG files saved in: {barcodes_dir}")
    print(f"JS data file saved to: {js_output_path}")

if __name__ == "__main__":
    main()

