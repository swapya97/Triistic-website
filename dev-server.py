"""
Triistic Local Development Server
Supports static file serving AND handles POST requests to /send-mail.php,
redirecting to thank-you.html.
"""

import http.server
import socketserver
import urllib.parse
import os
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

PORT = 8000
DIRECTORY = os.path.dirname(os.path.abspath(__file__))

class TriisticRequestHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)

    def do_POST(self):
        # Handle form submissions to /send-mail.php
        if self.path.startswith('/send-mail.php'):
            content_length = int(self.headers.get('Content-Length', 0))
            post_body = self.rfile.read(content_length).decode('utf-8')
            form_data = urllib.parse.parse_qs(post_body)
            
            # Extract fields
            name = form_data.get('name', [''])[0]
            email = form_data.get('email', [''])[0]
            company = form_data.get('company', [''])[0]
            phone = form_data.get('phone', [''])[0]
            country = form_data.get('country', [''])[0]
            service = form_data.get('service', [''])[0]
            message = form_data.get('message', [''])[0]
            
            print(f"\n[DEV SERVER] Form submitted successfully!")
            print(f"  Name:    {name}")
            print(f"  Email:   {email}")
            print(f"  Company: {company}")
            print(f"  Service: {service}")
            print(f"  Message: {message}")
            print(f"  Redirecting to thank-you.html...\n")

            # Redirect to thank-you.html
            self.send_response(303)
            self.send_header('Location', '/thank-you.html')
            self.end_headers()
            return
            
        # Fallback for other POST requests
        self.send_error(404, "Endpoint not found")

if __name__ == '__main__':
    # Allow port reuse
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.TCPServer(("", PORT), TriisticRequestHandler) as httpd:
        print(f"[DEV SERVER] Serving Triistic website at http://localhost:{PORT}")
        print(f"[DEV SERVER] Form POST handler active for /send-mail.php -> /thank-you.html")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n[DEV SERVER] Server stopped.")
