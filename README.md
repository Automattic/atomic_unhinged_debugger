# atomic_unhinged_debugger

1. `git clone https://github.com/Automattic/atomic_unhinged_debugger.git` in `htdocs/`
2. create `custom-redirects.php` in `htdocs`
3. add `require __DIR__ . '/atomic_unhinged_debugger/includes/custom-redirects.php';` to `custom-redirects.php`
4. watch `/tmp/php-errors`
5. access the site
6. open `https://{your.site}/atomic_unhinged_debugger/` in your web browser
7. using your browsers debug tools set a cookie named `enableAUD` with the value `true` for your site and start digging...

Hopefully that works... ¯\\_(ツ)_/¯

# debugging

## plugin.php is not pluggable

If you see something like

```PHP Fatal error:  Cannot redeclare add_filter() (previously declared in /srv/htdocs/atomic_unhinged_debugger/wp-includes/plugin.php:127) in /wordpress/core/6.6/wp-includes/plugin.php on line 121```

Then there is some code, somewhere, that is doing something like this example from `wp-content/plugins/woocommerce/src/Utilities/PluginUtil.php` `require_once ABSPATH . WPINC . '/plugin.php';`. These will need to be fixed, unfortunately, by changing the code to something like `if ( ! function_exists( 'add_filter' ) ) { require_once ABSPATH . WPINC . '/plugin.php'; }`
