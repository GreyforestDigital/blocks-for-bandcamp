=== Blocks for Bandcamp ===
Plugin URI: https://www.greyforest.digital/plugins/blocks-for-bandcamp
Author: Greyforest
Author URI: https://www.greyforest.digital
Contributors: GreyforestDigital
Donate link: https://www.greyforest.digital/donate
Tags: bandcamp, band, embed, merch, album
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.3.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg blocks for Bandcamp with functions for embedding merchandise, discography, featured albums, audio players, and download code forms.

== Description ==

A collection of Gutenberg blocks for Bandcamp with functions for embedding merchandise, discography, featured albums, audio players, and download code forms.

Blocks for Bandcamp is a 100% free WordPress plugin that bridges your Bandcamp presence with your WordPress site by providing a suite of Gutenberg blocks specifically made for Bandcamp artists, labels, and music promoters to direct fans to your site while still selling music and merch from Bandcamp. This plugin gives you easy drag and drop editor tools with tons of customization options to showcase your music, merchandise, and download code forms with full customization — no API keys, logging in, or technical setup required.

This plugin was created with love by a musician, so that any band, solo artist, composer, poet, comedian, collective, sound artist, audiobook, podcast, record label, music blog, or fan can share Bandcamp content in a new and unique way on their own website.

Does not require API connection or any login - works for any public Bandcamp account / album.

### ALBUM
* Display information about a single album from Bandcamp account
* Header section with cover art, title, artist, release date, and links
    * Options to toggle display of each element
* Playlist section with custom HTML5 player displaying tracks
* Merch footer section for displaying items connected to album
    * Product photos, format, title, description, price, quantity remaining, and more
* Option to only display certain sections (header / playlist / merch)

### DISCOGRAPHY
* Display all releases from a Bandcamp account based on albums from /music page
* Grid of releases with cover art, title, artist, and link button
    * Options to toggle display of each element
* Layout options for responsive grid Column Count (desktop + mobile)
* Display options for release borders, padding, alignment, colors, and more

### EMBED
* Custom options for embedding Bandcamp players (via shortcode, iFrame, or URL)
* Legacy [bandcamp] shortcode functionality that matches the one provided by Bandcamp

### FORM
* Generate and customize a custom download code redemption form
* Choose colors, borders, font size, padding, and other styles for input field and button independently

### MERCH
* Display a full listing of all merch items from Bandcamp account
* Option to display merch items from single album only
* Grid or List view options
* Display options for photos, album name, artist name, price, format, description, and more

### MINIPLAYER
* Display a mini audio player of a specific track via album URL
* Select track by number or set to album's "featured" track
* Display options for album name, artist name, track title, album link, progress bar, and colors

### COMPATIBILITY
* This plugin requires a minimum PHP version of 8.0
* This plugin requires Gutenberg Editor to be activated

#### NOTE TO ARTISTS & ACCOUNT OWNERS
The HTML5 audio players for the "Miniplayer" and "Album" blocks use a Bandcamp MP3, but any "plays" by visitors ARE NOT TRACKED OR COUNTED TOWARD YOUR BANDCAMP STATS.

"Plays" are only tracked/counted via the official Bandcamp "embed" option, available in the "Album" and "Embed" blocks.

#### NOTICE
*Your use of this plugin as an individual and a site owner is governed by the terms outlined on Bandcamp's ["Terms of Use"](https://bandcamp.com/terms_of_use) and ["Acceptable Use and Modern Policy"](https://get.bandcamp.help/hc/en-us/articles/23005947027991-Bandcamp-s-Acceptable-Use-and-Moderation-policy). This plugin makes use of publicly available data on Bandcamp album pages, but using or displaying data from other artists/accounts is explicitly forbidden.*

*This plugin is not affiliated with, endorsed by, or built in collaboration with Bandcamp.*


== Usecases ==

Create a "Discography" section on your website, with a custom page per album. Add the **ALBUM** block to embed the artwork, audio, and merch for each album in a unified way. It's a perfect way to generate a catalog of your work with visual customization per album.

Have a new album coming out and want to share a custom URL for promotion? Embed a **MERCH** block to display links to your pre-order merchandise products tied to that album, and use the **MINIPLAYER** block to show off the album artwork with a playable audio interface for any track.

Want to create a unique portal for fans to redeem your download codes? Use the **FORM** block to insert a simple input field & button for fans to enter their code in. Put it on a custom page with a full-screen video background or a blown-up version of the album art, and you just made an experience for that fan.

Tired of copying/pasting custom HTML to display your albums? Use the **EMBED** block to display any of the default Bandcamp audio players via pasting the URL of the album, the iFrame code, or the WordPress.com [bandcamp] code.

== Support ==

If you have questions, need help, or just want to share feedback, I recommend using the Support tab on the WordPress.org plugin page. It's the best way to reach out quickly and keep everything organized. I appreciate positive reviews if you liked this plugin or found it useful.

For general inquiries, you can also reach out at [https://www.greyforest.digital/contact](https://www.greyforest.digital/contact).

== Changelog ==

= 1.3.0 -> July 13th, 2026 =
* New: Custom HTML5 audio player interface for "Album" block with color options
* New: Accessibility improvements for all blocks (aria tags, semantic elements, etc)
* "Miniplayer" progress bar now allows time seeking throughout track

= 1.2.3 -> July 8th, 2026 =
* Implemented strict UTF-8 encoding for non-Latin characters in names and titles

= 1.2.2 -> June 9th, 2026 =
* Fixed styling in custom HTML5 audio player for tracks that don't have an MP3
* Resolved error log message for json_decode when API data array is empty
* Resolved error log message from unset key used to display tag for cached blocks upon first load in editor

= 1.2.1 -> June 1st, 2026 =
* Compatibility check for WordPress 7.0
* Fixed custom HTML5 audio player in "Album" block not setting featured track mp3 on load
* Updated URL embed type for "Embed" block to work with single tracks in addition to albums
* Added log entry if embed data sync is unsuccessful

= 1.2.0 -> April 25th, 2026 =
* New: "Discography" block to display all releases from artist as grid
* New: Caching via transients for all calls to Bandcamp == faster page loads
* New: Settings page for viewing and clearing transients and logs
* New: Logger for tracking all calls to Bandcamp for debugging
* Rebuilt internal API system for optimized logic + better user error messages
* SSRF hardening before wp_remote_get calls
* Optimized fetch calls for meta tags == faster initial syncs for album data
* CSS fix to prevent accidental external link clicks while in editor
* CSS tweak to Merch "card" layout items for better font-size inheritance
* Main product photo in Merch block links to product page if purchase buttons are hidden
* wp_kses_allowed_html function for safely outputting SVG + iframe with escaping

= 1.1.0 -> January 15th, 2026 =
* Compatibility check for WordPress 6.9
* Updated readme
* Updated minimum PHP version to 8.0

= 1.0.4 -> November 10th, 2025 =
* Fixed missing band_url in non-album merch links

= 1.0.3 -> November 5th, 2025 =
* Fixed Gutenberg component dependencies error
* Block Error CSS enqueuing fix

= 1.0.2 -> November 5th, 2025 =
* Discarded - commit error

= 1.0.1 -> October 1st, 2025 =
* "Album" playlist JS fix
* Fixed CSS targeting on frontend
* Admin CSS enqueuing fix

= 1.0.0 -> September 26th, 2025 =
* Initial commit to repository.