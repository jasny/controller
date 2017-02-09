Jasny Controller
===

[![Build Status](https://travis-ci.org/jasny/controller.svg?branch=master)](https://travis-ci.org/jasny/controller)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/controller/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/controller/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/controller/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4a7a21bb-c5be-4abf-968e-4503b6f627df/mini.png)](https://insight.sensiolabs.com/projects/4a7a21bb-c5be-4abf-968e-4503b6f627df)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)
[![Packagist License](https://img.shields.io/packagist/l/jasny/controller.svg)](https://packagist.org/packages/jasny/controller)

A general purpose controller for PSR-7

The controller is responsible handling the HTTP request, maninipulate the modal and initiate the view. The code in
the controller read as a high level description of each action. The controller should not contain implementation
details. This belongs in the model, view or in services and libraries.

Installation
---

Install using composer

    composer require jasny\controller

Setup
---

`Jasny\Controller` can be used as a base class for each of your controllers. It let's you interact with the
[PSR-7](http://www.php-fig.org/psr/psr-7/) server request and response in a friendly matter.

A controller is a callable object. This means it implements the [`_invoke`](http://php.net/manual/en/language.oop5.magic.php#object.invoke)
method. The invoke method takes a PSR-7 server request and response object and will return a modified response object.
This all is abstracted away when you write your controller.

### Run

What you need to do is implement the `run()` method. It takes no arguments. The controller methods allow you to interact
with the request and response objects.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        // Do something
    }
}
```

Note that the `run` method doesn't need to return anything. There are different methods to manipulate the response.
Anything that is returned is simply ignored.

### Output

When using PSR-7, you shouldn't use `echo`. Instead, the `output()` method can be used to output stuff. To output
'Hello world' as text, you'd do `$this->output("Hello world", 'text')`. To output an array as JSON you'd use
`$this->output($array, 'json')`.

For some types `output` will also encode the data. Almost any data can be encoded to JSON. For XML the controller
expects a `SimpleXML` or `DOM` object as output data.

Setting the output type will set the `Content-Type` header. The type should match commen file extensions. The controller
uses [Dflydev's Apache MIME Types](https://github.com/dflydev/dflydev-apache-mime-types) library to get the mime type
for the type.

Instead of using a short type, you can also specify the full mime type as `$this->output($array, 'application/json')`.

```php
class MyPageController extends Jasny\Controller
{
    /**
     * Output a random number between 0 and 100 as plain text
     */
    public function run()
    {
        $number = rand(0, 100);
        $this->output($number, 'text');
    }
}
```

You're not required to set the output type. In that case, the controller will guess. If the `Content-Type` response
header has been explictly set ([more on that later](#set-the-content-type)), it will be used. If nothing is set, it
defaults to `text/html`.

### Query parameters (aka GET data)

With PSR-7, you shouldn't use the `$_GET` super global. To get all query parameters (typically in `$_GET`), use
`$this->getQueryParams()`.

You can check if query parameter 'foo' is set using `$this->hasQueryParam("foo")`. To get just that query parameter, use
`$this->getQueryParam("foo")`. If the query parameter doesn't exist `getQueryParam` will return `null`.

When getting a single query parameter using `getQueryParam()` you can specify a default as second argument. Additionally
you can specify a [filter](http://php.net/manual/en/filter.filters.php) with filter options.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        $page = $this->getQueryParam("page", 1, FILTER_VALIDATE_INT, ['min_range' => 1]);
        // ...
    }
}
```

You can get a number of specific query parameters, optionally with default values, using `getQueryParams()`.

```php
list($foo, $bar, $zoo) = $this->getQueryParams(['foo', 'bar' => 10, 'zoo' => 'monkey']);
```

### Input data (aka POST data)

With PSR-7, you shouldn't use the `$_POST` and `$_FILES` super globals directly. Instead the `getInput()` method will
get the input data.

If the POST request is a form upload, so the `Content-Type` of the request is either `application/x-url-form-encoded` or
`multipart/form-data`, the input is a mixture of post data and uploaded files. For other data type, the PSR response
object will try to parse the content body. This typically works for JSON and XML. In other cases, calling `getInput()`
will return `null`.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        $data = $this->getInput();
        // ...
    }
}
```

### Setting the response status

To set the response type you can use the `respondWith()` method. This method can take the response status as integer or
as string specifying both the status code and phrase.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        if ($this->hasQueryParam('type')) {
            $this->respondWith("400 Bad Request");
            $this->output("Missing the 'type' query parameters");
            return;
        }

        // Create something ...
        
        $this->setResponseHeader("Location: http://www.example.com/foo/something");
        $this->respondWith(201);
        $this->output($something, 'json');
    }
}
```

_Note that the `respondWith()` method can also be used to [set the `Content-Type` response header](#set-the-content-type)._

Alternatively and preferably you can use helper method to set a specific response status. Some method can optionally
take arguments that make sence for that status.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        if ($this->hasQueryParam('type')) {
            return $this->badRequest("Missing the 'type' query parameters"); // Doesn't actually return anything
        }

        // Create something ...
        
        $this->created("http://www.example.com/foo/something");
        $this->output($something, 'json');
    }
}
```

The following methods for setting the output status are available

+-------------------------+---------------------------------------------------------------------------+-----------------------------------------------------+
| status code             | method                                                                    |                                                     |
+-------------------------+---------------------------------------------------------------------------+-----------------------------------------------------+
| [200][]                 | `ok()`                                                                    |                                                     |
| [201][]                 | `created(string $location = null)`                                        | Optionally set the `Location` header                |
| [203][]                 | `accepted()`                                                              |                                                     |
| [204][]                 | `noContent(int $code = 204)`                                              |                                                     |
| [206][]                 | `partialContent(int $rangeFrom, int $rangeTo, int $totalSize)`            | Set the `Content-Range` and `Content-Length` header |
| [30x][]                 | `redirect(string $url, int $code = 303)`                                  | Url for the `Location` header                       |
| [303][]                 | `back()`                                                                  | Redirect to the referer*                            |
| [304][]                 | `notModified()`                                                           |                                                     |
| [40x][400]              | `badRequest(string $message, int $code = 400)`                            |                                                     |
| [401][]                 | `requireAuth()` or `requireLogin()`                                       |                                                     |
| [402][]                 | `paymentRequired(string $message = "Payment required")`                   |                                                     |
| [403][]                 | `forbidden(string $message = "Access denied")`                            |                                                     |
| [404][]/[405][]/[410][] | `notFound(string $message = "Not found", int $code = 404)`                |                                                     |
| [409][]                 | `conflict(string $message)`                                               |                                                     |
| [429][]                 | `tooManyRequests(string $message = "Too many requests")`                  |                                                     |
| [5xx][500]              | `error(string $message = "An unexpected error occured", int $code = 500)` |                                                     |
+-------------------------+---------------------------------------------------------------------------+-----------------------------------------------------+

- Some methods take a `$message` argument. This will set the output.
- If a method takes a `$code` argument, you can specify the status code. _Note that you can specify any status code,
  though only some should be used (don't use a 400 status with `redirect()`)._
- The `back()` method will redirect to the referer, but only if the referer is from the same domain as the current url.

[200]: https://httpstatuses.com/200
[201]: https://httpstatuses.com/201
[203]: https://httpstatuses.com/203
[204]: https://httpstatuses.com/200
[206]: https://httpstatuses.com/200
[303]: https://httpstatuses.com/200
[304]: https://httpstatuses.com/200
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

### Setting response headers

You can set the response header using the `setResponseHeader()` method.

```php
class MyPageController extends Jasny\Controller
{
    public function run()
    {
        $this->setResponseHeader("Content-Language", "nl");
        // ...
    }
}
```

By default response headers get overwritting. In some cases you want to have duplicate headers. In that case set the
third argument to `false`, eg `setResponseHeader($header, $value, false)`.

```php
$this->setResponseHeaders("Cache-Control", "no-cache");
$this->setResponseHeaders("Cache-Control", "no-store", false);
```

#### Set the content type

To set the `Content-Type` header, you can also use the `respondWith()` method. You can specify the full mime type as
`$this->respondWith("application/json")`. Alternative you can use a type (which corresponds with a file extension). The
controller uses [Dflydev's Apache MIME Types](https://github.com/dflydev/dflydev-apache-mime-types) library to get the
mime type for the type.

You can use `respondWith()` to set both the response status and content type as `$this->respondWith(200, "json")`.

The method `byDefaultSerializeTo()` can be used to let the application automatically change the content type the output
data isn't a string. You can set it to eliminate having to specify the content type with the `output()` method.
