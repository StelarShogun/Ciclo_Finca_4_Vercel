<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminAlertComponentTest extends TestCase
{
    public function test_renders_error_as_alert_danger_with_accessibility_attributes(): void
    {
        $html = Blade::render(
            '<x-admin.admin-alert type="error" title="Validation error" message="Fix fields." />'
        );

        $this->assertStringContainsString('class="alert alert-danger admin-alert', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="assertive"', $html);
        $this->assertStringContainsString('Validation error', $html);
        $this->assertStringContainsString('Fix fields.', $html);
    }

    public function test_renders_success_as_status_and_is_dismissible_by_default(): void
    {
        $html = Blade::render(
            '<x-admin.admin-alert type="success" title="Saved" message="Changes saved." />'
        );

        $this->assertStringContainsString('class="alert alert-success admin-alert', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('admin-alert__close', $html);
    }
}
