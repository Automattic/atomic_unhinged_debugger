<?php

class aud_wpdb extends wpdb {
	public function query( ...$args ) {
		AUD_Timer::instance()->start( 'query', $args[0] );
		$rval = parent::query(...$args);
		AUD_Timer::instance()->stop();
		return $rval;
	}
}

