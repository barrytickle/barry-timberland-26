# Barry Timberland 26

A clean, light editorial WordPress portfolio theme built with Timber 2, Twig, ACF Pro, native ACF blocks, Tailwind CSS 4, Vite and Alpine.js.

## Requirements and setup

- PHP 8.1 or newer
- WordPress 6.5 or newer
- ACF Pro
- Node.js 20 or newer
- Composer 2

From the repository root:

```bash
composer install
npm install
npm run build
```

The WordPress-installable theme lives in `theme/`; Composer dependencies live in `vendor/` one level above it.

## Local development

Set `vite.environment` to `development` in `config.json`, then run:

```bash
npm run dev
```

Vite serves assets from `http://localhost:3000`. For production or local development without the Vite server, set the environment to `production` and run `npm run build`.

## Tailwind 4 and design tokens

Tailwind remains on version 4. Semantic colours, typography, breakpoints and other theme tokens live in `theme/assets/styles/theme.css` using Tailwind 4's `@theme` syntax. `tailwind.config.js` is intentionally minimal and scans all PHP, Twig, JavaScript and JSON files under `theme/`.

Do not downgrade Tailwind or duplicate the token set in JavaScript.

## ACF Local JSON

Field groups are committed in `theme/acf-json`. ACF is configured to load and save JSON there. After pulling changes, open **Custom Fields → Field Groups** and use **Sync available** if ACF presents it.

The committed groups cover Site Settings, case-study details and every custom portfolio block. Keys beginning `group_bt26_` and `field_bt26_` are stable identifiers.

## Menus and Site Settings

Assign menus under **Appearance → Menus → Manage Locations**:

- Primary navigation
- Footer primary navigation
- Footer secondary navigation

Header and footer content is configured under **Site Settings** in WordPress. Complete the Branding, Header, Contact, Social and Footer tabs. The header CTA is intentionally separate from the primary menu.

## Case studies

The theme registers a public **Case Studies** post type at `/work/`. It supports the block editor, thumbnails, excerpts, revisions and ordering. Add only projects Barry can legitimately present as his own work; CRO-only engagements can be discussed elsewhere without being represented as builds.

Suggested initial entries:

1. Physio123
2. Liverpool Guitar Lessons
3. barrytickle.com
4. Liverpool Piano Lessons

Project outcomes are always editor-provided. The theme does not contain fabricated percentages, metrics or testimonials.

## Homepage

Create a normal page, set it as the static front page under **Settings → Reading**, then insert the **Editorial portfolio homepage** pattern. Its recommended sequence is:

1. Hero with Image
2. Section Intro
3. Project — Image Right
4. Project — Image Left
5. Project — Image Right
6. About Split
7. Services Grid
8. Client Logo Slider
9. Contact CTA

The homepage remains fully editable as blocks; there is no hard-coded homepage template.

## Assets

Original interface icons from the supplied Codex pack are in `theme/assets/icons`. Icon names are validated against an allowlist before rendering. Upload real project screenshots and authorised client logos to the WordPress Media Library and select them through ACF. Do not use the supplied text-only brand placeholders as official logos.

## Migration from barry-timberland-25

- Reassign menus to the three explicit menu locations; menu names/slugs are no longer used as API identifiers.
- Re-enter global branding, contact, social and footer content in the new Site Settings field group.
- Existing `case_study` posts remain usable, but the archive URL changes from the old custom setup to `/work/`. Visit **Settings → Permalinks** and save once after activation.
- Map old hero and case-study block content into the new blocks in the editor. Similar field names were retained where practical, but no destructive database migration is performed.
- Upload screenshots into the new `project_browser_image` field and ensure Media Library alt text is meaningful.
- Replace any old hard-coded footer columns with the new WordPress menu assignments and Site Settings repeaters.

## Production build and deployment

```bash
npm ci
npm run build
composer install --no-dev --optimize-autoloader
```

Deploy `theme/` and `vendor/`. Vite writes hashed assets and a manifest to `theme/assets/dist`; the theme reads the manifest rather than relying on fixed filenames.

## Troubleshooting

- **Unstyled page:** run `npm run build`, or start `npm run dev` when `config.json` is set to development.
- **Blocks missing:** install/activate ACF Pro, then sync Local JSON field groups.
- **Menus missing:** assign menus to the registered locations rather than relying on their names.
- **404 at `/work/`:** resave WordPress permalinks after activating the theme.
- **Images missing:** choose Media Library images in the relevant ACF fields; placeholders only appear inside editor previews.
- **Composer error:** run `composer install` from the repository root, not from the nested `theme/` folder.
