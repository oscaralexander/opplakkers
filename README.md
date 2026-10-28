# Boilerplate

A Craft CMS 5 starter for client websites. Every site built from it shares the
same content model, templates and JavaScript; what differs per client lives in
**one stylesheet** (`src/css/client.scss`), the fonts and the favicon, and of
course the content.

The control panel is in Dutch (field names, instructions, section names). Field
handles, templates and code are in English.

- Craft CMS 5.11 · PHP 8.4 · MySQL 8 · DDEV
- Vite 6 · Sass · GSAP (ScrollTrigger, SplitText)
- CKEditor for rich text · Lucide icons (inlined server-side, no icon JS)

---

## 1. Spinning up a new client site

### 1.1 Copy the boilerplate

```bash
cp -R ~/Sites/boilerplate ~/Sites/klantnaam
cd ~/Sites/klantnaam
rm -rf .git node_modules vendor storage/uploads storage/runtime storage/logs
git init && git add -A && git commit -m "Start from boilerplate"
```

(Or `git clone` the boilerplate repository and `rm -rf .git` — the point is a
fresh history per client.)

### 1.2 DDEV

```bash
ddev config --project-name=klantnaam   # keeps the rest of .ddev/config.yaml
ddev start
```

The site is then at `https://klantnaam.ddev.site`.

### 1.3 Environment

```bash
cp .env.example.dev .env
```

Edit `.env`: set `PRIMARY_SITE_URL=https://klantnaam.ddev.site`, then generate
the two secrets:

```bash
ddev composer install
ddev craft setup/app-id
ddev craft setup/security-key
```

### 1.4 Install Craft

```bash
ddev craft install
```

Answer the prompts (admin user, site name = the client's name, site URL
`$PRIMARY_SITE_URL`, language `nl`). Because `config/project/` already exists,
the installer applies the full content model — sections, fields, globals,
volumes — as part of the install. Nothing to configure by hand.

If the installer did *not* pick up the project config for some reason:

```bash
ddev craft project-config/apply
```

### 1.5 Front-end build

```bash
ddev npm install        # also copies the Lucide icon set into templates/icons/lucide
ddev npm run build      # production build into web/dist (committed to git)
```

During development run the Vite dev server instead, for hot reloading:

```bash
ddev npm run serve      # https://klantnaam.ddev.site:5173
```

When the dev server is not running Craft falls back to `web/dist` automatically
(`checkDevServer` in `config/vite.php`), so a plain `ddev start` always shows a
working site.

### 1.6 Demo content (optional, recommended)

```bash
ddev exec php scripts/seed-demo-content.php
```

Creates flat grey placeholder images and a home page that uses every section type, plus
a few people, testimonials, companies, products, blog posts and the legal
pages. It is the quickest way to see the client's colours and fonts on real
layouts, and to hand the client a site they can start editing. Remove it when
the real content goes in:

```bash
ddev exec php scripts/reset-content.php --yes   # deletes ALL entries and assets
```

### 1.7 Brand the site

Everything client-specific, in order:

| What                         | Where                                                                                     |
| ---------------------------- | ----------------------------------------------------------------------------------------- |
| Colours, fonts, radii, buttons, form fields, nav | `src/css/client.scss` — see section 3                                 |
| Font files                   | `web/assets/fonts/` (referenced from `client.scss` as `@public/assets/fonts/…`)           |
| Favicon                      | `web/favicon.svg` and `web/apple-touch-icon.png` (180 × 180)                             |
| Site name                    | Control panel → Instellingen → Sites (this writes to `config/project`)                    |
| Logo, footer text, share image | Control panel → Globals → **Huisstijl**                                                 |
| Address, e-mail, phone, KVK, socials | Control panel → Globals → **Contactgegevens**                                     |
| Nav bar buttons              | Control panel → Globals → **Navigatie**                                                   |
| Analytics / verification tags, parallax | Control panel → Globals → **Instellingen**                                     |

Rebuild after changing `client.scss` (`ddev npm run build`), commit, done.

### 1.8 Going live

