# zoneplusone
Zone Plus One System for IFL Facilities

This plugin contained in zoneplusone.php creates three tables --
* `wp_iflzpo_zones` for zone names, e.g. Electronics Zone
* `wp_iflzpo_zone_tokens` for registering zone tokens to users
* `wp_iflzpo_plus_one_zones` for storing the user ID, zone ID, and date when a member touches their token to a zone's plus-one sensor

-- and has many useful functions (and custom wp-admin menus) for working with the data/tables, including for the API in 
`rest-api.php` to use when a member does a plus-one with their token.

A web site "Dashboard" page is in process that will among other things display the tool reservation calendar and issue 
some kind of an alert every day at sunset (plus or minus some optimum-number-of-minutes offset, if that winds up being 
a good idea) so that people can go outside to watch the sunset -- the file `for-dashboard-page.txt` contains the 
WordPress source for that in-process page.

The file `for-functions-php-theme-functions-file.txt` contains the sunset functionality shortcode-in-progress to be 
added to the WordPress site's functions.php theme functions file.
