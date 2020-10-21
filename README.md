# Laravel 7.x Email Exceptions
[![Build Status](https://api.travis-ci.org/abrigham1/laravel-email-exceptions.png?branch=master)](https://travis-ci.org/abrigham1/laravel-email-exceptions)
[![Coverage Status](https://img.shields.io/codecov/c/github/abrigham1/laravel-email-exceptions/master.svg)](https://codecov.io/github/abrigham1/laravel-email-exceptions?branch=master)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.txt)

The Laravel 5 Email Exceptions package, based on this excellent cakephp package (https://github.com/ebrigham1/cakephp-error-email), is designed to give developers an easy way to email debug information
to themselves whenever an exception is thrown in their application. Information provided by default is:
* Environment
* Exception/Error Url
* Exception/Error Class
* Exception/Error Message
* Exception/Error Code
* File and Line Number
* Stack Trace

![Screenshot](https://cloud.githubusercontent.com/assets/7387512/22176626/047ac836-dfdd-11e6-9a91-bb99b98333ac.png)

## Table of Contents
* [Installation](#installation)
* [Configuration](#configuration)
* [Basic Usage](#basic-usage)
    * [Basic Config](#basic-config)
    * [Throttling](#throttling)
    * [Global Throttling](#global-throttling)
* [Advanced Usage](#advanced-usage)
	* [Changing the view](#changing-the-view)
	* [Adding Arbitrary don't email logic](#adding-arbitrary-dont-email-logic)
* [Gotchas](#gotchas)
* [Bugs and Feedback](#bugs-and-feedback)
* [License](#license)

## Installation
You can install this plugin into your laravel 5.x application using [composer](http://getcomposer.org).

Run the following command
```bash
composer require abrigham/laravel-email-exceptions
 ```
After updating composer, add the service provider to the providers array in config/app.php
> If you are in laravel >= 5.5 you can skip adding the provider to config/app.php as it will be auto-discovered

```php
Abrigham\LaravelEmailExceptions\EmailExceptionsServiceProvider::class,
```

Then in app/Exceptions/Handler.php replace
```php
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
```
with
```php
use Abrigham\LaravelEmailExceptions\Exceptions\EmailHandler as ExceptionHandler;
```

## Configuration
To publish the config file and view run the following command
```bash
php artisan vendor:publish --provider="Abrigham\LaravelEmailExceptions\EmailExceptionsServiceProvider"
```

That will create a config file for you in config/laravelEmailExceptions.php and a view in
resources/views/vendor/laravelEmailExceptions/emailExceptions.blade.php

Default configuration:
```php
'ErrorEmail' => [
    'email' => true,
    'dontEmail' => [],
    'throttle' => false,
    'throttleCacheDriver' => env('CACHE_DRIVER', 'file'),
    'throttleDurationMinutes' => 5,
    'dontThrottle' => [],
    'globalThrottle' => true,
    'globalThrottleLimit' => 20,
    'globalThrottleDurationMinutes' => 30,
    'toEmailAddress' => null,
    'fromEmailAddress' => null,
    'emailSubject' => null
]
```

* email (bool) - Enable or disable emailing of errors/exceptions
* dontEmail (array) - This works exactly like laravel's $dontReport variable documented here: https://laravel.com/docs/5.4/errors#the-exception-handler under Ignoring Exceptions By Type. Keep in mind also any exceptions under laravel's $dontReport also will not be emailed
* throttle (bool) - Enable or disable throttling of exception emails. Throttling is only performed if its been determined the exact same exception/error has already been emailed by checking the cache. Errors/Exceptions are determined to be unique by exception class + exception message + exception code
* throttleCacheDriver (string) - The cache driver to use for throttling, by default it uses CACHE_DRIVER from your env file
* throttleDurationMinutes (int) - The duration in minutes of the throttle for example if you put 5 and a BadMethodCallException triggers an email if that same exception is thrown again it will not be emailed until 5 minutes have passed
* dontThrottle (array) - This is the same as dontEmail except provide a list of exceptions you do not wish to throttle ever even if throttling is turned on
* globalThrottle (bool) - Enable or disable whether you want to globally throttle the number of emails you can receive of all exception types by this application
* globalThrottleLimit (int) - The the maximum number of emails you want to receive in a given period.
* throttleDurationMinutes (int) - The duration in minutes of the global throttle for example if you put in 30 and have 10 for your globalThrottleLimit when the first email is sent out a 30 minute timer will commence once you reach the 10 email threshold no more emails will go out for that 30 minute period. 
* toEmailAddress (string|array) - The email(s) to send the exceptions emails to such as the dev team dev@yoursite.com
* fromEmailAddress (string) - The email address these emails should be sent from such as noreply@yoursite.com.
* emailSubject (string) - The subject of email, leave NULL to use default Default Subject: An Exception has been thrown on APP_URL APP_ENV

**Note:** the dontReport variable from **app/Exceptions/Handler.php** file will also not be emailed as it's assumed if they are not important enough to log then they also are not important enough to email

**Important:** You must fill out a toEmailAddress and fromEmailAddress or you will not receive emails.

## Basic Usage
#### Basic Config
Update your config values in **config/laravelEmailExceptions.php**
```php
'ErrorEmail' => [
    'email' => true,
    'dontEmail' => [],
    'throttle' => true,
    'throttleCacheDriver' => env('CACHE_DRIVER', 'file'),
    'throttleDurationMinutes' => 5,
    'dontThrottle' => [],
    'globalThrottle' => true,
    'globalThrottleLimit' => 20,
    'globalThrottleDurationMinutes' => 30,
    'toEmailAddress' => 'dev@yoursite.com',
    'fromEmailAddress' => 'noreply@yoursite.com',
    'emailSubject' => null,
]
```

#### Throttling
Both throttling and global throttling are put in place in an attempt to prevent spam to the dev team. Throttling works
by creating a unique cache key made from exception class + exception message + exception code. Its aim is to prevent duplicate
exceptions from being reported via email giving the team time to fix them before they are reported again.

#### Global Throttling
Global throttling is a similar idea except it's put in place to prevent more then a certain number of emails going out 
within a given time period. This should typically only be necessary for an app wide failure ex major portions of the
site are down so many varied types of exceptions are coming in from all directions.

## Advanced Usage
### Changing the view
If you published your view using the command above you will be able to change the look of the exception email
by modifying your view in **resources/views/vendor/laravelEmailExceptions/emailException.blade.php**

### Adding Arbitrary don't email logic
If you need more complicated logic then just checking instanceof against the thrown exception
there is a convenient hook for adding arbitrary logic to decide if an exception should be emailed.

In **app/Exceptions/Handler.php** implement the function appSpecificDontEmail(Exception $exception) ex.

```php
<?php
class Handler extends ExceptionHandler
{
    protected function appSpecificDontEmail(Exception $exception)
    {
        // add logic here to determine if exception should be emailed return true
        // if it should and return false if it should not
    }
}
```
## Gotchas
If you're having trouble getting this working first make sure you have configured your
application to send mail correctly. One of the easiest ways to get mail up and running 
is by signing up for a free account on [mailtrap.io](https://mailtrap.io). Once you've done that you'll have 
to update your .env file with values like these replacing the username and password 
with those listed in your demo inbox
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-user-string
MAIL_PASSWORD=your-password-string
MAIL_ENCRYPTION=null
```

## Bugs and Feedback
http://github.com/abrigham1/laravel-email-exceptions/issues

## License
Copyright (c) 2017 Aaron Brigham

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
