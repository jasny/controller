<?php

namespace Jasny;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller
 */
abstract class Controller
{
    /**
     * Server request
     * @var ServerRequestInterface
     **/
    protected $request = null;

    /**
     * Response
     * @var ResponseInterface
     **/
    protected $response = null;

    /**
     * Common input and output formats with associated MIME
     * @var array
     */
    protected $contentFormats = [
        'text/html' => 'html',
        'application/json' => 'json',
        'application/xml' => 'xml',
        'text/xml' => 'xml',
        'text/plain' => 'text',
        'application/javascript' => 'js',
        'text/css' => 'css',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpeg',
        'image/x-icon' => 'ico',
        'application/x-www-form-urlencoded' => 'post',
        'multipart/form-data' => 'post'
    ];

    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    abstract public function run();

    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Run the controller as function
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;

        return $this->run();
    }

    /**
     * Set the headers with HTTP status code and content type.
     * @link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * 
     * Examples:
     * <code>
     *   $this->responseWith(200, 'json');
     *   $this->responseWith(200, 'application/json');
     *   $this->responseWith(204);
     *   $this->responseWith("204 Created");
     *   $this->responseWith('json');
     * </code>
     * 
     * @param int          $code            HTTP status code (may be omitted)
     * @param string|array $format          Mime or content format
     * @return ResponseInterface $response
     */
    public function responseWith($code, $format = null)
    {
        $response = $this->getResponse();

        // Shift arguments if $code is omitted
        if (!is_int($code) && !preg_match('/^\d{3}\b/', $code)) {
            list($code, $format) = array_merge([null], func_get_args());
        }

        if ($code) {
            $response = $response->withStatus((int)$code);
        }
        
        if ($format) {
            $contentType = $this->getContentType($format);
            $response = $response->withHeader('Content-Type', $contentType);   
        }

        return $response;
    }

    /**
     * Response with success 200 code
     *
     * @return ResponseInterface $response
     */
    public function ok()
    {
        return $this->responseWith(200);
    }

    /**
     * Response with created 201 code, and optionaly redirect to created location
     *
     * @param string $location              Url of created resource
     * @return ResponseInterface $response
     */
    public function created($location = '')
    {
        $response = $this->responseWith(201);

        if ($location) {
            $response = $response->withHeader('Location', $location);
        }

        return $response;
    }

    /**
     * Response with 204 'No Content'
     *
     * @return ResponseInterface $response
     */
    public function noContent()
    {
        return $this->responseWith(204);
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @param int $code    301 (Moved Permanently), 303 (See Other) or 307 (Temporary Redirect)
     * @return ResponseInterface $response
     */
    public function redirect($url, $code = 303)
    {
        $response = $this->responseWith($code, 'html');
        $response = $response->withHeader('Location', $url);
        $response->getBody()->write('You are being redirected to <a href="' . $url . '">' . $url . '</a>');

        return $response;
    }

    /**
     * Redirect to previous page, or to home page
     *
     * @return ResponseInterface $response
     */
    public function back()
    {
        return $this->redirect($this->getLocalReferer() ?: '/');
    }

    /**
     * Route to 401
     * Note: While the 401 route is used, we don't respond with a 401 http status code.
     *
     * @return ResponseInterface $response
     */
    public function requireLogin()
    {
        return $this->redirect('/401');
    }

    /**
     * Alias of requireLogin
     *
     * @return ResponseInterface $response
     */
    public function requireAuth()
    {
        return $this->requireLogin();
    }

    /**
     * Set response to error 'Bad Request' state
     *
     * @param string $message
     * @param int $code                      HTTP status code
     * @return ResponseInterface $response
     */
    public function badRequest($message, $code = 400)
    {
        return $this->error($message, $code);   
    }

    /**
     * Set response to error 'Forbidden' state
     *
     * @param string $message
     * @param int $code                      HTTP status code
     * @return ResponseInterface $response
     */
    public function forbidden($message, $code = 403)
    {
        return $this->error($message, $code);   
    }

    /**
     * Set response to error 'Not Found' state
     *
     * @param string $message
     * @param int $code                      HTTP status code
     * @return ResponseInterface $response
     */
    public function notFound($message, $code = 404)
    {
        return $this->error($message, $code);   
    }

    /**
     * Set response to error 'Conflict' state
     *
     * @param string $message
     * @param int $code                      HTTP status code
     * @return ResponseInterface $response
     */
    public function conflict($message, $code = 409)
    {
        return $this->error($message, $code);   
    }

    /**
     * Set response to error 'Too Many Requests' state
     *
     * @param string $message
     * @param int $code                      HTTP status code
     * @return ResponseInterface $response
     */
    public function tooManyRequests($message, $code = 429)
    {
        return $this->error($message, $code);   
    }

    /**
     * Set response to error state
     *
     * @param string $message
     * @param int $code                     HTTP status code
     * @return ResponseInterface $response
     */
    public function error($message, $code = 400)
    {        
        $response = $this->getResponse();

        $errorResponse = $response->withStatus($code);
        $errorResponse->getBody()->write($message);

        return $errorResponse;
    }

    /**
     * Check if response is 2xx succesful, or empty
     * 
     * @return boolean
     */
    public function isSuccessful()
    {
        $code = $this->getResponseStatusCode();

        return !$code || ($code >= 200 && $code < 300);
    }
    
    /**
     * Check if response is a 3xx redirect
     * 
     * @return boolean
     */
    public function isRedirection()
    {
        $code = $this->getResponseStatusCode();

        return $code >= 300 && $code < 400;
    }
    
    /**
     * Check if response is a 4xx client error
     * 
     * @return boolean
     */
    public function isClientError()
    {
        $code = $this->getResponseStatusCode();

        return $code >= 400 && $code < 500;
    }
    
    /**
     * Check if response is a 5xx redirect
     * 
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getResponseStatusCode() >= 500;
    }   

    /**
     * Check if response is 4xx or 5xx error
     *
     * @return boolean
     */
    public function isError()
    {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * Returns the HTTP referer if it is on the current host
     *
     * @return string
     */
    public function getLocalReferer()
    {
        $request = $this->getRequest();
        $referer = $request->getHeaderLine('HTTP_REFERER');
        $host = $request->getHeaderLine('HTTP_HOST');

        return $referer && parse_url($referer, PHP_URL_HOST) === $host ? $referer : '';
    }

    /**
     * Output result
     *
     * @param mixed $data
     * @param string $format 
     * @return ResponseInterface $response
     */
    public function output($data, $format)
    {
        $response = $this->getResponse();
        $contentType = $this->getContentType($format);
        $response = $response->withHeader('Content-Type', $contentType);
        $content = is_scalar($data) ? $data : $this->encodeData($data, $format);

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * Encode data to send to client
     *
     * @param mixed $data
     * @param string $format
     * @return string
     */
    public function encodeData($data, $format)
    {
        switch ($format) {
            case 'json': return $this->encodeDataAsJson($data);                            
            case 'xml': return $this->encodeDataAsXml($data);
            case 'html':
                throw new \InvalidArgumentException("To encode HTML please use a view");                
            default: 
                throw new \InvalidArgumentException("Can not encode data for format '$format'");                
        }
    } 

    /**
     * Encode data as xml
     *
     * @param \SimpleXMLElement $data
     * @return string
     */
    protected function encodeDataAsXml(\SimpleXMLElement $data)
    {
        return $data->asXML();
    }

    /**
     * Encode data as json
     *
     * @param mixed
     * @return string
     */
    protected function encodeDataAsJson($data)
    {
        $data = json_encode($data);

        return $this->isJsonp() ? 
            $this->getRequest()->getQueryParams()['callback'] . '(' . $data . ')' : 
            $data;
    }

    /**
     * Check if we should respond with jsonp
     *
     * @return boolean
     */
    protected function isJsonp()
    {
        $request = $this->getRequest();

        return $request && !empty($request->getQueryParams()['callback']);
    }
    
    /**
     * Get status code of response
     *
     * @return int
     */
    protected function getResponseStatusCode()
    {
       $response = $this->getResponse();
       
       return $response ? $response->getStatusCode() : 0;
    }

    /**
     * Get valid content type by simple word description
     *
     * @param string $format
     * @return string
     */
    protected function getContentType($format)
    {
        return array_search($format, $this->contentFormats) ?: $format;
    }
}

