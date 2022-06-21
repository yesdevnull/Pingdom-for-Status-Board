# Pingdom for Status Board

A graph of host response times from [Pingdom](http://pingdom.com) for Panic's [Status Board](http://panic.com/statusboard/) iPad app.

**Note:** this is not maintained and has been archived.

## Usage
If you only have a few host checks I recommend adding ```autohost=true``` to your query string.  ```autohost``` will pull __every__ host from Pingdom and graph its response time, so if you have 5+ hosts, your graph may look horrible.  If you'd rather only see certain hosts, specify them in the config.php file like below.

```php
<?php
/* file: config.php */

$pingdomCredentials = [
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

Log into [My Pingdom](https://my.pingdom.com) then for each host you want to check, to go the "Edit Check" for each host then copy the ID from the address bar (see image below)

![Edit Check](http://yesdevnull.net/wp-content/uploads/2014/01/Edit_Check.png)

### Time And Date Stamps

If you're in the U.S., or you have a different way of reading time stamps, Pingdom for Status Board will respect your Localization settings for your account.  I do some parsing to ensure that the date and time outputs are identical to your expected output.

Please note that I strip the year and seconds off all timestamps.  Having the year in a date stamp makes the graph too messy, same with seconds.  I figure it's not necessary to see either of those time/dates.


## Requirements
- PHP 5.4 or newer

### Required PHP Modules/Extensions
- cURL
- JSON

### Nice To Have PHP Modules/Extensions
- zlib with ```gzip``` compression enabled

## TODO
- Add support for more graphs like last hour, last month etc...


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/yesdevnull/pingdom-for-status-board/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

