# atomic_unhinged_debugger

1. `git clone https://github.com/Automattic/atomic_unhinged_debugger.git` in `htdocs/`
2. create `custom-redirects.php` in `htdocs`
3. add `require __DIR__ . '/atomic_unhinged_debugger/includes/custom-redirects.php';` to `custom-redirects.php`
4. watch `/tmp/php-errors`
5. access the site
6. open `https://{your.site}/atomic_unhinged_debugger/` in your web browser
7. using your browsers debug tools set a cookie named `enableAUD` with the value `true` for your site and start digging...

Hopefully that works... ¯\\_(ツ)_/¯
