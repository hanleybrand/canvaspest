# CanvasPest

[![Latest Version](https://img.shields.io/packagist/v/smtech/canvaspest.svg)](https://packagist.org/packages/smtech/canvaspest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/smtech/canvaspest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/smtech/canvaspest/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/smtech/canvaspest/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/smtech/canvaspest/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/smtech/canvaspest/badges/build.png?b=master)](https://scrutinizer-ci.com/g/smtech/canvaspest/build-status/master)

Historic Object-oriented access to the Canvas API using PHP.

# What's new (for this fork)

## IMPORTANT ADVICE BETWEEN US

**This repo is a fork of an archived library which has been slightly updated to help legacy projects using it be brought up to basic compatibility with PHP 8 and Instructure's recent API changes for the Canvas LMS.** 

You should only use this repo with a legacy tool that needs to stay in production until it's possible rewrite the parts that lean on canvaspest or write a new tool entirely. _{insert appropriate emoji that conveys bitter-but-maniacal-laughter here}_

**Seriously, don't begin new projects with this library**, look instead at projects like [canvas-api-php-library](https://packagist.org/packages/uncgits/canvas-api-php-library) or [php-canvas-api](https://github.com/longhornopen/php-canvas-api) or something else. 


## Updating from the original via Composer 

In your `composer.json`, change the `smtech/canvaspest` requirement from:

```php
"require": {
    "smtech/canvaspest": "1.*"
}
```
to 

```php
"require": {
  "name":  "smtech/canvaspest",
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:hanleybrand/canvaspest.git"
    }
  ],
  "require": {
    "smtech/canvaspest": "php8"
  }
}
```

To be honest, I'm neither a PHP or Composer expert, so hopefully that's correct—if not, please submit an issue with the correction, lash out on social media, whatever works.


### Alternative (and irresponsible) update method

If the app that needs updating isn't really set up for composer & version management (perhaps more likely, composer update is borked and fubared and you can't even with all these errors) you can download the zip of this repo and copy the files to overwrite the ones in your project

* `src/CanvasArray.php`  ==> `vendor/smtech/canvaspest/src/CanvasArray.php`
* `src/CanvasObject.php` ==> `vendor/smtech/canvaspest/src/CanvasObject.php`
* `src/CanvasPest.php`   ==> `vendor/smtech/canvaspest/src/CanvasPest.php`

Not recommended, but sometimes you need to accept re-cutting a corner once again instead of doing it right in maintenance. 

"_The perfect is the enemy of the good,_" or so the project manager keeps telling me.

## Changes from the smtech/canvaspest repo

* Minor syntax updates to CanvasPest & CanvasArray objects for PHP8 compatibility
  * seriously, it was stuff like the PHP Object function serialize() needing to be renamed to __serialize() 
  * also added some typing annotations, why not?
* Changed construction of the CanvasPest object to add a user-agent header, `'CanvasPest-AA/1.0'` by default 
  * Header value can be overridden with an ENV `$CANVASPEST_USER_AGENT` set to whatever string you prefer
  * [Matt](https://github.com/giddemore) figured this fix out

# Original README contents

## Use

### CanvasPest

Create a new CanvasPest to make RESTful queries to the Canvas API:

```
// construct with the API URL and an API access token
$api = new CanvasPest('https://canvas.instructure.com/api/v1', 'df2bcbad95f606d6e80093f8e40c4e5ca171d8c5e4f2138e1d58273e33b262ef')
```

Make a RESTful query to the API:

```PHP
// GET, PUT, POST, DELETE are all supported
$obj = $api->get('users/self/profile');
```

The response from a query is either a `CanvasObject` or a `CanvasArray` (of CanvasObjects, natch), depending on whether you requested a specific object or a list of objects (even if the list turns out to be a single object).

### CanvasObjects

CanvasObject fields can be accessed either object-style or array style:

```PHP
$obj = $api->get('courses/123');
echo $obj['sis_course_id']; // array-style
echo $obj->title; // object-style
```

### CanvasArrays

CanvasArrays can be iterated conveniently using the `foreach` control structure.

```PHP
$arr = $api->get('/accounts/1/users');
foreach($arr as $obj) {
	echo $obj->name;
}
```

One could also access arbitrary elements of the CanvasArray:

```PHP
$arr = $api->get('accounts/1/courses');
echo $arr[1337]->title;
```

Note that both CanvasObjects and CanvasArrays are immutable objects -- that is, they are treated as read-only. In fact, if you attempt to alter a CanvasObject or a CanvasArray, exceptions will be thrown.

[Full API documentation](http://smtech.github.io/canvaspest/namespaces/smtech.CanvasPest.html) is available in the repository.
