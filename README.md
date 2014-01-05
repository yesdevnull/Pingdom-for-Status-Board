# Pingdom for Status Board
A graph of website response times from [Pingdom](http://pingdom.com) for Panic's [Status Board](http://panic.com/statusboard/).

## Usage
Before you can use this graph you'll need to supply your user credentials and a list of hosts/ids in a file called config.php

```php
<?php
/* file: config.php */

$pingdomCredentials = [
	'appKey' => 'your api key here' , // Chuck your Pingdom API key here
	'username' => 'pingdom@pretendco.com' , // Put in your login name here
	'password' => 'password' , // And your password goes here
];

$checkHosts = [
	[
		'name' => 'pretendco.com' , // Stylised name, this is purely cosmetic
		'id' => '12345' ,
	] ,
	[
		'name' => 'My Blog' , // Stylised name, this is purely cosmetic
		'id' => '1234' ,
	] ,
];
```

### What's My Host's ID?

Log into [MyPingdom](https://my.pingdom.com) then for each host you want to check, to go the "Edit Check" for each host then copy the ID from the address bar (see image below)

![Edit Check](http://www.yesdevnull.net/wp-content/uploads/2014/01/Edit_Check.png)


### U.S. Date Stamps

If you're a citizen  of the U.S. and would prefer your datestamps in MM/DD form, please add a variable to the query string called ```us``` with the value of ```true```.  For example:

```
http://pretendco.com/path/to/pingdom.php?us=true
```

OR

```
http://pretendco.com/path/to/pingdom.php?resolution=last-week&us=true
```

## Requirements
- PHP 5.4 or newer

### PHP Modules/Extensions
- cURL

## TODO
- Add support for more last hour, last week, last month and more...
