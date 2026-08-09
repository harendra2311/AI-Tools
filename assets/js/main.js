/**
 * FileNova — Main JavaScript
 * Modular frontend utilities. Processing hooks are stubs for Phase 2 API.
 */
(function () {
  'use strict';

  const BRAND = 'FileNova';
  const THEME_KEY = 'filenova-theme';

  /* ---------- Tool catalog (search / filter) ---------- */
  const TOOLS = [
    { id: 'compress-pdf', name: 'Compress PDF', desc: 'Reduce PDF size while keeping quality.', cat: ['pdf', 'compress'], path: 'pdf-tools/compress-pdf.html', icon: 'pdf' },
    { id: 'merge-pdf', name: 'Merge PDF', desc: 'Combine multiple PDF files into one.', cat: ['pdf', 'utilities'], path: 'pdf-tools/merge-pdf.html', icon: 'pdf' },
    { id: 'split-pdf', name: 'Split PDF', desc: 'Extract pages or split into multiple files.', cat: ['pdf', 'utilities'], path: 'pdf-tools/split-pdf.html', icon: 'pdf' },
    { id: 'rotate-pdf', name: 'Rotate PDF', desc: 'Rotate pages left or right in bulk.', cat: ['pdf', 'utilities'], path: 'pdf-tools/rotate-pdf.html', icon: 'pdf' },
    { id: 'pdf-to-word', name: 'PDF to Word', desc: 'Convert PDF documents into editable Word files.', cat: ['pdf', 'convert'], path: 'pdf-tools/pdf-to-word.html', icon: 'convert' },
    { id: 'pdf-to-jpg', name: 'PDF to JPG', desc: 'Export PDF pages as JPG images.', cat: ['pdf', 'convert', 'images'], path: 'pdf-tools/pdf-to-jpg.html', icon: 'convert' },
    { id: 'pdf-to-png', name: 'PDF to PNG', desc: 'Export PDF pages as PNG images.', cat: ['pdf', 'convert', 'images'], path: 'pdf-tools/pdf-to-png.html', icon: 'convert' },
    { id: 'jpg-to-pdf', name: 'JPG to PDF', desc: 'Convert images into a PDF document.', cat: ['pdf', 'convert', 'images'], path: 'pdf-tools/jpg-to-pdf.html', icon: 'convert' },
    { id: 'word-to-pdf', name: 'Word to PDF', desc: 'Turn Word documents into PDF files.', cat: ['pdf', 'convert'], path: 'pdf-tools/word-to-pdf.html', icon: 'convert' },
    { id: 'excel-to-pdf', name: 'Excel to PDF', desc: 'Export spreadsheets to PDF.', cat: ['pdf', 'convert'], path: 'pdf-tools/excel-to-pdf.html', icon: 'convert' },
    { id: 'protect-pdf', name: 'Protect PDF', desc: 'Add a password to lock your PDF.', cat: ['pdf', 'security'], path: 'pdf-tools/protect-pdf.html', icon: 'security' },
    { id: 'unlock-pdf', name: 'Unlock PDF', desc: 'Remove password protection when you know it.', cat: ['pdf', 'security'], path: 'pdf-tools/unlock-pdf.html', icon: 'security' },
    { id: 'watermark-pdf', name: 'Watermark PDF', desc: 'Stamp text or image watermarks on pages.', cat: ['pdf', 'utilities'], path: 'pdf-tools/watermark-pdf.html', icon: 'pdf' },
    { id: 'add-page-numbers', name: 'Add Page Numbers', desc: 'Number PDF pages with flexible placement.', cat: ['pdf', 'utilities'], path: 'pdf-tools/add-page-numbers.html', icon: 'pdf' },
    { id: 'compress-image', name: 'Compress Image', desc: 'Reduce image size for web and sharing.', cat: ['images', 'compress'], path: 'image-tools/compress-image.html', icon: 'image' },
    { id: 'resize-image', name: 'Resize Image', desc: 'Change image dimensions precisely.', cat: ['images', 'utilities'], path: 'image-tools/resize-image.html', icon: 'image' },
    { id: 'convert-image', name: 'Convert Image', desc: 'Switch between popular image formats.', cat: ['images', 'convert'], path: 'image-tools/convert-image.html', icon: 'convert' },
    { id: 'jpg-to-webp', name: 'JPG to WebP', desc: 'Convert JPG photos to modern WebP.', cat: ['images', 'convert'], path: 'image-tools/jpg-to-webp.html', icon: 'convert' },
    { id: 'png-to-webp', name: 'PNG to WebP', desc: 'Convert PNG graphics to WebP.', cat: ['images', 'convert'], path: 'image-tools/png-to-webp.html', icon: 'convert' },
    { id: 'webp-to-jpg', name: 'WebP to JPG', desc: 'Convert WebP images to JPG.', cat: ['images', 'convert'], path: 'image-tools/webp-to-jpg.html', icon: 'convert' },
    { id: 'webp-to-png', name: 'WebP to PNG', desc: 'Convert WebP images to PNG.', cat: ['images', 'convert'], path: 'image-tools/webp-to-png.html', icon: 'convert' },
    { id: 'image-to-pdf', name: 'Image to PDF', desc: 'Build a PDF from one or more images.', cat: ['images', 'convert', 'pdf'], path: 'image-tools/image-to-pdf.html', icon: 'convert' },
    { id: 'image-to-text', name: 'Image to Text', desc: 'Extract text from images with OCR.', cat: ['images', 'ocr'], path: 'image-tools/image-to-text.html', icon: 'ocr' },
    { id: 'background-remover', name: 'Background Remover', desc: 'Isolate subjects by removing backgrounds.', cat: ['images', 'utilities'], path: 'image-tools/background-remover.html', icon: 'image' }
  ];

  window.FileNova = window.FileNova || {};
  window.FileNova.TOOLS = TOOLS;
  window.FileNova.BRAND = BRAND;

  /* ---------- Path helpers ---------- */
  function getDepth() {
    const path = window.location.pathname.replace(/\\/g, '/');
    const parts = path.split('/').filter(Boolean);
    // If file is in a subfolder (pdf-tools, image-tools, blog)
    if (parts.length >= 2 && ['pdf-tools', 'image-tools', 'blog'].includes(parts[parts.length - 2])) {
      return '../';
    }
    return '';
  }

  function resolvePath(rel) {
    return getDepth() + rel;
  }

  window.FileNova.resolvePath = resolvePath;

  /* ---------- Theme ---------- */
  function initTheme() {
    const stored = localStorage.getItem(THEME_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem(THEME_KEY, next);
      });
    });
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
      btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('title', theme === 'dark' ? 'Light mode' : 'Dark mode');
    });
  }

  /* ---------- Header scroll ---------- */
  function initHeaderScroll() {
    const header = document.querySelector('.site-header');
    if (!header) return;
    const onScroll = () => {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Mobile menu ---------- */
  function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const nav = document.querySelector('[data-mobile-nav]');
    if (!toggle || !nav) return;

    const close = () => {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('nav-open');
    };

    toggle.addEventListener('click', () => {
      const open = !nav.classList.contains('is-open');
      nav.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      document.body.classList.toggle('nav-open', open);
    });

    nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });
  }

  /* ---------- Global search overlay ---------- */
  function initSearchOverlay() {
    const overlay = document.querySelector('[data-search-overlay]');
    const openBtns = document.querySelectorAll('[data-search-open]');
    const input = overlay && overlay.querySelector('[data-search-input]');
    const results = overlay && overlay.querySelector('[data-search-results]');
    if (!overlay || !input || !results) return;

    const open = () => {
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      setTimeout(() => input.focus(), 50);
    };
    const close = () => {
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      input.value = '';
      results.innerHTML = '';
    };

    openBtns.forEach((b) => b.addEventListener('click', open));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open();
      }
    });

    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      if (!q) {
        results.innerHTML = '';
        return;
      }
      const matches = TOOLS.filter(
        (t) =>
          t.name.toLowerCase().includes(q) ||
          t.desc.toLowerCase().includes(q) ||
          t.cat.some((c) => c.includes(q)) ||
          t.id.includes(q)
      ).slice(0, 8);
      results.innerHTML = matches.length
        ? matches
            .map(
              (t) =>
                `<a class="search-result-item" href="${resolvePath(t.path)}"><div><strong>${escapeHtml(t.name)}</strong><div class="meta">${escapeHtml(t.desc)}</div></div></a>`
            )
            .join('')
        : '<p class="text-muted" style="padding:0.75rem">No tools found.</p>';
    });
  }

  /* ---------- Tool grid filtering ---------- */
  function initToolFilters() {
    const grid = document.querySelector('[data-tool-grid]');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('[data-tool-card]'));
    const search = document.querySelector('[data-tool-search]');
    const cats = document.querySelectorAll('[data-cat-filter]');
    const empty = document.querySelector('[data-no-results]');
    let activeCat = 'all';

    const apply = () => {
      const q = (search && search.value.trim().toLowerCase()) || '';
      let visible = 0;
      cards.forEach((card) => {
        const name = (card.dataset.name || '').toLowerCase();
        const desc = (card.dataset.desc || '').toLowerCase();
        const catsAttr = (card.dataset.categories || '').toLowerCase();
        const matchQ = !q || name.includes(q) || desc.includes(q) || catsAttr.includes(q);
        const matchCat = activeCat === 'all' || catsAttr.split(/\s+/).includes(activeCat);
        const show = matchQ && matchCat;
        card.hidden = !show;
        if (show) visible += 1;
      });
      if (empty) empty.hidden = visible > 0;
    };

    if (search) search.addEventListener('input', apply);
    cats.forEach((btn) => {
      btn.addEventListener('click', () => {
        activeCat = btn.dataset.catFilter;
        cats.forEach((b) => {
          b.classList.toggle('is-active', b === btn);
          b.setAttribute('aria-pressed', String(b === btn));
        });
        apply();
      });
    });

    // Pre-fill from URL ?q=
    const params = new URLSearchParams(window.location.search);
    if (params.get('q') && search) {
      search.value = params.get('q');
    }
    if (params.get('cat')) {
      activeCat = params.get('cat');
      cats.forEach((b) => {
        const on = b.dataset.catFilter === activeCat;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });
    }
    apply();
  }

  /* ---------- Hero search ---------- */
  function initHeroSearch() {
    const form = document.querySelector('[data-hero-search]');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = form.querySelector('input').value.trim();
      window.location.href = resolvePath('tools.html') + (q ? '?q=' + encodeURIComponent(q) : '');
    });
  }

  /* ---------- FAQ accordion ---------- */
  function initFAQ() {
    document.querySelectorAll('[data-faq]').forEach((list) => {
      list.querySelectorAll('.faq-item').forEach((item) => {
        const btn = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        if (!btn || !answer) return;
        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', () => {
          const open = item.classList.contains('is-open');
          // Allow multiple open; toggle current
          item.classList.toggle('is-open', !open);
          btn.setAttribute('aria-expanded', String(!open));
        });
      });
    });
  }

  /* ---------- Toast ---------- */
  function ensureToastStack() {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    return stack;
  }

  function toast(message, type) {
    const stack = ensureToastStack();
    const el = document.createElement('div');
    el.className = 'toast' + (type ? ' is-' + type : '');
    el.textContent = message;
    stack.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 250);
    }, 4200);
  }

  window.FileNova.toast = toast;

  /* ---------- Utils ---------- */
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let n = bytes;
    while (n >= 1024 && i < units.length - 1) {
      n /= 1024;
      i += 1;
    }
    return (i === 0 ? n : n.toFixed(n < 10 ? 2 : 1)) + ' ' + units[i];
  }

  function getExt(name) {
    const i = name.lastIndexOf('.');
    return i >= 0 ? name.slice(i + 1).toUpperCase() : 'FILE';
  }

  window.FileNova.formatBytes = formatBytes;

  /* ---------- Processing simulation (no real output) ---------- */
  function simulateProcessing(container, onDone) {
    if (!container) return;
    container.classList.remove('hidden');
    const steps = container.querySelectorAll('[data-step]');
    const label = container.querySelector('[data-process-label]');
    const labels = ['Uploading…', 'Processing…', 'Finalizing…'];
    let i = 0;
    steps.forEach((s) => s.classList.remove('is-active', 'is-done'));
    const tick = () => {
      if (i > 0) steps[i - 1] && steps[i - 1].classList.replace('is-active', 'is-done');
      if (i < steps.length) {
        steps[i].classList.add('is-active');
        if (label) label.textContent = labels[i] || 'Working…';
        i += 1;
        setTimeout(tick, 700);
      } else {
        steps.forEach((s) => {
          s.classList.remove('is-active');
          s.classList.add('is-done');
        });
        if (label) label.textContent = 'Ready (demo)';
        if (onDone) onDone();
      }
    };
    tick();
  }

  /* ---------- UploadBox (single file) ---------- */
  function initUploadBoxes() {
    document.querySelectorAll('[data-upload]').forEach((root) => {
      const input = root.querySelector('input[type="file"]');
      const drop = root.querySelector('[data-dropzone]');
      const selected = root.querySelector('[data-file-selected]');
      const multi = root.hasAttribute('data-multi');
      const accept = (input && input.accept) || '';
      let files = [];

      if (!input || !drop) return;

      const render = () => {
        if (!selected) return;
        if (!files.length) {
          selected.classList.add('hidden');
          selected.innerHTML = '';
          drop.classList.remove('hidden');
          return;
        }
        drop.classList.add('hidden');
        selected.classList.remove('hidden');
        selected.innerHTML = files
          .map((f, idx) => {
            const isImage = /^image\//.test(f.type);
            const thumbId = 'thumb-' + idx;
            return `<div class="file-item" data-idx="${idx}" draggable="${multi ? 'true' : 'false'}">
              ${isImage ? `<img class="file-thumb" id="${thumbId}" alt="">` : `<div class="file-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>`}
              <div class="file-meta"><strong>${escapeHtml(f.name)}</strong><span>${formatBytes(f.size)} · ${getExt(f.name)}</span></div>
              <button type="button" class="btn btn-ghost btn-sm" data-remove="${idx}" aria-label="Remove ${escapeHtml(f.name)}">Remove</button>
            </div>`;
          })
          .join('');

        files.forEach((f, idx) => {
          if (/^image\//.test(f.type)) {
            const img = selected.querySelector('#thumb-' + idx);
            if (img) {
              const reader = new FileReader();
              reader.onload = (e) => {
                img.src = e.target.result;
              };
              reader.readAsDataURL(f);
            }
          }
        });

        // Preview panel for single image tools
        const preview = root.querySelector('[data-image-preview]');
        if (preview && files[0] && /^image\//.test(files[0].type)) {
          const reader = new FileReader();
          reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
              preview.innerHTML = `<div class="image-preview-wrap"><img src="${e.target.result}" alt="Selected preview"></div>
                <div class="stats-row">
                  <div class="stat-box"><strong>${escapeHtml(files[0].name)}</strong><span>Filename</span></div>
                  <div class="stat-box"><strong>${formatBytes(files[0].size)}</strong><span>File size</span></div>
                  <div class="stat-box"><strong>${img.naturalWidth}×${img.naturalHeight}</strong><span>Dimensions</span></div>
                </div>`;
              preview.classList.remove('hidden');
              root.dispatchEvent(new CustomEvent('filenova:image-meta', { detail: { width: img.naturalWidth, height: img.naturalHeight, file: files[0] } }));
            };
            img.src = e.target.result;
          };
          reader.readAsDataURL(files[0]);
        }

        // Compress PDF estimate panel
        const estimate = root.querySelector('[data-size-estimate]');
        if (estimate && files[0]) {
          const original = files[0].size;
          const mode = (root.querySelector('input[name="compress-mode"]:checked') || {}).value || 'balanced';
          const factors = { high: 0.35, balanced: 0.55, quality: 0.78 };
          const estimated = Math.round(original * (factors[mode] || 0.55));
          const reduction = Math.round((1 - estimated / original) * 100);
          estimate.classList.remove('hidden');
          estimate.innerHTML = `<div class="stats-row">
            <div class="stat-box"><strong>${formatBytes(original)}</strong><span>Original size</span></div>
            <div class="stat-box"><strong>${formatBytes(estimated)}</strong><span>Estimated size</span></div>
            <div class="stat-box"><strong>−${reduction}%</strong><span>Estimated reduction</span></div>
          </div>
          <p class="text-muted" style="font-size:0.85rem">Estimates are illustrative only. Actual results require server-side processing.</p>`;
        }

        root.dispatchEvent(new CustomEvent('filenova:files', { detail: { files: files.slice() } }));
      };

      const addFiles = (list) => {
        const arr = Array.from(list || []);
        if (!arr.length) return;
        if (multi) {
          files = files.concat(arr);
        } else {
          files = [arr[0]];
        }
        render();
      };

      drop.addEventListener('click', (e) => {
        if (e.target.closest('button')) {
          e.preventDefault();
        }
        input.click();
      });
      drop.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          input.click();
        }
      });

      ['dragenter', 'dragover'].forEach((ev) => {
        drop.addEventListener(ev, (e) => {
          e.preventDefault();
          drop.classList.add('is-dragover');
        });
      });
      ['dragleave', 'drop'].forEach((ev) => {
        drop.addEventListener(ev, (e) => {
          e.preventDefault();
          drop.classList.remove('is-dragover');
        });
      });
      drop.addEventListener('drop', (e) => addFiles(e.dataTransfer.files));
      input.addEventListener('change', () => {
        addFiles(input.files);
        input.value = '';
      });

      selected &&
        selected.addEventListener('click', (e) => {
          const btn = e.target.closest('[data-remove]');
          if (!btn) return;
          const idx = Number(btn.dataset.remove);
          files.splice(idx, 1);
          render();
        });

      // Drag reorder for multi
      if (multi && selected) {
        let dragIdx = null;
        selected.addEventListener('dragstart', (e) => {
          const item = e.target.closest('.file-item');
          if (!item) return;
          dragIdx = Number(item.dataset.idx);
          item.classList.add('is-dragging');
        });
        selected.addEventListener('dragend', (e) => {
          const item = e.target.closest('.file-item');
          if (item) item.classList.remove('is-dragging');
          dragIdx = null;
        });
        selected.addEventListener('dragover', (e) => e.preventDefault());
        selected.addEventListener('drop', (e) => {
          e.preventDefault();
          const item = e.target.closest('.file-item');
          if (!item || dragIdx === null) return;
          const to = Number(item.dataset.idx);
          if (to === dragIdx) return;
          const moved = files.splice(dragIdx, 1)[0];
          files.splice(to, 0, moved);
          render();
        });
      }

      root.querySelectorAll('[data-add-more]').forEach((btn) => {
        btn.addEventListener('click', () => input.click());
      });

      root.querySelectorAll('input[name="compress-mode"]').forEach((r) => {
        r.addEventListener('change', render);
      });

      // Process button — frontend stub
      root.querySelectorAll('[data-process]').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (!files.length) {
            toast('Please select a file first.', 'warning');
            return;
          }
          const toolId = root.dataset.toolId || document.body.dataset.tool || '';
          const futureMsg =
            toolId.indexOf('pdf') !== -1 || /pdf/i.test(btn.textContent)
              ? 'PDF processing will be connected in a future version.'
              : 'Processing will be connected in a future version.';
          const processing = root.querySelector('[data-processing]');
          const result = root.querySelector('[data-result]');
          if (processing) {
            simulateProcessing(processing, () => {
              if (result) {
                result.classList.remove('hidden');
                result.innerHTML = `<div class="result-panel">
                  <h3>Your file is ready (demo)</h3>
                  <p class="text-muted mt-1">This is a frontend simulation. No file was uploaded or transformed.</p>
                  <div class="notice info mt-2">${futureMsg} Hook your API into <code>FileNova.processTool()</code>.</div>
                  <button type="button" class="btn btn-primary mt-3" data-demo-download>Download (unavailable)</button>
                </div>`;
                result.querySelector('[data-demo-download]')?.addEventListener('click', () => {
                  toast(futureMsg, 'warning');
                });
              }
              toast(futureMsg, 'warning');
            });
          } else {
            toast(futureMsg, 'warning');
          }
        });
      });

      // Expose for custom pages
      root._filenovaGetFiles = () => files.slice();
    });
  }

  /* Phase 2 hook */
  window.FileNova.processTool = function processTool(/* toolId, files, options */) {
    return Promise.reject(new Error('Backend processing is not connected yet.'));
  };

  /* ---------- Range value display ---------- */
  function initRanges() {
    document.querySelectorAll('[data-range]').forEach((wrap) => {
      const input = wrap.querySelector('input[type="range"]');
      const out = wrap.querySelector('[data-range-value]');
      if (!input || !out) return;
      const sync = () => {
        out.textContent = input.value + (input.dataset.suffix || '');
      };
      input.addEventListener('input', sync);
      sync();
    });
  }

  /* ---------- Contact form stub ---------- */
  function initContactForm() {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      toast('Thanks! Contact form submission will be wired in a future version.', 'success');
      form.reset();
    });
  }

  /* ---------- Resize tool sync ---------- */
  function initResizeTool() {
    const panel = document.querySelector('[data-resize-tool]');
    if (!panel) return;
    const root = panel.closest('[data-upload]') || panel;
    const wInput = panel.querySelector('[name="width"]');
    const hInput = panel.querySelector('[name="height"]');
    const lock = panel.querySelector('[name="lock-aspect"]');
    let ratio = null;

    root.addEventListener('filenova:image-meta', (e) => {
      ratio = e.detail.width / e.detail.height;
      if (wInput) wInput.value = e.detail.width;
      if (hInput) hInput.value = e.detail.height;
    });

    if (wInput && hInput) {
      wInput.addEventListener('input', () => {
        if (lock && lock.checked && ratio) {
          hInput.value = Math.round(Number(wInput.value) / ratio) || '';
        }
      });
      hInput.addEventListener('input', () => {
        if (lock && lock.checked && ratio) {
          wInput.value = Math.round(Number(hInput.value) * ratio) || '';
        }
      });
    }
  }

  /* ---------- Init ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initHeaderScroll();
    initMobileMenu();
    initSearchOverlay();
    initToolFilters();
    initHeroSearch();
    initFAQ();
    initUploadBoxes();
    initRanges();
    initContactForm();
    initResizeTool();
  });
})();
