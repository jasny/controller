<?php
declare(strict_types=1);

namespace Jasny\Controller\Traits;

use Negotiation\{AbstractNegotiator, Negotiator, LanguageNegotiator, EncodingNegotiator, CharsetNegotiator};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller methods to negotiate content
 */
trait ContentNegotiation
{
    abstract protected function getRequest(): ServerRequestInterface;
    abstract protected function getResonse(): ResponseInterface;

    abstract protected function header(string $header, string|int|\Stringable $value, bool $add = false): static;

    /**
     * Pick the best content type
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateContentType(array $priorities): string
    {
        $contentType = $this->negotiate(new Negotiator(), 'Accept', $priorities);

        if ($contentType !== '') {
            $this->header('Content-Type', $contentType);
        }

        return $contentType;
    }

    /**
     * Pick the best language and set the `Content-Language` header
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateLanguage(array $priorities): string
    {
        $language = $this->negotiate(new LanguageNegotiator(), 'Accept-Language', $priorities);

        if ($language !== '') {
            $this->header('Content-Language', $language);
        }

        return $language;
    }

    /**
     * Pick the best encoding and set `Content-Encoding` header
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateEncoding(array $priorities): string
    {
        $encoding = $this->negotiate(new EncodingNegotiator(), 'Accept-Encoding', $priorities);

        if ($encoding !== '') {
            $this->header('Content-Encoding', $encoding);
        }

        return $encoding;
    }

    /**
     * Pick the best charset.
     * This method will modify the `Content-Type` header if it's set.
     *
     * @param string[] $priorities
     * @return string
     */
    protected function negotiateCharset(array $priorities): string
    {
        $charset = $this->negotiate(new CharsetNegotiator(), 'Accept-Charset', $priorities);

        $contentType = $this->getResonse()->getHeaderLine('Content-Type');
        if ($contentType !== '') {
            $contentType = preg_replace('/;\s*charset\s*=[^;]+/', '', $contentType)
                . "; charset=$charset";
            $this->header('Content-Type', $contentType);
        }

        return $charset;
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
