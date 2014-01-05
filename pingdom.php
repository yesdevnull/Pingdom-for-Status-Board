<?php

require_once ( 'config.php' );

$yesterday = strtotime ( '-23 hours' );
$now = time();

// Build the base graph
$finalGraph = [
	'graph' => [
		'title' => 'Response Time (Last Day)' ,
		'type' => 'line' ,
		'refreshEveryNSeconds' => '600' ,
		'datasequences' => '' ,
		'yAxis' => [
			'units' => [
				'suffix' => 'ms' ,
			] ,
		] ,
	]
];

// Enumerate through the list of hosts from config.php
foreach ( $checkHosts as $host ) {
	// Here we go!
	$ch = curl_init();
	
	curl_setopt ( $ch , CURLOPT_URL , 'https://api.pingdom.com/api/2.0/summary.performance/' . $host['id'] . '?from=' . $yesterday . '&to=' . $now . '&resolution=hour' );
	curl_setopt ( $ch , CURLOPT_CUSTOMREQUEST , 'GET' );
	curl_setopt ( $ch , CURLOPT_USERPWD , $pingdomCredentials['username'] . ':' . $pingdomCredentials['password'] );
	curl_setopt ( $ch , CURLOPT_HTTPHEADER , [ 'App-Key: ' . $pingdomCredentials['appKey'] ] );
	curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , true );
	
	// Decode the response from Pingdom to an associative array
	$response = json_decode ( curl_exec ( $ch ) , true );
	
	// ... and it's closed.
	curl_close ( $ch );
	
	$checkOrig = [
		[
			'title' => '00:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '01:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '02:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '03:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '04:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '05:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '06:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '07:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '08:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '09:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '10:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '11:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '12:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '13:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '14:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '15:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '16:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '17:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '18:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '19:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '20:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '21:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '22:00' ,
			'value' => '0' ,
		] ,
		[
			'title' => '23:00' ,
			'value' => '0' ,
		] ,
	];
	
	foreach ( $response['summary']['hours'] as $hour ) {
		foreach ( $checkOrig as & $values ) {
			if ( ( $values['title'] ) == ( date ( 'H:i' , $hour['starttime'] ) ) ) {
				$values['value'] = $hour['avgresponse'];
			}
		}
	}
	
	$responseTime[] = [
		'title' => $host['name'] ,
		'datapoints' => $checkOrig ,
	];
	
	// Unset the $check variable so it doesn't insert values into the next hosts array
	unset ($checkOrig);
}

$finalGraph['graph']['datasequences'] = $responseTime;

//var_dump($finalGraph);

header ( 'content-type: application/json' );

echo json_encode ( $finalGraph );

// And we're done!