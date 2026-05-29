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
}
