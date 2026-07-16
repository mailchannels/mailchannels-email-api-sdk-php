<?php

namespace MailChannels\Symfony\Transport;

use MailChannels\Client;
use MailChannels\Exception\MailChannelsException;
use MailChannels\Message\Attachment;
use MailChannels\Message\Content;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\EmailParams;
use MailChannels\Message\Personalization;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Symfony Mailer transport for the MailChannels Email API (https://docs.mailchannels.com).
 *
 * Delegates the actual send to the mailchannels/mailchannels-php SDK's own
 * `Client::$emails->queue()` — payload construction, HTTP transport, error
 * handling, and response decoding all live in the SDK rather than being
 * duplicated here.
 */
class MailChannelsApiTransport extends AbstractTransport
{
    /**
     * Standard MIME/envelope headers that Symfony's Email always carries as
     * part of its Headers collection. These are already represented by
     * structured EmailParams fields (or rendered by the API itself), so they
     * must not be re-sent through the `headers` passthrough.
     */
    private const STRUCTURAL_HEADERS = [
        'authentication-results',
        'bcc',
        'cc',
        'content-transfer-encoding',
        'content-type',
        'date',
        'dkim-signature',
        'from',
        'message-id',
        'mime-version',
        'received',
        'reply-to',
        'subject',
        'to',
    ];

    private readonly Client $client;

    public function __construct(
        #[\SensitiveParameter] string $apiKey,
        private readonly ?string $baseUrl = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->client = new Client(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            logger: $logger,
        );

        parent::__construct($dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('mailchannels+api://%s', $this->baseUrl ?? 'default');
    }

    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();
        if (!$originalMessage instanceof Message) {
            throw new TransportException(sprintf('The MailChannels transport only supports structured emails, got "%s".', get_debug_type($originalMessage)));
        }

        try {
            $email = MessageConverter::toEmail($originalMessage);
        } catch (\Exception $e) {
            throw new TransportException('Unable to convert the message to an email for MailChannels: '.$e->getMessage(), previous: $e);
        }

        try {
            $params = $this->getParams($email, $message->getEnvelope());
        } catch (\Exception $e) {
            throw new TransportException('Unable to create message args for MailChannels API: '.$e->getMessage(), previous: $e);
        }

        try {
            $response = $this->client->emails->queue($params);
        } catch (MailChannelsException $e) {
            throw new TransportException('Unable to queue an email via MailChannels: '.$e->getMessage(), previous: $e);
        }

        # todo: update $message with message_id for synchronous sends (async endpoint doesn't return mids)
    }

    private function getParams(Email $email, Envelope $envelope): EmailParams
    {
        $from = $this->toEmailAddress($email->getFrom()[0] ?? $envelope->getSender());
        $envelopeFrom = $this->toEmailAddress($envelope->getSender());

        $to = $this->toEmailAddressArray($this->getTo($email, $envelope));
        $cc = $this->toEmailAddressArray($email->getCc());
        $bcc = $this->toEmailAddressArray($email->getBcc());

        $personalization = new Personalization(to: $to, cc: $cc, bcc: $bcc);

        // We only support a single Reply-To address
        $replyTo = $email->getReplyTo() ? $this->toEmailAddress($email->getReplyTo()[0]) : null;

        $attachments = $this->getAttachments($email);

        $headers = $this->getCustomHeaders($email->getHeaders());

        return new EmailParams(
            from: $from,
            subject: (string) $email->getSubject(),
            personalizations: [$personalization],
            content: $this->getContent($email),
            replyTo: $replyTo,
            envelopeFrom: $envelopeFrom,
            headers: $headers ?: null,
            attachments: $attachments ?: null,
        );
    }

    // getRecipients returns merged list of tos, ccs, and bccs.
    // But we need to use this instead of $email->getTo() because users can overwrite
    // the recipient list by modifying the envelope
    // Note: If an Address object is in both `to` and `cc` or `bcc`, it will be removed from the `to` list.
    /** @return Address[] */
    private function getTo(Email $email, Envelope $envelope): array
    {
        $ccdAndBccd = array_merge($email->getCc(), $email->getBcc());
        return array_filter(
            $envelope->getRecipients(),
            static fn (Address $address) => !\in_array($address, $ccdAndBccd, true)
        );
    }

    /** @return EmailAddress[]|null */
    private function toEmailAddressArray(array|null $addresses): ?array {
        if ($addresses == null) {
            return null;
        }
        return array_map($this->toEmailAddress(...), $addresses);
    }

    private function toEmailAddress(Address $address): EmailAddress
    {
        return new EmailAddress($address->getAddress(), $address->getName() ?: null);
    }

    /** @return array<string, string> */
    private function getCustomHeaders(Headers $headers): array
    {
        $customHeaders = [];

        foreach ($headers->all() as $name => $header) {
            if (!$header instanceof HeaderInterface || \in_array($name, self::STRUCTURAL_HEADERS, true)) {
                continue;
            }

            $customHeaders[$header->getName()] = $header->getBodyAsString();
        }

        return $customHeaders;
    }

    /** @return list<Content> */
    private function getContent(Email $email): array
    {
        $content = [];

        if (null !== $text = $this->bodyToString($email->getTextBody())) {
            $content[] = Content::plain($text);
        }

        if (null !== $html = $this->bodyToString($email->getHtmlBody())) {
            $content[] = Content::html($html);
        }

        if (!$content) {
            $content[] = Content::plain('');
        }

        return $content;
    }

    /** @param resource|string|null $body */
    private function bodyToString($body): ?string
    {
        if (is_resource($body)) {
            return stream_get_contents($body) ?: '';
        }

        return is_string($body) ? $body : null;
    }

    /** @return list<Attachment> */
    private function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $contentType = $headers->get('Content-Type')?->getBody();

            $attachments[] = new Attachment(
                // Symfony's DataPart already Base64-encodes the body; MailChannels expects that encoding as-is.
                content: str_replace("\r\n", '', $attachment->bodyToString()),
                filename: $filename ?? 'attachment',
                type: is_string($contentType) ? $contentType : null,
            );
        }

        return $attachments;
    }
}
