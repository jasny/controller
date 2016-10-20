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
     * Encode data to send to client
     *
     * @param mixed $data
     * @return string
     */
    public function encodeData($data)
    {
        $response = $this->getResponse();
        $invalid = !$response || 
            !($type = $response->getHeaderLine('Content-Type')) ||
            !in_array($type, ['application/json', 'text/xml', 'application/xml'], true);

        if ($invalid) {
            throw new \RuntimeException("Valid content type is not set in response for encoding data");            
        }

        switch ($type) {
            case 'application/json': 
                return $this->encodeDataAsJson($data);                            
            case 'text/xml':
            case 'application/xml':
                return $this->encodeDataAsXml($data);
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
}

