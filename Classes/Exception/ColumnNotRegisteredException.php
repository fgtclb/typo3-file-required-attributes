<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Exception;

/**
 * Thrown when a column is registered as required that the TCA of "sys_file_metadata" does not define.
 */
final class ColumnNotRegisteredException extends \RuntimeException {}
