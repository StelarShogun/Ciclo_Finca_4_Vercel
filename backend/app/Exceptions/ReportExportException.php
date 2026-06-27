<?php

namespace App\Exceptions;

final class ReportExportException extends DomainException
{
    public function __construct(string $message, private readonly int $status = 400)
    {
        parent::__construct($message);
    }

    public static function unknownReport(): self
    {
        return new self('Reporte no encontrado.', 404);
    }

    public static function invalidFormat(): self
    {
        return new self('Formato no válido. Use pdf, excel o csv.', 400);
    }

    public function status(): int
    {
        return $this->status;
    }
}
