<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Event;

final class TcaRequiredFlagEvent
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    private string $currentField;

    public function __construct(
        array $data,
        string $currentField
    ) {
        $this->data = $data;
        $this->currentField = $currentField;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCurrentField(): string
    {
        return $this->currentField;
    }
}
