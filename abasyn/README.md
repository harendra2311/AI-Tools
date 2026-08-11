# Abasyn International — About Us page

Static About Us page matching the live Abasyn design system at [tinywowtools.com/abasyn](https://tinywowtools.com/abasyn/).

## Contents

- `about.html` — full page with SEO title/description, story, beliefs, and roadmap
- `assets/css/abasyn.css` — shared Abasyn styles from the live site
- `assets/css/about-page.css` — About page layout
- `assets/js/abasyn.js` — header drawer + scroll reveal

## Deploy to Hostinger / WordPress

### Option A — Static folder (recommended)

Upload the `abasyn/` folder contents so the page is available at:

`https://tinywowtools.com/abasyn/about/`

1. Create `/public_html/abasyn/about/` (or your site root equivalent)
2. Upload `about.html` as `index.html` inside that folder
3. Upload the `assets/` folder next to it (so paths `assets/css/...` resolve)
4. In WordPress/Elementor header, point the **About** menu item to `/abasyn/about/`

### Option B — WordPress page

1. Create a new page titled **About Us** with slug `about`
2. Set SEO title and meta description as in `about.html`
3. Use a full-width / Elementor canvas template
4. Either embed the page body HTML, or host the static files and link from the menu

## SEO (already in `about.html`)

- **Title:** About Abasyn International | Hotel Supply Partner Serving North America
- **Meta description:** Learn about Abasyn International — a Toronto-headquartered import and supply company helping hotels across North America stay fully stocked with quality essentials at bulk prices.
- **Headline:** A Supply Partner Built for Hospitality

## Local preview

```bash
cd abasyn
python3 -m http.server 8080
# open http://localhost:8080/about.html
```
