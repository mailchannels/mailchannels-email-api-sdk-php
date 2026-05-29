<?php

declare(strict_types=1);

namespace MailChannels\Message;

use JsonSerializable;
use MailChannels\Exception\ValidationException;

final class Attachment implements JsonSerializable
{
    public const DISPOSITION_ATTACHMENT = 'attachment';
    public const DISPOSITION_INLINE = 'inline';

    public function __construct(
        public readonly string $content,
        public readonly string $filename,
        public readonly ?string $type = null,
        public readonly ?string $disposition = null,
        public readonly ?string $contentId = null,
    ) {
    }

    public static function fromBytes(
        string $data,
        string $filename,
        ?string $contentType = null,
        string $disposition = self::DISPOSITION_ATTACHMENT,
        ?string $contentId = null,
    ): self {
        return new self(
            content: base64_encode($data),
            filename: $filename,
            type: $contentType ?? self::guessContentType($filename),
            disposition: $disposition,
            contentId: $contentId,
        );
    }

    public static function fromFile(
        string $path,
        ?string $filename = null,
        ?string $contentType = null,
        string $disposition = self::DISPOSITION_ATTACHMENT,
        ?string $contentId = null,
    ): self {
        if (!is_file($path) || !is_readable($path)) {
            throw new ValidationException(
                "Unable to read attachment file: {$path}",
                errorCode: 'AttachmentReadError',
            );
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new ValidationException(
                "Failed to read attachment file: {$path}",
                errorCode: 'AttachmentReadError',
            );
        }
        $name = $filename ?? basename($path);
        return self::fromBytes(
            data: $bytes,
            filename: $name,
            contentType: $contentType ?? self::guessContentType($name),
            disposition: $disposition,
            contentId: $contentId,
        );
    }

    public static function inlineFile(
        string $path,
        string $contentId,
        ?string $filename = null,
        ?string $contentType = null,
    ): self {
        return self::fromFile(
            path: $path,
            filename: $filename,
            contentType: $contentType,
            disposition: self::DISPOSITION_INLINE,
            contentId: $contentId,
        );
    }

    /**
     * Fetch a remote URL and build an attachment from its bytes. Content type
     * resolution cascades: caller-supplied `$contentType` → server's
     * `Content-Type` response header → filename-extension guess.
     *
     * `$timeout` is the per-stream read timeout in seconds; it does not cover
     * connect time on every platform. Each call blocks the caller, so prefer
     * a short value when fetching multiple attachments before a send.
     */
    public static function fromUrl(
        string $url,
        ?string $filename = null,
        ?string $contentType = null,
        string $disposition = self::DISPOSITION_ATTACHMENT,
        ?string $contentId = null,
        float $timeout = 10.0,
    ): self {
        $context = stream_context_create([
            'http' => ['timeout' => $timeout, 'follow_location' => 1],
            'https' => ['timeout' => $timeout, 'follow_location' => 1],
        ]);

        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) {
            $errorMessage = error_get_last()['message'] ?? 'unknown error';
            throw new ValidationException(
                "Unable to fetch attachment URL: {$url} ({$errorMessage})",
                errorCode: 'AttachmentFetchError',
            );
        }

        try {
            $metadata = stream_get_meta_data($stream);
            $bytes = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        if (!is_string($bytes)) {
            throw new ValidationException(
                "Failed to read attachment URL response: {$url}",
                errorCode: 'AttachmentFetchError',
            );
        }

        $wrapperData = $metadata['wrapper_data'] ?? null;
        /** @var list<string>|null $headers */
        $headers = is_array($wrapperData) ? array_values(array_filter(
            $wrapperData,
            static fn (mixed $v): bool => is_string($v),
        )) : null;

        $resolvedFilename = $filename ?? self::filenameFromUrl($url);
        $resolvedContentType = $contentType
            ?? self::contentTypeFromHeaders($headers)
            ?? self::guessContentType($resolvedFilename);

        return self::fromBytes(
            data: $bytes,
            filename: $resolvedFilename,
            contentType: $resolvedContentType,
            disposition: $disposition,
            contentId: $contentId,
        );
    }

    private static function filenameFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return 'attachment';
        }
        $path = $parsed['path'] ?? '';
        if (!is_string($path) || $path === '') {
            return 'attachment';
        }
        $base = basename($path);
        if ($base === '' || $base === '/' || $base === '.') {
            return 'attachment';
        }
        return $base;
    }

    /** @param list<string>|null $headers */
    private static function contentTypeFromHeaders(?array $headers): ?string
    {
        if ($headers === null) {
            return null;
        }
        foreach ($headers as $line) {
            if (preg_match('/^Content-Type:\s*([^;]+)(?:;.*)?$/i', $line, $match)) {
                $value = trim($match[1]);
                return $value === '' ? null : $value;
            }
        }
        return null;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = [
            'content' => $this->content,
            'filename' => $this->filename,
        ];
        if ($this->type !== null) {
            $out['type'] = $this->type;
        }
        if ($this->disposition !== null) {
            $out['disposition'] = $this->disposition;
        }
        if ($this->contentId !== null) {
            $out['content_id'] = $this->contentId;
        }
        return $out;
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function guessContentType(string $filename): string
    {
        $map = [
            'pdf'  => 'application/pdf',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
            'html' => 'text/html',
            'htm'  => 'text/html',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'zip'  => 'application/zip',
            'gz'   => 'application/gzip',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ics'  => 'text/calendar',
            'eml'  => 'message/rfc822',
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $map[$ext] ?? 'application/octet-stream';
    }
}
