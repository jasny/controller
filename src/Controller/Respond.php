<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;
use Dflydev\ApacheMimeTypes\PhpRepository as ApacheMimeTypes;

/**
 * Methods for a controller to send a response
 */
trait Respond
{
    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    abstract protected function getResponse();

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    abstract protected function setResponse(ResponseInterface $response);

    /**
     * Returns the HTTP referer if it is on the current host
     *
     * @return string
     */
    abstract public function getLocalReferer();
    
    
    /**
     * Set a response header
     * 
     * @param string  $header
     * @param string  $value
     * @param boolean $overwrite
     */
    public function setResponseHeader($header, $value, $overwrite = true)
    {
        $fn = $overwrite ? 'withHeader' : 'withAddedHeader';
        $response = $this->getResponse()->$fn($value, $header);
        
        $this->setResponse($response);
    }
    
    /**
     * Set the headers with HTTP status code and content type.
     * @link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * 
     * Examples:
     * <code>
     *   $this->respondWith(200, 'json');
     *   $this->respondWith(200, 'application/json');
     *   $this->respondWith(204);
     *   $this->respondWith("204 Created");
     *   $this->respondWith('json');
     * </code>
     * 
     * @param int          $code            HTTP status code (may be omitted)
     * @param string|array $format          Mime or content format
     */
    public function respondWith($code, $format = null)
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

        $this->setResponse($response);
    }

    
    /**
     * Response with 200 OK
     *
     * @return ResponseInterface $response
     */
    public function ok()
    {
        $this->respondWith(200);
    }

    /**
     * Response with created 201 code, and optionaly redirect to created location
     *
     * @param string $location  Url of created resource
     */
    public function created($location = '')
    {
        $this->respondWith(201);

        if ($location) {
            $this->setResponseHeader('Location', $location);
        }
    }

    /**
     * Response with 204 No Content
     */
    public function noContent()
    {
        $this->respondWith(204);
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @param int $code    301 (Moved Permanently), 303 (See Other) or 307 (Temporary Redirect)
     */
    public function redirect($url, $code = 303)
    {
        $this->respondWith($code, 'text/html');
        $this->setResponseHeader('Location', $url);
        
        $urlHtml = htmlentities($url);
        $this->output('You are being redirected to <a href="' . $urlHtml . '">' . $urlHtml . '</a>');
    }

    /**
     * Redirect to previous page, or to home page
     *
     * @return ResponseInterface $response
     */
    public function back()
    {
        $this->redirect($this->getLocalReferer() ?: '/');
    }

    
    /**
     * Respond with 400 Bad Request
     *
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function badRequest($message, $code = 400)
    {
        $this->respondWith($code);
        $this->output($message);
    }

    /**
     * Respond with a 401 Unauthorized
     */
    public function requireAuth()
    {
        $this->respondWith(401);
    }

    /**
     * Alias of requireAuth
     * @deprecated
     */
    final public function requireLogin()
    {
        $this->requireAuth();
    }
    
    /**
     * Respond with 402 Payment Required
     *
     * @param string $message
     */
    public function paymentRequired($message = "Payment required")
    {
        $this->respondWith(402);
        $this->output($message);
    }

    /**
     * Respond with 403 Forbidden
     *
     * @param string $message
     */
    public function forbidden($message = "Forbidden")
    {
        $this->respondWith(403);
        $this->output($message);
    }

    /**
     * Respond with 404 Not Found
     *
     * @param string $message
     * @param int    $code     HTTP status code (404 or 405)
     */
    public function notFound($message = "Not found", $code = 404)
    {
        $this->respondWith($code);
        $this->output($message);
    }

    /**
     * Respond with 409 Conflict
     *
     * @param string $message
     */
    public function conflict($message)
    {
        $this->respondWith(409);
        $this->output($message);
    }

    /**
     * Respond with 429 Too Many Requests
     *
     * @param string $message
     */
    public function tooManyRequests($message = "Too many requests")
    {
        $this->respondWith(429);
        $this->output($message);
    }

    
    /**
     * Respond with a server error
     *
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function error($message = "An unexpected error occured", $code = 500)
    {
        $this->respondWith($code);
        $this->output($message);
    }
    
    
    /**
     * Get MIME type for extension
     *
     * @param string $format
     * @return string
     */
    protected function getContentType($format)
    {
        if (\Jasny\str_contains($format, '/')) { // Already MIME
            return $format;
        }
        
        $repository = new ApacheMimeTypes();
        $mime = $repository->findType('html');

        if (!isset($mime)) {
            throw new \UnexpectedValueException("Format $format doesn't correspond with a MIME type");
        }
        
        return $mime;
    }
    
    /**
     * Serialize data
     * 
     * @param mixed  $data
     * @param string $contentType
     * @return string
     */
    protected function serializeData($data, $contentType)
    {
        if ($contentType == 'json') {
            return (is_string($data) && (json_decode($data) !== null || !json_last_error()))
                ? $data : json_encode($data);
        }
        
        if (!is_scalar($data)) {
            throw new \Exception("Unable to serialize data to {$contentType}");
        }
        
        return $data;
    }

    /**
     * Output result
     *
     * @param mixed  $data
     * @param string $format  Output format as MIME or extension
     */
    public function output($data, $format = null)
    {
        if (!isset($format)) {
            $contentType = $this->getResponse()->getHeaderLine('Content-Type') ?: 'text/html';
        } else {
            $contentType = $this->getContentType($format);
            $this->setResponseHeader('Content-Type', $contentType);
        }
        
        $content = $this->serializeData($data, $contentType);

        $this->getResponse()->getBody()->write($content);
    }
}