1. Copy `.env.example.production` to `.env` on the server and fill it in
   (`CRAFT_ENVIRONMENT=production`, `CRAFT_DEV_MODE=false`,
   `CRAFT_ALLOW_ADMIN_CHANGES=false`, `CRAFT_DISALLOW_ROBOTS=false`, database
   credentials, `PRIMARY_SITE_URL`).
2. Deploy the repository including `web/dist` (built locally) — the server
   only needs PHP and Composer, not Node.
3. On the server: `composer install --no-dev`, then `php craft install` for a
   first deploy or `php craft project-config/apply` for every later one, then
   `php craft clear-caches/all`.
4. Point the web root at `web/`. Uploads live in `storage/uploads/` (make it
   writable, and exclude it from deploys that replace the directory);
   `web/uploads` is a committed symlink to it.

Set `CRAFT_ALLOW_ADMIN_CHANGES=false` in production so the content model can
only change through `config/project` in git.

---

## 2. Project structure

```
config/project/          The content model (Craft project config). Source of truth.
scripts/
  build-project-config.php   Generates config/project from a declarative model. Boilerplate
                             bootstrap only — never run on a client site (see header).
  seed-demo-content.php      Demo content covering every section.
  reset-content.php          Deletes all entries and assets.
  copy-icons.mjs             Copies Lucide SVGs to templates/icons/lucide (runs on npm install).
src/
  css/client.scss        ← per-client customisation
  css/1-settings/        Tokens (variables.scss) — the defaults client.scss overrides
  css/2-tools/           Mixins
  css/3-elements/        Element defaults: reset, headings, forms
  css/4-components/      Components, one file each; sections/ for the page sections
  css/5-utils/           Utilities, including the section colour themes
  js/index.js            Registers components
  js/App.js              Binds components to `.js-*` elements (lazy-loads the rare ones)
  js/lib/gsap.js         Registers GSAP plugins once
  js/components/         Accordion, Equinox, Nav, TextSplit, Ticker
templates/
  _layout.twig           Document shell
  _macros/ui.twig        Shared fragments: icon, image, ctas, sectionHeader, youtube, …
  _partials/             nav, footer, seo (meta + schema.org)
  _sections/             One template per section type
  _blocks/               Blog body blocks
  pages/_entry.twig      Pages and legal pages
  blog/_entry.twig       Blog posts
  _sitemap.twig          sitemap.xml (routed in config/routes.php)
  icons/                 Social SVGs + the Lucide set
web/
  assets/fonts/          Client font files
  dist/                  Vite build output (committed)
  uploads/               Symlink to storage/uploads (asset uploads, not committed)
```

---

## 3. Styling a client — `src/css/client.scss`

`client.scss` is loaded straight after `1-settings/variables.scss`, so any
custom property it declares on `:root` wins. The file ships with every token
listed and commented out; uncomment what the client needs. The groups:

1. **Lettertypen** — `@font-face` declarations for files in `web/assets/fonts/`.
   The default is self-hosted Inter (`1-settings/fonts.scss`); a client font
   only needs its `@font-face` here plus `--font-1`/`--font-2`.
2. **Typografie** — `--font-1` (body), `--font-2` (headings), the size scale,
   and per-heading `--h1…--h5` font/weight/spacing/transform.
3. **Kleurenpalet** — `--color-1` (primary), `--color-2`, `--color-neutral`
   (text and borders derive from it), link colours, and the two section
   surfaces the editor picks from the *Kleurthema* dropdown besides "none":
   `--theme-tint-color-bg` (light panel) and `--theme-contrast-*` (dark
   panel with light text and buttons).
4. **Afgeronde hoeken** — `--border-radius-s/m/l`, button and input radii.
5. **Knoppen** — height, weight, padding, primary/secondary colours.
6. **Formulierelementen** — `--input-*`, used for embedded forms too.
7. **Navigatiebalk** — height, the floating-pill offsets, radius, shadow, logo height.

The large sizes are fluid (`clamp()`), so one value covers mobile and desktop
and overrides never need media queries. Section themes only re-point the
generic `--color-*` tokens, so components never know which theme they are in.

Everything else in `src/css` is shared across clients. If a client needs a
layout change, that is a change to the boilerplate, not to `client.scss`.

