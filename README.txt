=== Bio Link ===
Contributors: alirezach
Tags: instagram, bio link, link in bio, social media, automation
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Instagram-style bio-link page with photo grid, color/BW rendering, and comment-to-DM automation.

== Description ==

**Bio Link** creates an Instagram-style bio-link page on your WordPress site. Visitors see a grid of your Instagram photos, with linked photos in color and unlinked ones in black-and-white — just like Linktree, but with your own branding.

**Features:**

* **Instagram-style photo grid** — photos sorted like Instagram, with color (has link) / black-and-white (no link) rendering
* **Profile photo + bio** — display your profile picture and short bio above the grid
* **Image proxy via middle server** — works even from Iran (Instagram is filtered)
* **Comment-to-DM automation** — when someone comments a keyword on your Instagram post, they automatically receive a DM with your note and link
* **Follow gate** — optionally require followers before sending DMs
* **Instagram sort order** — photos ordered the same way as your Instagram profile
* **WordPress standards** — REST API, sanitization, capability checks, nonces
* **Open source** — GPL v2, fully hackable

**How it works:**

1. Add photos to the Bio Link menu (upload manually or link an Instagram post)
2. Optionally set a post link per photo (linked = color, no link = black & white)
3. Place the `[bio_link]` shortcode on any page
4. Done. Visitors see your Instagram-style grid.

**For Iran-based users:**

Instagram is filtered in Iran. Bio Link uses a **middle server** (Cloudflare Worker or your own VPS) to proxy Instagram images — your WordPress site never contacts Instagram directly.

**DM Automation requires:**

* Instagram Business or Creator account
* Facebook App with Instagram Graph API + Instagram Messaging permission
* Middle server deployed (see `bio-link-middle-server/` repo)

== Installation ==

1. Upload `bio-link-wp-plugin/` to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Bio Link' in the admin menu to configure
4. Add photos and set your profile photo + bio
5. Place `[bio_link]` shortcode on any page

== Frequently Questions ==

= Does this work from inside Iran? =
Yes, if you deploy the middle server (Cloudflare Worker or VPS). The middle server fetches Instagram images and your WordPress site talks to the middle server, not Instagram directly.

= Can I use this without the middle server? =
Yes — photos can be uploaded manually. But automatic fetching from Instagram requires the middle server.

= Is DM automation included in the plugin? =
The plugin provides the UI and REST API. The actual DM sending happens on the middle server (separate repo: `bio-link-middle-server`).

== Changelog ==

= 1.0.0 =
* Initial release
