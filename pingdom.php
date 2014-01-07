<?php

require_once ( 'config.php' );

// API Key for Pingdom for Status Board
// As per: https://twitter.com/pingdom/status/420541439534067712
$pingdomCredentials['appKey'] = 'fpyrvcla3w52w85i8u8pmo8j9vhxv8wv';

$resolution = filter_input ( INPUT_GET , 'resolution' , FILTER_SANITIZE_STRING );
$autoHost = filter_input ( INPUT_GET , 'autohost' , FILTER_SANITIZE_STRING );

$now = time();

// Build the base graph
$finalArray = [
	'graph' => [
		'title' => 'Check Response Time' ,
		'type' => 'line' ,
		'datasequences' => '' ,
		'yAxis' => [
			'minValue' => '0' ,
			'units' => [
				'suffix' => 'ms' ,
			] ,
		] ,
	]
];

// Pingdom's Localization strings don't use the same characters as PHP's date function:
// settings.dateformat is OK, but settings.timeformat needs some fixing
// @link https://www.pingdom.com/features/api/documentation/#MethodGet+Account+Settings
function processTime ( $time , $format ) {
	$find = [
		'h' ,
		'%I' ,
		'%p' ,
		'%S' ,
		'%M' ,
		'%H' ,
		'm' ,
	];
	
	$replace = [
		'\h' ,
		'h' ,
		'a' ,
		's' ,
		'i' ,
		'H' ,
		'\m' ,
	];
	
	$newFormat = str_replace ( $find , $replace , $format );
	
	if ( $newFormat{1} == ':' ) {
		// This is for 14:45
		$newFormat = substr ( $newFormat , 0 , 3 );
	} else {
		// This is for 14h45m
		$newFormat = substr ( $newFormat , 0 , 6 );
	}
	
	// We do this because 02:00 breaks the Status Board graph as it merges the am and pm values.
	// This way, there's no confusion for Status Board!
	if ( $newFormat == 'h:i' ) {
		$newFormat .= 'a';
	}
	
	return date ( $newFormat , $time );
}

// Here I'm taking out the '%' signs and slightly reformatting the date string to 
// only include the day and the month.
function processDate ( $date , $format ) {
	$newFormat = str_replace ( '%' , '' , $format );
	
	if ( substr ( $newFormat , 0 , 1 ) == 'Y' ) {
		$newFormat = substr ( $newFormat , 2 , 3 );
	} else {
		$newFormat = substr ( $newFormat , 0 , 3 );
	}
	
	return date ( $newFormat , $date );
}

// Here we go!
$ch = curl_init();

// Set up the basic cURL details
curl_setopt ( $ch , CURLOPT_CUSTOMREQUEST , 'GET' );
curl_setopt ( $ch , CURLOPT_USERPWD , $pingdomCredentials['username'] . ':' . $pingdomCredentials['password'] );
curl_setopt ( $ch , CURLOPT_HTTPHEADER , [ 'App-Key: ' . $pingdomCredentials['appKey'] ] );
curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , true );

// Recommended by Pingdom's API page
// @link https://www.pingdom.com/features/api/documentation/#php+code+example
if ( strtoupper ( substr ( PHP_OS , 0 , 3 ) ) == 'WIN' ) {
	curl_setopt ( $ch , CURLOPT_SSL_VERIFYPEER , 0 );
}

// If the PHP installation has gzip compression enabled, we'll use it
// @link https://www.pingdom.com/features/api/documentation/#gzip
if ( zlib_get_coding_type () == 'gzip' ) {
	curl_setopt( $ch , CURLOPT_ENCODING , 'gzip' );
}

// Here I quickly pull your account Localization settings for time and date stamps
curl_setopt ( $ch , CURLOPT_URL , 'https://api.pingdom.com/api/2.0/settings' );

$settingsResponse = json_decode ( curl_exec ( $ch ) , true );

$dateFormat = $settingsResponse['settings']['dateformat'];
$timeFormat = $settingsResponse['settings']['timeformat'];

