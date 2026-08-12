#!/usr/bin/env python3
"""Generate inner Draftline pages with shared chrome."""
from pathlib import Path

ROOT = Path(__file__).resolve().parent

HEADER = """<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{title}</title>
  <meta name="description" content="{description}">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="header" id="site-header">
    <div class="header-inner">
      <a class="brand" href="/" aria-label="Draftline home">
        <svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="#0e6b56"/><path d="M9 10h10.5a3.5 3.5 0 0 1 0 7H13v5" stroke="#fff" stroke-width="2.2" fill="none" stroke-linecap="round"/><path d="M9 17h14" stroke="#c9e8df" stroke-width="2" stroke-linecap="round"/></svg>
        Draftline
      </a>
      <nav class="nav" aria-label="Primary">
        <a class="nav-link" href="/invoice/">Invoice Generator</a>
        <a class="nav-link" href="/tools/">Business Tools</a>
        <a class="nav-link" href="/templates/">Templates</a>
        <a class="nav-link" href="/resources/">Resources</a>
      </nav>
      <div class="header-actions">
        <a class="btn btn-ghost btn-signin" href="/sign-in/">Sign In</a>
        <a class="btn btn-primary" href="/invoice/">Create Invoice</a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu"><span></span></button>
      </div>
    </div>
    <nav class="mobile-nav" id="mobile-nav" hidden>
      <a href="/invoice/">Invoice Generator</a>
      <a href="/tools/">Business Tools</a>
      <a href="/templates/">Templates</a>
      <a href="/resources/">Resources</a>
      <a href="/sign-in/">Sign In</a>
    </nav>
  </header>
  <main id="main">
"""

FOOTER = """
  </main>
  <footer class="footer">
    <div class="wrap footer-grid">
      <div class="footer-brand">
        <a class="brand" href="/">Draftline</a>
        <p>Simple invoice and business tools for freelancers and small businesses.</p>
      </div>
      <div>
        <h3>Invoice Tools</h3>
        <ul>
          <li><a href="/invoice/">Invoice Generator</a></li>
          <li><a href="/recurring-invoice/">Recurring Invoice</a></li>
          <li><a href="/proforma/">Proforma Invoice</a></li>
          <li><a href="/receipt/">Receipt Generator</a></li>
          <li><a href="/quote/">Quote Generator</a></li>
        </ul>
      </div>
      <div>
        <h3>Business Tools</h3>
        <ul>
          <li><a href="/estimate/">Estimate Generator</a></li>
          <li><a href="/credit-note/">Credit Note Generator</a></li>
          <li><a href="/calculators/tax/">Tax Calculator</a></li>
          <li><a href="/calculators/profit-margin/">Profit Margin Calculator</a></li>
          <li><a href="/calculators/discount/">Discount Calculator</a></li>
        </ul>
      </div>
      <div>
        <h3>Resources</h3>
        <ul>
          <li><a href="/templates/">Invoice Templates</a></li>
          <li><a href="/guide/">Invoice Guide</a></li>
          <li><a href="/resources/">Small Business Resources</a></li>
          <li><a href="/blog/">Blog</a></li>
          <li><a href="/#faq">FAQ</a></li>
        </ul>
      </div>
    </div>
    <div class="wrap footer-bottom">
      <div>© 2026 Draftline</div>
      <div class="footer-legal">
        <a href="/privacy/">Privacy Policy</a>
        <a href="/terms/">Terms</a>
        <a href="/contact/">Contact</a>
      </div>
    </div>
  </footer>
  <script src="/js/main.js" defer></script>
  {extra_js}
</body>
</html>
"""

