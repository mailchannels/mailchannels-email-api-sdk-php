<?php

namespace MailChannels\Tests\Unit\Symfony\Transport;

use MailChannels\Symfony\Transport\MailChannelsApiTransport;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class MailChannelsApiTransportTest extends TestCase
{
    private function email(): Email
    {
        return (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->subject('Hello')
            ->text('Hello, world!')
            ->html('<p>Hello, world!</p>');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSuccessfulSendBuildsExpectedPayloadAndHeaders(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('mc-from-config', httpClient: $http);

        $transport->send($this->email());

        $req = $http->lastRequest();
        $this->assertSame('mc-from-config', $req->getHeaderLine('X-Api-Key'));
        $this->assertStringEndsWith('/tx/v1/send-async', (string) $req->getUri());


        $payload = $http->lastJson();
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Sender'], $payload['from']);
        $this->assertSame('Hello', $payload['subject']);
        $this->assertSame([
            ['type' => 'text/plain', 'value' => 'Hello, world!'],
            ['type' => 'text/html', 'value' => '<p>Hello, world!</p>'],
        ], $payload['content']);
        $expectedPersonalizations = [
            [
                "to" => [[ "email" => "recipient@example.com", "name" => "Recipient"]],
            ],
        ];
        $this->assertSame($expectedPersonalizations, $payload['personalizations']);
    }

    public function testErrorResponseThrowsHttpTransportExceptionWithApiErrors(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(400, ['data' => json_encode([
            'errors' => ['from address is not authorized'],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('*from address is not authorized*');

        $transport->send($this->email());
    }

    public function testCcBccReplyToAndAttachmentsArePassedThrough(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->attach('file contents', 'notes.txt', 'text/plain');

        $transport->send($email);

        $payload = $http->lastJson();
        $expectedPersonalizations = [
            [
                "to" => [[ "email" => "recipient@example.com", 'name' => 'Recipient']],
                "cc" => [[ "email" => "cc@example.com" ]],
                "bcc" => [[ "email" => "bcc@example.com" ]]
            ]
        ];
        $expectedAttachments = [
            [
                "content" => base64_encode('file contents'),
                'filename' => 'notes.txt',
                'type' => 'text/plain',
            ]
        ];
        $this->assertSame($expectedPersonalizations, $payload['personalizations']);
        $this->assertSame(["email" => 'reply@example.com'], $payload['reply_to']);
        $this->assertSame($expectedAttachments, $payload['attachments']);
    }

    public function testMultipleTos(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()
            ->to('to@example.com', new Address('to2@example.com', "recipient"))
            ->addto('to3@example.net')->addto(new Address('to4@example.net'))->addto(new Address('to5@example.net', "Other Recipient"));

        $transport->send($email);

        $payload = $http->lastJson();

        $expectedPayload = [
            ["to" => [
                ["email" => "to@example.com"],
                ["email" => "to2@example.com", "name" => "recipient"],
                ["email" => "to3@example.net"],
                ["email" => "to4@example.net"],
                ["email" => "to5@example.net", "name" => "Other Recipient"],
                ]
            ]
        ];
        $this->assertSame($expectedPayload, $payload['personalizations']);
    }

    public function testMultipleCC(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()
            ->cc('cc@example.com', new Address('cc2@example.com', "recipient"))
            ->addcc('cc3@example.net')->addcc(new Address('cc4@example.net'))->addcc(new Address('cc5@example.net', "Other Recipient"));

        $transport->send($email);

        $payload = $http->lastJson();

        $expectedPayload = [
            [
                "to" => [["email" => "recipient@example.com", "name" => "Recipient"]],
                "cc" => [
                    ["email" => "cc@example.com"],
                    ["email" => "cc2@example.com", "name" => "recipient"],
                    ["email" => "cc3@example.net"],
                    ["email" => "cc4@example.net"],
                    ["email" => "cc5@example.net", "name" => "Other Recipient"],
                ]
            ]
        ];
        $this->assertSame($expectedPayload, $payload['personalizations']);
    }

    public function testMultipleBCC(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()
            ->bcc('bcc@example.com', new Address('bcc2@example.com', "recipient"))
            ->addBcc('bcc3@example.net')->addBcc(new Address('bcc4@example.net'))->addBcc(new Address('bcc5@example.net', "Other Recipient"));

        $transport->send($email);

        $payload = $http->lastJson();

        $expectedPayload = [
            [
                "to" => [["email" => "recipient@example.com", "name" => "Recipient"]],
                "bcc" => [
                    ["email" => "bcc@example.com"],
                    ["email" => "bcc2@example.com", "name" => "recipient"],
                    ["email" => "bcc3@example.net"],
                    ["email" => "bcc4@example.net"],
                    ["email" => "bcc5@example.net", "name" => "Other Recipient"],
                ]
            ]
        ];
        $this->assertSame($expectedPayload, $payload['personalizations']);
    }

    public function testMultipleReplyTos(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()
            ->replyTo(new Address('to@example.com', "recipient"), 'to2@example.com')
            ->addReplyTo('to3@example.net')->addto(new Address('to4@example.net'))->addto(new Address('to5@example.net', "Other Recipient"));

        $transport->send($email);

        $payload = $http->lastJson();

        // Only one replyTo supported
        $this->assertEquals(["name" => "recipient", "email" => "to@example.com"], $payload['reply_to']);
    }

    public function testDataPartAttachmentsArePassedThrough(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email()->addPart(new DataPart(new File(__FILE__)));

        $transport->send($email);

        $payload = $http->lastJson();
        /** @var list<array{filename: string, content: string}> $attachments */
        $attachments = $payload['attachments'];
        $this->assertSame('MailChannelsApiTransportTest.php', $attachments[0]['filename']);

        $contents = file_get_contents(__FILE__);
        $this->assertNotFalse($contents);
        $this->assertSame(base64_encode($contents), $attachments[0]['content']);
    }

    public function testHeadersArePassedThrough(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email();
        $email->getHeaders()->addTextHeader('X-Custom-Header', 'Custom Value');
        $email->getHeaders()->addIdHeader('References', ['12345@example.com', '54321@example.com']);

        $transport->send($email);

        $payload = $http->lastJson();
        /** @var array<string, string> $headers */
        $headers = $payload['headers'];
        $this->assertSame('Custom Value', $headers['X-Custom-Header']);
        $this->assertSame('<12345@example.com> <54321@example.com>', $headers['References']);
    }


    public function testDuplicateHeader(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('key', httpClient: $http);

        $email = $this->email();
        $email->getHeaders()->addTextHeader('X-Custom-Header', 'Custom Value 1');
        $email->getHeaders()->addTextHeader('X-Custom-Header', 'Custom Value 2');

        $transport->send($email);

        $payload = $http->lastJson();
        /** @var array<string, string> $headers */
        $headers = $payload['headers'];
        $this->assertSame('Custom Value 2', $headers['X-Custom-Header']);
    }

    public function testSendWithDkim(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('mc-from-config', httpClient: $http);

        $email = $this->email();

        $private_key = "-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC1shfc9xW7FK/7SlVt55uXZG2vGqfJAD52ni8kqtLEgLx6tS1j
l9lSN6ix7b5VjajnF5ehAR1SA1c9epXrG/rlErZwjj/9RBukK4eHOOq6EuiP2RmC
IpcHznzg8G1wTKdLv2nz+JYairzaij9hBcDuN8jxGNhOute+UTFRQ6zwAQIDAQAB
AoGAIP0r3f+yn1v7M7nkVucVVeBzFbbWPeVgcfL4hkTJrcnd7r2Udx+r4oSeiWes
BiaJQyQCcMYeB7T5kvz92UkkPUKxkBL+ZlfFpqKuaFttcOGjbk3af3f1K/YYiyIB
UMX5JxhzzAhukXAqq5+iofzTcE6+f5/Xh4n6in2f3bcMdAECQQDmmrR3OG+Kb6V3
7Cy5SkYZobWU4mfEdfTczR2WVfMrJRzqZp3sK7gjGBzKro9RZsw3Mb4cr/cf52I2
0hChMrChAkEAybSI+zOAFNmeRE/WS8gIa+YagJxHQisO9UpGUeKW8SVNwb0JwOMV
Qodg6DNu5oO3oZDNK+UO9i4IEGy8FhIjYQJBAJhvx7/PhJwkdWMysdXCLVigNuI2
T5Xky3px9I7+PgJVRCoGr3oA5HX+aHnqrj6HD5OkxCE+iMOENr46WlfUpEECQC0Q
arJE1U7TN+rnM2eG72q75BvrznfYCuV+jKtqxnJl9TNdurofgfZ1LI90Pki+EN7F
7TDrYiZaLm8M/BCqSMECQE3SlAqMwy2Bftf/m2oCraciKVg7lATUPAwoswTZF3kw
FiXtf/n8ypTJQrKxt6WXo7qVPJl1C2wh68gZGLKV9ao=
-----END RSA PRIVATE KEY-----";

        $signer = new DkimSigner($private_key, 'example.com', 'default');
        $signer->sign($email);

        $transport->send($email);

        $req = $http->lastRequest();
        $this->assertSame('mc-from-config', $req->getHeaderLine('X-Api-Key'));
        $this->assertStringEndsWith('/tx/v1/send-async', (string) $req->getUri());

        $this->assertNull($email->getHeaders()->get('dkim-signature'));

        $payload = $http->lastJson();
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Sender'], $payload['from']);
        $this->assertSame('Hello', $payload['subject']);
        $this->assertSame([
            ['type' => 'text/plain', 'value' => 'Hello, world!'],
            ['type' => 'text/html', 'value' => '<p>Hello, world!</p>'],
        ], $payload['content']);
        /** @var list<array{to: list<array{email: string, name?: string}>}> $personalizations */
        $personalizations = $payload['personalizations'];
        $this->assertSame('recipient@example.com', $personalizations[0]['to'][0]['email']);
    }

    public function testCustomEnvelope(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('mc-from-config', httpClient: $http);

        $email = $this->email();

        $envelope = new Envelope(
            new Address("other@example.com", "other sender"),
            [new Address("recipient2@example.net", "Recipient 2"), new Address("recipient3@example.net")]
        );

        $transport->send($email, $envelope);

        $payload = $http->lastJson();
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Sender'], $payload['from']);
        $this->assertSame(['email' => 'other@example.com', 'name' => 'other sender'], $payload['envelope_from']);
        $this->assertSame('Hello', $payload['subject']);
        $this->assertSame([
            ['type' => 'text/plain', 'value' => 'Hello, world!'],
            ['type' => 'text/html', 'value' => '<p>Hello, world!</p>'],
        ], $payload['content']);

        // When creating the Envelope it strips the name from all recipients
        /** @var list<array{to: list<array{email: string, name?: string}>}> $personalizations */
        $personalizations = $payload['personalizations'];
        $this->assertSame(['email' => 'recipient2@example.net'], $personalizations[0]['to'][0]);
        $this->assertSame(['email' => 'recipient3@example.net'], $personalizations[0]['to'][1]);
    }

    public function testRecipientInToAndCC(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, ['data' => json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ])]));

        $transport = new MailChannelsApiTransport('mc-from-config', httpClient: $http);

        $email = $this->email();

        $rcpt = new Address("test@example.com");
        $email->to($rcpt, "test2@example.com", "test3@example.com");
        $email->cc($rcpt, "test3@example.com");

        $transport->send($email);

        $payload = $http->lastJson();
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Sender'], $payload['from']);
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Sender'], $payload['envelope_from']);
        $this->assertSame('Hello', $payload['subject']);
        $this->assertSame([
            ['type' => 'text/plain', 'value' => 'Hello, world!'],
            ['type' => 'text/html', 'value' => '<p>Hello, world!</p>'],
        ], $payload['content']);

        // If an Address object is in both `to` and `cc` or `bcc`, we remove it from the `to` field.
        // If the addresses are separate objects, (which happens when the `value is just set with a string) we can tell which is from
        // the `to` list and what is from the `cc` / `bcc` list, and it won't be filtered out
        /** @var list<array{to: list<array{email: string, name?: string}>, cc: list<array{email: string, name?: string}>}> $personalizations */
        $personalizations = $payload['personalizations'];
        $this->assertSame([['email' => 'test2@example.com'], ['email' => 'test3@example.com']], $personalizations[0]['to']);
        $this->assertSame([['email' => 'test@example.com'], ['email' => 'test3@example.com']], $personalizations[0]['cc']);
    }
}
