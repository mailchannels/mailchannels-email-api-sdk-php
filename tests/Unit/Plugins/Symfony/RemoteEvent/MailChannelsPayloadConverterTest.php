<?php

namespace MailChannels\Tests\Unit\Plugins\Symfony\RemoteEvent;

use MailChannels\Plugins\Symfony\RemoteEvent\MailChannelsPayloadConverter;
use MailChannels\Plugins\Symfony\RemoteEvent\TestEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;

class MailChannelsPayloadConverterTest extends TestCase
{
    private PayloadConverterInterface $converter;

    private string $processed = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "processed",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "campaign_id": "campaign_123",
        "recipients": ["recipient@example.com", "recipient2@example.com"]
    }';

    private string $dropped = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "dropped",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "status": "451",
        "reason": "Spam detected",
        "recipients": ["recipient@example.com"]
    }';

    private string $clicked = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "click",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "url": "example.com",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3",
        "smtp_id": "<1625097500@example.com>",
        "ip": "127.0.0.1",
        "recipients": ["recipient@example.com"]
    }';

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new MailChannelsPayloadConverter();
    }

    public function testSuccessfulProcessedEvent(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->processed, true);
        $event = $this->converter->convert($payload);

        $this->assertInstanceOf(MailerDeliveryEvent::class, $event);
        $this->assertEquals(MailerDeliveryEvent::RECEIVED, $event->getName());
        $this->assertEquals('wBLWrCnK0Z965pf-cgxhNg8bo5s=_1625097600', $event->getId());
        $this->assertEquals($payload, $event->getPayload());
        // $event can only store one recipient, we provide the first one if there were multiple
        $this->assertEquals("recipient@example.com", $event->getRecipientEmail());
    }

    public function testSuccessfulDroppedEvent(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->dropped, true);
        $event = $this->converter->convert($payload);

        $this->assertInstanceOf(MailerDeliveryEvent::class, $event);
        $this->assertEquals(MailerDeliveryEvent::DROPPED, $event->getName());
        $this->assertEquals('wBLWrCnK0Z965pf-cgxhNg8bo5s=_1625097600', $event->getId());
        $this->assertEquals($payload, $event->getPayload());
        $this->assertEquals("recipient@example.com", $event->getRecipientEmail());
        $this->assertEquals("Spam detected", $event->getReason());
    }

    public function testSuccessfulClickEvent(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->clicked, true);
        $event = $this->converter->convert($payload);

        $this->assertInstanceOf(MailerEngagementEvent::class, $event);
        $this->assertEquals(MailerEngagementEvent::CLICK, $event->getName());
        $this->assertEquals('wBLWrCnK0Z965pf-cgxhNg8bo5s=_1625097600', $event->getId());
        $this->assertEquals($payload, $event->getPayload());
        $this->assertEquals("recipient@example.com", $event->getRecipientEmail());
    }

    public function testInvalidType(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->clicked, true);
        $payload["event"] = 5;
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing or invalid event type');
        $this->converter->convert($payload);
    }

    public function testUnknownType(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->clicked, true);
        $payload["event"] = "unknown-event-type";
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unknown webhook type: unknown-event-type');
        $this->converter->convert($payload);
    }

    public function testInvalidTimestamp(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->clicked, true);
        $payload["timestamp"] = null;
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing or invalid timestamp in payload');
        $this->converter->convert($payload);
    }

    public function testInvalidReason(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->dropped, true);
        $payload["reason"] = 5;
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid reason in payload');
        $this->converter->convert($payload);
    }

    public function testInvalidRecipient(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->dropped, true);
        $payload["recipients"] = [5];
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid recipient in payload');
        $this->converter->convert($payload);
    }

    public function testTypeMappings(): void
    {
        // event_type, json_base, expected_class, expected_name
        $tests = [
          ["processed", $this->processed, MailerDeliveryEvent::class, MailerDeliveryEvent::RECEIVED],
          ["delivered", $this->processed, MailerDeliveryEvent::class, MailerDeliveryEvent::DELIVERED],
          ["hard-bounced", $this->processed, MailerDeliveryEvent::class, MailerDeliveryEvent::BOUNCE],
          ["soft-bounced", $this->processed, MailerDeliveryEvent::class, MailerDeliveryEvent::DEFERRED],
          ["dropped", $this->processed, MailerDeliveryEvent::class, MailerDeliveryEvent::DROPPED],
          ["open", $this->clicked, MailerEngagementEvent::class, MailerEngagementEvent::OPEN],
          ["click", $this->clicked, MailerEngagementEvent::class, MailerEngagementEvent::CLICK],
          ["complained", $this->clicked, MailerEngagementEvent::class, MailerEngagementEvent::SPAM],
          ["unsubscribed", $this->clicked, MailerEngagementEvent::class, MailerEngagementEvent::UNSUBSCRIBE],
          ["test", $this->processed, TestEvent::class, TestEvent::TEST]
        ];
        foreach ($tests as $test) {
            $type = $test[0];
            $req = $test[1];
            $expectedClass = $test[2];
            $expectedType = $test[3];

            /** @var array<string, mixed> $payload */
            $payload = json_decode($req, true);
            $payload["event"] = $type;
            $event = $this->converter->convert($payload);

            $this->assertInstanceOf($expectedClass, $event);
            $this->assertEquals($expectedType, $event->getName());
            $this->assertEquals('wBLWrCnK0Z965pf-cgxhNg8bo5s=_1625097600', $event->getId());
            $this->assertEquals($payload, $event->getPayload());
            $this->assertEquals("recipient@example.com", $event->getRecipientEmail());
        }
    }
}
