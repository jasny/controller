<?php

namespace Jasny\Controller\Traits;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Jasny\Controller\View\Twig as JasnyTwig;
use Slim\Views\Twig as SlimTwig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

trait Twig
{
    private JasnyTwig|SlimTwig $twig;

    abstract protected function getResponse(): ResponseInterface;

    /**
     * Output rendered Twig template
     *
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    protected function view(string $template, array $data = []): ResponseInterface
    {
        return $this->twig->render($this->getResponse(), $template, $data);
    }
}