DOC_FORM = """
    <section class="page-hero">
      <div class="wrap">
        <p class="eyebrow">Free · No signup · PDF download</p>
        <h1>{heading}</h1>
        <p class="hero-lead">{lead}</p>
      </div>
    </section>
    <section class="wrap app-shell">
      <form class="panel no-print" id="doc-form" data-doc-type="{doc_type}">
        <div class="toolbar">
          <strong>Details</strong>
          <button type="button" class="btn btn-secondary" id="save-local">Save locally</button>
        </div>
        <div class="form-grid">
          <label>From / Business<input name="fromName" value="Your business"></label>
          <label>Document number<input name="number" value="0001"></label>
          <label class="span-2">Business details<textarea name="fromMeta">123 Market Street
Austin, TX</textarea></label>
          <label>Bill to<input name="toName" value="Client name"></label>
          <label>Currency
            <select name="currency">
              <option>USD</option><option>GBP</option><option>EUR</option>
              <option>CAD</option><option>AUD</option>
            </select>
          </label>
          <label class="span-2">Client details<textarea name="toMeta">Client company
City, Country</textarea></label>
          <label>Date<input type="date" name="date"></label>
          <label>Due date<input type="date" name="due"></label>
          <label>Tax %<input type="number" name="tax" value="0" min="0" step="0.01"></label>
          <label>Discount %<input type="number" name="discount" value="0" min="0" step="0.01"></label>
          <label class="span-2">Payment information<input name="payment" value="Bank transfer · Due on receipt"></label>
          <label class="span-2">Notes<textarea name="notes">Thank you for your business. Please include the document number with payment.</textarea></label>
        </div>
        <div class="toolbar" style="margin-top:16px">
          <strong>Line items</strong>
          <button type="button" class="btn btn-secondary" id="add-item">Add item</button>
        </div>
        <table class="items-table">
          <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th></th></tr></thead>
          <tbody id="items-body"></tbody>
        </table>
      </form>
      <div>
        <div class="toolbar no-print">
          <strong>Preview</strong>
          <button type="button" class="btn btn-primary" id="download-pdf">Download PDF</button>
        </div>
        <div class="preview-paper" id="preview"></div>
      </div>
    </section>
"""

CALC_FORM = """
    <section class="page-hero">
      <div class="wrap">
        <h1>{heading}</h1>
        <p class="hero-lead">{lead}</p>
      </div>
    </section>
    <section class="wrap" style="padding-bottom:72px">
      <form class="panel calc-grid" id="calc-form" data-calc="{calc}">
        {fields}
        <div class="result" id="calc-result">Enter values to calculate.</div>
        <a class="btn btn-primary" href="/invoice/">Create Invoice from this amount →</a>
      </form>
    </section>
"""


def write(path: str, title: str, description: str, body: str, extra_js: str = "") -> None:
    dest = ROOT / path
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(
        HEADER.format(title=title, description=description)
        + body
        + FOOTER.format(extra_js=extra_js),
        encoding="utf-8",
    )


docs = [
    ("invoice/index.html", "invoice", "Invoice Generator", "Create a professional invoice and download it as a PDF. No signup required."),
    ("receipt/index.html", "receipt", "Receipt Generator", "Create a printable professional receipt in seconds."),
    ("quote/index.html", "quote", "Quote Generator", "Create a client-ready quote quickly."),
    ("estimate/index.html", "estimate", "Estimate Generator", "Build a clear project estimate."),
    ("proforma/index.html", "proforma", "Proforma Invoice Generator", "Create a professional proforma invoice."),
    ("credit-note/index.html", "credit-note", "Credit Note Generator", "Create a credit note for refunds and adjustments."),
    ("recurring-invoice/index.html", "recurring-invoice", "Recurring Invoice", "Set up a repeating invoice template you can reuse."),
    ("purchase-order/index.html", "purchase-order", "Purchase Order Generator", "Create a clean purchase order for vendors."),
]

for path, doc_type, heading, lead in docs:
    write(
        path,
        f"{heading} | Draftline",
        lead,
        DOC_FORM.format(heading=heading, lead=lead, doc_type=doc_type),
        '<script src="/js/document.js" defer></script>',
    )

