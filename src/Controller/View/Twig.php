<?php

namespace Jasny\Controller\View;

use Jasny\Controller\View;
use Jasny\ViewInterface;
use Jasny\View\Twig as TwigView;


/**
 * View using Twig
 */
trait Twig
{
    use View;
    
    /**
     * @var ViewInterface 
     */
    protected $viewer;
    
    
    /**
     * Get the template engine abstraction
     * 
     * @return TwigView
     */
    public function getViewer()
    {
        if (!isset($this->viewer)) {
            $this->viewer = new TwigView(['path' => $this->getViewPath()]);
            $this->viewer->addDefaultExtensions();
        }
        
        return $this->viewer;
    }
}
