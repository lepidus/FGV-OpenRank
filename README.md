**English** | [Português Brasileiro](docs/README-pt_BR.md) | [Español](docs/README-es.md)

# FGV OpenRank

[![OJS compatibility](https://img.shields.io/badge/ojs-3.3.0.x-brightgreen)](https://github.com/pkp/ojs/tree/stable-3_3_0)
[![License type](https://img.shields.io/badge/license-GPL--3.0-blue)](https://www.gnu.org/licenses/gpl-3.0)

This plugin adds a ranking block to the homepage of a journal running [OJS](https://pkp.sfu.ca/software/ojs/). The block lists articles in tabs — **Most recent**, **Most read**, **Most cited**, **Trending** and a free-content **Highlight** tab — and the journal manager decides which tabs appear, in what order, with which title, description and number of items.

## How it works

You choose where the block goes in the plugin settings — top of the homepage, after a given section of it, bottom, or wherever you place `<div class="rankingTabs"></div>` in the journal's Additional Content. Each tab loads its articles asynchronously from the plugin's own API, which serves data from a cache refreshed once a day. External services (Crossref, Altmetric) are queried by the scheduled task, not while a reader waits for the page.

| Tab | What it lists | Source | Needs |
| --- | --- | --- | --- |
| **Most recent** | The latest published articles | OJS | — |
| **Most read** | The most viewed articles in the last *N* days (120 by default) | OJS usage statistics | Usage statistics recorded |
| **Most cited** | The most cited articles of the journal | [Crossref](https://www.crossref.org/services/cited-by/) | Journal ISSN + article DOIs |
| **Trending** | The articles with the highest Altmetric score | [Altmetric](https://www.altmetric.com/) API or a manual DOI list | Article DOIs (ISSN and API key if automatic) |
| **Highlight** | Any content you write yourself (rich text) | — | — |

## Getting started

### 1. Install the plugin

Download the `.tar.gz` of the latest version compatible with your OJS from the [releases page](https://gitlab.lepidus.com.br/softwares-pkp/plugins_ojs/rankingPlugin/-/releases), then go to *Settings → Website → Plugins → Upload a new plugin*, send the file and enable the plugin for your journal.

### 2. Choose where the block appears

Open the plugin's *Settings* and pick the **Position on the journal homepage**:

- **At the top of the homepage** — above every other section.
- **After a given section of the homepage** — then answer **After which section?**: `1` puts the block after the first section, `2` after the second, and so on. The count covers every section your theme stacks on the homepage — the homepage image, the journal description, the announcements, the current issue, the additional content, and anything else the theme renders. What counts as a section therefore depends on the theme and on what the journal has configured, and some themes group several of them into a single wrapper, so expect to try a couple of numbers. A number higher than the number of sections puts the block at the bottom.
- **At the bottom of the homepage** — below every other section.
- **Where the `rankingTabs` element is** (the default) — in *Settings → Website → Appearance → Advanced*, add this to **Additional Content**:

  ```html
  <div class="rankingTabs"></div>
  ```

  The block is rendered inside that element.

The first three options are handled by the plugin itself, with no custom CSS involved. They are counted among the homepage blocks rather than matched against theme-specific CSS classes, so they hold up when a theme renames, reorders or drops a section — a heavily customized theme may still need adjusting.

### 3. Allow your host

The tabs call the plugin's API on the journal's own address, so the host must be listed in `allowed_hosts` in `config.inc.php`:

```php
allowed_hosts = '["myjournal.org"]'
```

### 4. Release Altmetric's domains, if the server sends a CSP

This step is only for the **Trending** tab, and only when your web server adds custom headers with a *Content Security Policy*.

| Directive | Domains | What it covers |
| --- | --- | --- |
| `script-src` | `https://d1bxh8uas1mnw7.cloudfront.net`<br>`https://embed.altmetric.com`<br>`https://api.altmetric.com` | the embed script the plugin inserts, the badge script it loads in turn, and the score itself — which travels as JSONP, so the browser checks it as a script |
| `style-src` | `https://embed.altmetric.com` | the donut's stylesheet |
| `img-src` | `https://badges.altmetric.com` | the donut image |

A minimal example for nginx:

```nginx
add_header Content-Security-Policy "
    script-src 'self' 'unsafe-inline' 'unsafe-eval'
        https://d1bxh8uas1mnw7.cloudfront.net
        https://embed.altmetric.com
        https://api.altmetric.com;
    style-src 'self' 'unsafe-inline'
        https://embed.altmetric.com;
    img-src 'self' data:
        https://badges.altmetric.com;
" always;
```

Or, if the policy is declared in a `<meta>` tag of the theme:

```html
<meta http-equiv="Content-Security-Policy"
      content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://d1bxh8uas1mnw7.cloudfront.net https://embed.altmetric.com https://api.altmetric.com;
               style-src 'self' 'unsafe-inline' https://embed.altmetric.com;
               img-src 'self' data: https://badges.altmetric.com;">
```

> [!NOTE]
> These are examples, not a complete policy: merge the domains into the directives you already have, keeping whatever OJS and your theme need.

### 5. Configure the tabs

Open the plugin's *Settings*. A grid lists the five tabs: use the toggle to enable or disable each one, drag the rows to change the order they appear in, and click a tab to edit it.

## Configuring a tab

Every tab has:

- **Custom title** — replaces the default title. Multilingual.
- **Description** — the text shown above the list. Multilingual.
- **Items per tab** — how many articles the tab holds (4 by default).
- **Items per page** — how many are shown at a time, the rest being paginated (4 by default).

Some tabs have their own extra settings:

- **Most read:** *Days for most read* — the window used to count views (120 by default).
- **Highlight:** *Custom content* — a rich text field. This tab makes no API call; it shows exactly what you write.
- **Trending:** the Altmetric API key and the manual DOI list, described below.

### The Trending tab: API key or manual list

The tab works in either of two ways:

- **With an Altmetric API key.** Articles are fetched from the Altmetric API by the journal's ISSN and ordered by score. The key is checked when you save it, and stored encrypted — which requires `api_key_secret` to be set in `config.inc.php` ([how to set one](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).
- **With a manual DOI list.** With no key stored, the tab shows the DOIs you list, in the order you list them. Only DOIs of articles published in this journal are accepted.

> [!NOTE]
> While a key is stored the manual list is ignored. To go back to it, tick *Remove the stored API key* and save.

## Cache and daily update

The tabs are served from a file cache per journal. A scheduled task — *FGV OpenRank cache update* — refreshes every tab of every enabled journal daily at midnight, so keep the **Acron** plugin enabled, or have `runScheduledTasks.php` in your server's crontab ([PKP Administrator's Guide](https://docs.pkp.sfu.ca/admin-guide/)). If a cache is empty when a reader arrives, the data is fetched on the spot.

To refresh by hand, from the OJS root:

```bash
php tools/runScheduledTasks.php
```

## Requirements

- **OJS 3.3.0.x**
- **`allowed_hosts`** including the journal's host.
- **An ISSN** registered for the journal — needed by *Most cited*, and by *Trending* when an API key is used.
- **DOIs** assigned to the articles — *Most cited* and *Trending* identify articles by DOI, so an article without one never appears in them.
- **`api_key_secret`** in `config.inc.php`, only if you are going to store an Altmetric API key.
- **Altmetric's domains released in the CSP**, only if your web server sends a custom *Content Security Policy* and the *Trending* tab is in use.

## Troubleshooting

<details>
<summary><strong>The block does not show up on the homepage</strong></summary>

Check that the plugin is enabled for this journal. If the position is set to *Where the `rankingTabs` element is*, make sure `<div class="rankingTabs"></div>` is in *Additional Content*. Only the first occurrence of the element on the page is used.

</details>

<details>
<summary><strong>The block shows up in the wrong place</strong></summary>

With *After a given section of the homepage*, try another answer to **After which section?** — how many sections a homepage has depends on the theme and on what the journal has configured. Numbers that are too high land the block at the bottom.

</details>

<details>
<summary><strong>A tab shows an error message</strong></summary>

Confirm the journal's host is in `allowed_hosts`. Errors coming from Crossref or Altmetric are recorded in your OJS server logs.

</details>

<details>
<summary><strong>Most cited or Trending is empty</strong></summary>

Usually the journal has no ISSN, the articles have no DOIs, or the external service has no data for them yet. In the Trending tab with no API key, check that the manual DOI list is filled in.

</details>

<details>
<summary><strong>Trending lists the articles but the donuts show a grey question mark</strong></summary>

The list comes from the plugin's cache, while the donut is fetched straight from Altmetric by the reader's browser, so one can fail without the other. If your server sends a *Content Security Policy*, check that Altmetric's domains are released. The browser console names both the blocked request and the directive that blocked it.

</details>

<details>
<summary><strong>Most read is empty</strong></summary>

There are no recorded views in the period. Increase *Days for most read*, or check that OJS usage statistics are being collected.

</details>

<details>
<summary><strong>The Altmetric key was rejected when saving</strong></summary>

Either the key is not valid for the Altmetric API, or `api_key_secret` is not configured in `config.inc.php`, in which case the key cannot be stored encrypted.

</details>

## Getting help

- **The plugin:** open an issue in this repository.
- **OJS itself:** ask on the [PKP Community Forum](https://forum.pkp.sfu.ca/).

## License

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0).

Copyright (c) 2025-2026 Lepidus Tecnologia.
