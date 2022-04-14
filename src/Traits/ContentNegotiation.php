<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Negotiation\AbstractNegotiator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller methods to negotiate content
 */
trait ContentNegotiation
{
    /**
     * Get request, set for controller
     */
    abstract protected function getRequest(): ServerRequestInterface;

    /**
     * Pick best content type
     *
     * @param array $priorities
     * @return string
     */
    protected function negotiateContentType(array $priorities)
    {
        return $this->negotiate($priorities);
    }

    /**
     * Pick best language
     *
     * @param array $priorities
     * @return string
     */
    protected function negotiateLanguage(array $priorities)
    {
        return $this->negotiate($priorities, 'language');
    }

    /**
     * Pick best encoding
     *
     * @param array $priorities
     * @return string
     */
    protected function negotiateEncoding(array $priorities)
    {
        return $this->negotiate($priorities, 'encoding');
    }

    /**
     * Pick best charset
     *
     * @param array $priorities
     * @return string
     */
    protected function negotiateCharset(array $priorities)
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
     */
    protected function getNegotiator(string $type = ''): AbstractNegotiator
    {
        $class = 'Negotiation\\' . ucfirst($type) . 'Negotiator';

        return new $class();
    }
}
