<?php

namespace Jasny\Controller\View;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * View using Twig
 */
trait Twig
{
    /**
     * Twig environment
     * @var \Twig_Environment
     */
    protected $twig = null;

    /**
     * Get server request
     * @return ServerRequestInterface
     */
    abstract public function getRequest();

    /**
     * Get server response
     * @return ResponseInterface
     */
    abstract public function getResponse();

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
     * @param string $function  Variable name
     * @param mixed  $callback
     * @param string $as        'function' or 'filter'
     * @return $this
     */
    public function setViewFunction($name, $function = null, $as = 'function')
    {
        if ($as === 'function') {
            $this->getTwig()->addFunction($this->createTwigFunction($name, $function));
        } elseif ($as === 'filter') {
            $this->getTwig()->addFilter($this->createTwigFilter($name, $function));
        } else {
            throw new \InvalidArgumentException("You should create either function or filter, not '$as'");
        }
        
        return $this;
    }

    /**
     * Add extension to view
     *
     * @param object $extension
     * @return $this
     */
    public function setViewExtension($extension)
    {
        $this->getTwig()->addExtension($extension);

        return $this;
    }

    /**
     * View rendered template
     *
     * @param string $name   Template name
     * @param array $context Template context
     * @return ResponseInterface
     */
    public function view($name, array $context = [])
    {
        if (!pathinfo($name, PATHINFO_EXTENSION)) $name .= '.html.twig';

        $twig = $this->getTwig();
        $tmpl = $twig->loadTemplate($name);

        $response = $this->getResponse();
        $response = $response->withHeader('Content-Type', 'text/html; charset=' . $twig->getCharset());
        $response->getBody()->write($tmpl->render($context));            

        return $response;
    }

    /**
     * Get twig environment
     *
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        if ($this->twig) return $this->twig;

        $loader = $this->getTwigLoader();
        $this->twig = $this->getTwigEnvironment($loader);

        $extensions = ['DateExtension', 'PcreExtension', 'TextExtension', 'ArrayExtension'];
        foreach ($extensions as $name) {
            $class = "Jasny\Twig\\$name";

            if (class_exists($class)) $this->setViewExtension(new $class());
        }
        
        $uri = $this->getRequest()->getUri()->getPath();
        $this->setViewVariable('current_url', $uri);

        return $this->twig;
    }

    /**
     * Get twig loasder for current working directory
     *
     * @return \Twig_Loader_Filesystem
     */
    public function getTwigLoader()
    {
        return new \Twig_Loader_Filesystem(getcwd());
    }

    /**
     * Get twig environment instance
     *
     * @param \Twig_Loader_Filesystem $loader
     * @return \Twig_Environment
     */
    public function getTwigEnvironment(\Twig_Loader_Filesystem $loader)
    {
        return new \Twig_Environment($loader);
    }

    /**
     * Create twig function
     *
     * @param string $name          Name of function in view
     * @param callable $function 
     * @return \Twig_SimpleFunction
     */
    public function createTwigFunction($name, $function)
    {
        if (!$name) throw new \InvalidArgumentException("Function name should not be empty");

        return new \Twig_SimpleFunction($name, $function ?: $name);
    }

    /**
     * Create twig filter
     *
     * @param string $name          Name of filter in view
     * @param callable $function 
     * @return \Twig_SimpleFilter
     */
    public function createTwigFilter($name, $function)
    {
        if (!$name) throw new \InvalidArgumentException("Filter name should not be empty");

        return new \Twig_SimpleFilter($name, $function ?: $name);
    }
}
