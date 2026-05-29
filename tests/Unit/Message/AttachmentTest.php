<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Message;

use MailChannels\Exception\ValidationException;
use MailChannels\Message\Attachment;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    public function testFromBytesEncodesBase64AndGuessesContentType(): void
    {
        $att = Attachment::fromBytes('hello world', 'note.txt');

        $this->assertSame(base64_encode('hello world'), $att->content);
        $this->assertSame('note.txt', $att->filename);
        $this->assertSame('text/plain', $att->type);
        $this->assertSame(Attachment::DISPOSITION_ATTACHMENT, $att->disposition);
        $this->assertNull($att->contentId);
    }

    public function testFromBytesAcceptsExplicitContentTypeAndContentId(): void
    {
        $att = Attachment::fromBytes(
            data: 'binary',
            filename: 'logo.png',
            contentType: 'image/custom',
            contentId: 'cid-1',
        );

        $this->assertSame('image/custom', $att->type);
        $this->assertSame('cid-1', $att->contentId);
    }

    public function testFromFileReadsFileContents(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mc-attachment-');
        $this->assertNotFalse($path);
        $renamed = $path . '.pdf';
        rename($path, $renamed);
        file_put_contents($renamed, 'pdf-bytes');

        try {
            $att = Attachment::fromFile($renamed);
            $this->assertSame(base64_encode('pdf-bytes'), $att->content);
            $this->assertSame(basename($renamed), $att->filename);
            $this->assertSame('application/pdf', $att->type);
        } finally {
            @unlink($renamed);
        }
    }

    public function testFromFileUsesGivenFilenameOverridingBasename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mc-attachment-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'data');

        try {
            $att = Attachment::fromFile($path, filename: 'renamed.csv');
            $this->assertSame('renamed.csv', $att->filename);
            $this->assertSame('text/csv', $att->type);
        } finally {
            @unlink($path);
        }
    }

    public function testFromFileWithMissingPathRaises(): void
    {
        $this->expectException(ValidationException::class);
        Attachment::fromFile('/tmp/does-not-exist-' . uniqid() . '.pdf');
    }

    public function testInlineFileSetsDispositionAndContentId(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mc-attachment-');
        $this->assertNotFalse($path);
        $renamed = $path . '.png';
        rename($path, $renamed);
        file_put_contents($renamed, 'png-bytes');

        try {
            $att = Attachment::inlineFile($renamed, contentId: 'inline-1');
            $this->assertSame('inline-1', $att->contentId);
            $this->assertSame(Attachment::DISPOSITION_INLINE, $att->disposition);
            $this->assertSame('image/png', $att->type);
        } finally {
            @unlink($renamed);
        }
    }

    public function testToArrayIncludesAllOptionalFieldsWhenSet(): void
    {
        $att = new Attachment(
            content: 'AAA',
            filename: 'x.txt',
            type: 'text/plain',
            disposition: Attachment::DISPOSITION_INLINE,
            contentId: 'cid-42',
        );

        $this->assertSame(
            [
                'content' => 'AAA',
                'filename' => 'x.txt',
                'type' => 'text/plain',
                'disposition' => 'inline',
                'content_id' => 'cid-42',
            ],
            $att->toArray(),
        );
    }

    public function testToArrayOmitsOptionalFieldsThatAreNull(): void
    {
        $att = new Attachment(content: 'AAA', filename: 'x.txt');

        $this->assertSame(['content' => 'AAA', 'filename' => 'x.txt'], $att->toArray());
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $att = Attachment::fromBytes('hi', 'msg.txt');

        $this->assertSame($att->toArray(), $att->jsonSerialize());
    }

    /** @return array<string, array{string, string}> */
    public static function contentTypeProvider(): array
    {
        return [
            'pdf' => ['report.pdf', 'application/pdf'],
            'csv' => ['data.csv', 'text/csv'],
            'html' => ['index.html', 'text/html'],
            'json' => ['payload.json', 'application/json'],
            'png' => ['logo.png', 'image/png'],
            'jpg' => ['photo.jpg', 'image/jpeg'],
            'jpeg' => ['photo.jpeg', 'image/jpeg'],
            'gif' => ['anim.gif', 'image/gif'],
            'svg' => ['icon.svg', 'image/svg+xml'],
            'docx' => [
                'doc.docx',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'xlsx' => [
                'sheet.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'ics' => ['cal.ics', 'text/calendar'],
            'eml' => ['mail.eml', 'message/rfc822'],
            'unknown extension defaults to octet-stream' => ['file.xyz', 'application/octet-stream'],
            'no extension defaults to octet-stream' => ['noext', 'application/octet-stream'],
            'uppercase extension still matches' => ['Report.PDF', 'application/pdf'],
        ];
    }

    /** @dataProvider contentTypeProvider */
    public function testFromBytesGuessesContentTypeFromExtension(
        string $filename,
        string $expectedType,
    ): void {
        $this->assertSame($expectedType, Attachment::fromBytes('x', $filename)->type);
    }

    public function testFromUrlReadsViaFileScheme(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mc-fromurl-');
        $this->assertNotFalse($path);
        $renamed = $path . '.pdf';
        rename($path, $renamed);
        file_put_contents($renamed, 'pdf-bytes');
        $url = 'file://' . $renamed;

        try {
            $att = Attachment::fromUrl($url);
            $this->assertSame(base64_encode('pdf-bytes'), $att->content);
            $this->assertSame(basename($renamed), $att->filename);
            // file:// has no Content-Type response header so we fall through
            // to the filename-extension guess.
            $this->assertSame('application/pdf', $att->type);
            $this->assertSame(Attachment::DISPOSITION_ATTACHMENT, $att->disposition);
        } finally {
            @unlink($renamed);
        }
    }

    public function testFromUrlOverridesFilenameAndContentType(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mc-fromurl-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'data');
        $url = 'file://' . $path;

        try {
            $att = Attachment::fromUrl(
                url: $url,
                filename: 'override.csv',
                contentType: 'application/custom',
                contentId: 'cid-from-url',
            );
            $this->assertSame('override.csv', $att->filename);
            $this->assertSame('application/custom', $att->type);
            $this->assertSame('cid-from-url', $att->contentId);
        } finally {
            @unlink($path);
        }
    }

    public function testFromUrlRaisesValidationExceptionOnFetchFailure(): void
    {
        $this->expectException(ValidationException::class);
        Attachment::fromUrl('file:///tmp/mc-fromurl-does-not-exist-' . uniqid() . '.pdf');
    }

    /** @return array<string, array{string, string}> */
    public static function urlFilenameProvider(): array
    {
        return [
            'path with extension' => ['https://example.com/path/logo.png', 'logo.png'],
            'path with extension and query' => ['https://example.com/file.csv?v=2', 'file.csv'],
            'root path' => ['https://example.com/', 'attachment'],
            'no path' => ['https://example.com', 'attachment'],
            'nested path' => ['https://example.com/a/b/c.txt', 'c.txt'],
        ];
    }

    /** @dataProvider urlFilenameProvider */
    public function testFilenameFromUrl(string $url, string $expected): void
    {
        $method = new \ReflectionMethod(Attachment::class, 'filenameFromUrl');
        $this->assertSame($expected, $method->invoke(null, $url));
    }

    public function testContentTypeFromHeadersExtractsHeader(): void
    {
        $method = new \ReflectionMethod(Attachment::class, 'contentTypeFromHeaders');

        $this->assertSame(
            'image/png',
            $method->invoke(null, ['HTTP/1.1 200 OK', 'Content-Type: image/png']),
        );
    }

    public function testContentTypeFromHeadersStripsCharsetParameter(): void
    {
        $method = new \ReflectionMethod(Attachment::class, 'contentTypeFromHeaders');

        $this->assertSame(
            'text/plain',
            $method->invoke(null, ['Content-Type: text/plain; charset=utf-8']),
        );
    }

    public function testContentTypeFromHeadersReturnsNullWhenAbsent(): void
    {
        $method = new \ReflectionMethod(Attachment::class, 'contentTypeFromHeaders');

        $this->assertNull($method->invoke(null, ['HTTP/1.1 200 OK', 'Server: nginx']));
        $this->assertNull($method->invoke(null, null));
    }
}
