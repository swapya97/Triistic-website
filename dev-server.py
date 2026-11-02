"""
Triistic Local Development Server (Multi-threaded with real Zoho SMTP sending)
Handles multiple concurrent browser connections and tabs (including Incognito).
Supports static file serving AND dispatches real emails via Zoho SMTP on form submission.
"""

import http.server
import urllib.parse
import os
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

PORT = 8000
DIRECTORY = os.path.dirname(os.path.abspath(__file__))

# Zoho SMTP Configuration
SMTP_HOST = 'smtp.zoho.in'
SMTP_PORT = 465
SMTP_USER = 'admin@triistic.com'
SMTP_PASS = 'hTlq@9je'
TO_EMAIL  = 'swapnilakulwar97@gmail.com'
FROM_NAME = 'Triistic Global Website'

def send_real_email(name, email, company, phone, country, service, message, form_type="Website Inquiry"):
    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = f"[Triistic Website] New Inquiry from {name}"
        msg['From'] = f"{FROM_NAME} <{SMTP_USER}>"
        msg['To'] = TO_EMAIL
        msg['Reply-To'] = f"{name} <{email}>"

        html_content = f"""<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {{ font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; padding: 20px; }}
    .container {{ max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }}
    .header {{ background: #001f3f; color: #ffffff; padding: 20px; text-align: center; }}
    .header h2 {{ margin: 0; font-size: 20px; }}
    .content {{ padding: 24px; }}
    table {{ width: 100%; border-collapse: collapse; margin-bottom: 20px; }}
    th, td {{ padding: 12px 14px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }}
    th {{ width: 35%; color: #64748b; background: #f8fafc; }}
    td {{ color: #0f172a; }}
    .message-box {{ background: #f8fafc; border-left: 4px solid #f59e0b; padding: 14px; border-radius: 4px; font-size: 14px; white-space: pre-line; }}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>{form_type}</h2>
    </div>
    <div class="content">
        <table>
            <tr><th>Full Name</th><td><strong>{name}</strong></td></tr>
            <tr><th>Email Address</th><td><a href="mailto:{email}">{email}</a></td></tr>
            <tr><th>Company</th><td>{company}</td></tr>
            <tr><th>Phone</th><td>{phone}</td></tr>
            <tr><th>Country</th><td>{country}</td></tr>
            <tr><th>Service</th><td>{service}</td></tr>
        </table>
        <h4>Message:</h4>
        <div class="message-box">{message}</div>
    </div>
</div>
</body>
</html>"""

        msg.attach(MIMEText(html_content, 'html', 'utf-8'))

        with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT) as server:
            server.login(SMTP_USER, SMTP_PASS)
            server.send_message(msg)
        print(f"[DEV SERVER] Email successfully sent to {TO_EMAIL} via {SMTP_HOST}!")
        return True
    except Exception as e:
        print(f"[DEV SERVER] SMTP Send Error: {e}")
        return False

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
            company = form_data.get('company', [''])[0] or 'Not specified'
            phone = form_data.get('phone', [''])[0] or 'Not provided'
            country = form_data.get('country', [''])[0] or 'Not specified'
            service = form_data.get('service', [''])[0]
            message = form_data.get('message', [''])[0]
            form_type = form_data.get('form_type', ['Website Inquiry'])[0]
            
            print(f"\n[DEV SERVER] Form submitted by: {name} ({email})")
            send_real_email(name, email, company, phone, country, service, message, form_type)

            # Redirect to thank-you.html
            self.send_response(303)
            self.send_header('Location', '/thank-you.html')
            self.send_header('Content-Length', '0')
            self.end_headers()
            return
            
        self.send_error(404, "Endpoint not found")

if __name__ == '__main__':
    server_address = ('', PORT)
    httpd = http.server.ThreadingHTTPServer(server_address, TriisticRequestHandler)
    httpd.daemon_threads = True
    print(f"[DEV SERVER] Multi-threaded server running at http://localhost:{PORT}")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n[DEV SERVER] Server stopped.")
