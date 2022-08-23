<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\UploadedFiles;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @covers \Jasny\Controller\Parameter\UploadedFiles
 */
class UploadedFilesTest extends TestCase
{
    protected UploadedFiles $parameter;

    public function setUp(): void
    {
        $this->parameter = new UploadedFiles();
    }

    public function test()
    {
        $files = [
            'document' => $this->createMock(UploadedFileInterface::class),
            'image' => $this->createMock(UploadedFileInterface::class),
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUploadedFiles')->willReturn($files);

        $value = $this->parameter->getValue($request, 'foo', 'array');

        $this->assertSame($files['document'], $value['document'] ?? null);
        $this->assertSame($files['image'], $value['image'] ?? null);
    }

}