// Instead of filling out the $checkHosts array in config.php, should we instead get each host from Pingdom?
if ( $autoHost ) {
	curl_setopt ( $ch , CURLOPT_URL , 'https://api.pingdom.com/api/2.0/checks' );
	
	$response = json_decode ( curl_exec ( $ch ) , true );
	
	// Reset $checkHosts just so it doesn't double up any hosts from config.php
	$checkHosts = [];
	
	// Grab each host's name and ID
	foreach ( $response['checks'] as $host ) {
		$checkHosts[] = [
			'name' => $host['name'] ,
			'id' => $host['id'] ,
		];
	}
}

// Make sure there's at least 1 host to check, otherwise it's pointless going any further
if ( count ( $checkHosts ) == 0 ) {
	$finalArray['graph']['error'] = [
		'message' => 'Error: No Hosts Defined (see README.md)' ,
		'detail' => 'You must define at least 1 host in the config.php file or add autohost=true to the query string.  This Status Board widget is useless without any hosts' ,
	];
	
	header ( 'content-type: application/json' );
	
	// Bye bye...
	exit ( json_encode ( $finalArray ) );
}

switch ( $resolution ) {
	case 'last-week' :
	
		$finalArray['graph']['title'] .= ' (Last 7 Days)';
		
		$lastWeek = strtotime ( '-7 days' );
		
		// Enumerate through the list of hosts from config.php
		foreach ( $checkHosts as $host ) {
			curl_setopt ( $ch , CURLOPT_URL , 'https://api.pingdom.com/api/2.0/summary.performance/' . $host['id'] . '?from=' . $lastWeek . '&to=' . $now . '&resolution=day' );
			
			// Decode the response from Pingdom to an associative array
			$response = json_decode ( curl_exec ( $ch ) , true );
			
			// Before we look through the response, was there an error?
			if ( isset ( $response['error'] ) ) {
				// Crap, there was an error!
				// Send the error to Status Board
				$finalArray['graph']['error'] = [
					'message' => 'Error: ' . $response['error']['statusdesc'] . ' (' . $response['error']['statuscode'] . '): ' . $response['error']['errormessage'] ,
					'detail' => $response['error']['errormessage'] ,
				];
				
				// Abort this loop!
				break;
			}
			
			foreach ( $response['summary']['days'] as $hour ) {
				$check[] = [
					'title' => processDate ( $hour['starttime'] , $dateFormat ) ,
					'value' => $hour['avgresponse'] ,
				];
			}
			
			$responseTime[] = [
				'title' => $host['name'] ,
				'datapoints' => $check ,
			];
			
			// Unset the $check variable so it doesn't insert values into the next hosts array
			unset ( $check );
			
		}
	
	// End 'last-week' case
	break;
	
	case 'last-day' :
	default :
	
		$finalArray['graph']['title'] .= ' (Last 24 Hours)';
		
		// If you do 1 day / 24 hours it doubles up the current timestamp with the timestamp 24 hours ago.  Not good!
		$yesterday = strtotime ( '-23 hours' );
		
		// Enumerate through the list of hosts from config.php
		foreach ( $checkHosts as $host ) {
			curl_setopt ( $ch , CURLOPT_URL , 'https://api.pingdom.com/api/2.0/summary.performance/' . $host['id'] . '?from=' . $yesterday . '&to=' . $now . '&resolution=hour' );
			
			// Decode the response from Pingdom to an associative array
			$response = json_decode ( curl_exec ( $ch ) , true );
			
			// Before we look through the response, was there an error?
			if ( isset ( $response['error'] ) ) {
				// Crap, there was an error!
				// Send the error to Status Board
				$finalArray['graph']['error'] = [
					'message' => $response['error']['statusdesc'] . ' (' . $response['error']['statuscode'] . '): ' . $response['error']['errormessage'] ,
					'detail' => $response['error']['errormessage'] ,
				];
				
				// Abort this loop!
				break;
			}
			
			foreach ( $response['summary']['hours'] as $hour ) {
				$check[] = [
					'title' => processTime ( $hour['starttime'] , $timeFormat ) ,
					'value' => $hour['avgresponse'] ,
				];
			}
			
			$responseTime[] = [
				'title' => $host['name'] ,
				'datapoints' => $check ,
			];
			
			// Unset the $check variable so it doesn't insert values into the next hosts array
			unset ( $check );
		}
	
	// End last-day / default case
	break;
}

// ... and it's closed.
curl_close ( $ch );

$finalArray['graph']['datasequences'] = $responseTime;

header ( 'content-type: application/json' );

echo json_encode ( $finalArray );

// And we're done!