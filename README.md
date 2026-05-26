<div align="center">

# 📧 Quillrush Newsletter Studio for Sendy

### Turn WordPress into a newsletter studio — built for Sendy + Amazon SES.

[![Version](https://img.shields.io/badge/version-1.6.0-2563eb?style=for-the-badge)](https://wordpress.org/plugins/quillrush-newsletter-studio-for-sendy/)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%E2%80%937.0-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![Sendy](https://img.shields.io/badge/Sendy-Compatible-22c55e?style=for-the-badge)](https://sendy.co)
[![Amazon SES](https://img.shields.io/badge/Amazon%20SES-Required-ff9900?style=for-the-badge&logo=amazonaws&logoColor=white)](https://aws.amazon.com/ses/)
[![License](https://img.shields.io/badge/License-GPLv2-e74c3c?style=for-the-badge)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Support on Ko-fi](https://img.shields.io/badge/Ko--fi-Support-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gunjanjaswal)

</div>

---

## 🎯 What it does

WordPress front-end for the **Sendy + Amazon SES** newsletter stack. Drag-and-drop your latest WordPress posts into a beautiful, fully-responsive HTML email. Choose between two ready-made layouts, pick the Sendy lists/segments to ship to, hit **Send Immediately**, **Save as Draft**, or **Schedule** — and the plugin talks straight to your self-hosted Sendy install, which forwards every message through Amazon SES.

No HTML coding. No external SaaS. No paid tier.

> ⚠️ **Requires a self-hosted [Sendy](https://sendy.co) install backed by [Amazon SES](https://aws.amazon.com/ses/).** This plugin is the WordPress UI — it does not replace Sendy or SES. If you don't have the stack set up yet, configure Sendy + SES first.

---

## 🚀 Features at a glance

<table>
<tr>
<td>🎨</td>
<td><b>Visual Builder</b> — drag posts in, see the rendered email update live in the right pane.</td>
</tr>
<tr>
<td>📨</td>
<td><b>Two newsletter formats per campaign:</b> <i>The Roundup</i> (subscriber-facing hero + grid) and <i>The Insider Brief</i> (editorial pitch for media & partners).</td>
</tr>
<tr>
<td>📋</td>
<td><b>Auto-fetched Sendy lists & segments</b> — pulled via the Sendy API (<code>get-lists.php</code>) and shown as checkboxes with active subscriber counts. Cached 10 min; one-click refresh.</td>
</tr>
<tr>
<td>🧠</td>
<td><b>Remembers your last selection</b> — the lists you sent to last time are pre-checked next time.</td>
</tr>
<tr>
<td>🔍</td>
<td><b>Infinite-scroll post search</b> — AJAX-loads posts in batches of 10 as you scroll.</td>
</tr>
<tr>
<td>🖼️</td>
<td><b>Smart hero image</b> — uses your uploaded banner if present, otherwise falls back to the first post's featured image.</td>
</tr>
<tr>
<td>📱</td>
<td><b>Mobile-first responsive layout</b> — 2 columns on desktop, single column on mobile, auto-height cards, no awkward whitespace.</td>
</tr>
<tr>
<td>📅</td>
<td><b>Three send modes</b> — Save as Draft in Sendy, Send Immediately, or Schedule for a future date/time.</td>
</tr>
<tr>
<td>🕐</td>
<td><b>Timezone-aware scheduling</b> — uses your WordPress timezone; displays current server time + zone next to the picker.</td>
</tr>
<tr>
<td>♻️</td>
<td><b>Auto-recovery for overdue campaigns</b> — if WP-Cron didn't fire on time, overdue campaigns are sent automatically when you next open the Campaigns screen.</td>
</tr>
<tr>
<td>🛠️</td>
<td><b>Retry on failure</b> — failed sends show the exact Sendy error + a one-click <b>Retry Send</b> button.</td>
</tr>
<tr>
<td>⚙️</td>
<td><b>Optional cron trigger</b> — after each send, optionally hit <code>scheduled.php?i=BRAND_ID</code> on your Sendy host so queued campaigns process without a server-side cron.</td>
</tr>
<tr>
<td>✍️</td>
<td><b>Custom footer block</b> — extra text/HTML above the footer, displayed in a highlighted box. Supports anchor tags + auto <code>nl2br</code>.</td>
</tr>
<tr>
<td>🌐</td>
<td><b>Social footer icons</b> — Instagram, LinkedIn, X (Twitter), YouTube.</td>
</tr>
<tr>
<td>🗃️</td>
<td><b>Campaign history</b> — every campaign is stored as a custom-post-type entry with Status / Scheduled For / Error columns.</td>
</tr>
<tr>
<td>🔒</td>
<td><b>WordPress-native security</b> — nonces on every action, capability checks (<code>manage_options</code>), <code>wp_safe_redirect</code> for all redirects.</td>
</tr>
</table>

---

## 📨 The two newsletter formats

| | 🗞️ The Roundup | ✉️ The Insider Brief |
|---|---|---|
| **Audience** | Subscribers / readers | Media, partners, collaborators |
| **Hero** | Banner image + first post as visual hero | Greeting + intro paragraph + centered hero with featured image |
| **Body** | 2-column grid of stories | Same 2-column grid under a "What Else We're Seeing" heading |
| **Callout** | — | "Why this matters" highlighted block |
| **CTA** | — | "For Media & Collaborations" block (HTML allowed) |
| **Highlighted box above footer** | Custom Footer Text | Centered About Us heading + body |
| **Header / dark footer** | Shared | Shared |
| **Copy editable from** | Custom Footer Text + Footer & Social settings | Settings → **The Insider Brief — Template Texts** |

Pick the format per-campaign on the Create Newsletter page (Design Settings → Newsletter Format).

---

## 📋 All settings, one place

Open **Quillrush Newsletter → Settings**. Three settings sections:

### 🔌 Sendy Connection Settings

| Field | Type | What it does |
|---|---|---|
| **Sendy Installation URL** | text | Base URL of your Sendy install (e.g. `https://sendy.yourdomain.com/`) |
| **API Key** | password | From Sendy → Settings → Your API Key |
| **Brand ID (Optional)** | text | From Sendy → Settings → Your Brand → ID. Required by some Sendy versions and used for auto-fetching lists |
| **Default From Name** | text | Pre-filled into every new campaign |
| **Default From Email** | email | Pre-filled into every new campaign |
| **Auto-Trigger Cron** | checkbox | After sending, hit `<sendy-url>/scheduled.php?i=BRAND_ID` so queued campaigns process without a system cron |
| **Show Article Excerpt** | checkbox | Insert a 20-word excerpt between the post title and the "Read More" button |

### 🎨 Footer & Social Settings

| Field | Type | What it does |
|---|---|---|
| **Footer Logo URL** | URL | Logo shown in the dark footer band |
| **Copyright Text** | text | Footer copyright line. `{year}` is replaced with the current year |
| **Custom Footer Text** | textarea (HTML) | Shown in a highlighted box above the footer in **The Roundup**. Newlines → `<br>` automatically |
| **"Read More Articles" Link** | URL | Optional link below the post grid |
| **Instagram URL** | URL | Social icon in footer |
| **LinkedIn URL** | URL | Social icon in footer |
| **X (Twitter) URL** | URL | Social icon in footer |
| **YouTube URL** | URL | Social icon in footer |

### ✉️ The Insider Brief — Template Texts

| Field | Type | Suggested use |
|---|---|---|
| **Greeting** | text | e.g. `Hi [First Name],` |
| **Intro Paragraph** | textarea (HTML) | Lead paragraph above the hero story |
| **Hero Section Label** | text | Small label above the hero (e.g. `Hero Story`) |
| **Grid Section Heading** | text | e.g. `🔍 What Else We're Seeing` |
| **"Why This Matters" Heading** | text | — |
| **"Why This Matters" Body** | textarea (HTML) | — |
| **Collaboration Heading** | text | e.g. `📩 For Media & Collaborations` |
| **Collaboration Body** | textarea (HTML) | CTA bullets + contact info |
| **About Us Heading** | text | — |
| **About Us Body** | textarea (HTML) | Centered About block above the footer |

---

## 🏗️ How the builder is laid out

```
┌─────────────────────────────────────────────────────────────────┐
│  Quillrush Newsletter → Create Newsletter                        │
├──────────────────────────┬──────────────────────────────────────┤
│  LEFT COLUMN             │  RIGHT COLUMN                        │
│  ────────────────────    │  ───────────────────                 │
│  🎨 Design Settings      │                                      │
│     • Newsletter Format  │   ┌────────────────────────────┐    │
│     • Banner Image       │   │ 👁  Email Preview          │    │
│                          │   │                            │    │
│  ⚙  Campaign Settings    │   │  Live render of the email  │    │
│     • Subject Line       │   │  exactly as Sendy will     │    │
│     • From Name / Email  │   │  ship it.                  │    │
│     • List checkboxes    │   │                            │    │
│       (auto-fetched +    │   │  Updates as you add posts, │    │
│        subscriber counts)│   │  change banner, swap       │    │
│                          │   │  format, etc.              │    │
│  🔍 Add Posts            │   │                            │    │
│     Search + infinite    │   └────────────────────────────┘    │
│     scroll               │                                      │
│                          │                                      │
│  📰 Selected Posts       │                                      │
│     Drag to reorder      │                                      │
│                          │                                      │
│  🚀 Actions              │                                      │
│     ○ Save as Draft      │                                      │
│     ○ Send Immediately   │                                      │
│     ○ Schedule           │                                      │
│       (datetime picker)  │                                      │
│                          │                                      │
│  ☕ Support & Contact    │                                      │
└──────────────────────────┴──────────────────────────────────────┘
```

---

## 📅 Send modes

| Mode | What happens |
|---|---|
| **💾 Save as Draft in Sendy** | Plugin POSTs to Sendy's `create-campaign.php` with `send_campaign=0`. Campaign appears in your Sendy dashboard as a draft for review. WordPress logs it as status: `draft`. |
| **🚀 Send Immediately** | POSTs to `create-campaign.php` with `send_campaign=1`. Sendy queues + dispatches via SES. WordPress logs status: `sent`. If **Auto-Trigger Cron** is on, plugin also hits `scheduled.php?i=BRAND_ID`. |
| **⏰ Schedule** | WordPress stores the campaign as a `qrnss_campaign` post with status `scheduled`, and registers a one-off `wp_schedule_single_event` for the chosen datetime. When the event fires, plugin sends via Sendy. Datetime picker is timezone-aware and uses your WP timezone setting. |

### 🛟 Failure handling

Failed sends never silently disappear:

- ❌ Red admin notice at the top of the Campaigns screen
- 📋 Exact Sendy error in the **Error** column
- 🔁 One-click **Retry Send** button (CSRF-nonced)
- ♻️ **Auto-recovery** — overdue scheduled campaigns automatically send on next admin page-load (in case WP-Cron didn't fire)

---

## 📦 Installation

### From WordPress.org

1. **Plugins → Add New** → search for *Quillrush Newsletter Studio for Sendy*
2. **Install Now → Activate**

### Final setup

1. Open **Quillrush Newsletter** in the admin sidebar.
2. Fill in **Sendy Installation URL**, **API Key**, and (recommended) **Brand ID**.
3. Hit **Save Settings**.
4. Go to **Quillrush Newsletter → Create Newsletter** and your Sendy lists appear as checkboxes with subscriber counts.

---

## 📖 Usage

1. **Pick a Newsletter Format** — *The Roundup* or *The Insider Brief*.
2. **Select a Banner** (optional) — uses WordPress Media Library; falls back to the hero post's featured image if you skip it.
3. **Add Posts** — search the input, scroll for more (10 at a time), click **Add**.
   - 1st post = **Hero**, rest = **Grid items**.
4. **Set Subject Line, From Name, From Email** — From fields pre-fill from your Settings defaults.
5. **Tick the lists & segments** you're shipping to — pulled live from Sendy.
6. **Choose Action**:
   - 💾 Save as Draft
   - 🚀 Send Immediately
   - ⏰ Schedule (datetime picker)
7. **Click Create Campaign**. Watch the right pane for the live preview.

---

## 🗄️ Campaign history (CPT)

Every campaign you create is stored as a `qrnss_campaign` custom-post-type entry. Admin columns:

| Column | Shows |
|---|---|
| **Title** | Subject line |
| **Status** | `draft` (grey) · `scheduled` (yellow) · `sent` (green) · `failed` (red) |
| **Scheduled For** | Datetime if scheduled |
| **Error** | Sendy error message if the send failed |

Status colours make it easy to scan history at a glance.

---

## 🔌 Sendy API calls

| Endpoint | When | What's sent |
|---|---|---|
| `api/subscribers/active-subscriber-count.php` | List cache refresh | `api_key`, `list_id` per list |
| `api/lists/get-lists.php` | Settings save + Create Newsletter screen (cached 10 min) | `api_key`, `brand_id` |
| `api/campaigns/create.php` | Save Draft / Send Immediately / Scheduled fire | `api_key`, `from_name`, `from_email`, `reply_to`, `title`, `subject`, `html_text`, `plain_text`, `list_ids`, `brand_id`, `send_campaign` |
| `scheduled.php` (your Sendy host) | Only if **Auto-Trigger Cron** is ticked | `i=BRAND_ID` query param to kick the Sendy queue |

All requests go straight to **your own self-hosted Sendy installation** at the URL you set in Settings. The plugin never talks to any third-party SaaS — Sendy itself fans out to Amazon SES from your host.

---

## 🧰 WP-Cron / production scheduling

WP-Cron only fires on page-loads. Low-traffic sites can miss scheduled sends.

**Recovery (automatic)**
The plugin detects overdue scheduled campaigns and auto-sends them on your next admin page-load, with a green success notice confirming the send.

**Recommended (deterministic)**
Replace WP-Cron with a real system cron:

```php
// wp-config.php
define( 'DISABLE_WP_CRON', true );
```

```cron
# Server cron — every minute
* * * * * wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

---

## 🛠️ Plugins-screen integration

- **Action links** (next to Deactivate): `Settings` · `Support on Ko-fi`
- **Row meta**: `Plugin Support` (wordpress.org/support/plugin/quillrush-newsletter-studio-for-sendy) · `Contact Developer` (mailto)

---

## 🆙 Migrating from a predecessor install

If you previously ran an earlier release line under a different option/CPT/meta prefix, the plugin **auto-migrates everything on first load**:

- ✅ All matching options → `qrnss_*` (including dynamic list-cache transients)
- ✅ Custom-post-type rows → `qrnss_campaign`
- ✅ Every legacy post-meta key → `_qrnss_*`
- ✅ Any pending legacy `*_send_scheduled_campaign` cron event → `qrnss_send_scheduled_campaign`
- ✅ Rewrite rules flushed

Each detected legacy prefix is guarded by a one-shot `qrnss_migrated_from_<prefix>` flag — runs at most once per install. Existing campaigns, drafts, scheduled sends, list-cache, and settings come across without manual intervention.

Steps:

1. **Deactivate** the previous plugin in WP Admin.
2. **Delete** it (your data lives in `wp_options` / `wp_posts` / `wp_postmeta`, not in the plugin folder — safe).
3. **Install + Activate** Quillrush Newsletter Studio for Sendy.
4. Migration fires automatically on first load. You'll see your existing campaigns under the new top-level menu.

---

## ❓ FAQ

<details>
<summary><b>Why didn't my scheduled campaign send on time?</b></summary>

WP-Cron only runs on page-loads. The plugin auto-recovers overdue campaigns on next admin page-load. For deterministic timing, replace WP-Cron with a system cron (see [WP-Cron section](#-wp-cron--production-scheduling)).
</details>

<details>
<summary><b>Campaign stuck on "Preparing to send..." in Sendy?</b></summary>

That means Sendy's own cron isn't running. Either:

- Tick **Auto-Trigger Cron** in plugin settings (plugin hits `scheduled.php?i=BRAND_ID` after each send), or
- Add a server cron on the Sendy host:

```cron
*/5 * * * * php /path/to/sendy/scheduled.php > /dev/null 2>&1
```

</details>

<details>
<summary><b>Lists don't appear in Create Newsletter?</b></summary>

Check **Sendy Installation URL**, **API Key**, and **Brand ID** in Settings. The plugin fetches lists via `api/lists/get-lists.php` which requires the Brand ID on most Sendy versions. Use the **Refresh lists from Sendy** link to bust the 10-minute cache.
</details>

<details>
<summary><b>A campaign failed. How do I retry?</b></summary>

Open **Quillrush Newsletter → Campaigns** (admin sidebar). Failed campaigns show the exact Sendy error and a **Retry Send** button. The retry clears the previous error before re-sending.
</details>

<details>
<summary><b>Can I customise the editorial copy without touching code?</b></summary>

Yes — every line of "The Insider Brief" (greeting, intro, hero label, grid heading, "Why this matters", collaboration block, About Us) lives under **Settings → The Insider Brief — Template Texts**. HTML allowed where appropriate.
</details>

<details>
<summary><b>Does it work with WordPress 7.0 / PHP 7.4+?</b></summary>

Yes. Tested up to WordPress 7.0. PHP 7.4 minimum.
</details>

---

## 🔒 Requirements

| | |
|---|---|
| **WordPress** | 5.8 or higher (tested up to 7.0) |
| **PHP** | 7.4 or higher |
| **Sendy** | Any reasonably recent self-hosted Sendy install |
| **Amazon SES** | Configured inside Sendy (out of scope for this plugin) |

---

## 📝 Changelog

### 1.6.0
- **WordPress 7.0 tested** and audited; PHP minimum bumped to 7.4.
- Added `Requires at least`, `Tested up to`, `Requires PHP` headers to the main plugin file.
- **Auto-migration on first load** (priority 1) for predecessor installs — sweeps every legacy option, custom-post-type row, post-meta key, and pending scheduled cron event to the current `qrnss_*` namespace. Per-prefix `qrnss_migrated_from_<prefix>` guard flag so each migration path runs at most once per install.
- New **Support on Ko-fi** action link next to Deactivate on the Plugins screen.
- New **Plugin Support** + **Contact Developer** row-meta links.
- New "Support the developer" card on the settings screen with Ko-fi button, Plugin Support Forum, and Contact Developer mailto.
- Donate link set to Ko-fi.

### 1.5.2
- Fix: Infinite scroll in the "Add Posts" panel now triggers correctly. Scroll handler bound directly to the results container instead of delegated.

### 1.5.1
- Improvement: "About Us" body in The Insider Brief footer block now centered.
- Cleanup: Removed Buy Me A Coffee references and Support card from settings, builder, plugin row meta, and readme files.

### 1.5.0
- Feature: "Add Posts" panel supports infinite scroll. Older posts load automatically as you scroll (10 per batch).
- Internal: `qrnss_search_posts` AJAX endpoint accepts a `page` parameter and returns `{ posts, page, has_more }`.

### 1.4.x
- Auto-fetched Sendy lists with active subscriber counts. Cached 10 min.
- Pre-checks the lists used in the last send.
- Editorial format ("The Insider Brief") added alongside The Roundup.

### 1.3.x
- Scheduled sending with timezone-aware datetime picker.
- Failed-campaign retry. Auto-recovery for overdue campaigns.

### 1.0.0
- Initial release.

---

## 💖 Support

If this plugin saves you time or money, back the development on Ko-fi:

[![Support on Ko-fi](https://img.shields.io/badge/Ko--fi-Support%20on%20Ko--fi-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gunjanjaswal)

Other ways to help:
- ⭐ Leave a review on the WordPress.org plugin page
- 💬 [Plugin Support Forum](https://wordpress.org/support/plugin/quillrush-newsletter-studio-for-sendy/)
- ✉️ [hello@gunjanjaswal.me](mailto:hello@gunjanjaswal.me)

---

## 📄 License

GPL-2.0-or-later — same as WordPress. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

<div align="center">

**Built by [Gunjan Jaswal](https://gunjanjaswal.me)** · [gunjanjaswal.me](https://gunjanjaswal.me) · [hello@gunjanjaswal.me](mailto:hello@gunjanjaswal.me)

</div>
