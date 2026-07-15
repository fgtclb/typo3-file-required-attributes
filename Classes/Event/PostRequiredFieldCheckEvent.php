<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Event;

final class PostRequiredFieldCheckEvent
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $requiredColumns
     */
    public function __construct(private readonly array $data, private array $requiredColumns)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredColumns(): array
    {
        return $this->requiredColumns;
    }

    /**
     * @param array<int, string> $requiredColumns
     */
    public function setRequiredColumns(array $requiredColumns): void
    {
        $this->requiredColumns = $requiredColumns;
    }
}
