<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Methods for a controller to read from the request
 */
trait Input
{
    /**
     * Get request, set for controller
     */
    abstract protected function getRequest(): ServerRequestInterface;
    
    
    /**
     * Get the request query parameters.
     * 
     * <code>
     *   // Get all parameters
     *   $params = $this->getQueryParams();
     * 
     *   // Get specific parameters, specifying defaults for 'bar' and 'zoo'
     *   [$foo, $bar, $zoo] = $this->getQueryParams(['foo', 'bar' => 10, 'zoo' => 'monkey']);
     * </code>
     *
     * @param array|null $list
     * @return array<string,mixed>
     */
    protected function getQueryParams(?array $list = null): array
    {
        return $list !== null
            ? $this->listQueryParams($list)
            : $this->getRequest()->getQueryParams();
    }
    
    /**
     * Apply list to query params
     * 
     * @param array $list
     * @return array<string,mixed>
     */
    private function listQueryParams(array $list): array
    {
        $result = [];
        $params = $this->getRequest()->getQueryParams();
        
        foreach ($list as $key => $value) {
            if (!is_string($key)) {
                $key = $value;
                $value = null;
            }
            
            $result[] = $params[$key] ?? $value;
        }
        
        return $result;
    }
    
    /**
     * Check if the request has a query parameter
     */
    protected function hasQueryParam(string $param): bool
    {
        return isset($this->getQueryParams()[$param]);
    }
    
    /**
     * Get a query parameter.
     * 
     * Optionally apply filtering to the value.
     * @link http://php.net/manual/en/filter.filters.php
     */
    protected function getQueryParam(
        string $param,
        mixed $default = null,
        ?int $filter = null,
        array|int $filterOptions = 0
    ): mixed {
        $params = $this->getQueryParams();
        $value = $params[$param] ?? $default;
        
        if (isset($filter) && isset($value)) {
            $value = filter_var($value, $filter, $filterOptions);
        }
        
        return $value;
    }

    
    /**
     * Get parsed body and uploaded files as input
     * 
     * @return array|object|null
     */
    protected function getInput(): array|null|object
    {
        $data = $this->getRequest()->getParsedBody();
        
        if (is_array($data)) {
            $files = $this->getRequest()->getUploadedFiles();
            $data = array_replace_recursive($data, $files);
        }
        
        return $data;
    }
}
