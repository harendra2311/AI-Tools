"""Command-line interface for AI-Tools."""

from __future__ import annotations

import argparse
import sys


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="AI-Tools command-line interface")
    subparsers = parser.add_subparsers(dest="command", required=True)

    hello_parser = subparsers.add_parser("hello", help="Verify the environment is working")
    hello_parser.add_argument("--name", default="world", help="Name to greet")

    args = parser.parse_args(argv)

    if args.command == "hello":
        print(f"Hello, {args.name}! AI-Tools environment is ready.")
        return 0

    parser.error(f"unknown command: {args.command}")
    return 1


if __name__ == "__main__":
    sys.exit(main())