---

## 4. Content model

| Section (CP name)        | Handle         | Type      | URL                | Notes                                        |
| ------------------------ | -------------- | --------- | ------------------ | -------------------------------------------- |
| Pagina's                 | `pages`        | structure | `/{slug}`, 2 levels | Home = page with slug `home`. Menu follows level 1 + children (toggle *Tonen in navigatie*). |
| Juridisch                | `legal`        | structure | `/{slug}`          | Privacy, terms — linked in the footer.       |
| Blog                     | `blogs`        | channel   | `/blog/{slug}`     | Body is a Matrix of text / images / items / video. |
| Personen                 | `people`       | structure | —                  | Shown by the *Personen* section.             |
| Testimonials             | `testimonials` | structure | —                  | Shown by *Testimonials* and *Ticker*.        |
| Bedrijven                | `companies`    | structure | —                  | Logos, shown by *Bedrijfslogo's*.            |
| Producten                | `products`     | structure | —                  | Price cards, shown by *Producten*.           |

A page is a Matrix (`sections`) of these section types, each with an
*Inhoud* and an *Opmaak* tab:

`hero` · `text` · `textMedia` · `columns` · `cards` · `accordion` · `stats` ·
`testimonials` · `people` · `products` · `companies` · `ticker` · `blogIndex` ·
`callToAction` · `embed`

Buttons everywhere are a `ctas` Matrix of `cta` blocks: a Link field (page,
URL, e-mail or phone, with label and new-tab option), a style (primary /
secondary / text link) and an optional Lucide icon name.

Globals: **Huisstijl** (`brand`), **Contactgegevens** (`contact`),
**Navigatie** (`navigation`), **Instellingen** (`settings`). Volumes:
`media`, `logos`, `people`. Users carry a photo, bio and LinkedIn URL for blog
author boxes.

Changing the model: do it in the control panel (dev only, with
`CRAFT_ALLOW_ADMIN_CHANGES=true`), commit `config/project`, and
`project-config/apply` on the other environments.

---

## 5. Front-end notes

**Icons.** `{{ ui.icon('sparkles') }}` inlines any Lucide icon from
`templates/icons/lucide/` — the set is copied there by `npm install`. Editors
type icon names from <https://lucide.dev/icons/>. No icon JavaScript ships to
the browser.

**Images.** `ui.image(asset, { ratio, sizes, widths, eager })` emits a WebP
`srcset` with intrinsic dimensions; `ui.srcset()` gives just the candidate
list for a `<picture>` source. Each section passes `sizes` measured from its
real layout (`ui.colSizes(span, mobile)` for grid columns) and `widths` up to
2× the largest CSS width, so 2×/3× screens get a sharp file without anyone
over-fetching. Transforms are generated on first request.

**JavaScript.** `App` binds a component to every `.js-<name>` element, once,
and keeps doing so for markup added later. `Accordion` and `Ticker` are loaded
on demand. All motion respects `prefers-reduced-motion`.

- `Nav` — menu toggle; hides the bar while scrolling down, shows it on the way up.
- `TextSplit` — GSAP SplitText: headings (`.js-textSplit`) rise in line by line, once.
- `Equinox` — exposes scroll progress as `--equinox-pos` for parallax (enable
  per site in *Instellingen*).
- `Ticker` — the scrolling logo/testimonial rows; draggable, pauses off screen.
- `Accordion` — animates native `<details>`, one open at a time.

**SEO.** `_partials/seo.twig` writes title, description, canonical, Open
Graph and an Organization schema from the *Contactgegevens* global; blog posts
add a BlogPosting schema. `sitemap.xml` is generated from all URL-bearing
entries.

---

## 6. Updating the boilerplate itself

The content model was generated once from `scripts/build-project-config.php`.
After that, `config/project` is the source of truth and the script is only a
reference for how the model is put together. To re-baseline the boilerplate
(new fields for every future client), either change the model in the control
panel and commit `config/project`, or edit the script, re-run it and re-apply
on a *fresh* install — never on a site with content.

Client sites do not receive boilerplate updates automatically; port changes by
hand (usually `templates/`, `src/`, and a `config/project` diff).
