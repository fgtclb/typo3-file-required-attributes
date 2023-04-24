# EXT:file_required_attributes

## What does it do?

This extension offers the ability to set metadata information as required.
With required attributes, it provides the possibility to disable references
having missing attributes.

If metadata is set in file reference, too, the file
reference is updated.

If attribute only appears in metadata, a virtual field is added to reference,
enforcing the ability to update metadata from reference. A warning, this change
is made globally, is added to the field description.

## Installation

```shell
composer req fgtclb/file-required-attributes
```

## How to use

Add required field registration in `TCA/Overrides/sys_file_metadata.php` inside
your extension:

```php
<?php

declare(strict_types=1);

(static function (): void {
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register('copyright');
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register('alternative');
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register('title');
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register('description');
})();
```

This extension will handle all required steps by itself, you don't need to
handle with TCA.
