=== Quillrush Newsletter Studio for Sendy ===
Contributors: gunjanjaswal
Tags: sendy, sendy-ses, amazon-ses, newsletter, email-marketing
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.0
Donate link: https://ko-fi.com/gunjanjaswal
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Newsletter studio for the Sendy + Amazon SES stack. Visual builder, two formats, scheduling, multi-list, auto-fetched lists.

== Description ==

📧 **Quillrush Newsletter Studio for Sendy** turns your WordPress dashboard into a full-blown newsletter creation studio for the **Sendy + Amazon SES** stack. Drag your latest posts into a beautiful responsive HTML email, pick the Sendy lists/segments to ship to, and send via your self-hosted Sendy installation, which forwards every message through Amazon SES. No HTML coding. No external SaaS. No paid tier.

> ⚠️ **Requires a self-hosted Sendy install backed by Amazon SES.** This plugin is the WordPress front-end — it does not replace Sendy or SES. If you don't have Sendy + SES set up yet, see [sendy.co](https://sendy.co/) and [aws.amazon.com/ses](https://aws.amazon.com/ses/).

= ✨ Headline features =

* 🎨 **Visual newsletter builder** — drag posts in, see the rendered email update live.
* 📨 **Two newsletter formats per campaign** — *The Roundup* (subscriber-facing) and *The Insider Brief* (editorial pitch for media & partners).
* 📋 **Auto-fetched Sendy lists & segments** — pulled live via the Sendy API, shown as checkboxes with active subscriber counts. Cached 10 minutes; one-click refresh.
* 🧠 **Remembers your last selection** — the lists you sent to last time are pre-checked next time.
* 🔍 **Infinite-scroll post search** — AJAX-loads posts in batches of 10 as you scroll.
* 🖼️ **Smart hero image** — uses your uploaded banner if present, otherwise falls back to the first post's featured image.
* 📱 **Mobile-first responsive layout** — 2 columns on desktop, single column on mobile (< 600px), auto-height cards.
* 📅 **Three send modes** — Save as Draft in Sendy, Send Immediately, or Schedule for a future date/time.
* 🕐 **Timezone-aware scheduling** — uses your WordPress timezone; shows current server time + zone next to the picker.
* ♻️ **Auto-recovery for overdue campaigns** — if WP-Cron didn't fire on time, overdue scheduled campaigns are auto-sent on next admin page-load.
* 🛠️ **Retry on failure** — failed sends show the exact Sendy error + a one-click Retry Send button.
* ⚙️ **Optional cron trigger** — after each send, optionally hit `scheduled.php?i=BRAND_ID` on your Sendy host so queued campaigns process without a server cron.
* ✍️ **Custom footer block** — extra text/HTML above the footer in a highlighted box. Supports anchor tags + auto `nl2br`.
* 🌐 **Social footer icons** — Instagram, LinkedIn, X (Twitter), YouTube.
* 🗃️ **Campaign history** — every campaign stored as a `qrnss_campaign` custom-post-type entry with Status / Scheduled For / Error columns.
* 🔒 **WordPress-native security** — nonces on every action, `manage_options` capability checks, `wp_safe_redirect()` for all redirects.

= 📨 The two newsletter formats =

🗞️ **The Roundup** — visual hero + 2-column story grid for your subscribers. Uses the "Custom Footer Text" highlighted box.

✉️ **The Insider Brief** — personal greeting, lead paragraph, centered hero with featured image, "What Else We're Seeing" 2-column grid, "Why this matters" callout, "For Media & Collaborations" CTA block, and a centered About Us block. Built for media pitches & partner updates. All copy editable from *Settings → The Insider Brief — Template Texts*.

Pick the format per-campaign on the Create Newsletter page (Design Settings → Newsletter Format). Header and dark footer (logo, social, copyright, unsubscribe) are shared across both formats.

= 📋 Full settings reference =

🔌 **Sendy Connection Settings**

* **Sendy Installation URL** — base URL of your Sendy install (e.g. `https://sendy.yourdomain.com/`).
* **API Key** — from Sendy → Settings → Your API Key.
* **Brand ID (Optional)** — from Sendy → Settings → Your Brand → ID. Required by some Sendy versions and used for auto-fetching lists.
* **Default From Name** — pre-filled into every new campaign.
* **Default From Email** — pre-filled into every new campaign.
* **Auto-Trigger Cron** — checkbox. After sending, hit `<sendy-url>/scheduled.php?i=BRAND_ID` so queued campaigns process without a system cron.
* **Show Article Excerpt** — checkbox. Insert a 20-word excerpt between the post title and the "Read More" button.

🎨 **Footer & Social Settings**

* **Footer Logo URL** — logo shown in the dark footer band.
* **Copyright Text** — footer copyright line. `{year}` is replaced with the current year.
* **Custom Footer Text** — textarea, HTML allowed. Shown in a highlighted box above the footer in *The Roundup*. Newlines → `<br>` automatically.
* **"Read More Articles" Link** — optional link below the post grid.
* **Instagram URL** / **LinkedIn URL** / **X (Twitter) URL** / **YouTube URL** — social icons in footer.