write(
    "tools/index.html",
    "Business Tools | Draftline",
    "Free invoice, quote, receipt and calculator tools for freelancers and small businesses.",
    """
    <section class="page-hero wrap">
      <h1>Business Tools</h1>
      <p class="hero-lead">Everything you need to create invoices, quotes, receipts and other business documents.</p>
    </section>
    <section class="section wrap">
      <div class="tools-grid">
        <article class="card tool-card featured"><h3>Invoice Generator</h3><p>Create professional invoices in seconds.</p><a class="btn btn-primary" href="/invoice/">Create Invoice →</a></article>
        <article class="card tool-card"><h3>Receipt Generator</h3><p>Create printable professional receipts.</p><a class="use-link" href="/receipt/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Quote Generator</h3><p>Create client-ready quotes quickly.</p><a class="use-link" href="/quote/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Estimate Generator</h3><p>Build clear project estimates.</p><a class="use-link" href="/estimate/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Proforma Invoice</h3><p>Create professional proforma invoices.</p><a class="use-link" href="/proforma/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Credit Note</h3><p>Create credit notes for refunds.</p><a class="use-link" href="/credit-note/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Recurring Invoice</h3><p>Reuse a repeating invoice template.</p><a class="use-link" href="/recurring-invoice/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Purchase Order</h3><p>Create vendor purchase orders.</p><a class="use-link" href="/purchase-order/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Tax Calculator</h3><p>Calculate invoice tax quickly.</p><a class="use-link" href="/calculators/tax/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Profit Margin</h3><p>Work out margin from cost and price.</p><a class="use-link" href="/calculators/profit-margin/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Discount Calculator</h3><p>Apply percentage discounts.</p><a class="use-link" href="/calculators/discount/">Use Tool →</a></article>
        <article class="card tool-card"><h3>Late Payment</h3><p>Estimate late fees on overdue invoices.</p><a class="use-link" href="/calculators/late-payment/">Use Tool →</a></article>
      </div>
    </section>
    """,
)

write(
    "templates/index.html",
    "Invoice Templates | Draftline",
    "Start with an invoice template designed for the way you work.",
    """
    <section class="page-hero wrap">
      <h1>Invoice Templates for Every Business</h1>
      <p class="hero-lead">Start with a template designed for the way you work.</p>
    </section>
    <section class="section wrap">
      <div class="tpl-grid">
        <article class="card tpl-card"><div class="mini-inv tpl-a"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Freelancer</h3><a class="use-link" href="/invoice/?template=freelancer">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-b"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Web Developer</h3><a class="use-link" href="/invoice/?template=developer">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-c"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Graphic Designer</h3><a class="use-link" href="/invoice/?template=designer">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-d"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Photographer</h3><a class="use-link" href="/invoice/?template=photographer">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-e"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Consultant</h3><a class="use-link" href="/invoice/?template=consultant">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-f"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Contractor</h3><a class="use-link" href="/invoice/?template=contractor">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-g"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Marketing Agency</h3><a class="use-link" href="/invoice/?template=agency">Use Template →</a></article>
        <article class="card tpl-card"><div class="mini-inv tpl-h"><div class="bar"></div><div class="lines"><i></i><i></i><i></i></div></div><h3>Small Business</h3><a class="use-link" href="/invoice/?template=small-business">Use Template →</a></article>
      </div>
    </section>
    """,
)

