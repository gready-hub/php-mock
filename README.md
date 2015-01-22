# About PHP-Mock

PHP-Mock is a testing library which mocks non deterministic built-in PHP functions like
`time()` or `rand()`. This is achived by [PHP's namespace fallback policy](http://php.net/manual/en/language.namespaces.fallback.php):

> PHP will fall back to global functions […]
> if a namespaced function […] does not exist.

PHP-Mock uses that feature by providing the namespaced function. I.e. you have
to be in a **non global namespace** context and call the function
**unqualified**:

```php
<?php

namespace foo;

$time = time(); // This call can be mocked, a call to \time() can't.
```

## Requirements and restrictions

* PHP-5.4 or newer. There's also a [PHP-5.3 branch](https://github.com/malkusch/php-mock/tree/php-5.3).

* Only *unqualified* function calls in a namespace context can be mocked.
  E.g. a call for `time()` in the namespace `foo` is mockable,
  a call for `\time()` is not.

* The mock has to be defined before the first call to the unqualified function
  in the tested class. This is documented in [Bug #68541](https://bugs.php.net/bug.php?id=68541).
  In most cases you can ignore this restriction. But if you happen to run into
  this issue you can call [`Mock::define()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.Mock.html#_define)
  before that first call. This would define a side effectless namespaced
  function which can be enabled later.

## Alternatives

If you can't rely on or just don't want to use the namespace fallback policy,
there are alternative techniques to mock built-in PHP functions:

* [**PHPBuiltinMock**](https://github.com/jadell/PHPBuiltinMock) relies on
  the [APD](http://php.net/manual/en/book.apd.php) extension.

* [**MockFunction**](https://github.com/tcz/phpunit-mockfunction) is a PHPUnit
  extension. It uses the [runkit](http://php.net/manual/en/book.runkit.php) extension.

* [**vfsStream**](https://github.com/mikey179/vfsStream) is a stream wrapper for
  a virtual file system. This will help you write tests which covers PHP
  stream functions (e.g. `fread()` or `readdir()`).

# Installation

Use [Composer](https://getcomposer.org/):

```json
{
    "require": {
        "malkusch/php-mock": "dev-master"
    }
}
```


# Usage

If you plan to use PHPUnit you can skip to the [PHPUnit integration](#phpunit-integration).

You find the API in the namespace [`malkusch\phpmock`](http://malkusch.github.io/php-mock/namespace-malkusch.phpmock.html).

Create a [`Mock`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.Mock.html)
object. You can do this with the fluent API of [`MockBuilder`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html):

* [`MockBuilder::setNamespace()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html#_setNamespace)
  sets the target namespace of the mocked function.

* [`MockBuilder::setName()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html#_setName)
  sets the name of the mocked function (e.g. `time()`).

* [`MockBuilder::setFunction()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html#_setFunction)
  sets the concrete mock implementation.

* [`MockBuilder::setFunctionProvider()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html#_setFunctionProvider)
  sets alternativly to `MockBuilder::setFunction()` the mock implementation as a
  [`FunctionProvider`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.FunctionProvider.html):

   * [`FixedValueFunction`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.FixedValueFunction.html)
     is a simple implementation which returns always the same value.

   * [`FixedMicrotimeFunction`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.FixedMicrotimeFunction.html)
     is a simple implementation which returns always the same microtime. This
     class is different to `FixedValueFunction` as it contains a converter for
     `microtime()`'s float and string format.

   * [`SleepFunction`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.SleepFunction.html)
     is a `sleep()` implementation, which doesn't halt but increases an
     [`Incrementable`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.Incrementable.html)
     e.g. a `time()` mock.

   * [`UsleepFunction`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.UsleepFunction.html)
     is an `usleep()` implementation, which doesn't halt but increases an
     `Incrementable` e.g. a `microtime()` mock.

* [`MockBuilder::build()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockBuilder.html#_build)
  builds a `Mock` object.

After you have build your `Mock` object you have to call [`enable()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.Mock.html#_enable)
to enable the mock in the given namespace. When you are finished with that mock you
should disable it by calling [`disable()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.Mock.html#_disable)
on the mock instance. 

This example illustrates mocking of the unqualified function `time()` in the 
namespace `foo`:

```php
<?php

namespace foo;

use malkusch\phpmock\MockBuilder;

$builder = new MockBuilder();
$builder->setNamespace(__NAMESPACE__)
        ->setName("time")
        ->setFunction(
            function () {
                return 1417011228;
            }
        );
                    
$mock = $builder->build();

// The mock is not enabled yet.
assert (time() != 1417011228);

$mock->enable();
assert (time() == 1417011228);

// The mock is disabled and PHP's built-in time() is called.
$mock->disable();
assert (time() != 1417011228);
```

Instead of setting the mock function with `MockBuilder::setFunction()` you could also
use the existing [`FixedValueFunction`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.functions.FixedValueFunction.html):

```php
<?php

namespace foo;

use malkusch\phpmock\MockBuilder;
use malkusch\phpmock\functions\FixedValueFunction;

$builder = new MockBuilder();
$builder->setNamespace(__NAMESPACE__)
        ->setName("time")
        ->setFunctionProvider(new FixedValueFunction(1417011228));

$mock = $builder->build();
```

## Mock environments

Complex mock environments of several mocked functions can be grouped in a [`MockEnvironment`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockEnvironment.html):

* [`MockEnvironment::enable()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockEnvironment.html#_enable)
  enables all mocked functions of this environment.

* [`MockEnvironment::disable()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.MockEnvironment.html#_disable)
  disables all mocked functions of this environment.

### SleepEnvironmentBuilder

The [`SleepEnvironmentBuilder`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.SleepEnvironmentBuilder.html)
builds a mock environment where `sleep()` and `usleep()` return immediatly.
Furthermore they increase the amount of time in the mocked `time()` and
`microtime()`:

```php
<?php

namespace foo;

use malkusch\phpmock\SleepEnvironmentBuilder;

$builder = new SleepEnvironmentBuilder();
$builder->setNamespace(__NAMESPACE__)
        ->setTimestamp(1417011228);

$environment = $builder->build();
$environment->enable();

// This won't delay the test for 10 seconds, but increase time().        
sleep(10);

assert(1417011228 + 10 == time());
```

## Unit testing

PHP-Mock is meant to be used for unit testing, but not coupled to PHPUnit. You
can use an arbitrary testing framework.

### PHPUnit integration

PHP-Mock comes with the trait
[`PHPMock`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.phpunit.PHPMock.html)
to integrate into your PHPUnit-4 test case. This trait extends the framework
by the method
[`getFunctionMock()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.phpunit.PHPMock.html#_getFunctionMock).
With this method you can build a mock in the way you are used to build a
PHPUnit mock:

```php
<?php

namespace foo;

use malkusch\phpmock\phpunit\PHPMock;

class FooTest extends \PHPUnit_Framework_TestCase
{

    use PHPMock;

    public function testBar()
    {
        $time = $this->getFunctionMock(__NAMESPACE__, "time");
        $time->expects($this->once())->willReturn(3);
        $this->assertEquals(3, time());
    }
}
```

Note that this integration doesn't require you to disable the mock after the
test. This is done automatically.

### Reset global state

An enabled mock changes global state. This will break subsequent tests if
they run code which would call the mock unintentionally. Therefore
you should always disable a mock after the test case. You can do this in
several ways:

#### PHPUnit's `tearDown()`

If you defined the mock as a test member in `setup()` you can disable it
with `tearDown()`:

```php
    protected function tearDown()
    {
        $this->mock->disable();
    }
```

#### PHP's `finally`

If you have defined the mock locally you should disable it in a `finally` block.
This will guarantee the reseting of the global state in case of an exception:

```php
    /**
     * @expectedException Exception
     */
    public function testFoo()
    {
        $function = function () {
            throw new \Exception();
        };
        $mock = new Mock(__NAMESPACE__, "time", $function);
        $mock->enable();
        try {
            time();

        } finally {
            $mock->disable();

        }
    }
```

#### Disable all mocks statically

If you don't have the created mock objects anymore you can disable all mocks
in a `finally` or `tearDown()` by calling the static method
[`Mock::disableAll()`](http://malkusch.github.io/php-mock/class-malkusch.phpmock.Mock.html#_disableAll).

### Example

Let's assume we want to test a class `Alarm` which rings an alarm on the second
we set:

```php
<?php

namespace foo;

class Alarm
{

    private $timestamp;

    //…

    public function isRinging()
    {
        // Note: time() is an unqualified function name in the namespace foo.
        return time() == $this->timestamp;
    }
}
```

This would be the unit test for `Alarm::isRinging()`:

```php
<?php

namespace foo;

use malkusch\phpmock\MockBuilder;
use malkusch\phpmock\functions\FixedValueFunction;

class AlarmTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Mock The time() mock.
     */
    private $mock;

    /**
     * @var FixedValueFunction The mock function.
     */
    private $time;
    
    protected function setup()
    {
        $this->time = new FixedValueFunction();
        $builder = new MockBuilder();

        $this->mock = $builder->setNamespace(__NAMESPACE__)
                              ->setName("time")
                              ->setFunctionProvider($this->time)
                              ->build();

        $this->mock->enable();
    }
    
    protected function tearDown()
    {
        $this->mock->disable();
    }

    public function testRingAlarm()
    {
        $timestamp = 1417011228;
        $alarm = new Alarm($timestamp);

        $this->time->setValue($timestamp - 1);
        $this->assertFalse($alarm->isRinging());

        $this->time->setValue($timestamp);
        $this->assertTrue($alarm->isRinging());

        $this->time->setValue($timestamp + 1);
        $this->assertFalse($alarm->isRinging());
    }

}
```


# License and authors

This project is free and under the WTFPL.
Responsable for this project is Markus Malkusch markus@malkusch.de.
This library was inspired by Fabian Schmengler's article
[*PHP: “Mocking” built-in functions like time() in Unit Tests*](http://www.schmengler-se.de/en/2011/03/php-mocking-built-in-functions-like-time-in-unit-tests/).

## Donations

If you like PHP-Mock and feel generous donate a few Bitcoins here:
[1335STSwu9hST4vcMRppEPgENMHD2r1REK](bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK)

[![Build Status](https://travis-ci.org/malkusch/php-mock.svg?branch=master)](https://travis-ci.org/malkusch/php-mock)