✉️ **The Insider Brief — Template Texts** *(used only by The Insider Brief format)*

* **Greeting** — e.g. `Hi [First Name],`
* **Intro Paragraph** — lead paragraph above the hero story (HTML allowed).
* **Hero Section Label** — small label above the hero (e.g. `Hero Story`).
* **Grid Section Heading** — e.g. `🔍 What Else We're Seeing`
* **"Why This Matters" Heading + Body**
* **Collaboration Heading + Body** — e.g. `📩 For Media & Collaborations` + CTA bullets and contact info (HTML allowed).
* **About Us Heading + Body** — centered About block above the footer.

= 📅 Send modes =

* 💾 **Save as Draft in Sendy** — POSTs to `create-campaign.php` with `send_campaign=0`. Campaign appears in your Sendy dashboard as a draft. WP logs status: `draft`.
* 🚀 **Send Immediately** — POSTs to `create-campaign.php` with `send_campaign=1`. Sendy queues + dispatches via SES. WP logs status: `sent`. If *Auto-Trigger Cron* is on, also hits `scheduled.php?i=BRAND_ID`.
* ⏰ **Schedule** — stored as a `qrnss_campaign` post with status `scheduled`. WordPress registers a one-off `wp_schedule_single_event` for the chosen datetime. When the event fires, the plugin sends via Sendy. Datetime picker is timezone-aware and uses your WP timezone setting.

= 🛟 Failure handling =

Failed sends never silently disappear:

* ❌ Red admin notice at the top of the Campaigns screen.
* 📋 Exact Sendy error in the **Error** column.
* 🔁 One-click **Retry Send** button (CSRF-nonced).
* ♻️ Auto-recovery — overdue scheduled campaigns automatically send on next admin page-load.

= 🗄️ Campaign history (CPT) =

Every campaign is stored as a `qrnss_campaign` custom-post-type entry. Admin columns:

* **Title** — subject line.
* **Status** — `draft` (grey), `scheduled` (yellow), `sent` (green), `failed` (red).
* **Scheduled For** — datetime if scheduled.
* **Error** — Sendy error message if the send failed.

= 🔌 Sendy API endpoints used =

All requests go straight to **your own self-hosted Sendy installation** at the URL you set in Settings. The plugin never talks to any third-party SaaS — Sendy itself fans out to Amazon SES from your host.

* `api/lists/get-lists.php` — fetch lists for the Create Newsletter screen (cached 10 min). Sends: `api_key`, `brand_id`.
* `api/subscribers/active-subscriber-count.php` — per-list subscriber counts. Sends: `api_key`, `list_id`.
* `api/campaigns/create.php` — Save as Draft, Send Immediately, scheduled fire. Sends: `api_key`, `from_name`, `from_email`, `reply_to`, `title`, `subject`, `html_text`, `plain_text`, `list_ids`, `brand_id`, `send_campaign`.
* `scheduled.php` (your Sendy host) — only when *Auto-Trigger Cron* is ticked. Sends: `i=BRAND_ID` query param.

= 🔒 Requirements =

* WordPress 5.8 or higher (tested up to 7.0).
* PHP 7.4 or higher.
* A reasonably recent self-hosted Sendy installation.
* Amazon SES configured inside Sendy (out of scope for this plugin).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/quillrush-newsletter-studio-for-sendy`, or install via Plugins → Add New.
2. Activate the plugin through the Plugins screen.
3. Open **Quillrush Newsletter** in the admin sidebar.
4. Fill in **Sendy Installation URL**, **API Key**, and (recommended) **Brand ID**. Hit **Save Settings**.
5. Go to **Quillrush Newsletter → Create Newsletter** — your Sendy lists appear as checkboxes with subscriber counts. Build away!

= 🆙 Migrating from a predecessor install =

If you previously ran an earlier release line under a different option/CPT/meta prefix (e.g. `sssb_*` or `pvnss_*`), the plugin auto-migrates everything on first load:

* All matching options → `qrnss_*` (including dynamic list-cache transients)
* Custom-post-type rows → `qrnss_campaign`
* Every legacy post-meta key → `_qrnss_*`
* Any pending legacy `*_send_scheduled_campaign` cron event → `qrnss_send_scheduled_campaign`
* Rewrite rules flushed

Each legacy prefix is guarded by a one-shot `qrnss_migrated_from_<prefix>` flag — runs at most once per install. Existing campaigns, drafts, scheduled sends, list-cache, and settings come across without manual intervention.

Steps:

1. Deactivate the previous plugin in WP Admin.
2. Delete it (your data lives in `wp_options` / `wp_posts` / `wp_postmeta`, safe).
3. Install + Activate Quillrush Newsletter Studio for Sendy.
4. Migration fires automatically on first load. Existing campaigns appear under the new top-level menu.

== Frequently Asked Questions ==

= Why didn't my scheduled campaign send on time? =

WP-Cron only runs on page-loads. The plugin auto-recovers overdue campaigns on next admin page-load (you'll see a green success notice). For deterministic timing on production sites, replace WP-Cron with a system cron:

`define('DISABLE_WP_CRON', true);` in `wp-config.php`, then add a server cron:

`* * * * * wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

