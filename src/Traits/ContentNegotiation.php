<?php
declare(strict_types=1);

namespace Jasny\Controller\Traits;

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
    protected function negotiateLanguage(array $priorities): string
    {
        return $this->negotiate(new LanguageNegotiator(), 'Accept-Language', $priorities);
    }

    /**
     * Pick best encoding
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateEncoding(array $priorities): string
    {
        return $this->negotiate(new EncodingNegotiator(), 'Accept-Encoding', $priorities);
    }

    /**
     * Pick best charset
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateCharset(array $priorities): string
    {
        return $this->negotiate(new CharsetNegotiator(), 'Accept-Charset', $priorities);
    }

    /**
     * Generalize negotiation.
     */
    private function negotiate(AbstractNegotiator $negotiator, string $header, array $priorities): string
    {
        $value = $this->getRequest()->getHeaderLine($header);
        $chosen = $negotiator->getBest($value, $priorities);

        return $chosen ? $chosen->getType() : '';
    }
}
