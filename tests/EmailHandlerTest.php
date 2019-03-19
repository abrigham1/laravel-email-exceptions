<?php
namespace Abrigham\LaravelEmailExceptions\Tests;

use Orchestra\Testbench\TestCase;
use Exception;
use BadMethodCallException;
use Mockery;
use Mail;
use Illuminate\Support\Facades\Cache;

class EmailHandlerTest extends TestCase
{

    /**
     * @var email handler mock object
     */
    protected $emailHandlerMock;

    public function setUp()
    {
        parent::setUp();

        // set up our email handler mock
        $this->emailHandlerMock = Mockery::mock(
            'Abrigham\LaravelEmailExceptions\Exceptions\EmailHandler'
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    /**
     * test report function
     *
     * @dataProvider reportProvider
     * @param $shouldMail
     */
    public function testReport($shouldMail)
    {
        // mock exception used for testing
        $exception = new Exception('Test Exception');

        // set up config value
        config(['laravelEmailExceptions.ErrorEmail.email' => false]);

        // we expect the email handler to receive should mail
        // and return true
        $this->emailHandlerMock
            ->shouldReceive('shouldMail')
            ->with($exception)
            ->once()
            ->andReturn($shouldMail);

        if ($shouldMail) {
            // we expect email handler to receive mailException and return null
            $this->emailHandlerMock
                ->shouldReceive('mailException')
                ->with($exception)
                ->once()
                ->andReturn(null);
        } else {
            // we expect the mail handler to not receive mailException
            $this->emailHandlerMock
                ->shouldNotReceive('mailException');
        }

        // we expect email handler to receive callParentReport and return null
        $this->emailHandlerMock
            ->shouldReceive('callParentReport')
            ->with($exception)
            ->once()
            ->andReturn(null);

        // function should return void so assertNull
        $actual = $this->emailHandlerMock->report($exception);
        $this->assertNull($actual);
    }

    /**
     * data provider for testReport
     *
     * @return array
     */
    public function reportProvider()
    {
        return [
            'should mail false' => [
                false,
            ],
            'should mail true' => [
                true,
            ],
        ];
    }

    /**
     * test should mail function
     *
     * @dataProvider shouldMailProvider
     * @param $email
     * @param $toEmailAddress
     * @param $fromEmailAddress
     * @param $shouldntReportReturn
     * @param $isInDontEmailListReturn
     * @param $throttleReturn
     * @param $appSpecificDontEmailReturn
     * @param $globalThrottleReturn
     * @param $expected
     */
    public function testShouldMail(
        $email,
        $toEmailAddress,
        $fromEmailAddress,
        $shouldntReportReturn,
        $isInDontEmailListReturn,
        $throttleReturn,
        $appSpecificDontEmailReturn,
        $globalThrottleReturn,
        $expected
    ) {

        // mock exception used for testing
        $exception = new Exception('Test Exception');

        // setting our config values from our data provider
        config([
            'laravelEmailExceptions.ErrorEmail.email' => $email,
            'laravelEmailExceptions.ErrorEmail.toEmailAddress' => $toEmailAddress,
            'laravelEmailExceptions.ErrorEmail.fromEmailAddress' => $fromEmailAddress,
        ]);

        if ($email == true && $toEmailAddress && $fromEmailAddress) {
            // if email is tru and we have a to and from address we should
            // receive a call to shouldntReport
            // if email is true and we have a to and from address we should
            // receive a call to isInDontEmailList
            $this->emailHandlerMock
                ->shouldReceive('shouldntReport')
                ->with($exception)
                ->once()
                ->andReturn($shouldntReportReturn);
            if ($shouldntReportReturn == false) {
                $this->emailHandlerMock
                    ->shouldReceive('isInDontEmailList')
                    ->with($exception)
                    ->once()
                    ->andReturn($isInDontEmailListReturn);
                if ($isInDontEmailListReturn == false) {
                    // should receive a call to appSpecificDontEmail
                    $this->emailHandlerMock
                        ->shouldReceive('appSpecificDontEmail')
                        ->once()
                        ->with($exception)
                        ->andReturn($appSpecificDontEmailReturn);
                    if ($appSpecificDontEmailReturn == false) {
                        // should receive a call to throttle
                        $this->emailHandlerMock
                            ->shouldReceive('throttle')
                            ->with($exception)
                            ->once()
                            ->andReturn($throttleReturn);
                        if ($throttleReturn == false) {
                            // if throttle return is also false we should receive a
                            // call to globalThrottle
                            $this->emailHandlerMock
                                ->shouldReceive('globalThrottle')
                                ->withNoArgs()
                                ->once()
                                ->andReturn($globalThrottleReturn);
                        } else {
                            // if app specific email return is true we shouldn't receive a call
                            // to global throttle
                            $this->emailHandlerMock
                                ->shouldNotReceive('globalThrottle');
                        }
                    } else {
                        // if throttle is true we wont get calls to appSpecificDontEmail or globalThrottle
                        $this->emailHandlerMock
                            ->shouldNotReceive('throttle');
                        $this->emailHandlerMock
                            ->shouldNotReceive('globalThrottle');
                    }
                } else {
                    // if isInDontEmailList is true we won't receive throttle appSpecificDontEmail or globalThrottle
                    $this->emailHandlerMock
                        ->shouldNotReceive('appSpecificDontEmail');
                    $this->emailHandlerMock
                        ->shouldNotReceive('throttle');
                    $this->emailHandlerMock
                        ->shouldNotReceive('globalThrottle');
                }
            } else {
                // if shouldntReport is true we won't receive isInDontEmail, throttle, appSpecificDontEmail or
                // global throttle
                $this->emailHandlerMock
                    ->shouldNotReceive('isInDontEmailList');
                $this->emailHandlerMock
                    ->shouldNotReceive('appSpecificDontEmail');
                $this->emailHandlerMock
                    ->shouldNotReceive('throttle');
                $this->emailHandlerMock
                    ->shouldNotReceive('globalThrottle');
            }
        }

        // check if actual = our expected value
        $actual = $this->emailHandlerMock->shouldMail($exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for the testShouldMail
     *
     * @return array
     */
    public function shouldMailProvider()
    {
        return [
            'email off' => [
                // email
                false,
                // toEmailAddress
                false,
                // fromEmailAddress
                false,
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on all others off' => [
                // email
                true,
                // toEmailAddress
                false,
                // fromEmailAddress
                false,
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on with to' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                false,
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on with to and from and not throttled or in dont lists' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                true,
            ],
            'email on with to and from and in dont report list' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                true,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on with to and from and in dont email list' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                true,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on with to and from and throttle true' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                true,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
            'email on with to and from and global throttle true' => [
                // email
                true,
                // toEmailAdrress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                false,
                // globalThrottleReturn
                true,
                // expected
                false,
            ],
            'email on with to and from and app specific dont email true' => [
                // email
                true,
                // toEmailAddress
                "dev@example.com",
                // fromEmailAddress
                "dev2@example.com",
                // shouldntReportReturn
                false,
                // isInDontEmailListReturn
                false,
                // throttleReturn
                false,
                // appSpecificDontEmailReturn
                true,
                // globalThrottleReturn
                false,
                // expected
                false,
            ],
        ];
    }

    /**
     * test should mail function
     *
     * @dataProvider mailExceptionProvider
     * @param $subject
     */
    public function testMailException($subject)
    {

        // mock exception for testing
        $exception = new Exception('Test Exception');

        // setting up config values
        config([
            'laravelEmailExceptions.ErrorEmail.toEmailAddress' => 'dev@example.com',
            'laravelEmailExceptions.ErrorEmail.fromEmailAddress' => 'dev2@example.com',
            'laravelEmailExceptions.ErrorEmail.subject' => $subject
        ]);

        // we should be testing the closure here too but its a pain in the butt
        Mail::shouldReceive('send')
            ->once()
            ->withAnyArgs();

        // test that actual is null (function returns void)
        $actual = $this->emailHandlerMock->mailException($exception);
        $this->assertNull($actual);
    }

    /**
     * data provider for the testMailException
     *
     * @return array
     */
    public function mailExceptionProvider()
    {
        return [
            'default subject' => [
                null,
            ],
            'config subject' => [
                'Your subject here',
            ]
        ];
    }

    /**
     * test the global throttle function
     *
     * @dataProvider globalThrottleProvider
     * @param $globalThrottle
     * @param $throttleCacheDriver
     * @param $globalThrottleLimit
     * @param $globalThrottleDurationMinutes
     * @param $globalThrottleCacheHasReturn
     * @param $globalThrottleCacheGetReturn
     * @param $expected
     */
    public function testGlobalThrottle(
        $globalThrottle,
        $throttleCacheDriver,
        $globalThrottleLimit,
        $globalThrottleDurationMinutes,
        $globalThrottleCacheHasReturn,
        $globalThrottleCacheGetReturn,
        $expected
    ) {

        // global throttle cache key
        $globalThrottleCacheKey = 'email_exception_global';

        // set up the config values from data provider
        config([
            'laravelEmailExceptions.ErrorEmail.globalThrottle' => $globalThrottle,
            'laravelEmailExceptions.ErrorEmail.throttleCacheDriver' => $throttleCacheDriver,
            'laravelEmailExceptions.ErrorEmail.globalThrottleLimit' => $globalThrottleLimit,
            'laravelEmailExceptions.ErrorEmail.globalThrottleDurationMinutes' => $globalThrottleDurationMinutes,
        ]);

        if ($globalThrottle == false) {
            // if global throttling is turned off we should
            // not receive any calls to the cache
            Cache::shouldReceive('store')
                ->never();
            Cache::shouldReceive('has')
                ->never();
            Cache::shouldReceive('get')
                ->never();
            Cache::shouldReceive('put')
                ->never();
            Cache::shouldReceive('increment')
                ->never();
        } else {
            // if global throttling is on we'll at least get a cache has call
            Cache::shouldReceive('has')
                ->once()
                ->with($globalThrottleCacheKey)
                ->andReturn($globalThrottleCacheHasReturn);

            if ($globalThrottleCacheHasReturn == true) {
                // if the item is in the cache we'll get a cache get call
                Cache::shouldReceive('get')
                    ->once()
                    ->with($globalThrottleCacheKey, 0)
                    ->andReturn($globalThrottleCacheGetReturn);
                if ($globalThrottleCacheGetReturn > $globalThrottleLimit) {
                    // if we're over our limit we'll receive store twice and increment never
                    Cache::shouldReceive('store')
                        ->twice()
                        ->with($throttleCacheDriver)
                        ->andReturnSelf();
                    Cache::shouldReceive('increment')
                        ->never();
                } else {
                    // if we're not over our limit we'll receive store 3 times and increment once
                    Cache::shouldReceive('store')
                        ->times(3)
                        ->with($throttleCacheDriver)
                        ->andReturnSelf();
                    Cache::shouldReceive('increment')
                        ->once()
                        ->with($globalThrottleCacheKey);
                }
            } else {
                // if the item is not in the cache we'll receive store twice
                // and one call to put to get it in the cache
                // we will not receive an increment call
                Cache::shouldReceive('store')
                    ->twice()
                    ->with($throttleCacheDriver)
                    ->andReturnSelf();
                Cache::shouldReceive('put')
                    ->once()
                    ->withAnyArgs();
                Cache::shouldReceive('increment')
                    ->never();
            }
        }

        // check our expected against our actual
        $actual = $this->emailHandlerMock->globalThrottle();
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for testGlobalThrottle
     *
     * @return array
     */
    public function globalThrottleProvider()
    {
        return [
            'global throttle off' => [
                false,
                'file',
                20,
                30,
                false,
                10,
                false,
            ],
            'global throttle on in cache under limit' => [
                true,
                'file',
                20,
                30,
                true,
                10,
                false,
            ],
            'global throttle on in cache over limit' => [
                true,
                'file',
                20,
                30,
                true,
                21,
                true,
            ],
            'global throttle on not in cache' => [
                true,
                'file',
                20,
                30,
                false,
                0,
                false,
            ],
        ];
    }

    /**
     * test the throttle function
     *
     * @dataProvider throttleProvider
     * @param $throttle
     * @param $throttleCacheDriver
     * @param $isInDontThrottleListReturn
     * @param $throttleCacheHasReturn
     * @param $throttleDurationMinutes
     * @param $expected
     */
    public function testThrottle(
        $throttle,
        $throttleCacheDriver,
        $isInDontThrottleListReturn,
        $throttleCacheHasReturn,
        $throttleDurationMinutes,
        $expected
    ) {

        // mock exception used for testing
        $exception = new Exception('Test Exception');

        // set up the config
        config([
            'laravelEmailExceptions.ErrorEmail.throttle' => $throttle,
            'laravelEmailExceptions.ErrorEmail.throttleCacheDriver' => $throttleCacheDriver,
            'laravelEmailExceptions.ErrorEmail.throttleDurationMinutes' => $throttleDurationMinutes,
        ]);

        // set up mock cache key
        $throttleCacheKey = 'insert_key_here';


        if ($throttle == false) {
            // if throttling is turned off we will not receive a call to check the dont throttle list
            $this->emailHandlerMock
                ->shouldNotReceive('isInDontThrottleList');
        } else {
            // else we will receive a call to check if its in the dont throttle list
            $this->emailHandlerMock
                ->shouldReceive('isInDontThrottleList')
                ->once()
                ->with($exception)
                ->andReturn($isInDontThrottleListReturn);
        }

        if ($throttle == false || $isInDontThrottleListReturn) {
            // if throttling is off or its in the dont throttle list
            // we wont be calling any of the other functions
            Cache::shouldReceive('store')
                ->never();
            Cache::shouldReceive('has')
                ->never();
            Cache::shouldReceive('put')
                ->never();
            $this->emailHandlerMock
                ->shouldNotReceive('getThrottleCacheKey');
        } else {
            // we made it past the dont throttle list and throttling is on
            // we'll at least recieve a call to check it's in the cache
            Cache::shouldReceive('has')
                ->once()
                ->with($throttleCacheKey)
                ->andReturn($throttleCacheHasReturn);
            if ($throttleCacheHasReturn == true) {
                // if it is in the cache we'll receive a call to get the cache key once
                $this->emailHandlerMock
                    ->shouldReceive('getThrottleCacheKey')
                    ->once()
                    ->with($exception)
                    ->andReturn($throttleCacheKey);

                // we'll also receive a call to store once
                Cache::shouldReceive('store')
                    ->once()
                    ->with($throttleCacheDriver)
                    ->andReturnSelf();

                // we will not receive a call to put it in the cache since its already there
                Cache::shouldReceive('put')
                    ->never();
            } else {
                // if its not in the cache we'll receive two calls to store (once for has once for put)
                Cache::shouldReceive('store')
                    ->twice()
                    ->with($throttleCacheDriver)
                    ->andReturnSelf();

                // we'll receive two calls to get the cache key (once for has once for put)
                $this->emailHandlerMock
                    ->shouldReceive('getThrottleCacheKey')
                    ->twice()
                    ->with($exception)
                    ->andReturn($throttleCacheKey);

                // and we'll receive one call to put the item in the cache
                Cache::shouldReceive('put')
                    ->once()
                    ->withAnyArgs();
            }
        }

        // check if our actual = expected
        $actual = $this->emailHandlerMock->throttle($exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for testThrottle
     */
    public function throttleProvider()
    {
        return [
            'throttle off' => [
                false,
                'file',
                false,
                false,
                5,
                false,
            ],
            'throttle on in dont throttle list' => [
                false,
                'file',
                true,
                false,
                5,
                false,
            ],
            'throttle on has cache key' => [
                true,
                'file',
                false,
                true,
                5,
                true,
            ],
            'throttle on does not have cache key' => [
                true,
                'file',
                false,
                false,
                5,
                false,
            ],
        ];
    }

    /**
     * test get throttle cache key function
     *
     * @dataProvider getThrottleCacheKeyProvider
     * @param $exception
     * @param $expected
     */
    public function testGetThrottleCacheKey($exception, $expected)
    {
        // check if actual = expected
        $actual = $this->emailHandlerMock->getThrottleCacheKey($exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for testGetThrottleCacheKey
     *
     * @return array
     */
    public function getThrottleCacheKeyProvider()
    {
        return [
            'Exception 1' => [
                new Exception('Test-Exception', 1),
                'laravelEmailExceptionExceptionTestException1',
            ],
            'Exception 2' => [
                new Exception('Test$Some Exception', 2),
                'laravelEmailExceptionExceptionTestSomeException2',
            ],
            'Exception 3' => [
                new BadMethodCallException('Test-Third_Exception', 3),
                'laravelEmailExceptionBadMethodCallExceptionTestThirdException3',
            ],
        ];
    }

    /**
     * test is in list function
     *
     * @dataProvider isInListProvider
     * @param $exception
     * @param $list
     * @param $expected
     */
    public function testIsInList($exception, $list, $expected)
    {
        // check if actual = expected
        $actual = $this->emailHandlerMock->isInList($list, $exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for test is in list
     *
     * @return array
     */
    public function isInListProvider()
    {
        return [
            'in list' => [
                new Exception('Test Exception'),
                [new Exception('Test Exception')],
                true,
            ],
            'not in list' => [
                new Exception('Test Exception'),
                [new BadMethodCallException('Other Exception')],
                false,
            ],
        ];
    }

    /**
     * test is in dont throttle list function
     *
     * @dataProvider isInDontThrottleListProvider
     * @param $exception
     * @param $dontThrottleList
     * @param $isInListReturn
     * @param $expected
     */
    public function testIsInDontThrottleList($exception, $dontThrottleList, $isInListReturn, $expected)
    {
        // set up config values
        config([
            'laravelEmailExceptions.ErrorEmail.dontThrottle' => $dontThrottleList,
        ]);

        // we should receive a call to is in list
        $this->emailHandlerMock
            ->shouldReceive('isInList')
            ->once()
            ->with($dontThrottleList, $exception)
            ->andReturn($isInListReturn);

        // check if actual is = to expected
        $actual = $this->emailHandlerMock->isInDontThrottleList($exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for testIsInDontThrottleList
     *
     * @return array
     */
    public function isInDontThrottleListProvider()
    {
        return [
            'in dont throttle list' => [
                new Exception('Test Exception'),
                [new Exception('Test Exception')],
                true,
                true,
            ],
            'not in dont throttle list' => [
                new Exception('Test Exception'),
                [new BadMethodCallException('Other Exception')],
                false,
                false,
            ],
        ];
    }

    /**
     * test is in dont email list function
     *
     * @dataProvider isInDontEmailListProvider
     * @param $exception
     * @param $dontEmailList
     * @param $isInListReturn
     * @param $expected
     */
    public function testIsInDontEmailList($exception, $dontEmailList, $isInListReturn, $expected)
    {

        // set up config values
        config([
            'laravelEmailExceptions.ErrorEmail.dontEmail' => $dontEmailList,
        ]);

        // we should receive a call to is in list
        $this->emailHandlerMock
            ->shouldReceive('isInList')
            ->once()
            ->with($dontEmailList, $exception)
            ->andReturn($isInListReturn);

        // check if actual is = to expected
        $actual = $this->emailHandlerMock->isInDontEmailList($exception);
        $this->assertEquals($expected, $actual);
    }

    /**
     * data provider for is testIsInDontEmailList
     *
     * @return array
     */
    public function isInDontEmailListProvider()
    {
        return [
            'in dont email list' => [
                new Exception('Test Exception'),
                [new Exception('Test Exception')],
                true,
                true,
            ],
            'not in dont email list' => [
                new Exception('Test Exception'),
                [new BadMethodCallException('Other Exception')],
                false,
                false,
            ],
        ];
    }
}
