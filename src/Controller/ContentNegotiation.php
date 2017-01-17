<?php

namespace Jasny\Controller;

/**
 * Controller methods to negotiate content
 */
trait ContentNegotiation
{
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    abstract public function getRequest();

    /**
     * Pick best content type
     *
     * @param array $priorities
     * @return string
     */
    public function negotiateContentType(array $priorities)
    {
        return $this->negotiate($priorities);
    }

    /**
     * Pick best language
     *
     * @param array $priorities
     * @return string
     */
    public function negotiateLanguage(array $priorities)
    {
        return $this->negotiate($priorities, 'language');
    }

    /**
     * Pick best encoding
     *
     * @param array $priorities
     * @return string
     */
    public function negotiateEncoding(array $priorities)
    {
        return $this->negotiate($priorities, 'encoding');
    }

    /**
     * Pick best charset
     *
     * @param array $priorities
     * @return string
     */
    public function negotiateCharset(array $priorities)
    {
        return $this->negotiate($priorities, 'charset');
    }

    /**
     * Generalize negotiation
     *
     * @param array $priorities
     * @param string $type       Negotiator type
     * @return string
     */
    protected function negotiate(array $priorities, $type = '')
    {
        $header = 'Accept';

        if ($type) {
            $header .= '-' . ucfirst($type);
        }

        $header = $this->getRequest()->getHeader($header);
        $header = join(', ', $header);

        $negotiator = $this->getNegotiator($type);
        $chosen = $negotiator->getBest($header, $priorities);

        return $chosen ? $chosen->getType() : '';
    }

    /**
     * Get negotiation library instance
     *
     * @param string $type  Negotiator type
     * @return Negotiation\AbstractNegotiator
     */
    protected function getNegotiator($type = '')
    {
        $class = $this->getNegotiatorName($type);

        return new $class();
    }

    /**
     * Get negotiator name
     *
     * @param string $type
     * @return string
     */
    protected function getNegotiatorName($type = '')
    {
        return 'Negotiation\\' . ucfirst($type) . 'Negotiator';
    }
}
