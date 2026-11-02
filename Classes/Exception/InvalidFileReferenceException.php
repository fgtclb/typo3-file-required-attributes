<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Exception;

/**
 * Thrown when a new file reference names its file in a form that does not resolve to a file uid.
 */
final class InvalidFileReferenceException extends \InvalidArgumentException {}
