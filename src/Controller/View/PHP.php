<?php

namespace Jasny\Controller\View;

use Jasny\Controller\View;
use Jasny\View\PHP as PHPView;

/**
 * View using PHP
 */
trait PHP
{
    use View;
    
    /**
     * Get the template engine abstraction
     * 
     * @return PHPView
     */
    public function getViewer()
    {
        if (!isset($this->viewer)) {
            $this->viewer = $this->createPHPView(['path' => $this->getViewPath()]);
        }
        
        return $this->viewer;
    }
    
    /**
     * Create a twig view object.
     * @ignore
     * @codeCoverageIgnore
     * 
     * @return PHPView
     */
    protected function createPHPView($options)
    {
        return new PHPView($options);
    }
}
