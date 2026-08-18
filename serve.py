#!/usr/bin/env python3
"""Serve the Draftline site from ./site on http://127.0.0.1:8080/"""
from __future__ import annotations

import functools
import http.server
import os
import socket
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent / "site"


def pick_port(start: int = 8080) -> int:
    env = os.environ.get("PORT")
    if env:
        return int(env)
    for port in range(start, start + 20):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            try:
                sock.bind(("127.0.0.1", port))
            except OSError:
                continue
            return port
    raise SystemExit("No free port found")


def main() -> None:
    if not (ROOT / "index.html").exists():
        raise SystemExit(f"Missing site files at {ROOT}")

    port = pick_port()
    handler = functools.partial(http.server.SimpleHTTPRequestHandler, directory=str(ROOT))
    server = http.server.ThreadingHTTPServer(("127.0.0.1", port), handler)
    url = f"http://127.0.0.1:{port}/"
    print(f"Draftline preview is running.\nOpen this URL in Chrome:\n  {url}\nUse http (not https). Press Ctrl+C to stop.", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nStopped.")
        sys.exit(0)


if __name__ == "__main__":
    main()
