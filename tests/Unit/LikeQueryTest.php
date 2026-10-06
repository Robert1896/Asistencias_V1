<?php

namespace Tests\Unit;

use App\Support\LikeQuery;
use Tests\TestCase;

class LikeQueryTest extends TestCase
{
    public function test_escapes_porcentaje_wildcard(): void
    {
        $this->assertSame('%100\%%', LikeQuery::escape('100%'));
    }

    public function test_escapes_underscore_wildcard(): void
    {
        $this->assertSame('%a\_b%', LikeQuery::escape('a_b'));
    }

    public function test_escapes_backslash(): void
    {
        $this->assertStringContainsString('\\\\', LikeQuery::escape('a\\b'));
    }

    public function test_percent_alone_does_not_match_everything(): void
    {
        $escaped = LikeQuery::escape('%');

        $this->assertSame('%\%%', $escaped);
        $this->assertNotSame('%%%', $escaped);
    }

    public function test_can_disable_wrapping(): void
    {
        $this->assertSame('100\\%', LikeQuery::escape('100%', false));
    }

    public function test_helper_function_matches_class(): void
    {
        $this->assertSame(LikeQuery::escape('a%b'), escape_like('a%b'));
    }

    public function test_plain_text_is_untouched_inside_wildcards(): void
    {
        $this->assertSame('%juan%', LikeQuery::escape('juan'));
    }

    public function test_multiple_wildcards_are_all_escaped(): void
    {
        $this->assertSame('%50\%\_off%', LikeQuery::escape('50%_off'));
    }
}
