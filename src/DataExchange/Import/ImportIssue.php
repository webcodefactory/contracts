<?php

declare(strict_types=1);

namespace Alumateria\Contracts\DataExchange\Import;


final class ImportIssue implements \JsonSerializable
{
    private function __construct(
        public readonly IssueSeverity $severity,
        public readonly string $message,
        public readonly ?int $row,
        public readonly ?string $column,
    ) {
    }

    public static function error(string $message, ?int $row = null, ?string $column = null): self
    {
        return new self(IssueSeverity::ERROR, $message, $row, $column);
    }

    public static function warning(string $message, ?int $row = null, ?string $column = null): self
    {
        return new self(IssueSeverity::WARNING, $message, $row, $column);
    }

    /**
     * @return array{severity: string, message: string, row: int|null, column: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'severity' => $this->severity->value,
            'message' => $this->message,
            'row' => $this->row,
            'column' => $this->column,
        ];
    }
}
