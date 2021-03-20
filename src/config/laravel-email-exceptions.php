<?php

return [
    /**
     * Configure the ErrorEmail service
     *
     * - email (bool) - Enable or disable emailing of errors/exceptions
     *
     * - dontEmail (array) - An array of classes that should never be emailed
     *   even if they are thrown Ex: ['']
     *
     * - throttle (bool) - Enable or disable throttling of errors/exceptions
     *
     * - throttleCacheDriver (string) - The cache driver to use for throttling emails,
     *   uses cache driver from your env file labeled 'CACHE_DRIVER' by default
     *
     * - throttleDurationMinutes (int) - The duration of the throttle in minutes
     *   ex if you want to be emailed only once every 5 minutes about each unique
     *   exception type enter 5
     *
     * - dontThrottle (array) - An array of classes that should never be throttled
     *   even if they are thrown more than once within the normal throttling window
     *
     * - globalThrottle (bool) - whether you want to globally throttle the number of emails
     *   you can receive of all exception types by this application
     *
     * - globalThrottleLimit (int) - the maximum number of emails you would like to receive
     *   for a given duration
     *
     * - globalThrottleDurationMinutes (int) - The duration of the global throttle in minutes
     *   ex if you want to receive a maximum of 20 emails in a given 30 minute time period
     *   enter 20 for the globalThrottleLimit and 30 for globalThrottleDurationMinutes
     *
     * - toEmailAddress (string|array) - The email address(es) to send these error emails to,
     *   typically the dev team for the website
     *
     * - fromEmailAddress (string) - The email address these emails should be sent from
     *
     * - emailSubject (string) - The subject of email, leave NULL to use default
     *   Default Subject: An Exception has been thrown on APP_URL APP_ENV
     *
     */
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
        'toEmailAddress' => null,
        'fromEmailAddress' => null,
        'emailSubject' => null
    ]
];
