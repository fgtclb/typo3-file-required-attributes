#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Based on Build/Scripts/checkIntegrityXliff.php of the TYPO3 core, adjusted to the extension
 * layout. symfony/translation is no dependency of the extension, so the XLIFF schema itself is
 * not validated. In addition, the target-language of each file is checked: TYPO3 14 treats a
 * file declaring one, even an empty one, as a translation.
 */

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;

require __DIR__ . '/../../.Build/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

final class CheckIntegrityXliff
{
    private const EXTENSION_KEY = 'file_required_attributes';
    private const NAMESPACE_XLIFF_12 = 'urn:oasis:names:tc:xliff:document:1.2';

    public function execute(): int
    {
        $output = new ConsoleOutput();
        $finder = (new Finder())
            ->files()
            ->in(__DIR__ . '/../../Resources/Private/Language/')
            ->name('*.xlf');

        $checked = 0;
        $errors = [];
        foreach ($finder as $labelFile) {
            $checked++;
            $error = $this->check($labelFile->getRealPath(), $labelFile->getRelativePathname());
            if ($error !== null) {
                $errors[$labelFile->getRelativePathname()] = $error;
            }
        }

        if ($checked === 0) {
            $output->writeln('<error>No XLIFF file found.</error>');
            return 1;
        }
        foreach ($errors as $file => $error) {
            $output->writeln(sprintf('<error>%s:</error> %s', $file, $error));
        }
        if ($errors !== []) {
            $output->writeln(sprintf('<error>%d error(s) found in %d files.</error>', count($errors), $checked));
            return 1;
        }
        $output->writeln(sprintf('<info>%d XLIFF files are fine.</info>', $checked));
        return 0;
    }

    private function check(string $path, string $relativePath): ?string
    {
        $xml = simplexml_load_file($path);
        if ($xml === false) {
            return 'XML not parsable';
        }
        $version = (string)($xml->attributes()['version'] ?? '');
        if ($version !== '1.2') {
            return sprintf('Incompatible version "%s" (expected: 1.2)', $version);
        }
        $namespaces = $xml->getNamespaces(true);
        if ($namespaces !== ['' => self::NAMESPACE_XLIFF_12]) {
            return sprintf('Invalid XLIFF namespace %s (expected: %s)', json_encode($namespaces), self::NAMESPACE_XLIFF_12);
        }

        $file = $xml->file->attributes();
        if ((string)($file['source-language'] ?? '') !== 'en') {
            return sprintf('Invalid source-language "%s"', (string)($file['source-language'] ?? ''));
        }
        if ((string)($file['datatype'] ?? '') !== 'plaintext') {
            return sprintf('Invalid datatype "%s"', (string)($file['datatype'] ?? ''));
        }
        $sourceFile = preg_replace('/^[a-z]{2}(_[A-Z]{2})?\./', '', $relativePath);
        $expectedOriginals = [
            'EXT:' . self::EXTENSION_KEY . '/Resources/Private/Language/' . $sourceFile,
            'messages',
        ];
        $original = (string)($file['original'] ?? '');
        if (!in_array($original, $expectedOriginals, true)) {
            return sprintf('Invalid original "%s" (expected: %s)', $original, implode(', ', $expectedOriginals));
        }
        $date = (string)($file['date'] ?? '');
        if (strtotime($date) === false || strtotime($date) === 0) {
            return sprintf('Invalid date "%s"', $date);
        }

        $language = preg_match('/^([a-z]{2}(?:_[A-Z]{2})?)\./', $relativePath, $matches) ? $matches[1] : null;
        if ($language === null && isset($file['target-language'])) {
            return 'A source file must not declare a target-language';
        }
        if ($language !== null && (string)($file['target-language'] ?? '') !== $language) {
            return sprintf('Invalid target-language "%s" (expected: %s)', (string)($file['target-language'] ?? ''), $language);
        }

        $xml->registerXPathNamespace('x', self::NAMESPACE_XLIFF_12);
        $seenIds = [];
        foreach ($xml->xpath('/x:xliff/x:file/x:body/x:trans-unit') ?: [] as $unit) {
            $id = (string)($unit->attributes()['id'] ?? '');
            if ($id === '') {
                return 'trans-unit without id';
            }
            if (isset($seenIds[$id])) {
                return sprintf('Duplicate trans-unit id "%s"', $id);
            }
            if ($language !== null && !isset($unit->target)) {
                return sprintf('trans-unit "%s" has no target', $id);
            }
            $seenIds[$id] = true;
        }
        return null;
    }
}

exit((new CheckIntegrityXliff())->execute());
