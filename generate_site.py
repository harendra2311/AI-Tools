#!/usr/bin/env python3
"""Generate FileNova static HTML pages. Run once; output is plain HTML."""
from pathlib import Path
from html import escape

ROOT = Path(__file__).resolve().parent

BRAND = "FileNova"
SITE = "https://filenova.example"

TOOLS = [
    {"id": "compress-pdf", "name": "Compress PDF", "desc": "Reduce PDF size while keeping quality.", "cat": ["pdf", "compress"], "path": "pdf-tools/compress-pdf.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "merge-pdf", "name": "Merge PDF", "desc": "Combine multiple PDF files into one.", "cat": ["pdf", "utilities"], "path": "pdf-tools/merge-pdf.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF", "multi": True},
    {"id": "split-pdf", "name": "Split PDF", "desc": "Extract pages or split into multiple files.", "cat": ["pdf", "utilities"], "path": "pdf-tools/split-pdf.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "rotate-pdf", "name": "Rotate PDF", "desc": "Rotate pages left or right in bulk.", "cat": ["pdf", "utilities"], "path": "pdf-tools/rotate-pdf.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "pdf-to-word", "name": "PDF to Word", "desc": "Convert PDF documents into editable Word files.", "cat": ["pdf", "convert"], "path": "pdf-tools/pdf-to-word.html", "icon": "convert", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "pdf-to-jpg", "name": "PDF to JPG", "desc": "Export PDF pages as JPG images.", "cat": ["pdf", "convert", "images"], "path": "pdf-tools/pdf-to-jpg.html", "icon": "convert", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "pdf-to-png", "name": "PDF to PNG", "desc": "Export PDF pages as PNG images.", "cat": ["pdf", "convert", "images"], "path": "pdf-tools/pdf-to-png.html", "icon": "convert", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "jpg-to-pdf", "name": "JPG to PDF", "desc": "Convert images into a PDF document.", "cat": ["pdf", "convert", "images"], "path": "pdf-tools/jpg-to-pdf.html", "icon": "convert", "accept": "image/jpeg,image/png,.jpg,.jpeg,.png", "formats": "JPG, PNG", "multi": True, "images": True},
    {"id": "word-to-pdf", "name": "Word to PDF", "desc": "Turn Word documents into PDF files.", "cat": ["pdf", "convert"], "path": "pdf-tools/word-to-pdf.html", "icon": "convert", "accept": ".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document", "formats": "DOC, DOCX"},
    {"id": "excel-to-pdf", "name": "Excel to PDF", "desc": "Export spreadsheets to PDF.", "cat": ["pdf", "convert"], "path": "pdf-tools/excel-to-pdf.html", "icon": "convert", "accept": ".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "formats": "XLS, XLSX"},
    {"id": "protect-pdf", "name": "Protect PDF", "desc": "Add a password to lock your PDF.", "cat": ["pdf", "security"], "path": "pdf-tools/protect-pdf.html", "icon": "security", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "unlock-pdf", "name": "Unlock PDF", "desc": "Remove password protection when you know it.", "cat": ["pdf", "security"], "path": "pdf-tools/unlock-pdf.html", "icon": "security", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "watermark-pdf", "name": "Watermark PDF", "desc": "Stamp text or image watermarks on pages.", "cat": ["pdf", "utilities"], "path": "pdf-tools/watermark-pdf.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "add-page-numbers", "name": "Add Page Numbers", "desc": "Number PDF pages with flexible placement.", "cat": ["pdf", "utilities"], "path": "pdf-tools/add-page-numbers.html", "icon": "pdf", "accept": ".pdf,application/pdf", "formats": "PDF"},
    {"id": "compress-image", "name": "Compress Image", "desc": "Reduce image size for web and sharing.", "cat": ["images", "compress"], "path": "image-tools/compress-image.html", "icon": "image", "accept": "image/*,.jpg,.jpeg,.png,.webp", "formats": "JPG, PNG, WebP", "images": True},
    {"id": "resize-image", "name": "Resize Image", "desc": "Change image dimensions precisely.", "cat": ["images", "utilities"], "path": "image-tools/resize-image.html", "icon": "image", "accept": "image/*,.jpg,.jpeg,.png,.webp", "formats": "JPG, PNG, WebP", "images": True},
    {"id": "convert-image", "name": "Convert Image", "desc": "Switch between popular image formats.", "cat": ["images", "convert"], "path": "image-tools/convert-image.html", "icon": "convert", "accept": "image/*,.jpg,.jpeg,.png,.webp,.gif", "formats": "JPG, PNG, WebP, GIF", "images": True},
    {"id": "jpg-to-webp", "name": "JPG to WebP", "desc": "Convert JPG photos to modern WebP.", "cat": ["images", "convert"], "path": "image-tools/jpg-to-webp.html", "icon": "convert", "accept": "image/jpeg,.jpg,.jpeg", "formats": "JPG, JPEG", "images": True},
    {"id": "png-to-webp", "name": "PNG to WebP", "desc": "Convert PNG graphics to WebP.", "cat": ["images", "convert"], "path": "image-tools/png-to-webp.html", "icon": "convert", "accept": "image/png,.png", "formats": "PNG", "images": True},
    {"id": "webp-to-jpg", "name": "WebP to JPG", "desc": "Convert WebP images to JPG.", "cat": ["images", "convert"], "path": "image-tools/webp-to-jpg.html", "icon": "convert", "accept": "image/webp,.webp", "formats": "WebP", "images": True},
    {"id": "webp-to-png", "name": "WebP to PNG", "desc": "Convert WebP images to PNG.", "cat": ["images", "convert"], "path": "image-tools/webp-to-png.html", "icon": "convert", "accept": "image/webp,.webp", "formats": "WebP", "images": True},
    {"id": "image-to-pdf", "name": "Image to PDF", "desc": "Build a PDF from one or more images.", "cat": ["images", "convert", "pdf"], "path": "image-tools/image-to-pdf.html", "icon": "convert", "accept": "image/*,.jpg,.jpeg,.png,.webp", "formats": "JPG, PNG, WebP", "multi": True, "images": True},
    {"id": "image-to-text", "name": "Image to Text", "desc": "Extract text from images with OCR.", "cat": ["images", "ocr"], "path": "image-tools/image-to-text.html", "icon": "ocr", "accept": "image/*,.jpg,.jpeg,.png,.webp", "formats": "JPG, PNG, WebP", "images": True},
    {"id": "background-remover", "name": "Background Remover", "desc": "Isolate subjects by removing backgrounds.", "cat": ["images", "utilities"], "path": "image-tools/background-remover.html", "icon": "image", "accept": "image/*,.jpg,.jpeg,.png,.webp", "formats": "JPG, PNG, WebP", "images": True},
]

BLOG = [
    {"slug": "how-to-compress-pdf", "title": "How to Compress a PDF Without Losing Quality", "excerpt": "Practical steps to shrink PDF files while keeping text sharp and images clear.", "date": "2026-03-12", "read": "6 min"},
    {"slug": "how-to-merge-pdf", "title": "How to Merge PDF Files", "excerpt": "A clear guide to combining multiple PDFs into a single document for sharing.", "date": "2026-03-18", "read": "5 min"},
    {"slug": "jpg-to-pdf-guide", "title": "How to Convert JPG to PDF", "excerpt": "Turn photos and scans into polished PDF documents with page size and margin tips.", "date": "2026-04-02", "read": "5 min"},
    {"slug": "reduce-image-size", "title": "How to Reduce Image Size", "excerpt": "Balance file size and visual quality when preparing images for the web.", "date": "2026-04-14", "read": "7 min"},
    {"slug": "what-is-webp", "title": "What Is WebP and Why Should You Use It?", "excerpt": "Understand WebP benefits, browser support, and when to convert from JPG or PNG.", "date": "2026-05-01", "read": "6 min"},
]

ICON_SVGS = {
    "pdf": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h2"/></svg>',
    "image": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
    "convert": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
    "security": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    "ocr": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7V4h3M17 4h3v3M4 17v3h3M17 20h3v-3"/><path d="M8 8h8v8H8z"/></svg>',
    "search": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>',
    "menu": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
    "moon": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>',
    "chevron": '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>',
    "arrow": '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>',
    "upload": '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
}


def depth_prefix(rel_path: str) -> str:
    return "../" if "/" in rel_path else ""


def logo_svg():
    return '''<span class="logo-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h10a4 4 0 0 1 0 8H8"/><path d="M8 15l-4 4"/><path d="M14 7V4a1 1 0 0 1 1-1h5"/><path d="M20 3v5"/></svg></span><span>FileNova</span>'''


def head(title, description, path, extra="", depth=""):
    canonical = f"{SITE}/{path}" if path != "index.html" else f"{SITE}/"
    return f'''<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{escape(title)}</title>
  <meta name="description" content="{escape(description)}">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="{canonical}">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{BRAND}">
  <meta property="og:title" content="{escape(title)}">
  <meta property="og:description" content="{escape(description)}">
  <meta property="og:url" content="{canonical}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{escape(title)}">
  <meta name="twitter:description" content="{escape(description)}">
  <meta name="theme-color" content="#0f766e">
  <link rel="icon" href="{depth}assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="{depth}assets/css/style.css">
  <script>
    (function(){{try{{var t=localStorage.getItem('filenova-theme');if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',t);}}catch(e){{}}}})();
  </script>
  {extra}
</head>
'''


def header(depth="", active=""):
    def nav_cur(key):
        return ' aria-current="page"' if active == key else ""

    return f'''<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="{depth}index.html">{logo_svg()}</a>
    <nav class="nav-desktop" aria-label="Primary">
      <a href="{depth}tools.html"{nav_cur("tools")}>Tools</a>
      <div class="nav-dropdown">
        <button type="button" aria-haspopup="true">PDF Tools {ICON_SVGS["chevron"]}</button>
        <div class="nav-dropdown-menu" role="menu">
          <a href="{depth}pdf-tools/compress-pdf.html">Compress PDF</a>
          <a href="{depth}pdf-tools/merge-pdf.html">Merge PDF</a>
          <a href="{depth}pdf-tools/split-pdf.html">Split PDF</a>
          <a href="{depth}pdf-tools/jpg-to-pdf.html">JPG to PDF</a>
          <a href="{depth}pdf-tools/pdf-to-word.html">PDF to Word</a>
          <a href="{depth}pdf-tools/protect-pdf.html">Protect PDF</a>
        </div>
      </div>
      <div class="nav-dropdown">
        <button type="button" aria-haspopup="true">Image Tools {ICON_SVGS["chevron"]}</button>
        <div class="nav-dropdown-menu" role="menu">
          <a href="{depth}image-tools/compress-image.html">Compress Image</a>
          <a href="{depth}image-tools/resize-image.html">Resize Image</a>
          <a href="{depth}image-tools/convert-image.html">Convert Image</a>
          <a href="{depth}image-tools/jpg-to-webp.html">JPG to WebP</a>
          <a href="{depth}image-tools/background-remover.html">Background Remover</a>
        </div>
      </div>
      <div class="nav-dropdown">
        <button type="button" aria-haspopup="true">Resources {ICON_SVGS["chevron"]}</button>
        <div class="nav-dropdown-menu" role="menu">
          <a href="{depth}blog.html">Blog</a>
          <a href="{depth}about.html">About</a>
          <a href="{depth}contact.html">Contact</a>
        </div>
      </div>
      <a href="{depth}about.html"{nav_cur("about")}>About</a>
    </nav>
    <div class="header-actions">
      <button type="button" class="icon-btn" data-search-open aria-label="Search tools">{ICON_SVGS["search"]}</button>
      <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle dark mode">{ICON_SVGS["moon"]}</button>
      <button type="button" class="icon-btn menu-toggle" data-menu-toggle aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">{ICON_SVGS["menu"]}</button>
    </div>
  </div>
</header>
<nav id="mobile-nav" class="mobile-nav" data-mobile-nav aria-label="Mobile">
  <a href="{depth}tools.html">All Tools</a>
  <div class="mobile-section-title">PDF Tools</div>
  <a href="{depth}pdf-tools/compress-pdf.html">Compress PDF</a>
  <a href="{depth}pdf-tools/merge-pdf.html">Merge PDF</a>
  <a href="{depth}pdf-tools/jpg-to-pdf.html">JPG to PDF</a>
  <a href="{depth}pdf-tools/pdf-to-word.html">PDF to Word</a>
  <div class="mobile-section-title">Image Tools</div>
  <a href="{depth}image-tools/compress-image.html">Compress Image</a>
  <a href="{depth}image-tools/resize-image.html">Resize Image</a>
  <a href="{depth}image-tools/convert-image.html">Convert Image</a>
  <div class="mobile-section-title">Resources</div>
  <a href="{depth}blog.html">Blog</a>
  <a href="{depth}about.html">About</a>
  <a href="{depth}contact.html">Contact</a>
</nav>
<div class="search-overlay" data-search-overlay aria-hidden="true">
  <div class="search-panel" role="dialog" aria-label="Search tools">
    <label class="sr-only" for="global-search">Search tools</label>
    <input id="global-search" type="search" placeholder="Search for a tool..." data-search-input autocomplete="off">
    <div class="search-results" data-search-results></div>
  </div>
</div>
'''


def footer(depth=""):
    return f'''<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="logo" href="{depth}index.html">{logo_svg()}</a>
      <p>Fast, focused online tools for PDFs and images. Built for clarity—ready for your next file task.</p>
    </div>
    <div class="footer-col">
      <h4>Tools</h4>
      <a href="{depth}tools.html">All Tools</a>
      <a href="{depth}tools.html?cat=pdf">PDF Tools</a>
      <a href="{depth}tools.html?cat=images">Image Tools</a>
      <a href="{depth}tools.html?cat=convert">Converters</a>
    </div>
    <div class="footer-col">
      <h4>PDF Tools</h4>
      <a href="{depth}pdf-tools/compress-pdf.html">Compress PDF</a>
      <a href="{depth}pdf-tools/merge-pdf.html">Merge PDF</a>
      <a href="{depth}pdf-tools/jpg-to-pdf.html">JPG to PDF</a>
      <a href="{depth}pdf-tools/protect-pdf.html">Protect PDF</a>
    </div>
    <div class="footer-col">
      <h4>Image Tools</h4>
      <a href="{depth}image-tools/compress-image.html">Compress Image</a>
      <a href="{depth}image-tools/resize-image.html">Resize Image</a>
      <a href="{depth}image-tools/jpg-to-webp.html">JPG to WebP</a>
      <a href="{depth}image-tools/background-remover.html">Background Remover</a>
    </div>
    <div class="footer-col">
      <h4>Resources</h4>
      <a href="{depth}blog.html">Blog</a>
      <a href="{depth}about.html">About</a>
      <a href="{depth}contact.html">Contact</a>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <a href="{depth}privacy.html">Privacy Policy</a>
      <a href="{depth}terms.html">Terms of Use</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; 2026 {BRAND}. All rights reserved.</span>
    <span>Static frontend — processing API coming in Phase 2.</span>
  </div>
</footer>
<script src="{depth}assets/js/main.js" defer></script>
'''


def tool_card(t, depth=""):
    cats = " ".join(t["cat"])
    badge = t["cat"][0].upper() if t["cat"] else "TOOL"
    return f'''<a class="tool-card" href="{depth}{t["path"]}" data-tool-card data-name="{escape(t["name"])}" data-desc="{escape(t["desc"])}" data-categories="{cats}">
  <div class="tool-card-top">
    <div class="tool-icon {t["icon"]}">{ICON_SVGS.get(t["icon"], ICON_SVGS["pdf"])}</div>
    <span class="badge">{badge}</span>
  </div>
  <h3>{escape(t["name"])}</h3>
  <p>{escape(t["desc"])}</p>
  <div class="tool-card-footer">
    <span class="arrow-link">Open {ICON_SVGS["arrow"]}</span>
  </div>
</a>
'''


def related_tools(current_id, depth="../"):
    current = next(t for t in TOOLS if t["id"] == current_id)
    related = []
    for t in TOOLS:
        if t["id"] == current_id:
            continue
        if set(t["cat"]) & set(current["cat"]):
            related.append(t)
    if len(related) < 5:
        for t in TOOLS:
            if t["id"] != current_id and t not in related:
                related.append(t)
            if len(related) >= 5:
                break
    related = related[:5]
    cards = "\n".join(tool_card(t, depth) for t in related)
    return f'''<section class="section">
  <div class="container">
    <div class="section-header"><div><h2>Related Tools</h2><p>Continue with similar file utilities.</p></div></div>
    <div class="tool-grid">{cards}</div>
  </div>
</section>
'''


def faq_block(items):
    parts = []
    for i, (q, a) in enumerate(items):
        parts.append(f'''<div class="faq-item">
  <button type="button" class="faq-question" id="faq-q-{i}" aria-controls="faq-a-{i}">{escape(q)} {ICON_SVGS["chevron"]}</button>
  <div class="faq-answer" id="faq-a-{i}" role="region" aria-labelledby="faq-q-{i}"><p>{a}</p></div>
</div>''')
    return f'<div class="faq-list" data-faq>{"".join(parts)}</div>'


def tool_options(t):
    tid = t["id"]
    if tid == "compress-pdf":
        return '''<div class="options-panel">
  <h3>Compression options</h3>
  <div class="option-group">
    <span class="field-label">Mode</span>
    <div class="radio-row">
      <label class="radio-pill"><input type="radio" name="compress-mode" value="high"><span>High compression</span></label>
      <label class="radio-pill"><input type="radio" name="compress-mode" value="balanced" checked><span>Balanced</span></label>
      <label class="radio-pill"><input type="radio" name="compress-mode" value="quality"><span>Best quality</span></label>
    </div>
  </div>
</div>
<div class="hidden" data-size-estimate></div>'''
    if tid == "merge-pdf":
        return '''<div class="options-panel">
  <h3>Merge options</h3>
  <p class="text-muted" style="font-size:0.9rem">Drag files in the list to reorder pages before merging.</p>
  <button type="button" class="btn btn-secondary mt-2" data-add-more>Add More Files</button>
</div>'''
    if tid == "jpg-to-pdf" or tid == "image-to-pdf":
        return '''<div class="options-panel">
  <h3>PDF layout</h3>
  <div class="field-row cols-3">
    <div class="field"><label class="field-label" for="page-size">Page size</label>
      <select id="page-size" name="page-size"><option>A4</option><option>Letter</option><option>Fit to image</option></select></div>
    <div class="field"><label class="field-label" for="orientation">Orientation</label>
      <select id="orientation" name="orientation"><option>Portrait</option><option>Landscape</option></select></div>
    <div class="field"><label class="field-label" for="margin">Margin</label>
      <select id="margin" name="margin"><option>None</option><option selected>Small</option><option>Medium</option></select></div>
  </div>
  <button type="button" class="btn btn-secondary mt-2" data-add-more>Add More Images</button>
</div>'''
    if tid == "compress-image":
        return '''<div class="options-panel">
  <h3>Quality</h3>
  <div class="range-wrap" data-range>
    <input type="range" min="10" max="100" value="75" data-suffix="%">
    <span class="range-value" data-range-value>75%</span>
  </div>
  <p class="text-muted mt-1" style="font-size:0.85rem">Lower quality usually means a smaller file. Preview is client-side only.</p>
</div>'''
    if tid == "resize-image":
        return '''<div class="options-panel" data-resize-tool>
  <h3>Dimensions</h3>
  <div class="field-row cols-2">
    <div class="field"><label class="field-label" for="width">Width (px)</label><input id="width" name="width" type="number" min="1" placeholder="Width"></div>
    <div class="field"><label class="field-label" for="height">Height (px)</label><input id="height" name="height" type="number" min="1" placeholder="Height"></div>
  </div>
  <label class="check-row mt-2" style="display:flex;align-items:center;gap:0.5rem">
    <input type="checkbox" name="lock-aspect" checked> Lock aspect ratio
  </label>
</div>'''
    if tid == "rotate-pdf":
        return '''<div class="options-panel"><h3>Rotation</h3>
  <div class="radio-row">
    <label class="radio-pill"><input type="radio" name="rotate" value="90" checked><span>90° right</span></label>
    <label class="radio-pill"><input type="radio" name="rotate" value="270"><span>90° left</span></label>
    <label class="radio-pill"><input type="radio" name="rotate" value="180"><span>180°</span></label>
  </div></div>'''
    if tid == "split-pdf":
        return '''<div class="options-panel"><h3>Split mode</h3>
  <div class="radio-row">
    <label class="radio-pill"><input type="radio" name="split" value="range" checked><span>Page range</span></label>
    <label class="radio-pill"><input type="radio" name="split" value="each"><span>Every page</span></label>
  </div>
  <div class="field mt-2"><label class="field-label" for="pages">Pages (e.g. 1-3, 5)</label><input id="pages" name="pages" type="text" placeholder="1-3, 5"></div>
</div>'''
    if tid == "protect-pdf":
        return '''<div class="options-panel"><h3>Password</h3>
  <div class="field"><label class="field-label" for="pwd">Set password</label><input id="pwd" type="password" autocomplete="new-password"></div>
  <div class="field mt-2"><label class="field-label" for="pwd2">Confirm password</label><input id="pwd2" type="password" autocomplete="new-password"></div>
</div>'''
    if tid == "unlock-pdf":
        return '''<div class="options-panel"><h3>Current password</h3>
  <div class="field"><label class="field-label" for="pwd">PDF password</label><input id="pwd" type="password" autocomplete="current-password"></div>
</div>'''
    if tid == "watermark-pdf":
        return '''<div class="options-panel"><h3>Watermark</h3>
  <div class="field"><label class="field-label" for="wm">Text</label><input id="wm" type="text" placeholder="CONFIDENTIAL"></div>
  <div class="field mt-2"><label class="field-label" for="opacity">Opacity</label>
    <div class="range-wrap" data-range><input id="opacity" type="range" min="10" max="80" value="30" data-suffix="%"><span class="range-value" data-range-value>30%</span></div>
  </div>
</div>'''
    if tid == "add-page-numbers":
        return '''<div class="options-panel"><h3>Page numbers</h3>
  <div class="field-row cols-2">
    <div class="field"><label class="field-label" for="pos">Position</label>
      <select id="pos"><option>Bottom center</option><option>Bottom right</option><option>Top center</option></select></div>
    <div class="field"><label class="field-label" for="start">Start at</label><input id="start" type="number" value="1" min="1"></div>
  </div>
</div>'''
    if tid == "convert-image":
        return '''<div class="options-panel"><h3>Output format</h3>
  <div class="radio-row">
    <label class="radio-pill"><input type="radio" name="fmt" value="jpg" checked><span>JPG</span></label>
    <label class="radio-pill"><input type="radio" name="fmt" value="png"><span>PNG</span></label>
    <label class="radio-pill"><input type="radio" name="fmt" value="webp"><span>WebP</span></label>
  </div></div>'''
    return ""


def process_label(t):
    mapping = {
        "compress-pdf": "Compress PDF",
        "merge-pdf": "Merge PDFs",
        "jpg-to-pdf": "Create PDF",
        "image-to-pdf": "Create PDF",
        "compress-image": "Compress Image",
        "resize-image": "Resize Image",
        "background-remover": "Remove Background",
        "image-to-text": "Extract Text",
    }
    return mapping.get(t["id"], f"Run {t['name']}")


def tool_faqs(t):
    name = t["name"]
    return [
        (f"Is {name} free to use?", f"The FileNova interface for {name} is free to explore. Actual file processing will be enabled when the backend is connected."),
        (f"Does my file leave this browser?", "This static demo keeps selected files in your browser memory for preview only. No upload occurs until a processing API is configured."),
        ("Which formats are supported?", f"This tool is designed for: {t['formats']}. Additional formats can be added later."),
        ("Can I use this on mobile?", "Yes. The upload UI and layout are responsive for phones, tablets, and desktops."),
        ("When will real processing work?", "Buttons show a clear frontend-only message today. Phase 2 will connect a secure processing API."),
    ]


def write(path: Path, content: str):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")
    print("wrote", path.relative_to(ROOT))


def gen_index():
    depth = ""
    popular = [t for t in TOOLS if t["id"] in ("compress-pdf", "merge-pdf", "jpg-to-pdf", "compress-image", "resize-image", "pdf-to-word", "split-pdf", "convert-image")]
    cards = "\n".join(tool_card(t, depth) for t in popular)
    chips = "".join(f'<a class="chip" href="{t["path"]}">{escape(t["name"])}</a>' for t in TOOLS if t["id"] in ("compress-pdf", "merge-pdf", "jpg-to-pdf", "compress-image", "resize-image"))
    cat_btns = "".join(
        f'<button type="button" class="cat-btn{" is-active" if c=="all" else ""}" data-cat-filter="{c}" aria-pressed="{"true" if c=="all" else "false"}">{label}</button>'
        for c, label in [("all", "All Tools"), ("pdf", "PDF"), ("convert", "Convert"), ("compress", "Compress"), ("images", "Images"), ("security", "Security"), ("ocr", "OCR"), ("utilities", "Utilities")]
    )
    all_cards = "\n".join(tool_card(t, depth) for t in TOOLS)
    html = head(
        f"{BRAND} — Online PDF & Image Tools",
        "Convert, compress, resize and manage your documents and images with simple online tools.",
        "index.html",
        depth=depth,
    ) + f'''<body>
{header(depth, "home")}
<main id="main">
  <section class="hero">
    <div class="container hero-grid">
      <div>
        <p class="hero-eyebrow">FileNova · PDF &amp; Image Toolkit</p>
        <h1>Everything You Need for Your Files</h1>
        <p class="hero-lead">Convert, compress, resize and manage your documents and images with simple online tools.</p>
        <form class="hero-search" data-hero-search role="search">
          <label class="sr-only" for="hero-q">Search for a tool</label>
          <input id="hero-q" type="search" placeholder="Search for a tool..." autocomplete="off">
          <button class="btn btn-primary" type="submit">Search</button>
        </form>
        <div class="popular-chips" aria-label="Popular tools">{chips}</div>
      </div>
      <div class="hero-visual" aria-hidden="true">
        <div class="illustration-stage">
          <div class="orbit-ring"></div>
          <div class="float-card pdf"><div class="icon-wrap">{ICON_SVGS["pdf"]}</div><strong>PDF Ready</strong><span>Documents</span></div>
          <div class="float-card image"><div class="icon-wrap">{ICON_SVGS["image"]}</div><strong>Images</strong><span>JPG · PNG · WebP</span></div>
          <div class="float-card compress"><div class="icon-wrap">{ICON_SVGS["convert"]}</div><strong>Compressing</strong><div class="progress-bar"><i></i></div></div>
          <div class="float-card convert"><div class="icon-wrap">{ICON_SVGS["convert"]}</div><strong>Convert</strong><span>Fast pipeline</span></div>
        </div>
      </div>
    </div>
  </section>
  <div class="container"><div class="ad-slot ad-top">Advertisement</div></div>
  <section class="section">
    <div class="container">
      <div class="section-header">
        <div><h2>Popular Tools</h2><p>Start with the workflows people open most often.</p></div>
        <a class="btn btn-secondary" href="tools.html">View all tools</a>
      </div>
      <div class="tool-grid cols-4">{cards}</div>
    </div>
  </section>
  <section class="section section-alt">
    <div class="container">
      <div class="section-header">
        <div><h2>Browse by Category</h2><p>Filter the full toolkit without leaving the page.</p></div>
      </div>
      <div class="category-filters">{cat_btns}</div>
      <div class="tool-grid" data-tool-grid>{all_cards}<p class="no-results hidden" data-no-results>No tools match your filters.</p></div>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="section-header"><div><h2>How FileNova Works</h2><p>A clear three-step path from upload to download.</p></div></div>
      <div class="steps">
        <div class="step-card"><div class="step-num">1</div><h3>Choose a tool</h3><p class="text-muted">Pick a PDF or image utility that matches your task.</p></div>
        <div class="step-card"><div class="step-num">2</div><h3>Add your files</h3><p class="text-muted">Drag and drop or browse—preview details before you continue.</p></div>
        <div class="step-card"><div class="step-num">3</div><h3>Process &amp; download</h3><p class="text-muted">Run the tool when processing is connected, then save the result.</p></div>
      </div>
    </div>
  </section>
  <section class="section section-alt">
    <div class="container">
      <div class="cta-band">
        <div><h2>Need the full catalog?</h2><p>Explore every converter, compressor, and utility in one place.</p></div>
        <a class="btn btn-primary btn-lg" href="tools.html">Browse all tools</a>
      </div>
    </div>
  </section>
</main>
{footer(depth)}
</body></html>'''
    write(ROOT / "index.html", html)


def gen_tools_page():
    depth = ""
    cat_btns = "".join(
        f'<button type="button" class="cat-btn{" is-active" if c=="all" else ""}" data-cat-filter="{c}" aria-pressed="{"true" if c=="all" else "false"}">{label}</button>'
        for c, label in [("all", "All Tools"), ("pdf", "PDF"), ("convert", "Convert"), ("compress", "Compress"), ("images", "Images"), ("security", "Security"), ("ocr", "OCR"), ("utilities", "Utilities")]
    )
    cards = "\n".join(tool_card(t, depth) for t in TOOLS)
    html = head(
        f"All File Tools – {BRAND}",
        "Browse every FileNova PDF and image tool. Search and filter by category.",
        "tools.html",
    ) + f'''<body>
{header(depth, "tools")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">All Tools</span></nav>
    <h1>All File Tools</h1>
    <p class="lead">Search the catalog or filter by category to find the right utility.</p>
  </div></div>
  <div class="container"><div class="ad-slot ad-top">Advertisement</div></div>
  <section class="section"><div class="container">
    <div class="tools-toolbar">
      <div class="tools-search">{ICON_SVGS["search"]}<label class="sr-only" for="tool-search">Search tools</label>
        <input id="tool-search" type="search" placeholder="Search tools (e.g. compress, pdf)…" data-tool-search></div>
    </div>
    <div class="category-filters">{cat_btns}</div>
    <div class="tool-grid" data-tool-grid>{cards}<p class="no-results hidden" data-no-results>No tools match your search.</p></div>
  </div></section>
</main>
{footer(depth)}
</body></html>'''
    write(ROOT / "tools.html", html)


def gen_tool_page(t):
    depth = "../"
    path = t["path"]
    multi = ' data-multi' if t.get("multi") else ""
    btn = process_label(t)
    faqs = faq_block(tool_faqs(t))
    options = tool_options(t)
    preview = '<div class="hidden" data-image-preview></div>' if t.get("images") and not t.get("multi") else ""
    how = f'''<section class="section section-alt"><div class="container content-block">
  <h2>How to {escape(t["name"])}</h2>
  <ol style="list-style:decimal;padding-left:1.25rem;color:var(--text-secondary)">
    <li style="margin-bottom:0.5rem">Open the {escape(t["name"])} tool and add your file{ "s" if t.get("multi") else "" }.</li>
    <li style="margin-bottom:0.5rem">Adjust options if needed, then review the on-screen preview details.</li>
    <li style="margin-bottom:0.5rem">Click “{escape(btn)}”. Real processing will connect in a future release.</li>
  </ol>
  <div class="ad-slot ad-content">Advertisement</div>
  <h2>Why Use Our {escape(t["name"])}?</h2>
  <p>{escape(t["desc"])} FileNova focuses on a calm, readable interface so you can finish file tasks without clutter.</p>
  <ul>
    <li>Clear upload and file details before you proceed</li>
    <li>Mobile-friendly controls and keyboard-accessible actions</li>
    <li>Structured for a future secure processing API</li>
  </ul>
  <h2>Supported Formats</h2>
  <p>{escape(t["formats"])}</p>
  <h2>Frequently Asked Questions</h2>
  {faqs}
</div></section>'''
    html = head(
        f"{t['name']} Online – {BRAND}",
        f"{t['desc']} Use {BRAND} {t['name']} in your browser.",
        path,
        depth=depth,
    ) + f'''<body>
{header(depth)}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="{depth}index.html">Home</a><span>/</span>
      <a href="{depth}tools.html">Tools</a><span>/</span>
      <span aria-current="page">{escape(t["name"])}</span>
    </nav>
    <h1>{escape(t["name"])} Online</h1>
    <p class="lead">{escape(t["desc"])}</p>
  </div></div>
  <div class="container tool-layout">
    <div class="tool-main">
      <div class="ad-slot ad-top">Advertisement</div>
      <div data-upload data-tool-id="{t["id"]}"{multi}>
        <div class="upload-box" data-dropzone role="button" tabindex="0" aria-label="Upload file">
          <div class="upload-icon">{ICON_SVGS["upload"]}</div>
          <h2>Drop your file{"s" if t.get("multi") else ""} here</h2>
          <p>or click to browse from your device</p>
          <button type="button" class="btn btn-primary">Choose File{"s" if t.get("multi") else ""}</button>
          <p class="hint">Supported formats: {escape(t["formats"])}</p>
          <input class="file-input-hidden" type="file" accept="{t["accept"]}"{" multiple" if t.get("multi") else ""} aria-hidden="true" tabindex="-1">
        </div>
        <div class="file-list hidden mt-2" data-file-selected></div>
        {preview}
        {options}
        <div class="mt-3">
          <button type="button" class="btn btn-primary btn-lg" data-process>{escape(btn)}</button>
        </div>
        <div class="processing-ui hidden mt-3" data-processing>
          <div class="spinner" aria-hidden="true"></div>
          <p data-process-label>Uploading…</p>
          <ul class="processing-steps">
            <li data-step>Uploading</li>
            <li data-step>Processing</li>
            <li data-step>Completed</li>
          </ul>
        </div>
        <div class="hidden mt-3" data-result></div>
        <div class="notice mt-3">Frontend demo only — no file is uploaded or transformed yet. Processing will be connected in a future version.</div>
      </div>
      <div class="ad-slot ad-tool">Advertisement</div>
    </div>
    <aside class="sidebar-sticky" aria-label="Sidebar">
      <div class="ad-slot ad-sidebar">Advertisement</div>
      <div class="options-panel">
        <h3>Quick tips</h3>
        <p class="text-muted" style="font-size:0.9rem">Keep originals until you confirm the result. Large files may take longer once the API is live.</p>
      </div>
    </aside>
  </div>
  {how}
  {related_tools(t["id"], depth)}
  <div class="container"><div class="ad-slot ad-bottom">Advertisement</div></div>
</main>
{footer(depth)}
</body></html>'''
    write(ROOT / path, html)


def gen_about():
    html = head(f"About {BRAND} – Online File Tools", f"Learn about {BRAND}, a focused toolkit for PDF and image tasks.", "about.html") + f'''<body>
{header("", "about")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">About</span></nav>
    <h1>About FileNova</h1>
    <p class="lead">A calm, modern toolkit for everyday PDF and image jobs—designed to grow from static UI into a full processing platform.</p>
  </div></div>
  <section class="section"><div class="container content-block prose">
    <p>FileNova is a productivity-focused collection of online utilities for documents and images. The brand emphasizes clarity: strong typography, purposeful color, and interfaces that stay out of the way.</p>
    <h2>What we build</h2>
    <p>Converters, compressors, and helpers for PDFs and images. Each tool page shares the same upload patterns so learning one tool teaches you the rest.</p>
    <h2>Current phase</h2>
    <p>This release is a complete static frontend. File selection, previews, and workflow UI are real; server-side processing will connect later without redesigning the product.</p>
    <h2>Values</h2>
    <ul>
      <li>Respect for your attention—no noisy layouts</li>
      <li>Accessibility and keyboard-friendly controls</li>
      <li>Honest messaging when features are still frontend-only</li>
    </ul>
  </div></section>
</main>
{footer()}
</body></html>'''
    write(ROOT / "about.html", html)


def gen_contact():
    html = head(f"Contact {BRAND}", "Reach the FileNova team with product questions or feedback.", "contact.html") + f'''<body>
{header("")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">Contact</span></nav>
    <h1>Contact</h1>
    <p class="lead">Questions about FileNova? Send a message—form delivery will be wired in a later release.</p>
  </div></div>
  <section class="section"><div class="container contact-grid">
    <form class="form-card" data-contact-form>
      <div class="form-group"><label for="name">Name</label><input id="name" name="name" required autocomplete="name"></div>
      <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email"></div>
      <div class="form-group"><label for="topic">Topic</label>
        <select id="topic" name="topic"><option>General</option><option>Feature request</option><option>Bug report</option><option>Partnership</option></select>
      </div>
      <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" required></textarea></div>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
    <div>
      <ul class="info-list">
        <li><strong>Support</strong>hello@filenova.example</li>
        <li><strong>Press</strong>press@filenova.example</li>
        <li><strong>Response time</strong>We aim to reply within 2 business days once email is connected.</li>
      </ul>
    </div>
  </div></section>
</main>
{footer()}
</body></html>'''
    write(ROOT / "contact.html", html)


def gen_privacy():
    html = head(f"Privacy Policy – {BRAND}", "How FileNova handles information in this static frontend release.", "privacy.html") + f'''<body>
{header("")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">Privacy</span></nav>
    <h1>Privacy Policy</h1>
    <p class="lead">Last updated: March 1, 2026</p>
  </div></div>
  <div class="container legal-content">
    <p>FileNova currently ships as a static website. Theme preference may be stored in your browser via localStorage. File selections for demos stay in memory and are not uploaded.</p>
    <h2>Information we collect</h2>
    <p>When backend services launch, we may process files you intentionally submit and basic technical logs needed for reliability and abuse prevention.</p>
    <h2>Cookies &amp; ads</h2>
    <p>Ad slots are placeholders. If advertising networks are added later, this policy will describe cookies and controls in detail.</p>
    <h2>Contact</h2>
    <p>Privacy questions: privacy@filenova.example</p>
  </div>
</main>
{footer()}
</body></html>'''
    write(ROOT / "privacy.html", html)


def gen_terms():
    html = head(f"Terms of Use – {BRAND}", "Terms governing use of the FileNova static website and future services.", "terms.html") + f'''<body>
{header("")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">Terms</span></nav>
    <h1>Terms of Use</h1>
    <p class="lead">Last updated: March 1, 2026</p>
  </div></div>
  <div class="container legal-content">
    <p>By using FileNova you agree to these terms. The current site is a frontend demonstration; processing features may be incomplete.</p>
    <h2>Acceptable use</h2>
    <ul>
      <li>Do not attempt to disrupt or misuse the service</li>
      <li>Do not upload unlawful content when processing is enabled</li>
      <li>Respect intellectual property rights</li>
    </ul>
    <h2>Disclaimer</h2>
    <p>Tools are provided “as is”. Demo actions do not produce real converted files until a processing backend is connected.</p>
    <h2>Contact</h2>
    <p>legal@filenova.example</p>
  </div>
</main>
{footer()}
</body></html>'''
    write(ROOT / "terms.html", html)


def blog_body(slug):
    bodies = {
        "how-to-compress-pdf": '''
<p>Large PDF attachments slow down email and eat storage. Compression reduces bulk while aiming to keep text readable and images usable.</p>
<h2>Pick the right balance</h2>
<p>High compression is ideal for drafts and archives. Balanced mode suits everyday sharing. Best quality keeps presentation decks looking sharp.</p>
<h2>Steps</h2>
<ol>
  <li>Open the <a href="../pdf-tools/compress-pdf.html">Compress PDF</a> tool.</li>
  <li>Add your PDF and choose a compression mode.</li>
  <li>Review the estimated size panel, then run compression when the API is available.</li>
</ol>
<h2>Tips</h2>
<ul>
  <li>Scanned PDFs compress differently than text-heavy exports.</li>
  <li>Keep a master copy before aggressive compression.</li>
</ul>
''',
        "how-to-merge-pdf": '''
<p>Merging PDFs turns proposals, receipts, or chapter files into one shareable packet.</p>
<h2>Steps</h2>
<ol>
  <li>Open <a href="../pdf-tools/merge-pdf.html">Merge PDF</a>.</li>
  <li>Select multiple PDFs and reorder them by dragging.</li>
  <li>Confirm the sequence, then merge when processing is connected.</li>
</ol>
<h2>Quality checklist</h2>
<p>Ensure page sizes are consistent when possible, and avoid merging encrypted files until you unlock them.</p>
''',
        "jpg-to-pdf-guide": '''
<p>Photographers, students, and office teams often need a PDF from a stack of JPG or PNG files.</p>
<h2>Layout choices</h2>
<p>Choose A4 or Letter for documents, or fit-to-image for full-bleed photos. Small margins help scans look tidy.</p>
<ol>
  <li>Visit <a href="../pdf-tools/jpg-to-pdf.html">JPG to PDF</a>.</li>
  <li>Add images, reorder thumbnails, set page size and orientation.</li>
  <li>Create the PDF when backend processing is live.</li>
</ol>
''',
        "reduce-image-size": '''
<p>Web pages and chat apps reward smaller images. Reducing size is a mix of dimensions and compression quality.</p>
<h2>Workflow</h2>
<ol>
  <li>Resize to the largest dimension you actually need.</li>
  <li>Compress with a quality slider that still looks good on a phone screen.</li>
  <li>Prefer WebP for modern browsers when transparency is not required.</li>
</ol>
<p>Try <a href="../image-tools/compress-image.html">Compress Image</a> and <a href="../image-tools/resize-image.html">Resize Image</a>.</p>
''',
        "what-is-webp": '''
<p>WebP is an image format designed for efficient lossy and lossless compression on the web.</p>
<h2>Why it helps</h2>
<p>Many photos become meaningfully smaller than JPG at similar visual quality, which improves load time.</p>
<h2>When to convert</h2>
<ul>
  <li>Marketing sites and blogs that already serve modern browsers</li>
  <li>Product galleries with dozens of photos per page</li>
</ul>
<p>Use <a href="../image-tools/jpg-to-webp.html">JPG to WebP</a> or <a href="../image-tools/png-to-webp.html">PNG to WebP</a> to prepare assets.</p>
''',
    }
    return bodies[slug]


def gen_blog():
    cards = []
    for b in BLOG:
        cards.append(f'''<a class="blog-card" href="blog/{b["slug"]}.html">
  <div class="blog-card-cover">Guide</div>
  <div class="blog-card-body">
    <div class="meta">{b["date"]} · {b["read"]} read</div>
    <h3>{escape(b["title"])}</h3>
    <p>{escape(b["excerpt"])}</p>
    <span class="arrow-link">Read article {ICON_SVGS["arrow"]}</span>
  </div>
</a>''')
    html = head(f"Blog – {BRAND} Guides", "Practical guides for PDF compression, merges, image size, and WebP.", "blog.html") + f'''<body>
{header("")}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.html">Home</a><span>/</span><span aria-current="page">Blog</span></nav>
    <h1>FileNova Blog</h1>
    <p class="lead">Short, practical guides for documents and images.</p>
  </div></div>
  <section class="section"><div class="container"><div class="blog-grid">{"".join(cards)}</div></div></section>
</main>
{footer()}
</body></html>'''
    write(ROOT / "blog.html", html)

    for b in BLOG:
        depth = "../"
        body = blog_body(b["slug"])
        html = head(f'{b["title"]} – {BRAND}', b["excerpt"], f'blog/{b["slug"]}.html', depth=depth) + f'''<body>
{header(depth)}
<main id="main">
  <div class="page-hero"><div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="{depth}index.html">Home</a><span>/</span>
      <a href="{depth}blog.html">Blog</a><span>/</span>
      <span aria-current="page">{escape(b["title"])}</span>
    </nav>
    <h1>{escape(b["title"])}</h1>
    <div class="article-meta"><span>{b["date"]}</span><span>{b["read"]} read</span></div>
  </div></div>
  <article class="section"><div class="container prose">{body}
    <div class="ad-slot ad-content">Advertisement</div>
  </div></article>
</main>
{footer(depth)}
</body></html>'''
        write(ROOT / "blog" / f'{b["slug"]}.html', html)


def gen_favicon():
    svg = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none">
  <rect width="32" height="32" rx="8" fill="#0f766e"/>
  <path d="M8 12h10a4 4 0 0 1 0 8h-6" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
  <path d="M12 20l-4 4" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
  <path d="M18 12V9a1 1 0 0 1 1-1h5" stroke="#99f6e4" stroke-width="2" stroke-linecap="round"/>
</svg>'''
    write(ROOT / "assets/icons/favicon.svg", svg)


def gen_readme():
    content = '''# FileNova

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
'''
    write(ROOT / "README.md", content)


def main():
    gen_favicon()
    gen_index()
    gen_tools_page()
    for t in TOOLS:
        gen_tool_page(t)
    gen_about()
    gen_contact()
    gen_privacy()
    gen_terms()
    gen_blog()
    gen_readme()
    print("Done.", len(TOOLS), "tools,", len(BLOG), "articles")


if __name__ == "__main__":
    main()
