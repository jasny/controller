<?php
declare(strict_types=1);

namespace Jasny\Controller\Traits;

use Psr\Http\Message\ResponseInterface;

/**
 * Set HTTP response header.
 */
trait Header
{
    abstract protected function getResponse(): ResponseInterface;

    abstract protected function setResponse(ResponseInterface $response): void;

    abstract protected function getLocalReferer(): ?string;

    abstract protected function output(string $content, ?string $format = null): static;


    /**
     * Set a response header.
     *
     * @return $this
     */
    protected function header(string $header, string|int|\Stringable $value, bool $add = false): static
    {
        $response = $add
            ? $this->getResponse()->withAddedHeader($header, (string)$value)
            : $this->getResponse()->withHeader($header, (string)$value);
        $this->setResponse($response);

        return $this;
    }

    /**
     * Set the HTTP status code.
     * @link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *
     * Examples:
     * <code>
     *   $this->status(200);
     *   $this->status("200 Ok");
     * </code>
     *
     * @param int|string $status
     * @return $this
     */
    protected function status(int|string $status): static
    {
        if (is_string($status)) {
            [$status, $phrase] = explode(' ', $status, 2) + [1 => ''];
        } else {
            $phrase = '';
        }

        $response = $this->getResponse()->withStatus((int)$status, $phrase);
        $this->setResponse($response);

        return $this;
    }


    /**
     * Response with 200 Ok
     *
     * @return $this
     */
    protected function ok(): static
    {
        return $this->status(200);
    }

    /**
     * Response with created 201 code, and optionally the created location
     *
     * @param string|null $location  Url of created resource
     * @return $this
     */
    protected function created(?string $location = null): static
    {
        $this->status(201);

        if (!empty($location)) {
            $this->header('Location', $location);
        }

        return $this;
    }

    /**
     * Response with 202 Accepted
     */
    protected function accepted(): static
    {
        return $this->status(202);
    }

    /**
     * Response with 204 No Content
     *
     * @param int $status  204 (No Content) or 205 (Reset Content)
     */
    protected function noContent(int $status = 204): static
    {
        if ($status !== 204 && $status !== 205) {
            throw new \DomainException("Invalid status code $status for no content response");
        }

        return $this->status($status);
    }

    /**
     * Respond with a 206 Partial content with `Content-Range` header
     *
     * @param int $rangeFrom  Beginning of the range in bytes
     * @param int $rangeTo    End of the range in bytes
     * @param int $totalSize  Total size in bytes
     * @return $this
     */
    protected function partialContent(int $rangeFrom, int $rangeTo, int $totalSize): static
    {
        return $this
            ->status(206)
            ->header('Content-Range', "bytes {$rangeFrom}-{$rangeTo}/{$totalSize}")
            ->header('Content-Length', $rangeTo - $rangeFrom);
    }


    /**
     * Redirect to url and output a short message with the link
     *
     * @param string     $url
     * @param int|string $status  301 (Moved Permanently), 302 (Found), 303 (See Other) or 307 (Temporary Redirect)
     */
    protected function redirect(string $url, int|string $status = 303): static
    {
        if ($status < 300 || $status >= 400) {
            throw new \DomainException("Invalid status code $status for redirect");
        }

        $urlHtml = htmlentities($url);

        return $this
            ->status($status)
            ->header('Location', $url)
            ->output('You are being redirected to <a href="' . $urlHtml . '">' . $urlHtml . '</a>', 'text/html');
    }

    /**
     * Redirect to previous page or to home page.
     *
     * @return $this
     */
    protected function back(): static
    {
        return $this->redirect($this->getLocalReferer() ?: '/');
    }

    /**
     * Respond with 304 Not Modified.
     *
     * @return $this
     */
    protected function notModified(): static
    {
        return $this->status(304);
    }


    /**
     * Respond with 400 Bad Request
     *
     * @param int $status  HTTP status code
     * @return $this
     */
    protected function badRequest(int $status = 400): static
    {
        if ($status < 400 || $status >= 500) {
            throw new \DomainException("Invalid status code $status for bad request response");
        }

        return $this->status($status);
    }

    /**
     * Respond with a 401 Unauthorized
     *
     * @return $this
     */
    protected function unauthorized(): static
    {
        return $this->status(401);
    }

    /**
     * Respond with 402 Payment Required
     *
     * @return $this
     */
    protected function paymentRequired(): static
    {
        return $this->status(402);
    }

    /**
     * Respond with 403 Forbidden
     *
     * @return $this
     */
    protected function forbidden(): static
    {
        return $this->status(403);
    }

    /**
     * Respond with 404 Not Found, 405 Method not allowed or 410 Gone
     *
     * @param int $status  404 (Not Found), 405 (Method not allowed) or 410 (Gone)
     * @return $this
     */
    protected function notFound(int $status = 404): static
    {
        if ($status !== 404 && $status !== 405 && $status !== 406) {
            throw new \DomainException("Invalid status code $status for no content response");
        }

        return $this->status($status);
    }

    /**
     * Respond with 409 Conflict
     *
     * @return $this
     */
    protected function conflict(): static
    {
        return $this->status(409);
    }

    /**
     * Respond with 429 Too Many Requests
     *
     * @return $this
     */
    protected function tooManyRequests(): static
    {
        return $this->status(429);
    }


    /**
     * Respond with a server error
     *
     * @param int $status  HTTP status code
     * @return $this
     */
    protected function error(int $status = 500): static
    {
        if ($status < 500 || $status >= 600) {
            throw new \DomainException("Invalid status code $status for server error response");
        }

        return $this->status($status);
    }

}
