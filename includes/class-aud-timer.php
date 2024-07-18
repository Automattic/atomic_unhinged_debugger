<?php

class AUD_Timer {

	private $fp;
	private $depth  = 0;
	private $timers = array();

	const VERSION = 1;

	private function stringify_thing( $thing ) {
		$type = gettype( $thing );
		switch ( $type ) {
		case 'string':
			return $thing;
		case 'array':
			$new_array = array();
			foreach( $thing as $idx => $value ) {
				$new_array[ $idx ] = $this->stringify_thing( $value );
			}
			return implode( ',', $new_array );
		case 'object':
			$class = get_class( $thing );
			if ( $class === 'Closure' ) {
				$r = new ReflectionFunction($thing);
				$name = $r->getName();
				$file = $r->getFileName();
				if ( $file ) {
					$name .= ' ' . $file;
					$line = $r->getStartLine();
					if ( $line ) {
						$name .= ':' . $line;
					}
				}
				return $name;
			}
			return $class;
		default:
			return $type;
		}
	}

	public function __construct() {
		$this->fp = fopen(
			tempnam( '/tmp/', 'pat-' . microtime( true ) . '-' . getmypid() . '-' ),
			'w'
		);
		fprintf( $this->fp, "%d\t%s\t%s\t%s\n", self::VERSION, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] );
	}

	public static function instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new AUD_Timer();
		}
		return $instance;
	}

	public function start( $kind_of_thing, $name_of_thing ) {
		$kind_name = $this->stringify_thing( $kind_of_thing );
		$name_name = $this->stringify_thing( $name_of_thing );
		if ( 0 === strpos( $name_name, 'Atomic_Unhinged_Debugger,' ) ) {
			return;
		}
		$this->timers[] = array( hrtime( true ), 0, $this->depth, json_encode( $kind_name ), json_encode( $name_name ) );
		$this->depth++;
	}

	public function stop( $kind_of_thing = '', $name_of_thing = '' ) {
		$at = hrtime( true );
		$kind_name = $this->stringify_thing( $kind_of_thing );
		$name_name = $this->stringify_thing( $name_of_thing );
		if ( 0 === strpos( $name_name, 'Atomic_Unhinged_Debugger,' ) ) {
			return;
		}
		$row = array_pop( $this->timers );
		$this->depth--;
		$row[1] = $at;
		fprintf(
			$this->fp,
			"%d\t%d\t%d\t%s\t%s\t\n",
			$row[0], // start nanoseconds
			$row[1], // stop nanoseconds
			$row[2], // depth
			$row[3], // kind of thing
			$row[4]  // thing	
		);
	}
}
