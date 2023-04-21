<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Hooks;

use Doctrine\DBAL\Driver\Exception;
use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileReferenceRequiredFieldsHook
{
    /**
     * @throws Exception
     */
    public function processDatamap_beforeStart(
        DataHandler $dataHandler
    ): void {
        if (empty($dataHandler->datamap['sys_file_reference'])) {
            return;
        }
        $data = [
            'sys_file_metadata' => [],
        ];
        $requiredColumns = RequiredColumnsUtility::getRequiredColumns();

        foreach ($dataHandler->datamap['sys_file_reference'] as $id => $reference) {
            if (array_key_exists('uid_local', $reference)) {
                $fileId = $reference['uid_local'];
            } else {
                if (MathUtility::canBeInterpretedAsInteger($id)) {
                    $originalReference = $this->detectReference((int)$id);
                } else {
                    [, $idSplit] = BackendUtility::splitTable_Uid((string)$id);
                    $originalReference = $this->detectReference((int)$idSplit);
                }
                if ($originalReference === null) {
                    continue;
                }
                $fileId = $originalReference['uid_local'];
            }
            $sysFileMetaData = $this->detectSysFileMetadataRecord($fileId);
            if ($sysFileMetaData === null) {
                $sysFileMetaData = [
                    'file' => $fileId,
                    'uid' => StringUtility::getUniqueId('NEW'),
                    'copyright' => '',
                ];
            }
            $missingColumns = [];
            foreach ($requiredColumns as $requiredColumn) {
                if ($this->isFieldPartOfReference($requiredColumn)) {
                    // check and update ref
                    if (
                        (
                            !array_key_exists($requiredColumn, $reference)
                            && empty($originalReference[$requiredColumn])
                        )
                        || empty($reference[$requiredColumn])
                    ) {
                        $missingColumns[] = $requiredColumn;
                    }
                } else {
                    // check and update metadata
                    if (
                        (
                            !array_key_exists($requiredColumn, $reference)
                            && empty($sysFileMetaData[$requiredColumn])
                        )
                        || empty($reference[$requiredColumn])
                    ) {
                        $missingColumns[] = $requiredColumn;
                    }
                }
            }
            if (array_key_exists('copyright', $reference)) {
                if ($reference['copyright'] !== null) {
                    $trimmedCopy = $this->trimNewCopyright($reference['copyright']);
                    if ($trimmedCopy != '') {
                        $data['sys_file_metadata'] = [
                            $sysFileMetaData['uid'] => [
                                'copyright' => $trimmedCopy,
                            ],
                        ];
                    }
                }
                unset($dataHandler->datamap['sys_file_reference'][$id]['copyright']);
            }

            if (
                !array_key_exists($sysFileMetaData['uid'], $data['sys_file_metadata'])
                && $sysFileMetaData['copyright'] == ''
                && (
                    !isset($dataHandler->datamap['sys_file_reference'][$id]['hidden'])
                    || $dataHandler->datamap['sys_file_reference'][$id]['hidden'] == 0
                )
            ) {
                $dataHandler->datamap['sys_file_reference'][$id]['hidden'] = 1;
                /** @var array<string, mixed> $sysFile */
                $sysFile = BackendUtility::getRecord(
                    'sys_file',
                    $sysFileMetaData['file']
                );

                // DataHandler called in CLI mode, too.
                // To prevent a message overflow, while
                // import, check for environment
                if (!Environment::isCli()) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        LocalizationUtility::translate(
                            'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.copyright.notSet.body',
                            null,
                            [
                                $sysFile['name'],
                            ]
                        ),
                        LocalizationUtility::translate(
                            'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.copyright.notSet.header'
                        ),
                        FlashMessage::WARNING,
                        true
                    );

                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $messageQueue->addMessage($message);
                }
            }
        }
        if (count($data['sys_file_metadata']) > 0) {
            $dataHandler->datamap = array_merge_recursive($dataHandler->datamap, $data);
        }
    }

    /**
     * Removes HTML entity &copy; and ©, if set
     *
     * @param string $copyright
     * @return string
     */
    private function trimNewCopyright(string $copyright): string
    {
        $copyright = str_replace(['&copy;', '©'], '', $copyright);
        return trim($copyright);
    }

    /**
     * @param int|string $field
     * @return array<int|string, mixed>|null
     * @throws Exception
     */
    private function detectSysFileMetadataRecord(mixed $field): ?array
    {
        if (MathUtility::canBeInterpretedAsInteger($field)) {
            $id = (int)$field;
        } else {
            [, $id] = BackendUtility::splitTable_Uid((string)$field);
        }

        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata');
        $result = $db->select(
            ['*'],
            'sys_file_metadata',
            [
                'uid' => $id,
            ]
        );
        $metaData = $result->fetchAssociative();

        return $metaData ?: null;
    }

    /**
     * @return array<int|string, mixed>|null
     * @throws Exception
     */
    private function detectReference(int $id): ?array
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference');
        $result = $db->select(
            ['*'],
            'sys_file_reference',
            [
                'uid' => $id,
            ]
        );
        $reference = $result->fetchAssociative();

        return $reference ?: null;
    }

    private function isFieldPartOfReference(string $field): bool
    {
        $fieldExists = false;
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference')
            ->getSchemaManager()
            ->listTableColumns('sys_file_reference');
        foreach ($columns as $column) {
            if ($column->getName() === $field) {
                $fieldExists = true;
                break;
            }
        }
        return $fieldExists;
    }
}
