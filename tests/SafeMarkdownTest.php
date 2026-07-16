<?php

declare(strict_types=1);

use App\Controllers\RepoViewController;
use PHPUnit\Framework\TestCase;

final class SafeMarkdownTest extends TestCase
{
    public function testUnsafeHtmlAndLinksAreNotRendered(): void
    {
        $html = RepoViewController::renderMarkdown("# Hello\n<script>alert(1)</script>\n[x](javascript:alert(1))");
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('href="javascript:', $html);
        self::assertStringContainsString('<h1>Hello</h1>', $html);
    }
}
