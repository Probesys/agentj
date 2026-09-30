<?php

namespace App\Tests\Service;

use App\Model\ListUnsubscribe;
use App\Service\ListUnsubscribeParser;
use PHPUnit\Framework\TestCase;

class ListUnsubscribeParserTest extends TestCase
{
    private ListUnsubscribeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ListUnsubscribeParser();
    }

    public function testParseOneClick(): void
    {
        $result = $this->parser->parse(
            '<mailto:unsubscribe@example.com?subject=unsubscribe>, <https://example.com/unsubscribe/abc>',
            'List-Unsubscribe=One-Click',
        );

        self::assertSame('https://example.com/unsubscribe/abc', $result->httpsUrl);
        self::assertSame('mailto:unsubscribe@example.com?subject=unsubscribe', $result->mailto);
        self::assertTrue($result->oneClick);
        self::assertSame(ListUnsubscribe::METHOD_ONE_CLICK, $result->getPreferredMethod());
    }

    public function testParseHttpsWithoutPostHeader(): void
    {
        $result = $this->parser->parse('<https://example.com/unsubscribe>');

        self::assertSame('https://example.com/unsubscribe', $result->httpsUrl);
        self::assertFalse($result->oneClick);
        self::assertSame(ListUnsubscribe::METHOD_HTTPS, $result->getPreferredMethod());
    }

    public function testParseMailtoOnly(): void
    {
        $result = $this->parser->parse('<mailto:leave@lists.example.com>');

        self::assertNull($result->httpsUrl);
        self::assertSame('mailto:leave@lists.example.com', $result->mailto);
        self::assertFalse($result->oneClick);
        self::assertSame(ListUnsubscribe::METHOD_MAILTO, $result->getPreferredMethod());
    }

    public function testOneClickRequiresHttpsUrl(): void
    {
        $result = $this->parser->parse(
            '<mailto:leave@lists.example.com>',
            'List-Unsubscribe=One-Click',
        );

        self::assertFalse($result->oneClick);
        self::assertSame(ListUnsubscribe::METHOD_MAILTO, $result->getPreferredMethod());
    }

    public function testOneClickRequiresExactPostValue(): void
    {
        $result = $this->parser->parse(
            '<https://example.com/unsubscribe>',
            'List-Unsubscribe=Something-Else',
        );

        self::assertFalse($result->oneClick);
    }

    public function testOneClickPostValueIsCaseInsensitive(): void
    {
        $result = $this->parser->parse(
            '<https://example.com/unsubscribe>',
            ' list-unsubscribe=one-click ',
        );

        self::assertTrue($result->oneClick);
    }

    public function testFirstValidUriOfEachTypeIsKept(): void
    {
        $result = $this->parser->parse(
            '<https://first.example.com/u>, <https://second.example.com/u>, '
            . '<mailto:first@example.com>, <mailto:second@example.com>',
        );

        self::assertSame('https://first.example.com/u', $result->httpsUrl);
        self::assertSame('mailto:first@example.com', $result->mailto);
    }

    public function testInsecureAndUnsupportedUrisAreIgnored(): void
    {
        $result = $this->parser->parse(
            '<http://example.com/unsubscribe>, <ftp://example.com/u>, <javascript:alert(1)>',
            'List-Unsubscribe=One-Click',
        );

        self::assertNull($result->httpsUrl);
        self::assertNull($result->mailto);
        self::assertFalse($result->oneClick);
        self::assertFalse($result->canUnsubscribe());
    }

    public function testInvalidMailtoIsIgnored(): void
    {
        $result = $this->parser->parse('<mailto:not-an-email>');

        self::assertNull($result->mailto);
    }

    public function testWhitespacesInsideUriAreRemoved(): void
    {
        // An URI folded on several lines keeps a space once unfolded by Amavis
        $result = $this->parser->parse('<https://example.com/ unsubscribe>, <mailto:leave@example.com>');

        self::assertSame('https://example.com/unsubscribe', $result->httpsUrl);
        self::assertSame('mailto:leave@example.com', $result->mailto);
    }

    public function testUrisWithoutAngleBracketsAreIgnored(): void
    {
        $result = $this->parser->parse('https://example.com/unsubscribe');

        self::assertFalse($result->canUnsubscribe());
    }

    public function testMissingHeaders(): void
    {
        $result = $this->parser->parse(null, null);

        self::assertNull($result->httpsUrl);
        self::assertNull($result->mailto);
        self::assertFalse($result->oneClick);
        self::assertNull($result->getPreferredMethod());
    }
}
