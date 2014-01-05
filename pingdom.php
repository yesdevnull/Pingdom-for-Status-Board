<?php

require_once ( 'config.php' );

$resolution = filter_input ( INPUT_GET , 'resolution' , FILTER_SANITIZE_STRING );

$now = time();

// Build the base graph
$finalArray = [
	'graph' => [
		'title' => 'Response Time' ,
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

switch ( $resolution ) {
	case 'last-day' :
	default :
	
		$finalArray['graph']['title'] .= ' (Last 24 Hours)';
		
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
					'title' => date ( 'H:i' , $hour['starttime'] ) ,
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