<?php

namespace MailChannels\Plugins\Symfony\RemoteEvent;

use DateTimeImmutable;
use Symfony\Component\RemoteEvent\Event\Mailer\AbstractMailerEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;

class MailChannelsPayloadConverter implements PayloadConverterInterface
{

    /** @param array<string, mixed> $payload */
    public function convert(array $payload): AbstractMailerEvent
    {
        if (!isset($payload["event"]) || !is_string($payload["event"])) {
            throw new ParseException("Missing or invalid event type in payload");
        }
        $type = match ($payload["event"]) {
            "processed" => MailerDeliveryEvent::RECEIVED,
            "delivered" => MailerDeliveryEvent::DELIVERED,
            "hard-bounced" => MailerDeliveryEvent::BOUNCE,
            "soft-bounced" => MailerDeliveryEvent::DEFERRED,
            "dropped" => MailerDeliveryEvent::DROPPED,
            "open" => MailerEngagementEvent::OPEN,
            "click" => MailerEngagementEvent::CLICK,
            "complained" => MailerEngagementEvent::SPAM,
            "unsubscribed" => MailerEngagementEvent::UNSUBSCRIBE,
            "test" => TestEvent::TEST,
            default => throw new ParseException("Unknown webhook type: ".$payload["event"]),
        };

        if (!isset($payload["timestamp"]) || !is_numeric($payload["timestamp"])) {
            throw new ParseException("Missing or invalid timestamp in payload");
        }
        $timestamp = $payload["timestamp"];

        $id = (string) $timestamp;
        if (isset($payload["request_id"]) && is_string($payload["request_id"])) {
            $id = $payload["request_id"] . "_" . $id;
        }

        $event = null;
        if (\in_array($type, [MailerDeliveryEvent::RECEIVED, MailerDeliveryEvent::DELIVERED, MailerDeliveryEvent::BOUNCE, MailerDeliveryEvent::DEFERRED, MailerDeliveryEvent::DROPPED], true)) {
            $event = new MailerDeliveryEvent($type, $id, $payload);
            if (isset($payload['reason'])) {
                if (!is_string($payload['reason'])) {
                    throw new ParseException("Invalid reason in payload");
                }
                $event->setReason($payload['reason']);
            }
        } else if (\in_array($type, [MailerEngagementEvent::OPEN, MailerEngagementEvent::CLICK, MailerEngagementEvent::SPAM, MailerEngagementEvent::UNSUBSCRIBE], true)) {
            $event = new MailerEngagementEvent($type, $id, $payload);
        } else {
            $event = new TestEvent($type, $id, $payload);
        }
        try {
            $date = DateTimeImmutable::createFromFormat('U', (string) $timestamp);
            if (!$date) {
                throw new ParseException("Invalid timestamp: ".$timestamp);
            }
            $event->setDate($date);
        } catch (\ValueError $e) {
            throw new ParseException("Timestamp contains null characters: ".$timestamp, previous: $e);
        }

        if (isset($payload['recipients']) && is_array($payload['recipients']) && sizeof($payload['recipients']) > 0) {
            if (!is_string($payload['recipients'][0])) {
                throw new ParseException("Invalid recipient in payload");
            }
            $event->setRecipientEmail($payload['recipients'][0]);
        }

        return $event;
    }
}
