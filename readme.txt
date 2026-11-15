=== Disable Random Content ===
Contributors: kaggdesign, ernest35
Tags: random content, redirects, permalinks
Requires at least: 7.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable Random Content turns off WordPress 7.2 random content redirects. Activate it and you're done; no settings required.

== Description ==

Don't want WordPress to send visitors to a random post? Disable Random Content turns off the random content redirect feature introduced in WordPress 7.2. Install and activate it; there are no settings to configure.

=== What it does ===

* Stops redirects from WordPress's random URLs, such as `/random/` and random archive routes.
* Prevents requests using the `random` query parameter from selecting and redirecting to a random post. The underlying archive or search request continues normally.
* Leaves your published content available at its usual URLs.
* Handles permalink rules automatically on individual sites and Multisite networks.

It does not affect unrelated random-order queries in themes or other plugins.

== Installation ==

1. In WordPress, go to Plugins > Add New and search for "Disable Random Content".
2. Install and activate the plugin. On Multisite, activate it for one site or across the network.

No settings or manual permalink changes are required.

== Frequently Asked Questions ==

= What exactly does the plugin disable? =

It disables WordPress's random content redirects, including the default `/random/` routes and redirects triggered by the `random` query parameter. It uses the `wp_enable_random_content_redirect` filter.

= Does it disable random ordering in themes or other plugins? =

No. Unrelated queries that explicitly request random ordering are unaffected.

= What happens to `/random/` after activation? =

The special random redirect route is removed. The URL follows your site's normal routing rules; if nothing else handles it, it normally returns a 404 response.

= Does it change my posts, pages, or menus? =

No. It does not edit content or menus. Published content remains available at its usual URLs.

= Do I need to resave my permalinks? =

No. The plugin refreshes permalink rules when activated or deactivated on an individual site. On Multisite, a network activation change clears saved rules for affected sites, and WordPress rebuilds them on each site's next request.

= Does it support WordPress Multisite? =

Yes. You can activate it for an individual site or across the network.

= Can I turn random content redirects back on? =

Yes. Deactivate the plugin. Other code on your site may still disable the feature.

= Can I exclude only some categories or post types? =

Not yet. If you need to exclude specific categories or post types, please send us a feature request, and we'll add this functionality to the plugin.

= Does the plugin contact external services or store data? =

The plugin makes no external requests and adds no tracking or separate data store. It does update WordPress's saved permalink rules when its activation status changes.

== Changelog ==

= 1.1.0 =
* Keep random content redirects disabled after deactivation and reactivation in the same request.

= 1.0.0 =
* Disable random content redirects and their rewrite rules.
* Refresh site and network rewrite rules when activation status changes.
