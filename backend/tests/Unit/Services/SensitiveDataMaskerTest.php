<?php

namespace Tests\Unit\Services;

use App\Services\Shared\Security\SensitiveDataMasker;
use RuntimeException;
use Tests\TestCase;

final class SensitiveDataMaskerTest extends TestCase
{
    public function test_exception_context_omits_raw_message(): void
    {
        $context = SensitiveDataMasker::exceptionContext(new RuntimeException('token=secret@example.test'));

        $this->assertArrayNotHasKey('error', $context);
        $this->assertArrayHasKey('message_hash', $context);
        $this->assertStringNotContainsString('secret@example.test', json_encode($context, JSON_THROW_ON_ERROR));
    }
}
