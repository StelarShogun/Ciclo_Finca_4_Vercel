<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyUiInventoryTest extends TestCase
{
    public function test_legacy_ui_inventory_does_not_grow(): void
    {
        $backendRoot = dirname(__DIR__, 2);
        $baselinePath = $backendRoot.'/docs/legacy-ui-baseline.json';
        $scriptPath = $backendRoot.'/scripts/audit-legacy-ui.php';

        $baseline = json_decode((string) file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);
        $current = json_decode((string) shell_exec('php '.escapeshellarg($scriptPath).' --json'), true, flags: JSON_THROW_ON_ERROR);

        $baselineLegacy = $this->legacySurface($baseline);
        $currentLegacy = $this->legacySurface($current);

        $newFindings = array_values(array_diff($currentLegacy, $baselineLegacy));

        $this->assertSame([], $newFindings, 'New legacy UI findings: '.implode(', ', $newFindings));
    }

    /**
     * @return list<string>
     */
    private function legacySurface(array $report): array
    {
        $findings = [];

        foreach ($report['findings'] as $finding) {
            if (! in_array($finding['classification'], ['delete', 'migrate-first', 'unknown'], true)) {
                continue;
            }

            $findings[] = implode('|', [
                $finding['classification'],
                $finding['kind'],
                $finding['path'],
                $finding['symbol'],
            ]);
        }

        sort($findings);

        return $findings;
    }
}
