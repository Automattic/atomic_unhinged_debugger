<?php
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
ini_set( 'display_errors', 'on' );

function _canonical_charset() { return 'UTF-8'; }
function is_utf8_charset() { return true; }
function apply_filters($f, $in) { return $in; }
function get_option(){}
function wp_load_alloptions(){}
require '/wordpress/core/latest/wp-includes/formatting.php';
require '/wordpress/core/latest/wp-includes/pomo/translations.php';
require '/wordpress/core/latest/wp-includes/l10n.php';
require '/wordpress/core/latest/wp-includes/kses.php';

if ( !empty( $_GET['minms'] ) ) {
	$_GET['minms'] = preg_replace( '/[^0-9.]/', '', $_GET['minms'] );
}

function pattern_select_dropdown_menu() {
	?>
	<form id="pattern-dropdown">
		<input type="hidden" name="section" value="<?php esc_attr_e( $_GET['section'] ?? 'summary' ); ?>"/>
		<input type="hidden" name="minms" value="<?php esc_attr_e( $_GET['minms'] ?? '1' ); ?>"/>
		<select name="view">
			<option value="">Select a file</option>
	<?php
		chdir( '/tmp' );
		foreach( glob( 'pat-*' ) as $entry ) {
			if ( ! preg_match( '/^pat-[0-9]+\.[0-9]+-[0-9]+-[a-z0-9_-]+$/i', $entry ) ) {
				continue;
			}
			$entries[] = $entry;
		}
		$now = time();

		foreach( $entries as $entry ) {
			$fp = fopen( $entry, 'r' );
			$status = fgets( $fp );
			fclose( $fp );
			$selected = "";
			if ( $entry === ( $_GET['view'] ?? '' ) ) {
				$selected = "selected";
			}
	?>
		<option <?php echo $selected; ?> value="<?php echo $entry; ?>"><?php echo $status; ?>, <?php echo $now - filectime( $entry ); ?>s ago</option>
	<?php
	}

	?>
		</select>
		<button type="submit">submit</button>
	</form>
	<hr/>
	<?php
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<style>
		body {
			font-family: monospace;
		}
		td {
			white-space: nowrap;
		}
	</style>
	<link rel="stylesheet" href="bootstrap-5.3.3/css/bootstrap.min.css"/>
	<script src="htmx/htmx.min.js"></script>
</head>
<body>
	<div class="container" role="main">
<?php

pattern_select_dropdown_menu();

if ( !empty( $_GET['view'] ) && ! preg_match( '/^pat-[0-9]+.[0-9]+-[0-9]+-[a-z0-9_-]+$/i',  $_GET['view'] ) ) {
	unset( $_GET['view'] );
}

if ( !empty( $_GET['view'] ) && !file_exists( $_GET['view'] ) ) {
	unset( $_GET['view'] );
}

if ( empty( $_GET['view'] ) ) {
	return;
}

$data = array();
$fp   = fopen( $_GET['view'], 'r' );
$info = fgets( $fp );
?>
<div class="container text-center">
	<div class="row">
		<div class="col">
			<form style="" method="GET">
				<input type="hidden" name="view" value="<?php echo $_GET['view']; ?>"/>
				<input type="hidden" name="section" value="summary"/>
				<button>View Summary</button>
			</form>
		</div>
		<div class="col">
			<form style="" method="GET">
			<input type="hidden" name="view" value="<?php echo $_GET['view']; ?>"/>
			<input type="hidden" name="section" value="cumulative"/>
			<button>View cumulative</button>
			</form>
		</div>
		<div class="col">
			<form style="" method="GET">
				<input type="hidden" name="view" value="<?php echo $_GET['view']; ?>"/>
				<input type="hidden" name="section" value="individual"/>
				@<input style="width:5em;" type="text" name="at" placeholder="position" value="<?php esc_attr_e( $_GET['at'] ?? '' ); ?>"/>
				&gt;<input style="width:5em;" type="text" name="minms" placeholder="ms or more" value="<?php esc_attr_e( $_GET['minms'] ?? '' ); ?>"/>ms
				<button>View individual</button>
			</form>
		</div>
	</div>
	<hr/>
	<div class="row">
		<div class="col" id="main">
<?php
$min = 0;
$max = 0;
$i=0;
$took = 0;
while ( ! feof( $fp ) ) {
	$line = fgets( $fp );
	if ( empty( $line ) ) {
		continue;
	}
	$line = explode( "\t", $line );
	//echo '<pre>' . print_r( $line, true ) . '</pre>';
	$max = $line[0];
	
	if ( $i === 0 ) {
		$min = floatval( $line[1] );
	}

	$row = array(
		'start' => floatval( $line[0] ),
		'stop' => floatval( $line[1] ),
		'func' => $line[3],
		'depth' => $line[2],
		'hook' => $line[4],
		'offset' => $line[0] - $min,
		'took' => $line[1] - $line[0],
	);
	$data[] = $row;
	$took += $row['took'];
	$i++;
}

fclose( $fp );

$data3 = array();
$data2 = array();

foreach ( $data as $i => $record ) {
	$hook = $record['hook'];

	switch( $record['func'] ) {
	case '"query"':
		$hook = '{queries}';
		break;
	case '"http_curl"':
		$hook = '{urls}';
		break;
	}

	$key = $record['func'] . ' ' . $hook;
	if ( ! isset( $data2[ $key ] ) ) {
		$data2[ $key ] = array(
			'func'     => $record['func'],
			'hook'     => $hook,
			'earliest' => $record['start'],
			'latest'   => $record['stop'],
			'took'     => array(),
		);
	}
	if ( ! isset( $data3[$record['func']] ) ) {
		$data3[$record['func']] = array( 'func' => $record['func'], 'took' => array());
	}
	$data3[$record['func']]['took'][] = $record['took'];
	$data2[ $key ]['latest'] = $record['stop'];
	$data2[ $key ]['took'][] = $record['took'];


}

usort(
	$data,
	function($a, $b) {
		return $a['start'] <=> $b['start'];
	}
);

usort(
	$data2,
	function($a, $b) {
		return array_sum($b['took']) <=> array_sum($a['took']);
	}
);

usort(
	$data3,
	function($a, $b) {
		return array_sum($b['took']) <=> array_sum($a['took']);
	}
);

if ( empty( $_GET['section'] ) || 'summary' === $_GET['section'] ) {
	echo '<table>';
	foreach( $data3 as $idx => $val ) {
		echo '<tr>';
		printf(
			'
			<td style="overflow: hidden;text-overflow: ellipsis ellipsis;">%s (%d @ %dms)</td>
			<td style="width:1000px;"><div style="background-color:#00f;margin-left:%f%%; width:%f%%; min-width:1px;">&nbsp;</div></td>
			',
			$val['func'],
			count( $val['took'] ),
			(array_sum( $val['took'])/1000000),
			0,
			// ( $val['earliest'] / $max ) * 100,
			( array_sum( $val['took'] ) / $took ) * 100,
		);
		echo '</tr>';
	}
	echo '</table>';
	return;
}

if ( 'cumulative' === $_GET['section'] ) {
	echo '<table>';
	foreach( $data2 as $idx => $val ) {
		echo '<tr>';
		printf(
			'
			<td style="max-width:25em;overflow: hidden;text-overflow: ellipsis ellipsis;">%s (%d @ %.2fms)</td>
			<td style="width:1000px;"><div style="background-color:#00f;margin-left:%f%%; width:%f%%; min-width:1px;">&nbsp;</div></td>
			',
			$val['func'] . ' ' . $val['hook'],
			count( $val['took'] ),
			(array_sum( $val['took'])/1000000),
			0,
			// ( $val['earliest'] / $max ) * 100,
			( array_sum( $val['took'] ) / $took ) * 100,
		);
		echo '</tr>';
	}
	echo '</table>';
}

function appendlink( $param, $value, $data = null ) {
	if ( null === $data ) {
		$data = $_GET;
	}
	$data[ $param ] = $value;

	return sprintf(
		'?%s',
		http_build_query( $data )
	);
}

$minms = -1;
if ( ! empty( $_GET['minms'] ) ) {
	$minms = floatval( $_GET['minms'] );
}
if ( ! empty( $_GET['at'] ) ) {
	$at = intval( $_GET['at'] );
	$subdata = array();
	$depth   = $data[ $at ]['depth'];
	$took    = $data[ $at ]['took'];
	foreach ( array_slice( $data, $at, null, true ) as $i => $row ) {
		if ( $i > $at && $row['depth'] <= $depth ) {
			break;
		}
		$subdata[$i] = $row;
	}
	$data = $subdata;
	if ( count( $data ) > 1 ) {
		echo '<table class="table table-sm table-bordered border-primary">';
		foreach ( array_shift( array_slice( $data, 0, 1 ) ) as $key => $val ) {
			switch( $key ) {
			case 'func':
			case 'hook':
				$val = json_decode( $val );
				break;
			}
			printf( '<tr><th scope="row" class="text-sm-end text-nowrap">%s</td><td class="text-wrap text-sm-start">%s</td></tr>', esc_html( $key ), esc_html( $val) );
		}
		echo '</table><hr/>';
	}
}

if ( 'individual' === $_GET['section'] ) {
	echo '<table style="width:100%">';
	$rows = 0;
	foreach ( $data as $i => $record ) {
		$ms = $record['took'] / 1000000;
		if ( $ms < $minms ) {
			continue;
		}
		++$rows;
		if ( $rows % 500 === 0 ) {
			echo '</table><table style="width:100%">';
		}
	?>

		<tr>
			<td style="width:2em;"><?php esc_html_e( $record['func'] ); ?></td>
			<td style="width:25em; max-width: 15em; overflow: hidden;text-overflow: ellipsis ellipsis;">
			<a href="<?php echo appendlink('at', $i); ?>" title="<?php esc_html_e( $record['hook'] ); ?>">
					<?php esc_html_e( $record['hook'] ); ?>
				</a>
			</td>
			<td style="width:2em;"><?php esc_html_e( $record['depth'] ); ?></td>
			<td style="width:1em;"><?php esc_html_e( str_replace( '#', htmlentities( '·' ), str_pad( sprintf( '%.2f', $ms ), 7, '#', STR_PAD_LEFT ) ) ); ?>ms</td>
			<td style="width:50%; padding-left:<?php echo $record['depth'] * 5; ?>px">
			<div style="background-color:#00f; width:<?php echo ( $record['took'] / $took ) * 100; ?>%; min-width:1px;">&nbsp;</div>
			</td>
		</tr>
	<?php
	}
	echo '</table>';
}
?>
		</div><!-- col -->
	</div><!-- row -->
	</div>
</body>
</html>
