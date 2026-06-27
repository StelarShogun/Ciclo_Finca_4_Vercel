<?php

namespace App\DTOs\Admin\Reports;

final readonly class RegistryReportData
{
    /**
     * @param  array<int, string>  $filterLines
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(
        public string $title,
        public string $subtitle,
        public string $filenameSlug,
        public array $filterLines,
        public array $headers,
        public array $rows,
    ) {}
}
