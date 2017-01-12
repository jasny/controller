<?php

namespace Jasny\Controller;

use Jasny\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * View using a template engine
 */
trait View
{
    /**
     * Get server request
     * 
     * @return ServerRequestInterface
     */
    abstract public function getRequest();

    /**
     * Get response. set for controller
     *
     * @param ResponseInterface $response
     */
    abstract public function setResponse(ResponseInterface $response);
    
    /**
     * Get the template engine abstraction
     * 
     * @return ViewInterface
     */
    abstract public function getViewer();
    
    
    /**
     * Get path of the view files
     *
     * @return string
     */
    protected function getViewPath()
    {
        return getcwd();
    }
    
    /**
     * View rendered template
     *
     * @param string $name    Template name
     * @param array  $context Template context
     */
    public function view($name, array $context = [])
    {
        $context += ['current_url', $this->getRequest()->getUri()];
        
        if (method_exists($this, 'flash')) {
            $context += ['flash' => $this->flash()];
        }
        
        $response = $this->getViewer()->view($this->getResponse(), $name, $context);
        
        $this->setResponse($response);
    }
}
