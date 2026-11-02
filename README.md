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

## Compatibility

| Extension | TYPO3                   | PHP       |
|-----------|-------------------------|-----------|
| 3.x       | 13.4 (>= 13.4.19), 14.3 | 8.2 - 8.5 |
| 2.x       | 12.4, 13.4              | 8.1 - 8.4 |

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
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register(
        'copyright',
        [
            \TYPO3\CMS\Core\Resource\FileType::IMAGE->value,
            // ...
        ]
    );
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register(
        'alternative',
        [
            \TYPO3\CMS\Core\Resource\FileType::IMAGE->value,
            // ...
        ]
    );
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register(
        'title',
        [
            \TYPO3\CMS\Core\Resource\FileType::IMAGE->value,
        ]
    );
    \FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility::register(
        'description',
        [
            \TYPO3\CMS\Core\Resource\FileType::IMAGE->value,
        ]
    );
})();
```

This extension will handle all required steps by itself, you don't need to
handle with TCA.

A required column counts as missing when it is empty or holds only whitespace. A required radio, select
or check column also counts as missing on `0`, the value of its "not set" item.

## Supported Versions

| Version | Supported          | End of Support |
|---------|--------------------|----------------|
| 3.x     | :white_check_mark: | 2029-06-30     |
| 2.x     | :white_check_mark: | 2027-12-31     |
| < 2.0   | :x:                | support ended  |

## Security

Found a vulnerability? Please report it privately via our
[security report form](https://security.fgtclb.com) — **do not** open a public issue.
See [SECURITY.md](SECURITY.md) for the full vulnerability disclosure policy,
including what to expect and our safe harbor statement.

## Simplified EU Declaration of Conformity (Annex VI)

> Hereby, web-vision GmbH declares that the product with digital elements
> type FGTCLB File required attributes is in compliance with Regulation (EU) 2024/2847.
>
> The full text of the EU declaration of conformity is available at the
> following internet address:
> https://security.fgtclb.com/conformity/fgtclb/file-required-attributes/2.1.0/en/

The full declarations are also included in this repository:
[English](EU-Declaration-of-Conformity.md) ·
[Deutsch](EU-Konformitaetserklaerung.md).

## License

This extension is released under the [GPL-3.0-or-later](LICENSE) license.
