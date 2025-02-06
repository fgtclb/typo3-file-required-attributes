<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Event;

final class PostRequiredFieldCheckEvent
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<int, string>
     */
    private array $requiredColumns;

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $requiredColumns
     */
    public function __construct(
        array $data,
        array $requiredColumns
    ) {
        $this->data = $data;
        $this->requiredColumns = $requiredColumns;
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
