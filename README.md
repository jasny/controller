Jasny Controller
===

[![PHP](https://github.com/jasny/controller/actions/workflows/php.yml/badge.svg)](https://github.com/jasny/controller/actions/workflows/php.yml)[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/controller/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/controller/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)
[![Packagist License](https://img.shields.io/packagist/l/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)

A general purpose controller for PSR-7

**The controller is responsible handling the HTTP request, maninipulate the modal and initiate the view.**

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
        $this->output("Hello, $name" . ($others ? " and $others" : ""), 'text');
    }
}
```

Actions should be defined as public methods of the controller.

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

When using PSR-7, you shouldn't use `echo`. Instead, use the `output` method of the controller.

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

The `json` method can be used to output data as JSON.

```php
class MyController extends Jasny\Controller\Controller
{
    /**
     * Output 5 random number between 0 and 100 as JSON
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
    public function process()
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
|-------------------------|----------------------------------------------------------------| --------------------------------------------------- |
| [200][]                 | `ok()`                                                         |                                                     |
| [201][]                 | `created(?string $location = null)`                            | Optionally set the `Location` header                |
| [202][]                 | `accepted()`                                                   |                                                     |
| [204][]/[205][]         | `noContent(int $code = 204)`                                   |                                                     |
| [206][]                 | `partialContent(int $rangeFrom, int $rangeTo, int $totalSize)` | Set the `Content-Range` and `Content-Length` header |
| [30x][303]              | `redirect(string $url, int $code = 303)`                       | Url for the `Location` header                       |
| [303][]                 | `back()`                                                       | Redirect to the referer*                            |
| [304][]                 | `notModified()`                                                |                                                     |
| [40x][400]              | `badRequest(int $code = 400)`                                  |                                                     |
| [401][]                 | `unauthorized()`                                               |                                                     |
| [402][]                 | `paymentRequired()`                                            |                                                     |
| [403][]                 | `forbidden()`                                                  |                                                     |
| [404][]/[405][]/[410][] | `notFound(int $code = 404)`                                    |                                                     |
| [409][]                 | `conflict()`                                                   |                                                     |
| [429][]                 | `tooManyRequests()`                                            |                                                     |
| [5xx][500]              | `error(int $code = 500)`                                       |                                                     |

- Some methods take a `$message` argument. This will set the output.
- If a method takes a `$code` argument, you can specify the status code. _Note that you can specify any status code,
  though only some should be used (don't use a 400 status with `redirect()`)._
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

| Attribute     | Arguments  |                                           |
|---------------|------------|-------------------------------------------|
| PathParam     | name, type | Path parameter obtained from router       |
| QueryParam    | name, type | Query parameter                           |
| Query         |            | All query parameters                      |
| BodyParam     | name, type | Body parameter                            |
| Body          |            | All body parameters or raw body as string |
| Cookie        | name, type | Cookie parameter                          |
| Cookies       |            | All cookies as key/value                  |
| UploadedFile  | name       | PSR-7 uploaded file(s)                    |
| UploadedFiles |            | Associative array of all uploaded files   |
| Header        | name, type | Request header (as string)                |
| Headers       |            | All headers as associative array          |     
| Attribute     | name, type | PSR-7 request attribute set by middleware |

[PHP attributes]: https://www.php.net/manual/en/language.attributes.overview.php

The controller will map each argument of a method to a parameter. By default, arguments are mapped to path parameters.

### Parameters

#### Path parameters

A router may extract parameters from the request URL. In the following example, the url path `/hello/world`,
the path parameter `name` will have the value `"world"`.

```php
$app->get('/hello/{name}', ['MyController', 'hello']);
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
`Attribute` attribute.

### Parameter name

For single parameters, the name of the argument will be used as parameter name. Alternatively, it's possible to specify
a name when defining the attribute.

```php
use Jasny\Controller\Parameter\PathParam;
use Jasny\Controller\Parameter\QueryParam;

class MyController extends Jasny\Controller\Controller
{
    public function hello(#[PathParam] string $name, #[QueryParam('and')] string $other = ''): void
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
use Jasny\Controller\Parameter\BodyParam;

class MyController extends Jasny\Controller\Controller
{
    public function send(#[BodyParam(type: 'email')] string $emailAddress): void
    {
        // ...
    }
}
```

Parameter attributes use the [`filter_var`](https://www.php.net/filter_var) function to sanitize input. The following
filters are defined

| type  | filter                |
|-------|-----------------------|
| bool  | FILTER_VALIDATE_BOOL  |
| int   | FILTER_VALIDATE_INT   |
| float | FILTER_VALIDATE_FLOAT |
| email | FILTER_VALIDATE_EMAIL |
| url   | FILTER_VALIDATE_URL   |

For other types (like `string`), no filter is applied.

```php
use Jasny\Controller\Parameter\PostParam;

class MyController extends Jasny\Controller\Controller
{
    public function message(#[PostParam(type: 'email')] array $email): void
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
