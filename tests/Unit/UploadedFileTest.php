<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Http\Request;
use Ds\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UploadedFileTest extends TestCase
{
    public function testRequestNormalizesSingleAndMultipleUploadedFiles(): void
    {
        $request = new Request(
            [],
            [],
            'POST',
            '/upload',
            'https',
            [
                'avatar' => [
                    'name' => 'avatar.png',
                    'type' => 'image/png',
                    'tmp_name' => '/tmp/not-an-http-upload',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 128,
                ],
                'documents' => [
                    'name' => ['empty.txt', 'report.pdf'],
                    'type' => ['text/plain', 'application/pdf'],
                    'tmp_name' => ['/tmp/no-file', '/tmp/not-an-http-upload'],
                    'error' => [UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK],
                    'size' => [0, 512],
                ],
            ]
        );

        self::assertInstanceOf(UploadedFile::class, $request->file('avatar'));
        self::assertSame('avatar.png', $request->file('avatar')->originalName());
        self::assertFalse($request->file('avatar')->isValid());
        self::assertCount(2, $request->files('documents'));
        self::assertSame(UPLOAD_ERR_NO_FILE, $request->files('documents')[0]->error());
        self::assertNull($request->file('missing'));
    }

    public function testInvalidUploadCannotBeStored(): void
    {
        $file = new UploadedFile([
            'name' => 'avatar.png',
            'tmp_name' => '/tmp/not-an-http-upload',
            'error' => UPLOAD_ERR_OK,
            'size' => 10,
        ]);

        $this->expectException(RuntimeException::class);
        $file->store(sys_get_temp_dir() . '/ds-invalid-upload-test');
    }
}
