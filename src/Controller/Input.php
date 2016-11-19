<?php

namespace Jasny\Controller;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Methods for a controller to read from the request
 */
trait Input
{
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    abstract public function getRequest();
    
    
    /**
     * Get the request query parameters.
     * 
     * <code>
     *   // Get all parameters
     *   $params = $this->getQueryParams();
     * 
     *   // Get specific parameters, specifying defaults for 'bar' and 'zoo'
     *   list($foo, $bar, $zoo) = $this->getQueryParams(['foo', 'bar' => 10, 'zoo' => 'monkey']);
     * </code>
     * 
     * @param array $list
     * @return array
     */
    public function getQueryParams(array $list = null)
    {
        return isset($list)
            ? $this->listQueryParams($list)
            : (array)$this->getRequest()->getQueryParams();
    }
    
    /**
     * Apply list to query params
     * 
     * @param array $list
     * @return array
     */
    protected function listQueryParams(array $list)
    {
        $result = [];
        $params = $this->getRequest()->getQueryParams();
        
        foreach ($list as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }
            
            $result[] = isset($params[$key]) ? $params[$key] : $value;
        }
        
        return $result;
    }
    
    /**
     * Check if the request has a query parameter
     * 
     * @param array $param
     * @return boolean
     */
    public function hasQueryParam($param)
    {
        $params = $this->getQueryParams();
        
        return isset($params[$param]);
    }
    
    /**
     * Get a query parameter.
     * 
     * Optionally apply filtering to the value.
     * @link http://php.net/manual/en/filter.filters.php
     * 
     * @param array  $param
     * @param string $default
     * @param int    $filter
     * @param mixed  $filterOptions
     * @return mixed
     */
    public function getQueryParam($param, $default = null, $filter = null, $filterOptions = null)
    {
        $params = $this->getQueryParams();
        $value = isset($params[$param]) ? $params[$param] : $default;
        
        if (isset($filter) && isset($value)) {
            $value = filter_var($value, $filter, $filterOptions);
        }
        
        return $value;
    }

    
    /**
     * Get parsed body and uploaded files as input
     * 
     * @return array|mixed
     */
    public function getInput()
    {
        $data = $this->getRequest()->getParsedBody();
        
        if (is_array($data)) {
            $files = $this->getRequest()->getUploadedFiles();
            $data = array_replace_recursive($data, (array)$files);
        }
        
        return $data;
    }
}
