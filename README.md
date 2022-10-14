Jasny Controller
===

[![PHP](https://github.com/jasny/controller/actions/workflows/php.yml/badge.svg)](https://github.com/jasny/controller/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/controller/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/controller/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)
[![Packagist License](https://img.shields.io/packagist/l/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)

A general purpose controller for PSR-7

**The controller is responsible handling the HTTP request, manipulate the model and initiate the view.**

The code in the controller read as a high level description of each action. The controller should not contain
implementation details. This belongs in the model, view or in services and libraries.

Installation
---

Install using composer

    composer require jasny\controller

Setup
---

`Jasny\Controller` can be used as a base class for each of your controllers. It lets you interact with the
[PSR-7](http://www.php-fig.org/psr/psr-7/) server request and response in a friendly matter.

```php
class MyController extends Jasny\Controller\Controller
{
    public function hello(string $name, #[QueryParam] string $others = ''): void
    {
        $this->output("Hello $name" . ($others ? " and $others" : ""), 'text');
    }
}
```

> Visiting `https://example.com/hello/Arnold&others=friends` would output `Hello Arnold and friends`.

Actions are defined as public methods of the controller.

A controller is a callable object by implementing the [`__invoke`][] method. The invoke method takes a PSR-7
server request and response object and will return a modified response object. This all is abstracted away when you
write your controller.

A router typically handles the request and chooses the correct controller object to call. The router is also responsible
for extracting parameters from the url path and possibly choosing a method to call within the controller.

[`__invoke`]: http://php.net/manual/en/language.oop5.magic.php#object.invoke

### SwitchRoute

This library works with [SwitchRoute](https://github.com/jasny/switch-route), a super-fast router based on generating
code. The router needs a PSR-15 request handler to work with PRS-7 server requests, like [Relay](https://relayphp.com/).

By default, the route action is converted to the method that will be called by the PSR-15 handler. For this library,
`__invoke` should be called instead. The invoke method will take care of calling the right method within the controller.

```php
$stud = fn($str) => strtr(ucwords($str, '-'), ['-' => '']);

$invoker = new Invoker(fn (?string $controller, ?string $action) => [
    $controller !== null ? $stud($controller) . 'Controller' : $stud($action) . 'Action',
    '__invoke'
]);
```

**[See SwitchRoute for more information](https://github.com/jasny/switch-route#readme)**

### Slim framework

[Slim](https://www.slimframework.com/) is a PHP micro framework that works with PSR-7. To use this library with slim,
use the provided middleware.

```php
use Jasny\Controller\Middleware\Slim as ControllerMiddleware;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->add(new ControllerMiddleware());
$app->addRoutingMiddleware();

$app->get('/hello/{name}', ['MyController', 'hello']);
```

Optionally, the middleware can convert error responses from the controller to Slim HTTP Errors by passing `true` to the
middleware constructor.

```php
use Jasny\Controller\Middleware\Slim as ControllerMiddleware;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->add(new ControllerMiddleware(true));
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
```

Output
---

When using PSR-7, you shouldn't use `echo`, because it makes it harder to write tests. Instead, use the `output` method
of the controller, which writes to the response body stream object.

```php
$this->output('Hello world');
```

A second argument may be passed, which sets the `Content-Type` header. You can pass a mime type like 'text/html'.
Alternatively you can  use a common file extension like 'txt'. The controller uses the
[ralouphie/mimey](https://github.com/ralouphie/mimey) library to get the mime type.

```php
class MyController extends Jasny\Controller\Controller
{
    /**
     * Output a random number between 0 and 100 as HTML
     */
    public function random()
    {
        $number = rand(0, 100);
        $this->output("<h1>$number</h1>", 'html');
    }
}
```

### JSON

The `json` method can be used to serialize and output data as JSON.

```php
class MyController extends Jasny\Controller\Controller
{
    /**
     * Output 5 random numbers between 0 and 100 as JSON
     */
    public function random()
    {
        $numbers = array_map(fn() => rand(0, 100), range(1, 5));
        $this->json($numbers);
    }
}
```

### Response status

To set the response status you can use the `status()` method. This method can take the response status as integer or
as string specifying both the status code and phrase.

```php
class MyController extends Jasny\Controller\Controller
{
    public function process(string $size)
    {
        if (!in_array($size, ['XS', 'S', 'M', 'L', 'XL'])) {
            return $this
                ->status("400 Bad Request")
                ->output("Invalid size: $size");
        }

        // Create something ...
        
        return $this
            ->status(201)
            ->header("Location: http://www.example.com/foo/something")
            ->json($something);
    }
}
```

Alternatively and preferably you can use helper method to set a specific response status. Some method can optionally
take arguments that make sense for that status.

```php
class MyController extends Jasny\Controller\Controller
{
    public function process(string $size)
    {
        if (!in_array($size, ['XS', 'S', 'M', 'L', 'XL'])) {
            return $this->badRequest()->output("Invalid size: $size");
        }

        // Create something ...
        
        return $this
            ->created("http://www.example.com/foo/something")
            ->json($something);
    }
}
```

The following methods for setting the output status are available

| status code             | method                                                         |                                                     |
|-------------------------|----------------------------------------------------------------|-----------------------------------------------------|
| [200][]                 | `ok()`                                                         |                                                     |
| [201][]                 | `created(?string $location = null)`                            | Optionally set the `Location` header                |
| [202][]                 | `accepted()`                                                   |                                                     |
| [204][]/[205][]         | `noContent(int $code = 204)`                                   |                                                     |
| [206][]                 | `partialContent(int $rangeFrom, int $rangeTo, int $totalSize)` | Set the `Content-Range` and `Content-Length` header |
| [30x][303]              | `redirect(string $url, int $code = 303)`                       | Url for the `Location` header                       |
| [303][]                 | `back()`                                                       | Redirect to the referer                             |
| [304][]                 | `notModified()`                                                |                                                     |
| [40x][400]              | `badRequest(int $code = 400)`                                  |                                                     |
| [401][]                 | `unauthorized()`                                               |                                                     |
| [402][]                 | `paymentRequired()`                                            |                                                     |
| [403][]                 | `forbidden()`                                                  |                                                     |
| [404][]/[405][]/[410][] | `notFound(int $code = 404)`                                    |                                                     |
| [406][]                 | `notAcceptable()`                                              |                                                     |
| [409][]                 | `conflict()`                                                   |                                                     |
| [429][]                 | `tooManyRequests()`                                            |                                                     |
| [5xx][500]              | `error(int $code = 500)`                                       |                                                     |

- Some methods take a `$message` argument. This will set the output.
- If a method takes a `$code` argument, you can specify the status code.
- The `back()` method will redirect to the referer, but only if the referer is from the same domain as the current url.

[200]: https://httpstatuses.com/200
[201]: https://httpstatuses.com/201
[202]: https://httpstatuses.com/202
[203]: https://httpstatuses.com/203
[204]: https://httpstatuses.com/204
[205]: https://httpstatuses.com/205
[206]: https://httpstatuses.com/206
[303]: https://httpstatuses.com/303
[304]: https://httpstatuses.com/304
[400]: https://httpstatuses.com/400
[401]: https://httpstatuses.com/401
[402]: https://httpstatuses.com/402
[403]: https://httpstatuses.com/403
[404]: https://httpstatuses.com/404
[405]: https://httpstatuses.com/405
[406]: https://httpstatuses.com/406
[410]: https://httpstatuses.com/410
[409]: https://httpstatuses.com/409
[429]: https://httpstatuses.com/429
[500]: https://httpstatuses.com/500

Sometimes it's useful to check the status code that has been set for the response. This can be done with the
`getStatusCode()` method. In addition, there are methods to check the type of status.

| status code | method              |
|-------------|---------------------|
| 1xx         | `isInformational()` |
| 2xx         | `isSuccessful()`    |
| 3xx         | `isRedirection()`   |
| 4xx         | `isClientError()`   |
| 5xx         | `isServerError()`   |
| 4xx or 5xx  | `isError()`         |


### Response headers

You can set the response header using the `setResponseHeader()` method.

```php
class MyController extends Jasny\Controller\Controller
{
    public function process()
    {
        $this->header("Content-Language", "nl");
        // ...
    }
}
```

By default, response headers are overwritten. In some cases you want to have duplicate headers. In that case set the
third argument to `true`, eg `header($header, $value, true)`.

```php
$this->header("Cache-Control", "no-cache"); // overwrite header
$this->header("Cache-Control", "no-store", true); // add header
```

Input
---

With PSR-7, you shouldn't use super globals `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`. Instead, these values are
available through the server request object. This is done using [PHP attributes][].

| Attribute       | Arguments  |                                           |
|-----------------|------------|-------------------------------------------|
| `PathParam`     | name, type | Path parameter obtained from router       |
| `QueryParam`    | name, type | Query parameter                           |
| `Query`         |            | All query parameters                      |
| `BodyParam`     | name, type | Body parameter                            |
| `Body`          |            | All body parameters or raw body as string |
| `Cookie`        | name, type | Cookie parameter                          |
| `Cookies`       |            | All cookies as key/value                  |
| `UploadedFile`  | name       | PSR-7 uploaded file(s)                    |
| `UploadedFiles` |            | Associative array of all uploaded files   |
| `Header`        | name, type | Request header (as string)                |
| `Headers`       |            | All headers as associative array          |     
| `Attr`          | name, type | PSR-7 request attribute set by middleware |

[PHP attributes]: https://www.php.net/manual/en/language.attributes.overview.php

The controller will map each argument of a method to a parameter. By default, arguments are mapped to path parameters.

### Parameters

#### Path parameters

A router may extract parameters from the request URL. In the following example, the url path `/hello/world`,
the path parameter `name` will have the value `"world"`.

```php
$app->get('/hello/{name}', ['MyController', 'hello']);
```

The `name` parameter will be passed as argument to the `hello` method.

```php
class MyController extends Jasny\Controller\Controller
{
    public function hello(string $name)
    {
        $this->output("Hello $name");
    }
}
```

#### Single request parameter

The controller will pass PSR-7 request parameters as arguments. This is specified by an attribute

* `QueryParam`
* `BodyParam`
* `Cookie`
* `UploadedFile`
* `Header`

If the argument name is used as parameter name

* for `QueryParam`, underscores are replaced with dashes. Eg: `$foo_bar` will translate to query param `foo-bar`.
* for `Header`, words are capitalized and underscores become dashes. Eg: `$foo_bar` translates to header `Foo-Bar`.

#### All request parameters

To get all request parameters of a specific type, the following attributes are available.

* `Query`
* `Body`
* `Cookies`
* `UploadedFiles`
* `Headers`

For the `Body` attribute, the type of the argument should either be an array or a string. If an array is passed the
argument will be the parsed body. In case of a string it will be the raw body.

#### PSR-7 request attribute

Middleware can set attributes of the PSR-7 request. These request attributes are available as arguments by using the
`Attr` attribute.

### Parameter name

For single parameters, the name of the argument will be used as parameter name. Alternatively, it's possible to specify
a name when defining the attribute.

```php
use Jasny\Controller\Controller;
use Jasny\Controller\Parameter\PathParam;
use Jasny\Controller\Parameter\QueryParam;

class MyController extends Controller
{
    public function hello(#[PathParam] string $name, #[QueryParam('and')] string $other = '')
    {
        $this->output("Hello $name" . ($other ? " and $other" : ""));
    }
}
```

_Note: `#[PathParam]` could be omitted, since it's the default behaviour._

### Parameter type

It's possible to specify a type as second argument when defining the attribute. By default, the type is determined on
the type of the argument.

```php
use Jasny\Controller\Controller;
use Jasny\Controller\Parameter\BodyParam;

class MyController extends Controller
{
    public function send(#[BodyParam(type: 'email')] string $emailAddress)
    {
        // ...
    }
}
```

Parameter attributes use the [`filter_var`](https://www.php.net/filter_var) function to sanitize input. The following
filters are defined

| type  | filter                  |
|-------|-------------------------|
| bool  | `FILTER_VALIDATE_BOOL`  |
| int   | `FILTER_VALIDATE_INT`   |
| float | `FILTER_VALIDATE_FLOAT` |
| email | `FILTER_VALIDATE_EMAIL` |
| url   | `FILTER_VALIDATE_URL`   |

For other types (like `string`), no filter is applied.

```php
use Jasny\Controller\Controller;
use Jasny\Controller\Parameter\PostParam;

class MyController extends Controller
{
    public function message(#[PostParam(type: 'email')] array $email)
    {
        // ...
    }
}
```

To add custom types, add filters to `SingleParameter::$types`

```php
use Jasny\Controller\Parameter\SingleParameter;

SingleParameter::$types['slug'] = [FILTER_VALIDATE_REGEXP, '/^[a-z\-]+$/'];
```

Content negotiation
---

Content negotiation allows the controller to give different output based on `Accept` request headers. It can be used to
select the content type (switch between JSON and XML), the content language, encoding, and charset.

| Method                   | Request header    | Response header    |    
|--------------------------|-------------------|--------------------|
| `negotiateContentType()` | `Accept`          | `Content-Type`     |
| `negotiateLanguage()`    | `Accept-Language` | `Content-Language` | 
| `negotiateEncoding()`    | `Accept-Encoding` | `Content-Encoding` | 
| `negotiateCharset()`     | `Accept-Charset`  |                    |

_`negotiateCharset()` will modify the `Content-Type` header if it's already set. Otherwise, it will just
return the selected charset._

The negotiate method takes a list or priorities as argument. It sets the response header and returns the selected
option.

```php
class MyController extends Jasny\Controller\Controller
{
    public function hello()
    {
        $language = $this->negotiateLanguage(['en', 'de', 'fr', 'nl;q=0.6']);
        
        switch ($language) {
            case 'en':
                return $this->output('Good morning');
            case 'de':
                return $this->output('Guten Morgen');
            case 'fr':
                return $this->output('Bonjour');
            case 'nl':
                return $this->output('Goedemorgen');
            default:
                return $this
                    ->notAcceptable()
                    ->output("This content isn't available in your language");
        }
    }
}
```

For more information, please check the documentation of the [willdurand/negotiation] library.

[willdurand/negotiation]: https://github.com/willdurand/Negotiation

Hooks
---

In addition to the action method, the controller will also call the `before()` and `after()` method.

### Before

The `before()` method is call prior to the action method. If it returns a response, the method action is never called.

```php
class MyController extends Jasny\Controller\Controller
{
    protected function before()
    {
        if ($this->auth->getUser()->getCredits() <= 0) {
            return $this->paymentRequired()->output("Sorry, you're out of credits");
        }
    }

    // ...
}
```

_Instead of `before()` consider using guards._

### After

The `after()` method is called after the action, regardless of the action response type.

```php
class MyController extends Jasny\Controller\Controller
{
    // ...
    
    protected function after()
    {
        $this->header('X-Available-Credits', $this->auth->getUser()->getCredits());
    }
}
```

Guards
---

Guards are [PHP Attributes] that are invoked before the controller method is called. A guard is similar to middleware,
though more limited. The purpose of using a guard is to check if the controller action may be executed. If the guard
returns a response, that response is emitted and the method on the controller is never called.

```php
class MyController extends Jasny\Controller\Controller
{
    #[MustBeLoggedIn]
    public function send()
    {
        // ...
    }
}
```

A guard class should implement the `process` method. A guard class has the same methods as a controller class. The
`process` method can have input parameters.

```php
use Jasny\Controller\Guard;
use Jasny\Controller\Parameter\Attr;

#[\Attribute]
class MustBeLoggedIn extends Guard
{
    public function process(#[Attr] User? $sessionUser)
    {
        if ($sessionUser === null) {
            return $this->forbidden()->output("Not logged in");
        }
    }
}
```

### Order or execution

Guards may be defined on the controller class or the action method. The order of execution is

* Class guards
* `before()`
* Method guards
* Action
* `after()`

### Dependency injection

Guards are attributes, which are [instantiated using PHP reflection]. Parameters can be specified when the guard is
declared.

```php
#[MinimalCredits(value: 20)]
class MyController extends \Jasny\Controller\Controller
{
    // ...
}
```

This makes it difficult to make a service (like a DB connection) available to a guard using dependency injection.

Some DI container libraries, like [PHP-DI](https://php-di.org/), are able to inject services to an already instantiated
object. To utilize this, overwrite the `Guardian` class and register it to the container.

```php
use Jasny\Controller\Guardian;
use Jasny\Controller\Guard;
use DI\Container;

return [
    Guardian::class => function (Container $container) {
        return new class ($container) extends Guardian {
            public function __construct(private Container $container) {}
            
            public function instantiate(\ReflectionAttribute $attribute): Guard {
                $guard = $attribute->newInstance();
                $this->container->injectOn($guard);
                
                return $guard;
            }
        } 
    }
];
```

The guard class can use `#[Inject]` attributes or `@Inject` annotations.

```php
use Jasny\Controller\Guard;
use DI\Attribute\Inject;

class MyGuard extends Guard
{
    #[Inject]
    private DBConnection $db;
    
    // ...
}
```

Make sure the `Guardian` service is injected into the controller using dependency injection.

```php
use Jasny\Controller\Controller;
use Jasny\Controller\Guardian;

class MyController extends Controller
{
    public function __construct(
        protected Guardian $guardian
    ) {}
}
```

[instantiated using PHP reflection]: https://www.php.net/manual/en/language.attributes.reflection.php
