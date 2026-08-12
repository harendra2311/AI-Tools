# AI-Tools

A collection of AI utilities and tooling.

## Development

This repository uses a Python package managed with `pyproject.toml`. Cloud Agents bootstrap the environment with `.cursor/install.sh`, which creates a virtual environment and installs the package in editable mode.

### Website

The Draftline marketing site and invoice tools live in `site/`. Serve locally:

```bash
python3 -m http.server 8080 --directory site
```

Then open `http://127.0.0.1:8080/`.

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
