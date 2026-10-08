<?php

namespace App\Tests\Service;

use App\Model\UnsubscribeMethods;
use App\Service\ListUnsubscribeParser;
use PHPUnit\Framework\TestCase;

class ListUnsubscribeParserTest extends TestCase
{
    private ListUnsubscribeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ListUnsubscribeParser();
    }

    public function testParseReturnsOneClickMethod(): void
    {
        $result = $this->parser->parse(
            '<mailto:unsubscribe@example.com?subject=unsubscribe>, <https://example.com/unsubscribe/abc>',
            'List-Unsubscribe=One-Click',
        );

        self::assertSame('https://example.com/unsubscribe/abc', $result->httpsUrl);
        self::assertSame('mailto:unsubscribe@example.com?subject=unsubscribe', $result->mailto);
        self::assertSame('unsubscribe@example.com', $result->mailtoAddress);
        self::assertTrue($result->oneClick);
        self::assertSame(UnsubscribeMethods::METHOD_ONE_CLICK, $result->getPreferredMethod());
    }

    public function testParseReturnsHttpsMethodWithoutPostHeader(): void
    {
        $result = $this->parser->parse('<https://example.com/unsubscribe>');

        self::assertSame('https://example.com/unsubscribe', $result->httpsUrl);
        self::assertFalse($result->oneClick);
        self::assertSame(UnsubscribeMethods::METHOD_HTTPS, $result->getPreferredMethod());
    }

    public function testParseReturnsMailtoMethod(): void
    {
        $result = $this->parser->parse('<mailto:leave@lists.example.com>');

        self::assertNull($result->httpsUrl);
        self::assertSame('mailto:leave@lists.example.com', $result->mailto);
        self::assertSame('leave@lists.example.com', $result->mailtoAddress);
        self::assertFalse($result->oneClick);
        self::assertSame(UnsubscribeMethods::METHOD_MAILTO, $result->getPreferredMethod());
    }

    public function testParseRequiresHttpsUrlForOneClick(): void
    {
        $result = $this->parser->parse(
            '<mailto:leave@lists.example.com>',
            'List-Unsubscribe=One-Click',
        );

        self::assertFalse($result->oneClick);
        self::assertSame(UnsubscribeMethods::METHOD_MAILTO, $result->getPreferredMethod());
    }

    public function testParseIgnoresPostValueCase(): void
    {
        $result = $this->parser->parse(
            '<https://example.com/unsubscribe>',
            ' list-unsubscribe=one-click ',
        );

        self::assertTrue($result->oneClick);
    }

    public function testParseIgnoresInsecureAndUnsupportedUris(): void
    {
        $result = $this->parser->parse(
            '<http://example.com/unsubscribe>, <ftp://example.com/u>, <javascript:alert(1)>',
            'List-Unsubscribe=One-Click',
        );

        self::assertNull($result->httpsUrl);
        self::assertNull($result->mailto);
        self::assertFalse($result->oneClick);
        self::assertFalse($result->hasMethod());
    }

    public function testParseIgnoresInvalidMailto(): void
    {
        $result = $this->parser->parse('<mailto:not-an-email>');

        self::assertNull($result->mailto);
        self::assertNull($result->mailtoAddress);
    }

    public function testParseDecodesMailtoAddress(): void
    {
        $result = $this->parser->parse('<mailto:leave%2Dlist@example.com?subject=unsubscribe>');

        self::assertSame('mailto:leave%2Dlist@example.com?subject=unsubscribe', $result->mailto);
        self::assertSame('leave-list@example.com', $result->mailtoAddress);
    }

    public function testParseRemovesWhitespacesInsideUri(): void
    {
        $result = $this->parser->parse('<https://example.com/ unsubscribe>, <mailto:leave@example.com>');

        self::assertSame('https://example.com/unsubscribe', $result->httpsUrl);
        self::assertSame('mailto:leave@example.com', $result->mailto);
    }

    public function testParseIgnoresUrisWithoutBrackets(): void
    {
        $result = $this->parser->parse('https://example.com/unsubscribe');

        self::assertFalse($result->hasMethod());
    }

    public function testParseHandlesMissingHeaders(): void
    {
        $result = $this->parser->parse(null, null);

        self::assertNull($result->httpsUrl);
        self::assertNull($result->mailto);
        self::assertFalse($result->oneClick);
        self::assertNull($result->getPreferredMethod());
    }
}
