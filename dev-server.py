"""
Triistic Local Development Server (Multi-threaded)
Handles multiple concurrent browser connections and tabs (including Incognito).
Supports static file serving AND handles POST requests to /send-mail.php.
"""

import http.server
import urllib.parse
import os

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
            self.send_header('Content-Length', '0')
            self.end_headers()
            return
            
        self.send_error(404, "Endpoint not found")

if __name__ == '__main__':
    # Use ThreadingHTTPServer so connections from multiple tabs/incognito are handled concurrently
    server_address = ('', PORT)
    httpd = http.server.ThreadingHTTPServer(server_address, TriisticRequestHandler)
    httpd.daemon_threads = True
    print(f"[DEV SERVER] Multi-threaded server running at http://localhost:{PORT}")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n[DEV SERVER] Server stopped.")