= My campaign is stuck on "Preparing to send..." in Sendy. =

Sendy's own cron isn't running. Either:

* Tick **Auto-Trigger Cron** in plugin settings, OR
* Add a server cron on the Sendy host:

`*/5 * * * * php /path/to/sendy/scheduled.php > /dev/null 2>&1`

= My lists don't appear in Create Newsletter. =

Check **Sendy Installation URL**, **API Key**, and **Brand ID** in Settings. The plugin fetches lists via `api/lists/get-lists.php` which requires the Brand ID on most Sendy versions. Use the **Refresh lists from Sendy** link on the Create Newsletter page to bust the 10-minute cache.

= A campaign failed. How do I retry? =

Open **Quillrush Newsletter → Campaigns** in the admin sidebar. Failed campaigns show the exact Sendy error and a **Retry Send** button. The retry clears the previous error before re-sending.

= Can I customise the editorial copy without touching code? =

Yes. Every line of "The Insider Brief" (greeting, intro, hero label, grid heading, "Why this matters", collaboration block, About Us) lives under **Settings → The Insider Brief — Template Texts**. HTML allowed where appropriate.

= Does it work with WordPress 7.0 / PHP 7.4+? =

Yes. Tested up to WordPress 7.0. PHP 7.4 minimum.

= Where does the plugin send data? =

Only to **your own self-hosted Sendy installation** at the URL you set in Settings. No third-party SaaS. Sendy itself handles the SES handoff from your host.

== Screenshots ==

1. Newsletter Builder — search for posts, drag them into the layout, watch the live preview.
2. Settings → Sendy Connection — Installation URL, API Key, Brand ID, defaults.
3. Settings → The Insider Brief Texts — every editorial line customisable.
4. Campaigns CPT — status colours at a glance (draft / scheduled / sent / failed).
5. Responsive email preview — 2-column desktop → single-column mobile.

== Changelog ==

= 1.6.0 =
* **WordPress 7.0 tested** and audited; PHP minimum bumped to 7.4.
* Added `Requires at least`, `Tested up to`, and `Requires PHP` headers to the main plugin file.
* Added one-time auto-migration on first load (priority 1) for predecessor installs: sweeps every legacy option, custom-post-type row, post-meta key, and pending scheduled cron event to the current `qrnss_*` namespace. Per-prefix guard flag so each migration path runs at most once.
* Added **Support on Ko-fi** action link next to Deactivate on the Plugins screen.
* Added **Plugin Support** (wordpress.org/support/plugin/quillrush-newsletter-studio-for-sendy) and **Contact Developer** (mailto:hello@gunjanjaswal.me) row-meta entries.
* Added "Support the developer" card on the Settings screen with branded Ko-fi button, Plugin Support Forum link, and Contact Developer mailto.
* Donate link set to Ko-fi (https://ko-fi.com/gunjanjaswal).

= 1.5.2 =
* Fix: Infinite scroll in the "Add Posts" panel now actually triggers. The scroll handler was using event delegation which doesn't work for scroll events; it's now bound directly to the results container.

= 1.5.1 =
* Improvement: "About Us" body in The Insider Brief footer block is now centered (heading was already centered).
* Cleanup: Removed all "Buy Me A Coffee" links and the Support card from the settings page, newsletter builder, plugin row meta, and readme files.

= 1.5.0 =
* Feature: "Add Posts" panel now supports infinite scroll. Older posts load automatically as you scroll the results list (10 at a time).
* Internal: `qrnss_search_posts` AJAX endpoint now accepts a `page` parameter and returns `{ posts, page, has_more }`.

= 1.4.x =
* Feature: Auto-fetched Sendy lists with active subscriber counts. Cached 10 minutes; one-click refresh.
* Feature: Plugin remembers the lists used in your last send and pre-checks them next time.
* Feature: Editorial format ("The Insider Brief") added alongside The Roundup. All copy editable from settings.

= 1.3.x =
* Feature: Scheduled sending with timezone-aware datetime picker.
* Feature: Failed-campaign retry and auto-recovery for overdue campaigns.
* Fix: Schedule datetime picker with proper z-index for calendar visibility.
* Fix: Reply-to email correctly passed for all campaign types.
* Fix: Resolved "cURL error 28: SSL connection timeout".
* Security: All inputs properly sanitized and validated.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.6.0 =
WordPress 7.0 tested; PHP 7.4 minimum. Adds one-time auto-migration from predecessor installs (settings, campaigns, post-meta, list-cache, and pending scheduled sends), Ko-fi support link, Plugin Support + Contact Developer row meta, and a Support card on the Settings screen.
