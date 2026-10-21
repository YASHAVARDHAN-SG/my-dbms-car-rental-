<?php

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testHtmlEscapingHandlesMarkupAndNull(): void
    {
        self::assertSame('&lt;script&gt;&quot; &amp; &#039;x&#039;&lt;/script&gt;', h('<script>" & \'x\'</script>'));
        self::assertSame('', h(null));
    }

    public function testCsrfTokenIsGeneratedOncePerSession(): void
    {
        $firstToken = csrf_token();

        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $firstToken);
        self::assertSame($firstToken, csrf_token());
    }

    public function testCsrfTokenVerificationRejectsMissingAndInvalidTokens(): void
    {
        self::assertFalse(verify_csrf_token('missing-session-token'));

        $_SESSION['csrf_token'] = str_repeat('a', 64);

        self::assertTrue(verify_csrf_token(str_repeat('a', 64)));
        self::assertFalse(verify_csrf_token(str_repeat('b', 64)));
        self::assertFalse(verify_csrf_token(null));
    }
}
