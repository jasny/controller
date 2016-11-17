<?php

namespace Jasny\Controller;

use Jasny\Controller\Session\Flash;

/**
 * Use session in the controller
 */
trait Session
{
    /**
     * Session
     * @var array|\ArrayObject
     */
    protected $session;

    /**
     * Flash message
     * @var Flash
     */
    protected $flash;
    
    
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    abstract protected function getRequest();
    
    
    /**
     * Link the session to the session property in the controller
     */
    protected function useSession()
    {
        $this->session = $this->getRequest()->getAttribute('session');
        
        if (!isset($this->session)) {
            $this->session =& $_SESSION;
        }
    }
    
    
    /**
     * Get an/or set the flash message.
     * 
     * @param mixed $type     flash type, eg. 'error', 'notice' or 'success'
     * @param mixed $message  flash message
     * @return Flash
     */
    public function flash($type = null, $message = null)
    {
        if (!isset($this->flash)) {
            $this->flash = new Flash($this->session);
        }
        
        if ($type) {
            $this->flash->set($type, $message);
        }
        
        return $this->flash;
    }
}
