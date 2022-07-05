<?php
declare(strict_types=1);

namespace Jasny\Controller\Traits;

use Psr\Http\Message\ResponseInterface;
use Mimey\MimeTypes;

/**
 * Methods for a controller to send a response
 */
trait Output
{
    /**
     * Get response, set for controller
     */
    abstract protected function getResponse(): ResponseInterface;

    /**
     * Set response.
     */
    abstract protected function setResponse(ResponseInterface $response): void;

    /**
     * Get MIME type for extension
     */
    protected function getMime(string $format): string
    {
        // Check if it's already MIME
        if (str_contains($format, '/')) {
            return $format;
        }
        
        $mime = (new MimeTypes())->getMimeType($format);

        if (!isset($mime)) {
            throw new \UnexpectedValueException("Format '$format' doesn't correspond with a MIME type");
        }
        
        return $mime;
    }

    /**
     * Output data as json.
     *
     * @param mixed $data
     * @param int   $flags   Flags for json_encode
     * @return $this
     */
    protected function json(mixed $data, int $flags = 0): static
    {
        return $this->output(json_encode($data, $flags), 'application/json');
    }

    /**
     * Output result
     *
     * @param string      $content
     * @param string|null $format  Output format as MIME or extension
     * @return $this
     */
    protected function output(string $content, ?string $format = null): static
    {
        $response = $this->getResponse();

        if ($format !== null) {
            $contentType = $this->getMime($format);
            $response = $response->withHeader('Content-Type', $contentType);
        }

        $response->getBody()->write($content);
        $this->setResponse($response);

        return $this;
    }
}
