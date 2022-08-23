<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\UploadedFile;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @covers \Jasny\Controller\Parameter\UploadedFile
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class UploadedFileTest extends TestCase
{
    public function provider()
    {
        return [
            'with name' => ['foo', 'foo'],
            'without name' => [null, 'bar'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function test(?string $name, string $expected)
    {
        $files = [
            'foo' => $this->createMock(UploadedFileInterface::class),
            'bar' => $this->createMock(UploadedFileInterface::class),
        ];

        $parameter = new UploadedFile($name);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUploadedFiles')
            ->willReturn($files);

        $value = $parameter->getValue($request, 'bar', UploadedFileInterface::class);
        $this->assertSame($files[$expected], $value);
    }

    public function testMissingOptional()
    {
        $parameter = new UploadedFile();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUploadedFiles')
            ->willReturn([]);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new UploadedFile();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUploadedFiles')
            ->willReturn([]);

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required uploaded file 'foo'");

        $parameter->getValue($request, 'foo', null, true);
    }
}
