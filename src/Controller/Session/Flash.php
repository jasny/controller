<?php

namespace Jasny\Controller\Session;

/**
 * Class for the flash message
 */
class Flash
{
    /**
     * @var array|null
     */
    protected $data;
    
    /**
     * @var array|\ArrayObject
     */
    protected $session;

    /**
     * Session key for flash
     * @var string
     */
    protected $key = 'flash';
    
    
    /**
     * Class constructor
     * 
     * @param array|\ArrayObject $session
     */
    public function __construct(&$session)
    {
        $this->session =& $session;
    }
    
    /**
     * Check if the flash is set.
     * 
     * @return boolean
     */
    public function isIssued()
    {
        return isset($this->session[$this->key]);
    }
    
    /**
     * Set the flash.
     * 
     * @param string $type     flash type, eg. 'error', 'notice' or 'success'
     * @param mixed  $message  flash message
     */
    public function set($type, $message)
    {
        $this->session[$this->key] = compact('type', 'message');
    }
    
    /**
     * Get the flash.
     * 
     * @return object
     */
    public function get()
    {
        if (!isset($this->data) && isset($this->session[$this->key])) {
            $this->data = $this->session[$this->key];
            unset($this->session[$this->key]);
        }
        
        return $this->data ? (object)$this->data : null;
    }
    
    /**
     * Reissue the flash.
     */
    public function reissue()
    {
        if (!isset($this->data) && isset($this->session[$this->key])) {
            $this->data = $this->session[$this->key];
        } else {
            $this->session[$this->key] = $this->data;
        }
    }
    
    /**
     * Clear the flash.
     */
    public function clear()
    {
        $this->data = null;
        unset($this->session[$this->key]);
    }     
    
    /**
     * Get the flash type
     * 
     * @return string
     */
    public function getType()
    {
        $data = $this->get();
        return isset($data) ? $data->type : null;
    }
    
    /**
     * Get the flash message
     * 
     * @return string
     */
    public function getMessage()
    {
        $data = $this->get();
        return isset($data) ? $data->message : null;
    }
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getMessage();
    }
}
