# FileNova

Static HTML/CSS/JavaScript website for online PDF and image tools.

## Brand

**FileNova** — teal/slate visual identity. Replace the logo text and CSS variables in `assets/css/style.css` to rebrand.

## Stack

- HTML5, CSS3, Vanilla JavaScript
- No build step, no backend
- Deploy by uploading the folder to any static host

## Structure

- `index.html` — homepage
- `tools.html` — searchable tool catalog
- `pdf-tools/` — PDF utilities
- `image-tools/` — image utilities
- `blog/` — static articles
- `assets/css/style.css` — design system
- `assets/js/main.js` — shared behavior

## Local preview

Open `index.html` in a browser, or serve the folder:

```bash
python3 -m http.server 8080
```

## Phase 2

Wire real processing to `FileNova.processTool()` in `assets/js/main.js`. Upload UI and options are already structured for an API.

## Regenerating pages

Optional: `python3 generate_site.py` rebuilds HTML from the generator (development helper only).
