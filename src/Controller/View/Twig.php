<?php

namespace Jasny\Controller\View;

use Jasny\Controller\View;
use Jasny\View\Twig as TwigView;

/**
 * View using Twig
 */
trait Twig
{
    use View;
    
    /**
     * Get the template engine abstraction
     * 
     * @return TwigView
     */
    public function getViewer()
    {
        if (!isset($this->viewer)) {
            $this->viewer = $this->createTwigView(['path' => $this->getViewPath()]);
            $this->viewer->addDefaultExtensions();
        }
        
        return $this->viewer;
    }
    
    /**
     * Create a twig view object.
     * @ignore
     * @codeCoverageIgnore
     * 
     * @return TwigView
     */
    protected function createTwigView($options)
    {
        return new TwigView($options);
    }
}
