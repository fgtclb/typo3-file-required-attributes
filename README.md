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

## Create a release (maintainers only)

Prerequisites:

* git binary
* ssh key allowed to push new branches to the repository
* GitHub command line tool `gh` installed and configured with user having permission to create pull requests.
* [`tailor`](https://github.com/TYPO3/tailor) and `composer` installed globally

**Create release**

> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main'.
> Set `RELEASE_VERSION` to release version working on, for example: '3.0.0'.
> Set `DEV_VERSION` to the version following the release, for example: '3.0.1'.

> [!IMPORTANT]
> Requires `GitHub cli tool` with personal token and
> maintainer permission on the extension repository.

```bash
echo '>> Create release' ; \
  RELEASE_BRANCH='main' ; \
  RELEASE_VERSION='3.0.0' ; \
  DEV_VERSION='3.0.1' ; \
  echo ">> Checkout branches" && \
  git checkout ${RELEASE_BRANCH} && \
  git fetch --all && \
  git pull --rebase && \
  echo ">> Create release ${RELEASE_VERSION}" && \
  git checkout -b release-${RELEASE_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${RELEASE_VERSION}\"/" Build/Scripts/runTests.sh && \
  sed -i "s/^  RELEASE_VERSION.*/  RELEASE_VERSION='${RELEASE_VERSION}' ; \\\\/" README.md && \
  sed -i "s/^  DEV_VERSION.*/  DEV_VERSION='${DEV_VERSION}' ; \\\\/" README.md && \
  tailor set-version ${RELEASE_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${RELEASE_VERSION}" && \
  echo "${RELEASE_VERSION}" > VERSION && \
  git add . && \
  git commit -m "[RELEASE] ${RELEASE_VERSION}" && \
  git push --set-upstream origin release-${RELEASE_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[RELEASE] ${RELEASE_VERSION}" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin && \
  git tag ${RELEASE_VERSION} -m "${RELEASE_VERSION}" && \
  git push origin ${RELEASE_VERSION} && \
  echo ">> Post-release - set dev version: ${DEV_VERSION}-dev" && \
  git checkout -b set-dev-version-${DEV_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${DEV_VERSION}-dev\"/" Build/Scripts/runTests.sh && \
  tailor set-version ${DEV_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${DEV_VERSION}-dev" && \
  echo "${DEV_VERSION}-dev" > VERSION && \
  git add . && \
  git commit -m "[TASK] Set dev version ${DEV_VERSION}" && \
  git push --set-upstream origin set-dev-version-${DEV_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[TASK] Set dev version \"${DEV_VERSION}-dev\"" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin
```

Pushing the tag triggers the `on push tags` workflow (`publish.yml`), which creates the upload package and the
GitHub release with the package attached. The upload to the TYPO3 Extension Repository is not part of it and has
to be done by the owner of the extension key.

## License

This extension is released under the [GPL-3.0-or-later](LICENSE) license.
