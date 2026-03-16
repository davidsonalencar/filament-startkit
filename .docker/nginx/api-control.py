from http.server import BaseHTTPRequestHandler, HTTPServer
import subprocess
import json
import os

HOST = ""         # escuta em 0.0.0.0
PORT = 9000       # porta interna, só rede docker
CONTROL_TOKEN = os.environ.get("CONTROL_TOKEN", "troque-este-token")  # simples auth

WEBROOT = "/var/www/html"     # tem que bater com seu webroot
EMAIL = os.environ.get("CERTBOT_EMAIL")

class Handler(BaseHTTPRequestHandler):
    def _json(self, code, payload):
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(json.dumps(payload).encode())

    def _check_auth(self):
        # Autenticação simples via header X-Auth-Token
        token = self.headers.get("X-Auth-Token")
        return token == CONTROL_TOKEN

    def _get_json(self):
        length = int(self.headers.get("Content-Length", 0) or 0)
        if length == 0:
            return {}
        raw = self.rfile.read(length)
        if not raw:
            return {}
        try:
            return json.loads(raw.decode())
        except json.JSONDecodeError:
            return {}

    def do_POST(self):
        if not self._check_auth():
            self._json(401, {"ok": False, "error": "unauthorized"})
            return

        data = self._get_json()


        if self.path == "/api/certbot/issue":
            self.handle_issue_cert(data)
        elif self.path == "/api/nginx/reload":
            self.handle_reload_nginx()
        else:
            self._json(404, {"ok": False, "error": "not_found"})

    def handle_issue_cert(self, data):
        # domain enviado pelo Laravel (IssueSslCertificate)
        domain = data.get("domain") if isinstance(data, dict) else None

        try:
            cmd = [
                "certbot",
                "--nginx",
                "-d", domain,
                "-d", f"www.{domain}",
                "--redirect",
                "--agree-tos",
                "--email", EMAIL,
                "--non-interactive",
            ]
            output = subprocess.check_output(
                cmd,
                stderr=subprocess.STDOUT
            )

            # reload nginx depois de emitir/renovar
            subprocess.check_call(["nginx", "-s", "reload"])

            self._json(200, {"ok": True, "output": output.decode()})
        except subprocess.CalledProcessError as e:
            self._json(500, {
                "ok": False,
                "error": e.output.decode() if e.output else str(e)
            })

    def handle_reload_nginx(self):
        try:
            subprocess.check_call(["nginx", "-s", "reload"])
            self._json(200, {"ok": True})
        except subprocess.CalledProcessError as e:
            self._json(500, {"ok": False, "error": str(e)})

if __name__ == "__main__":
    server = HTTPServer((HOST, PORT), Handler)
    print(f"[*] Control API listening on {HOST}:{PORT}")
    server.serve_forever()
