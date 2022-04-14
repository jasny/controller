<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Psr\Http\Message\ResponseInterface;
use Dflydev\ApacheMimeTypes\PhpRepository as ApacheMimeTypes;

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
        
        $repository = new ApacheMimeTypes();
        $mime = $repository->findType($format);

        if (!isset($mime)) {
            throw new \UnexpectedValueException("Format '$format' doesn't correspond with a MIME type");
        }
        
        return $mime;
    }


    /**
     * Serialize data.
     */
    protected function serializeData(mixed $data, string $contentType): string
    {
        if (is_string($data)) {
            return $data;
        }
        
        $repository = new ApacheMimeTypes();
        $format = $repository->findExtensions($contentType)[0] ?? null;
        $method = $format !== null ? 'serializeDataTo' . $format : null;
        
        if ($method !== null && method_exists($this, $method)) {
            return $this->$method($data);
        }

        $type = (is_object($data) ? get_class($data) . ' ' : '') . gettype($data);
        throw new \UnexpectedValueException("Unable to serialize $type to '$contentType'");
    }
    
    /**
     * Serialize data to JSON.
     */
    protected function serializeDataToJson(mixed $data): string
    {
        return json_encode($data);
    }
    
    /**
     * Serialize data to XML.
     *
     * @param mixed $data
     * @return string
     * @noinspection PhpComposerExtensionStubsInspection
     */
    protected function serializeDataToXml(mixed $data): string
    {
        if ($data instanceof \SimpleXMLElement) {
            return $data->asXML();
        }
        
        if (($data instanceof \DOMNode && isset($data->ownerDocument)) || $data instanceof \DOMDocument) {
            $dom = $data instanceof \DOMDocument ? $data : $data->ownerDocument;
            return $dom->saveXML($data);
        }
        
        $type = (is_object($data) ? get_class($data) . ' ' : '') . gettype($data);
        throw new \UnexpectedValueException("Unable to serialize $type to XML");
    }

    /**
     * Output result
     *
     * @param mixed  $data
     * @param string $format  Output format as MIME or extension
     * @return $this
     */
    protected function output(mixed $data, string $format = 'text/html'): static
    {
        $contentType = $this->getMime($format);
        $content = $this->serializeData($data, $contentType);

        $response = $this->getResponse()->withHeader('Content-Type', $contentType);
        $response->getBody()->write($content);

        $this->setResponse($response);

        return $this;
    }
}
