<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Service;

final class MetadataService
{
    public const SYS_FILE_METADATA = 'sys_file_metadata';

    public const SYS_FILE_REFERENCE = 'sys_file_reference';

    public function isFieldInMetadata(string $fieldName): bool
    {
        return in_array($fieldName, $this->getRegisteredFieldsForMetadata());
    }

    /**
     * @return string[]
     */
    public function getRegisteredFieldsForMetadata(): array
    {
        return array_keys($GLOBALS['TCA'][self::SYS_FILE_METADATA]['columns'] ?? []);
    }

    public function isFieldInReference(string $fieldName): bool
    {
        return in_array($fieldName, $this->getRegisteredFieldsForReference());
    }

    /**
     * @return string[]
     */
    public function getRegisteredFieldsForReference(): array
    {
        return array_keys($GLOBALS['TCA'][self::SYS_FILE_REFERENCE]['columns'] ?? []);
    }
}
