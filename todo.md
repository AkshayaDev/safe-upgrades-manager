## Calling core loading files directly

Calling core files like wp-config.php, wp-blog-header.php, wp-load.php directly via an include is not permitted.

These calls are prone to failure as not all WordPress installs have the exact same file structure. In addition it opens your plugin to security issues, as WordPress can be easily tricked into running code in an unauthenticated manner.

Your code should always exist in functions and be called by action hooks. This is true even if you need code to exist outside of WordPress. Code should only be accessible to people who are logged in and authorized, if it needs that kind of access. Your plugin's pages should be called via the dashboard like all the other settings panels, and in that way, they'll always have access to WordPress functions.

https://developer.wordpress.org/plugins/hooks/

There are some exceptions to the rule in certain situations and for certain core files. In that case, we expect you to use require_once to load them and to use a function from that file immediately after loading it.

If you are trying to "expose" an endpoint to be accessed directly by an external service, you have some options.
You can expose a 'page' use query_vars and/or rewrite rules to create a virtual page which calls a function. A practical example.
You can create an AJAX endpoint.
You can create a REST API endpoint.

Example(s) from your plugin:
custom-plugin-upgrader.php:217 require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
admin.php:50 require_once(ABSPATH . 'wp-admin/admin-header.php');
admin.php:28 require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
custom-theme-upgrader.php:198 require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
admin.php:101 include(ABSPATH . 'wp-admin/admin-footer.php');
admin.php:84 require_once(ABSPATH . 'wp-admin/admin-header.php');
admin.php:502 require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
admin.php:67 include(ABSPATH . 'wp-admin/admin-footer.php');