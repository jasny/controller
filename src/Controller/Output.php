<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;
use Dflydev\ApacheMimeTypes\PhpRepository as ApacheMimeTypes;

/**
 * Methods for a controller to send a response
 */
trait Output
{
    /**
     * @var string
     */
    protected $defaultFormat;
    
    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    abstract public function getResponse();

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    abstract public function setResponse(ResponseInterface $response);

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
        $response = $this->getResponse()->$fn($header, $value);
        
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
     * @param int|string   $status  HTTP status (may be omitted)
     * @param string|array $format  Mime or content format
     */
    public function respondWith($status, $format = null)
    {
        $response = $this->getResponse();

        // Shift arguments if $code is omitted
        if (isset($status) && !is_int($status) && (!is_string($status) || !preg_match('/^\d{3}\b/', $status))) {
            list($status, $format) = array_merge([null], func_get_args());
        }

        if (!empty($status)) {
            list($code, $phrase) = explode(' ', $status, 2) + [1 => null];
            $response = $response->withStatus((int)$code, $phrase);
        }
        
        if (!empty($format)) {
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
     * Response with created 201 code, and optionally the created location
     *
     * @param string $location  Url of created resource
     */
    public function created($location = null)
    {
        $this->respondWith(201);

        if (!empty($location)) {
            $this->setResponseHeader('Location', $location);
        }
    }

    /**
     * Response with 203 Accepted
     */
    public function accepted()
    {
        $this->respondWith(202);
    }

    /**
     * Response with 204 No Content
     * 
     * @param int $code  204 (No Content) or 205 (Reset Content)
     */
    public function noContent($code = 204)
    {
        $this->respondWith($code);
    }
    
    /**
     * Respond with a 206 Partial content with `Content-Range` header
     * 
     * @param int $rangeFrom  Beginning of the range in bytes
     * @param int $rangeTo    End of the range in bytes
     * @param int $totalSize  Total size in bytes
     */
    public function partialContent($rangeFrom, $rangeTo, $totalSize)
    {
        $this->respondWith(206);
        
        $this->setResponseHeader('Content-Range', "bytes {$rangeFrom}-{$rangeTo}/{$totalSize}");
        $this->setResponseHeader('Content-Length', $rangeTo - $rangeFrom);
    }

    
    /**
     * Redirect to url and output a short message with the link
     *
     * @param string $url
     * @param int    $code  301 (Moved Permanently), 302 (Found), 303 (See Other) or 307 (Temporary Redirect)
     */
    public function redirect($url, $code = 303)
    {
        $this->respondWith($code);
        $this->setResponseHeader('Location', $url);
        
        $urlHtml = htmlentities($url);
        $this->output('You are being redirected to <a href="' . $urlHtml . '">' . $urlHtml . '</a>', 'text/html');
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
     * Respond with 304 Not Modified
     */
    public function notModified()
    {
        $this->respondWith(304);
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
    public function forbidden($message = "Access denied")
    {
        $this->respondWith(403);
        $this->output($message);
    }

    /**
     * Respond with 404 Not Found
     *
     * @param string $message
     * @param int    $code     404 (Not Found), 405 (Method not allowed) or 410 (Gone)
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
        // Check if it's already MIME
        if (\Jasny\str_contains($format, '/')) {
            return $format;
        }
        
        $repository = new ApacheMimeTypes();
        $mime = $repository->findType($format);

        if (!isset($mime)) {
            throw new \UnexpectedValueException("Format '$format' doesn't correspond with a MIME type");
        }
        
        return $mime;
    }
    
    
    /**
     * If a non scalar value is passed without an format, use this format
     * 
     * @param string $format  Format by extention or MIME
     */
    public function byDefaultSerializeTo($format)
    {
        $this->defaultFormat = $format;
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
        if (is_string($data)) {
            return $data;
        }
        
        $repository = new ApacheMimeTypes();
        list($format) = $repository->findExtensions($contentType) + [null];
        
        $method = 'serializeDataTo' . $format;
        
        if (method_exists($this, $method)) {
            return $this->$method($data);
        }

        $type = (is_object($data) ? get_class($data) . ' ' : '') . gettype($data);
        throw new \UnexpectedValueException("Unable to serialize $type to '$contentType'");
    }
    
    /**
     * Serialize data to JSON
     * @internal made private because this will likely move to a library
     * 
     * @param mixed $data
     * @return string
     */
    private function serializeDataToJson($data)
    {
        return json_encode($data);
    }
    
    /**
     * Serialize data to XML.
     * @internal made private because this will likely move to a library
     * 
     * @param mixed $data
     * @return string
     */
    private function serializeDataToXml($data)
    {
        if ($data instanceof \SimpleXMLElement) {
            return $data->asXML();
        }
        
        if (($data instanceof \DOMNode && isset($data->ownerDocument)) || $data instanceof \DOMDocument) {
            $dom = $data instanceof \DOMDocument ? $data : $data->ownerDocument;
            return $dom->saveXML($data);
        }
        
        $type = (is_object($data) ? get_class($data) . ' ' : '') . gettype($data);
        throw new \UnexpectedValueException("Unable to serialize $type to XML");
    }

    /**
     * Set the content type for the output
     * 
     * @param string $format
     * @return string
     */
    protected function outputContentType($format)
    {
        if (!isset($format)) {
            $contentType = $this->getResponse()->getHeaderLine('Content-Type');
            
            if (empty($contentType)) {
                $format = $this->defaultFormat ?: 'text/html';
            }
        }
        
        if (empty($contentType)) {
            $contentType = $this->getContentType($format);
            $this->setResponseHeader('Content-Type', $contentType);
        }
        
        return $contentType;
    }
    
    /**
     * Output result
     *
     * @param mixed  $data
     * @param string $format  Output format as MIME or extension
     */
    public function output($data, $format = null)
    {
        $contentType = $this->outputContentType($format);

        try {
            $content = $this->serializeData($data, $contentType);
        } catch (\UnexpectedValueException $e) {
            if (!isset($format) && isset($this->defaultFormat) && $this->defaultFormat !== $contentType) {
                $this->output($data, $this->defaultFormat); // Try default format instead
                return;
            }
            
            throw $e;
        }

        $this->getResponse()->getBody()->write($content);
    }
}
