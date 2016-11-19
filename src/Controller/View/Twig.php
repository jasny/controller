<?php

namespace Jasny\Controller\View;

use Jasny\Controller\Session\Flash;
use Psr\Http\Message\ServerRequestInterface;

/**
 * View using Twig
 */
trait Twig
{
    /**
     * Twig environment
     * @var \Twig_Environment
     */
    protected $twig;

    
    /**
     * Get server request
     * @return ServerRequestInterface
     */
    abstract public function getRequest();

    /**
     * Output result
     *
     * @param mixed  $data
     * @param string $format  Output format as MIME or extension
     */
    abstract public function output($data, $format = null);

    
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
     * Add a global variable to the view.
     * 
     * @param string $name   Variable name
     * @param mixed  $value
     * @return $this
     */
    public function setViewVariable($name, $value)
    {
        if (!$name) throw new \InvalidArgumentException("Name should not be empty");        

        $this->getTwig()->addGlobal($name, $value);

        return $this;
    }
    
    /**
     * Expose a function to the view.
     * 
     * @param string      $name      Function name
     * @param string|null $function
     * @param string      $as        'function' or 'filter'
     * @return $this
     */
    public function setViewFunction($name, $function = null, $as = 'function')
    {
        if ($as === 'function') {
            $function = new \Twig_SimpleFunction($name, $function ?: $name);
            $this->getTwig()->addFunction($function);
        } elseif ($as === 'filter') {
            $filter = \Twig_SimpleFilter($name, $function ?: $name);
            $this->getTwig()->addFilter($filter);
        } else {
            throw new \InvalidArgumentException("You should create either function or filter, not '$as'");
        }
        
        return $this;
    }

    /**
     * Get twig environment instance
     *
     * @return \Twig_Environment
     */
    protected function createTwigEnvironment()
    {
        $path = $this->getViewPath();
        $loader = new \Twig_Loader_Filesystem($path);
        
        return new \Twig_Environment($loader);
    }

    /**
     * Initialize the Twig environment
     */
    protected function initTwig()
    {
        $this->twig = $this->createTwigEnvironment();

        $extensions = ['DateExtension', 'PcreExtension', 'TextExtension', 'ArrayExtension'];
        foreach ($extensions as $name) {
            $class = "Jasny\Twig\\$name";
            
            if (class_exists($class)) {
                $this->twig->addExtension(new $class());
            }
        }
        
        $uri = $this->getRequest()->getUri();
        
        $this->setViewVariable('current_url', $uri);
        $this->setViewVariable('flash', new Flash());
    }

    /**
     * Get Twig environment
     *
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        if (!isset($this->twig)) {
            $this->initTwig();
        }
        
        return $this->twig;
    }
    

    /**
     * View rendered template
     *
     * @param string $name    Template name
     * @param array  $context Template context
     */
    public function view($name, array $context = [])
    {
        if (!pathinfo($name, PATHINFO_EXTENSION)) {
            $name .= '.html.twig';
        }

        $twig = $this->getTwig();
        $tmpl = $twig->loadTemplate($name);

        $this->output($tmpl->render($context), 'text/html; charset=' . $twig->getCharset());
    }
}
