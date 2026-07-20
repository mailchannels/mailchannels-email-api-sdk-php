<?php

namespace MailChannels\Plugins\Symfony\Transport;

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

    private const HEADERS_TO_SEND_OPTIONS = [
        'x-mailchannels-send-async' => ['sendAsync', 'bool'],
        'x-mailchannels-click-tracking-enabled' => ['clickTrackingEnabled', 'bool'],
        'x-mailchannels-click-tracking-domain' => ['clickTrackingDomain', 'string'],
        'x-mailchannels-open-tracking-enabled' => ['openTrackingEnabled', 'bool'],
        'x-mailchannels-open-tracking-domain' => ['openTrackingDomain', 'string'],
        'x-mailchannels-unsubscribe-domain' => ['unsubscribeDomain', 'string'],
        'x-mailchannels-transactional' => ['transactional', 'bool'],
        'x-mailchannels-dkim-domain' => ['dkimDomain', 'string'],
        'x-mailchannels-dkim-selector' => ['dkimSelector', 'string'],
        'x-mailchannels-dkim-private-key' => ['dkimPrivateKey', 'string'],
        'x-mailchannels-campaign-id' => ['campaignId', 'string'],
    ];

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
            throw new TransportException('Unable to convert the message to an email for MailChannels: ' . $e->getMessage(), previous: $e);
        }

        try {
            [$params, $sendAsync] = $this->getParamsAndSendOptions($email, $message->getEnvelope());
        } catch (\Exception $e) {
            throw new TransportException('Unable to create message args for MailChannels API: ' . $e->getMessage(), previous: $e);
        }


        try {
            if ($sendAsync === false) {
                $response = $this->client->emails->send($params);
                $result = $response->results[0] ?? null;
                if ($result === null) {
                    throw new TransportException('MailChannels API did not return a message response the sent email, thought it may have still been sent.');
                }
                if ($result->messageId !== null) {
                    $message->setMessageId($result->messageId);
                }
            } else {
                $this->client->emails->queue($params);
                // This endpoint doesn't return message ids, so we can't update $message
            }
        } catch (MailChannelsException $e) {
            throw new TransportException('Unable to send an email via MailChannels: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array{0: EmailParams, 1: bool} Returns the EmailParams object as well as the resolved sendAsync flag
     */
    private function getParamsAndSendOptions(Email $email, Envelope $envelope): array
    {
        $from = $this->toEmailAddress($email->getFrom()[0] ?? $envelope->getSender());
        $envelopeFrom = $this->toEmailAddress($envelope->getSender());

        $to = $this->toEmailAddressArray($this->getTo($email, $envelope));
        $cc = $this->toEmailAddressArray($email->getCc()) ?: null;
        $bcc = $this->toEmailAddressArray($email->getBcc()) ?: null;

        $personalization = new Personalization(to: $to, cc: $cc, bcc: $bcc);

        // We only support a single Reply-To address
        $replyTo = $email->getReplyTo() ? $this->toEmailAddress($email->getReplyTo()[0]) : null;

        $attachments = $this->getAttachments($email);

        [$headers, $boolOptions, $stringOptions] = $this->getHeadersAndSendOptions($email->getHeaders());

        $trackingSettings = [];
        if ($boolOptions['clickTrackingEnabled'] ?? false) {
            $clickTrackingSettings = ['enable' => true];
            if ($stringOptions['clickTrackingDomain'] ?? false) {
                $clickTrackingSettings['custom_domain_name'] = $stringOptions['clickTrackingDomain'];
            }
            $trackingSettings['click_tracking'] = $clickTrackingSettings;
        }

        if ($boolOptions['openTrackingEnabled'] ?? false) {
            $openTrackingSettings = ['enable' => true];
            if ($stringOptions['openTrackingDomain'] ?? false) {
                $openTrackingSettings['custom_domain_name'] = $stringOptions['openTrackingDomain'];
            }
            $trackingSettings['open_tracking'] = $openTrackingSettings;
        }

        $unsubscribeSettings = null;
        if ($stringOptions['unsubscribeDomain'] ?? false) {
            $unsubscribeSettings = [
                'custom_domain_name' => $stringOptions['unsubscribeDomain'],
            ];
        }

        $sendAsync = $boolOptions['sendAsync'] ?? true;

        return [new EmailParams(
            from: $from,
            subject: (string) $email->getSubject(),
            personalizations: [$personalization],
            content: $this->getContent($email),
            replyTo: $replyTo,
            envelopeFrom: $envelopeFrom,
            headers: $headers ?: null,
            attachments: $attachments ?: null,
            transactional: $boolOptions['transactional'] ?? null,
            campaignId: $stringOptions['campaignId'] ?? null,
            trackingSettings: $trackingSettings ?: null,
            dkimDomain: $stringOptions['dkimDomain'] ?? null,
            dkimPrivateKey: $stringOptions['dkimPrivateKey'] ?? null,
            dkimSelector: $stringOptions['dkimSelector'] ?? null,
            unsubscribeSettings: $unsubscribeSettings ?: null
        ), $sendAsync];
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

    /**
     * @param array<Address> $addresses
     * @return array<EmailAddress>
     */
    private function toEmailAddressArray(array $addresses): array {
        return array_map($this->toEmailAddress(...), $addresses);
    }

    private function toEmailAddress(Address $address): EmailAddress
    {
        return new EmailAddress($address->getAddress(), $address->getName() ?: null);
    }

    /** @return array{0: array<string, string>, 1: array<string, bool>, 2: array<string, string>} */
    private function getHeadersAndSendOptions(Headers $headers): array
    {
        $customHeaders = [];
        $boolOptions = [];
        $stringOptions = [];

        foreach ($headers->all() as $header) {
            if (!$header instanceof HeaderInterface) {
                continue;
            }

            $normalizedName = strtolower($header->getName());
            if (\in_array($normalizedName, self::STRUCTURAL_HEADERS, true)) {
                continue;
            }

            $value = $header->getBodyAsString();

            $parsedOpts = self::HEADERS_TO_SEND_OPTIONS[$normalizedName] ?? null;
            if ($parsedOpts !== null) {
                [$optionKey, $type] = $parsedOpts;

                if ($type === 'bool') {
                    $bool = $this->headerToBool($value);
                    if ($bool !== null) {
                        $boolOptions[$optionKey] = $bool;
                    }
                } elseif ($type === 'string') {
                    $stringOptions[$optionKey] = $value;
                } else {
                    throw new TransportException("Error parsing mailchannels send options: invalid header type");
                }
                continue;
            }

            $customHeaders[$header->getName()] = $value;
        }

        return [$customHeaders, $boolOptions, $stringOptions];
    }

    private function headerToBool(?string $value): ?bool
    {
        $value = trim($value ?? '');
        if (!$value) {
            return null;
        }

        $normalized = strtolower($value);
        if ($normalized === 'true') {
            return true;
        }

        if ($normalized === 'false') {
            return false;
        }

        throw new TransportException("Error parsing mailchannels send option header: invalid boolean value '$value'");
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
