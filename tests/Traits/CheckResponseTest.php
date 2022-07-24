<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \Jasny\Controller\Traits\CheckResponse
 */
class CheckResponseTest extends TestCase
{
    public function testGetResponseHeader()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderLine')->with('foo')->willReturn('bar');

        $controller = new class ($this, $response) extends Controller {
            public function __construct(public TestCase $test, protected ResponseInterface $response) {}

            protected function getResponse(): ResponseInterface {
                return $this->response;
            }

            public function testGetResponseHeader(string $name) {
                return $this->getResponseHeader($name);
            }
        };

        $this->assertEquals('bar', $controller->testGetResponseHeader('foo'));
    }

    /**
     * Provide data for testing status methods.
     */
    public function responseStatusProvider()
    {
        return [
            [null, 'successful'],
            [100, 'informational'],
            [199, 'informational'],
            [200, 'successful'],
            [201, 'successful'],
            [299, 'successful'],
            [300, 'redirect'],
            [304, 'redirect'],
            [399, 'redirect'],
            [400, 'client error'],
            [403, 'client error'],
            [499, 'client error'],
            [500, 'server error'],
            [503, 'server error'],
            [999, 'unkown']
        ];
    }

    /**
     * Test functions that check response status code
     * @dataProvider responseStatusProvider
     */
    public function testResponseStatus(?int $code, string $type)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($code);

        $controller = new class ($this, $response) extends Controller {
            public function __construct(public TestCase $test, protected ResponseInterface $response) {}

            protected function getResponse(): ResponseInterface {
                return $this->response;
            }

            public function assertResponseStatus(string $type) {
                $this->test->assertSame($type === 'informational', $this->isInformational(), 'isInformational');
                $this->test->assertSame($type === 'successful', $this->isSuccessful(), 'isSuccessful');
                $this->test->assertSame($type === 'redirect', $this->isRedirection(), 'isRedirection');
                $this->test->assertSame($type === 'client error', $this->isClientError(), 'isClientError');
                $this->test->assertSame($type === 'server error', $this->isServerError(), 'isServerError');
                $this->test->assertSame(in_array($type, ['client error', 'server error']), $this->isError(), 'isError');
            }
        };

        $controller->assertResponseStatus($type);
    }
}
