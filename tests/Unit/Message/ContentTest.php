<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Message;

use MailChannels\Message\Content;
use PHPUnit\Framework\TestCase;

final class ContentTest extends TestCase
{
    public function testPlainFactoryProducesTextPlainContent(): void
    {
        $content = Content::plain('hello');

        $this->assertSame(Content::TEXT_PLAIN, $content->type);
        $this->assertSame('hello', $content->value);
        $this->assertNull($content->templateType);
    }

    public function testHtmlFactoryProducesTextHtmlContent(): void
    {
        $content = Content::html('<p>hi</p>');

        $this->assertSame(Content::TEXT_HTML, $content->type);
        $this->assertSame('<p>hi</p>', $content->value);
        $this->assertNull($content->templateType);
    }

    public function testMustacheHtmlSetsTemplateType(): void
    {
        $content = Content::mustacheHtml('<p>Hi {{ name }}</p>');

        $this->assertSame(Content::TEXT_HTML, $content->type);
        $this->assertSame(Content::TEMPLATE_MUSTACHE, $content->templateType);
        $this->assertSame(
            ['type' => 'text/html', 'value' => '<p>Hi {{ name }}</p>', 'template_type' => 'mustache'],
            $content->toArray(),
        );
    }

    public function testToArrayOmitsTemplateTypeWhenNull(): void
    {
        $content = new Content(Content::TEXT_PLAIN, 'body');

        $this->assertSame(['type' => 'text/plain', 'value' => 'body'], $content->toArray());
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $content = Content::mustacheHtml('hi {{ name }}');

        $this->assertSame($content->toArray(), $content->jsonSerialize());
    }
}
