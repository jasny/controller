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
     * @var ViewInterface 
     */
    protected $viewer;
    
    /**
     * Get server request
     * 
     * @return ServerRequestInterface
     */
    abstract public function getRequest();
    
    /**
     * Get server request
     * 
     * @return ResponseInterface
     */
    abstract public function getResponse();

    /**
     * Get response. set for controller
     *
     * @param ResponseInterface $response
     * @return void
     */
    abstract public function setResponse(ResponseInterface $response);
    
    
    /**
     * Get the template engine abstraction
     * 
     * @return ViewInterface
     */
    public function getViewer()
    {
        if (!isset($this->viewer)) {
            throw new \LogicException("Viewer has not been set");
        }
        
        return $this->viewer;
    }
    
    /**
     * Get the template engine abstraction
     * 
     * @param ViewInterface $viewer
     */
    public function setViewer(ViewInterface $viewer)
    {
        $this->viewer = $viewer;
    }
    
    
    /**
     * Get path of the view files
     *
     * @return string
     */
    public function getViewPath()
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
        $context += ['current_url' => $this->getRequest()->getUri()];
        
        if (method_exists($this, 'flash')) {
            $context += ['flash' => $this->flash()];
        }
        
        $response = $this->getViewer()->render($this->getResponse(), $name, $context);
        
        $this->setResponse($response);
    }
}
