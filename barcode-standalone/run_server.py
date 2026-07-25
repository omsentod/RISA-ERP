import os
import sys
import socket
import subprocess
import http.server
import socketserver

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

def find_available_port(start_port=8000):
    port = start_port
    while port < 8100:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            if s.connect_ex(('0.0.0.0', port)) != 0:
                return port
            port += 1
    return start_port

def main():
    current_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(current_dir)

    print("====================================================")
    print("      RISA STANDALONE BARCODE SYSTEM SERVER         ")
    print("====================================================")

    # 1. Regenerate barcode files from Python script
    gen_script = os.path.join(current_dir, "generate_barcodes.py")
    if os.path.exists(gen_script):
        print("\n[1/2] Menjalankan Python Barcode Generator secara otomatis...")
        try:
            res = subprocess.run([sys.executable, gen_script], cwd=current_dir)
            if res.returncode == 0:
                print("-> Barcode berhasil disinkronkan dari BARCODE.xlsx!")
            else:
                print("-> Peringatan: Proses generator mengembalikan kode status non-nol.")
        except Exception as e:
            print(f"-> Peringatan: Gagal memproses generator barcode: {e}")
    else:
        print("-> File generate_barcodes.py tidak ditemukan, melanjutkan server...")

    # 2. Setup Web Server
    local_ip = get_local_ip()
    port = find_available_port(8000)

    print("\n[2/2] Mengaktifkan Standalone Web Server...")
    print(f"Direktori Standalone : {current_dir}")
    print("----------------------------------------------------")
    print(f"1. Cetak Barcode (PC)   : http://localhost:{port}")
    print(f"2. Scanner & Verifikasi : http://{local_ip}:{port}/checker.html")
    print(f"3. Pendataan Outbound   : http://{local_ip}:{port}/pendataan.html")
    print("----------------------------------------------------")
    print("Untuk Akses Publik via Ngrok:")
    print(f"Jalankan:  .\\ngrok.exe http {port}")
    print("           atau")
    print(f"           C:\\laragon\\bin\\ngrok\\ngrok.exe http {port}")
    print("====================================================")
    print("Tekan Ctrl+C di terminal ini untuk mematikan server.")
    print("----------------------------------------------------\n")

    # Start HTTP Server on 0.0.0.0
    Handler = http.server.SimpleHTTPRequestHandler
    
    class CustomHandler(Handler):
        def end_headers(self):
            self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
            self.send_header('Pragma', 'no-cache')
            self.send_header('Expires', '0')
            super().end_headers()

    socketserver.TCPServer.allow_reuse_address = True
    try:
        with socketserver.TCPServer(("0.0.0.0", port), CustomHandler) as httpd:
            httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n[INFO] Standalone Barcode Server dihentikan.")
    except Exception as e:
        print(f"\n[ERROR] Gagal menjalankan web server: {e}")

if __name__ == "__main__":
    main()
