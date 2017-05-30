<?php

namespace Abrigham\LaravelEmailExceptions\Exceptions;

use Exception;
use Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class EmailHandler extends ExceptionHandler
{

    /**
     * @var string global throttle cache key
     */
    protected $globalThrottleCacheKey = "email_exception_global";

    /**
     * @var null|string throttle cache key
     */
    protected $throttleCacheKey = null;


    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        // check if we should mail this exception
        if ($this->shouldMail($exception)) {
            // if we passed our validation lets mail the exception
            $this->mailException($exception);
        }

        // run the parent report (logs exception and all that good stuff)
        $this->callParentReport($exception);
    }

    /**
     * wrapping the parent call to isolate for testing
     *
     * @param Exception $exception
     */
    protected function callParentReport(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Determine if the exception should be mailed
     *
     * @param Exception $exception
     * @return bool
     */
    protected function shouldMail(Exception $exception)
    {
        // if emailing is turned off in the config
        if (config('laravelEmailExceptions.ErrorEmail.email') != true ||

            // if we dont have an email address to mail to
            !config('laravelEmailExceptions.ErrorEmail.toEmailAddress') ||

            // if we dont have an email address to mail from
            !config('laravelEmailExceptions.ErrorEmail.fromEmailAddress') ||

            $this->shouldntReport($exception) ||

            // if the exception is in the don't mail list
            $this->isInDontEmailList($exception) ||

            // if there is any app specific don't email logic
            $this->appSpecificDontEmail($exception) ||

            // if the exception has already been mailed within the last throttle period
            $this->throttle($exception) ||

            // if we've already sent the maximum amount of emails for the global throttle period
            $this->globalThrottle()
        ) {
            // we should not mail this exception
            return false;
        }

        // we made it past all the possible reasons to not email so we should mail this exception
        return true;
    }

    /**
     * app specific dont email logic should go in this function
     *
     * @param Exception $exception
     * @return bool
     */
    protected function appSpecificDontEmail(Exception $exception)
    {
        // override this in app/Exceptions/Handler.php if you need more complicated logic
        // then checking instanceof with exception classes
        return false;
    }

    /**
     * mail the exception
     *
     * @param Exception $exception
     */
    protected function mailException(Exception $exception)
    {
        $data = [
            'exception' => $exception,
            'toEmail' => config('laravelEmailExceptions.ErrorEmail.toEmailAddress'),
            'fromEmail' => config('laravelEmailExceptions.ErrorEmail.fromEmailAddress'),
        ];

        Mail::send('laravelEmailExceptions::emailException', $data, function ($message) {

            $default = 'An Exception has been thrown on '.
                config('app.name', 'unknown').' ('.config('app.env', 'unknown').')';
            $subject = config('laravelEmailExceptions.ErrorEmail.emailSubject') ?: $default;

            $message->from(config('laravelEmailExceptions.ErrorEmail.fromEmailAddress'))
                ->to(config('laravelEmailExceptions.ErrorEmail.toEmailAddress'))
                ->subject($subject);
        });
    }

    /**
     * check if we need to globally throttle the exception
     *
     * @return bool
     */
    protected function globalThrottle()
    {
        // check if global throttling is turned on
        if (config('laravelEmailExceptions.ErrorEmail.globalThrottle') == false) {
            // no need to throttle since global throttling has been disabled
            return false;
        } else {
            // if we have a cache key lets determine if we are over the limit or not
            if (Cache::store(
                config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
            )->has($this->globalThrottleCacheKey)
            ) {
                // if we are over the limit return true since this should be throttled
                if (Cache::store(
                    config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
                )->get(
                    $this->globalThrottleCacheKey,
                    0
                ) >= config('laravelEmailExceptions.ErrorEmail.globalThrottleLimit')
                ) {
                    return true;
                } else {
                    // else lets increment the cache key and return false since its not time to throttle yet
                    Cache::store(
                        config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
                    )->increment($this->globalThrottleCacheKey);

                    return false;
                }
            } else {
                // we didn't find an item in cache lets put it in the cache
                Cache::store(
                    config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
                )->put(
                    $this->globalThrottleCacheKey,
                    1,
                    config('laravelEmailExceptions.ErrorEmail.globalThrottleDurationMinutes')
                );

                // if we're just making the cache key now we are not global throttling yet
                return false;
            }
        }
    }

    /**
     * check if we need to throttle the exception and do the throttling if required
     *
     * @param Exception $exception
     * @return bool return true if we should throttle or false if we should not
     */
    protected function throttle(Exception $exception)
    {
        // if throttling is turned off or its in the dont throttle list we won't throttle this exception
        if (config('laravelEmailExceptions.ErrorEmail.throttle') == false ||
            $this->isInDontThrottleList($exception)
        ) {
            // report that we do not need to throttle
            return false;
        } else {
            // else lets check if its been reported within the last throttle period
            if (Cache::store(
                config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
            )->has($this->getThrottleCacheKey($exception))
            ) {
                // if its in the cache we need to throttle
                return true;
            } else {
                // its not in the cache lets add it to the cache
                Cache::store(
                    config('laravelEmailExceptions.ErrorEmail.throttleCacheDriver')
                )->put(
                    $this->getThrottleCacheKey($exception),
                    true,
                    config('laravelEmailExceptions.ErrorEmail.throttleDurationMinutes')
                );

                // report that we do not need to throttle as its not been reported within the last throttle period
                return false;
            }
        }
    }

    /**
     * get the throttle cache key
     *
     * @param Exception $exception
     * @return mixed
     */
    protected function getThrottleCacheKey(Exception $exception)
    {

        // if we haven't already set the cache key lets set it
        if ($this->throttleCacheKey == null) {
            // make up the cache key from a prefix, exception class, exception message, and exception code
            // with all special characters removed
            $this->throttleCacheKey = preg_replace(
                "/[^A-Za-z0-9]/",
                '',
                'laravelEmailException'.get_class($exception).$exception->getMessage().$exception->getCode()
            );
        }

        // return the cache key
        return $this->throttleCacheKey;
    }

    /**
     * check if a given exception matches the class of any in the list
     *
     * @param $list
     * @param Exception $exception
     * @return bool
     */
    protected function isInList($list, Exception $exception)
    {
        // check if we actually have a list and its an array
        if ($list && is_array($list)) {
            // if we do lets loop through and check if our exception matches any of the classes
            foreach ($list as $type) {
                if ($exception instanceof $type) {
                    // if we match return true
                    return true;
                }
            }
        }

        // we got to the end there must be no match
        return false;
    }

    /**
     * check if the exception is in the dont throttle list
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isInDontThrottleList(Exception $exception)
    {
        $dontThrottleList = config('laravelEmailExceptions.ErrorEmail.dontThrottle');

        return $this->isInList($dontThrottleList, $exception);
    }

    /**
     * check if the exception is in the dont email list
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isInDontEmailList(Exception $exception)
    {
        $dontEmailList = config('laravelEmailExceptions.ErrorEmail.dontEmail');

        return $this->isInList($dontEmailList, $exception);
    }
}
