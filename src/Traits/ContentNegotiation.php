<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Negotiation\{AbstractNegotiator, Negotiator, LanguageNegotiator, EncodingNegotiator, CharsetNegotiator};
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller methods to negotiate content
 */
trait ContentNegotiation
{
    abstract protected function getRequest(): ServerRequestInterface;

    abstract protected function header(string $header, string|int|\Stringable $value, bool $overwrite = true): static;

    /**
     * Pick best content type
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateContentType(array $priorities): string
    {
        return $this->negotiate(new Negotiator(), 'Accept', $priorities);
    }

    /**
     * Pick best language
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateLanguage(array $priorities)
    {
        return $this->negotiate(new $priorities, 'language');
    }

    /**
     * Pick best encoding
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateEncoding(array $priorities)
    {
        return $this->negotiate($priorities, 'encoding');
    }

    /**
     * Pick best charset
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateCharset(array $priorities)
    {
        return $this->negotiate($priorities, 'charset');
    }

    /**
     * Generalize negotiation.
     */
    private function negotiate(AbstractNegotiator $negotiator, string $header, array $priorities)
    {
        $value = join(', ', $this->getRequest()->getHeader($header));
        $chosen = $negotiator->getBest($value, $priorities);

        return $chosen ? $chosen->getType() : '';
    }
}
