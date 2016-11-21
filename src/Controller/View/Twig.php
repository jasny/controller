<?php

namespace Jasny\Controller\View;

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
     * Assert valid variable, function and filter name
     * 
     * @param string $name
     * @throws \InvalidArgumentException
     */
    protected function assertViewVariableName($name)
    {
        if (!is_string($name)) {
            $type = (is_object($name) ? get_class($name) . ' ' : '') . gettype($name);
            throw new \InvalidArgumentException("Expected name to be a string, not a $type");
        }

        if (!preg_match('/^[a-z]\w*$/i', $name)) {
            throw new \InvalidArgumentException("Invalid name '$name'");
        }
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
        $this->assertViewVariableName($name);

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
        $this->assertViewVariableName($name);
        
        if ($as === 'function') {
            $function = new \Twig_SimpleFunction($name, $function ?: $name);
            $this->getTwig()->addFunction($function);
        } elseif ($as === 'filter') {
            $filter = new \Twig_SimpleFilter($name, $function ?: $name);
            $this->getTwig()->addFilter($filter);
        } else {
            $not = is_string($as) ? "'$as'" : 'a ' . gettype($as);
            throw new \InvalidArgumentException("You should create either a 'function' or 'filter', not $not");
        }

        return $this;
    }

    /**
     * Get twig environment instance
     *
     * @return \Twig_Environment
     */
    public function createTwigEnvironment()
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

        $this->twig->addGlobal('current_url', $this->getRequest()->getUri());
        
        if (method_exists($this, 'flash')) {
            $this->twig->addGlobal('flash', $this->flash());
        }
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