simple_pages = [
    (
        "resources/index.html",
        "Small Business Resources | Draftline",
        "Guides and tools to help you invoice with confidence.",
        "<section class='page-hero wrap'><h1>Small Business Resources</h1><p class='hero-lead'>Practical pages to help you send clearer invoices and get paid faster.</p></section><section class='section wrap'><div class='feat-grid'><article class='card feat-card'><h3>Invoice Guide</h3><p>What to include on a professional invoice.</p><a class='use-link' href='/guide/'>Read guide →</a></article><article class='card feat-card'><h3>Templates</h3><p>Start from a layout that matches your work.</p><a class='use-link' href='/templates/'>Browse templates →</a></article><article class='card feat-card'><h3>FAQ</h3><p>Answers about PDF downloads, taxes and accounts.</p><a class='use-link' href='/#faq'>View FAQ →</a></article><article class='card feat-card'><h3>Create Invoice</h3><p>Skip the reading and start a free invoice now.</p><a class='use-link' href='/invoice/'>Create Invoice →</a></article></div></section>",
    ),
    (
        "guide/index.html",
        "Invoice Guide | Draftline",
        "A short guide to creating a professional invoice.",
        "<section class='page-hero wrap'><h1>Invoice Guide</h1><p class='hero-lead'>A professional invoice should make it obvious who is billing whom, what was delivered, when payment is due, and how to pay.</p></section><section class='section wrap'><article class='card feat-card'><h3>Include these details</h3><p>Your business name and contact information, client details, invoice number, dates, itemized work, tax, total, and payment instructions. Draftline’s generator includes fields for each of these so you can download a PDF and send it the same day.</p><p style='margin-top:16px'><a class='btn btn-primary' href='/invoice/'>Create Free Invoice</a></p></article></section>",
    ),
    (
        "blog/index.html",
        "Blog | Draftline",
        "Notes on invoicing and running a small independent business.",
        "<section class='page-hero wrap'><h1>Blog</h1><p class='hero-lead'>Short, practical notes for freelancers and small businesses.</p></section><section class='section wrap'><article class='card feat-card'><h3>How to send your first invoice</h3><p>Keep it simple: your details, the client’s details, the work, the total, and how to pay. Then download a PDF and send it.</p><a class='use-link' href='/invoice/'>Create Invoice →</a></article></section>",
    ),
    (
        "privacy/index.html",
        "Privacy Policy | Draftline",
        "How Draftline handles information when you create invoices in the browser.",
        "<section class='page-hero wrap'><h1>Privacy Policy</h1><p class='hero-lead'>You can create a basic invoice in your browser without creating an account. Document details you enter can be saved locally on your device if you choose Save locally. This policy will be updated as the product adds optional accounts or hosted features. We do not require an account just to create a basic invoice.</p></section>",
    ),
    (
        "terms/index.html",
        "Terms | Draftline",
        "Terms of use for Draftline invoice and business tools.",
        "<section class='page-hero wrap'><h1>Terms</h1><p class='hero-lead'>Draftline provides free tools to help you create business documents. You are responsible for the accuracy of the invoices and documents you generate and for complying with tax and legal requirements in your jurisdiction. The tools are provided as-is for general business use.</p></section>",
    ),
    (
        "contact/index.html",
        "Contact | Draftline",
        "Get in touch with Draftline.",
        "<section class='page-hero wrap'><h1>Contact</h1><p class='hero-lead'>For product questions, use the FAQ or start creating an invoice. For legal or privacy questions, email hello@draftline.app.</p><p style='margin-top:20px'><a class='btn btn-primary' href='/invoice/'>Create Free Invoice</a></p></section>",
    ),
    (
        "sign-in/index.html",
        "Sign In | Draftline",
        "Accounts are optional. Create an invoice without signing up.",
        "<section class='wrap'><div class='card auth-card'><h1>Sign in is optional</h1><p>You can create and download a professional invoice without an account. Saved profiles will be available later for people who want them.</p><a class='btn btn-primary btn-lg' href='/invoice/'>Continue without an account</a></div></section>",
    ),
]

for path, title, desc, body in simple_pages:
    write(path, title, desc, body)

calcs = [
    (
        "calculators/tax/index.html",
        "tax",
        "Invoice Tax Calculator",
        "Calculate tax on an invoice subtotal.",
        "<label>Subtotal<input name='amount' type='number' value='1250' step='0.01'></label><label>Tax %<input name='rate' type='number' value='10' step='0.01'></label>",
    ),
    (
        "calculators/profit-margin/index.html",
        "margin",
        "Profit Margin Calculator",
        "Work out profit margin from cost and selling price.",
        "<label>Cost<input name='cost' type='number' value='800' step='0.01'></label><label>Selling price<input name='price' type='number' value='1250' step='0.01'></label>",
    ),
    (
        "calculators/discount/index.html",
        "discount",
        "Discount Calculator",
        "Apply a percentage discount to an amount.",
        "<label>Amount<input name='amount' type='number' value='1250' step='0.01'></label><label>Discount %<input name='rate' type='number' value='10' step='0.01'></label>",
    ),
    (
        "calculators/late-payment/index.html",
        "late",
        "Late Payment Calculator",
        "Estimate a simple late fee on an overdue invoice.",
        "<label>Invoice total<input name='amount' type='number' value='1250' step='0.01'></label><label>Fee %<input name='rate' type='number' value='1.5' step='0.01'></label><label>Months overdue<input name='months' type='number' value='1' step='1'></label>",
    ),
]

for path, calc, heading, lead, fields in calcs:
    write(
        path,
        f"{heading} | Draftline",
        lead,
        CALC_FORM.format(heading=heading, lead=lead, calc=calc, fields=fields),
        '<script src="/js/calculators.js" defer></script>',
    )

print("pages generated")
