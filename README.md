# AI-Tools

A collection of AI utilities and tooling.

## Development

This repository uses a Python package managed with `pyproject.toml`. Cloud Agents bootstrap the environment with `.cursor/install.sh`, which creates a virtual environment and installs the package in editable mode.

### Website

The Draftline marketing site and invoice tools live in `site/`.

Preview on your computer (this is required — `draftline.app` is not live yet, and `127.0.0.1` only works after the server is running):

```bash
python3 serve.py
```

Then open **http://127.0.0.1:8080/** in Chrome. Use `http`, not `https`. Keep the terminal open while you view the page.

### Local setup

```bash
./.cursor/install.sh
.venv/bin/ai-tools hello
```

### Commands

| Command | Description |
| --- | --- |
| `./.cursor/install.sh` | Create `.venv` and install dependencies |
| `.venv/bin/ai-tools hello` | Smoke test that the environment works |
