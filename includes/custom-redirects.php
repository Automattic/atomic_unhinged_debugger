<?php

class Atomic_Unhinged_Debugger {


	public function index_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			"require __DIR__ . '/wp-blog-header.php';",
			"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_blog_header_dot_php() );",
			substr( file_get_contents( ABSPATH . 'index.php' ), 6 )
		);
	}

	public function wp_blog_header_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			"require_once __DIR__ . '/wp-load.php';",
			"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_load_dot_php() );",
			substr( file_get_contents( ABSPATH . 'wp-blog-header.php' ), 6 )
		);
	}

	public function wp_load_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			array(
				"require_once dirname( ABSPATH ) . '/wp-config.php';",
				"require_once ABSPATH . WPINC . '/load.php';",
			),
			array(
				"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_config_dot_php() );",
				"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_includes_slash_load_dot_php() );",
			),
			substr( file_get_contents( ABSPATH . 'wp-load.php' ), 6 )
		);
	}


	public function http_before( $ch ) {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		$info = curl_getinfo( $ch );
		AUD_Timer::instance()->start( 'http_curl', $info['url'] );
	}

	public function http_after( $params = null ) {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		AUD_Timer::instance()->stop();
	}

	public function wp_config_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return preg_replace(
			'/^.*wp-settings\.php.*$/m',
			"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_settings_dot_php() );",
			substr( file_get_contents( '/srv/htdocs/wp-config.php' ), 6 )
		);
	}

	public function class_wp_plugin_dependencies_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			"require_once ABSPATH . WPINC . '/plugin.php';",
			"# require_once ABSPATH . WPINC . '/plugin.php';",
			substr( file_get_contents( ABSPATH . 'wp-includes/class-wp-plugin-dependencies.php' ), 6 )
		);
	}

	public function wp_settings_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			array(
				"require_once ABSPATH . WPINC . '/plugin.php';",
				"require ABSPATH . WPINC . '/class-wp-plugin-dependencies.php';",
				"require ABSPATH . WPINC . '/load.php';",
			),
			array(
				implode(
					"\n",
					array(
						sprintf( "require_once '%s/../wp-includes/plugin.php';", __DIR__ ),
						"add_action( 'requests-curl.before_send', array( \$GLOBALS['__atomic_unhinged_debugger'], 'http_before' ) );",
						"add_action( 'requests-curl.after_send', array( \$GLOBALS['__atomic_unhinged_debugger'], 'http_after' ) );",
					)
				),
				"eval( \$GLOBALS['__atomic_unhinged_debugger']->class_wp_plugin_dependencies_dot_php() );",
				"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_includes_slash_load_dot_php() );",
			),
			substr( file_get_contents( ABSPATH . 'wp-settings.php' ), 6 )
		);
	}

	public function admin_ajax_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			"require_once dirname( __DIR__ ) . '/wp-load.php';",
			"eval( \$GLOBALS['__atomic_unhinged_debugger']->wp_load_dot_php() );",
			substr( file_get_contents( ABSPATH . 'wp-admin/admin-ajax.php' ), 6 )
		);
	}

	public function wp_includes_slash_load_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
				'$wpdb = new wpdb( $dbuser, $dbpassword, $dbname, $dbhost );',
				"require_once '" . __DIR__ . "/class-aud-wpdb.php';\n" . '$wpdb = new aud_wpdb( $dbuser, $dbpassword, $dbname, $dbhost );',
				substr( file_get_contents( ABSPATH . 'wp-includes/load.php' ), 6 )
		);
	}

	public function get_scripts_slash_env_dot_php_from_frame_for_eval( $frame ) {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );

		$scripts_slash_env_dot_php = array_slice(
			file( '/scripts/env.php' ),
			$frame['line']+1
		);

		$scripts_slash_env_dot_php_eval = array();
		$inside_function = false;
		foreach ( $scripts_slash_env_dot_php as $line ) {
			if ( true === $inside_function ) {
				if ( "}\n" !== $line ) {
					continue;
				}
				$inside_function = false;
				continue;
			}
			if ( 0 === strpos( $line, 'function ', 0 ) ) {
				$scripts_slash_env_dot_php_eval[] = "# function redacted to avoid redeclaration\n";
				$inside_function = true;
				continue;
			}
			$scripts_slash_env_dot_php_eval[] = $line;
		}
		return implode( '', $scripts_slash_env_dot_php_eval );
	}

	public function wp_cron_dot_php() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		return str_replace(
			"define( 'DOING_CRON', true );",
			"define( 'DOING_CRON', true );\neval( \$GLOBALS['__atomic_unhinged_debugger']->wp_load_dot_php() );",
			substr( file_get_contents( ABSPATH . 'wp-cron.php' ), 6 )
		);
	}

	public function should() {
		if ( empty( $_COOKIE['enableAUD'] ) ) {
			return false;
		}
		switch ( $_SERVER['SCRIPT_FILENAME'] ) {
		case '/srv/htdocs/__wp__/index.php':
			return true;
			break;
		case '/srv/htdocs/__wp__/wp-cron.php':
			error_log( 'should(): ' . __LINE__ );
			return true;
		case '/srv/htdocs/__wp__/wp-admin/admin-ajax.php':
			return true;
		}
		return false;
	}

	public function get_starting_eval() {
		error_log( sprintf( '%s->%s', __CLASS__, __FUNCTION__ ) );
		switch ( $_SERVER['SCRIPT_FILENAME'] ) {
		case '/srv/htdocs/__wp__/index.php':
			return $this->index_dot_php();
		case '/srv/htdocs/__wp__/wp-cron.php':
			return $this->wp_cron_dot_php();
		case '/srv/htdocs/__wp__/wp-admin/admin-ajax.php':
			return $this->admin_ajax_dot_php();
		}
		
	}

	public function __construct() {
	}
}

$GLOBALS['__atomic_unhinged_debugger'] = new Atomic_Unhinged_Debugger();
if ( true !== $GLOBALS['__atomic_unhinged_debugger']->should() ) {
	return;
}

$__atomic_unhinged_debugger_resume_frame = array_pop( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 ) );
if ( '/scripts/env.php' !== $__atomic_unhinged_debugger_resume_frame['file'] ) {
	return;
}
eval( $GLOBALS['__atomic_unhinged_debugger']->get_scripts_slash_env_dot_php_from_frame_for_eval( $__atomic_unhinged_debugger_resume_frame ) );
require __DIR__ . '/class-aud-timer.php';
define( 'ABSPATH', dirname( realpath( __DIR__ . '/../../__wp__/wp-load.php' ) ) . '/' );
unset( $_COOKIE['enableAUD'] );
eval( $GLOBALS['__atomic_unhinged_debugger']->get_starting_eval() );
exit( 0 );
