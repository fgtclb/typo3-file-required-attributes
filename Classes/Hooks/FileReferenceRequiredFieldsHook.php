<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Hooks;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileReferenceRequiredFieldsHook
{
    /**
     * @throws Exception
     * @throws DBALException
     */
    public function processDatamap_beforeStart(
        DataHandler $dataHandler
    ): void
    {
        if (empty($dataHandler->datamap['sys_file_reference'])) {
            return;
        }
        $data = [
            'sys_file_metadata' => [],
        ];
        $requiredColumnsMatrix = RequiredColumnsUtility::getRequiredColumnsFromTCA();

        foreach ($dataHandler->datamap['sys_file_reference'] as $id => $reference) {
            $fileId = null;
            // ID set, we have an original record
            if (MathUtility::canBeInterpretedAsInteger($id)) {
                $originalReference = $this->detectReference((int)$id);
            } else
                // string starts with NEW, relation new created
                if (str_starts_with((string)$id, 'NEW')) {
                    if (MathUtility::canBeInterpretedAsInteger($reference['uid_local'])) {
                        $fileId = $reference['uid_local'];
                    } else {
                        [, $fileId] = BackendUtility::splitTable_Uid((string)$reference['uid_local']);
                        if (!MathUtility::canBeInterpretedAsInteger($fileId)) {
                            throw new \InvalidArgumentException(
                                sprintf('Given file reference "%s" not usable', $reference['uid_local']),
                                1684163297042
                            );
                        }
                        $fileId = (int)$fileId;
                    }
                    $originalReference = [];
                } // fallback to table_id and split
                else {
                    [, $idSplit] = BackendUtility::splitTable_Uid((string)$id);
                    $originalReference = $this->detectReference((int)$idSplit);
                }
            if (array_key_exists('uid_local', $originalReference)) {
                $fileId = $originalReference['uid_local'];
            }

            if ($fileId === null) {
                // Cleanup, if one of the fields is set. Should NEVER be passed
                foreach ($requiredColumnsMatrix as $requiredColumns) {
                    foreach ($requiredColumns as $requiredColumn) {
                        if (!$this->isFieldPartOfReference($requiredColumn)) {
                            unset($dataHandler->datamap['sys_file_reference'][$id][$requiredColumn]);
                        }
                    }
                }
                continue;
            }

            $originalFile = $this->getFileRecord($fileId);

            $requiredColumns = $requiredColumnsMatrix[$originalFile['type']] ?? [];

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
                        !isset($originalReference[$requiredColumn])
                        && !isset($reference[$requiredColumn])
                        && !isset($sysFileMetaData[$requiredColumn])
                    ) {
                        $missingColumns[] = $requiredColumn;
                    }
                } else {
                    // check and update metadata
                    if (
                        !isset($sysFileMetaData[$requiredColumn])
                        && !isset($reference[$requiredColumn])
                    ) {
                        $missingColumns[] = $requiredColumn;
                    } elseif (isset($reference[$requiredColumn])) {
                        $data['sys_file_metadata'][$sysFileMetaData['uid']][$requiredColumn] = $reference[$requiredColumn];
                    }
                    // force unsetting virtual field
                    // isset would not match
                    // when value is empty or null,
                    // but removal must be enforced
                    if (array_key_exists($requiredColumn, $dataHandler->datamap['sys_file_reference'][$id])) {
                        unset($dataHandler->datamap['sys_file_reference'][$id][$requiredColumn]);
                    }
                }
            }

            if (
                count($missingColumns) > 0
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

                $missingColumnsLabels = [];
                foreach ($missingColumns as $missingColumn) {
                    $missingColumnsLabels[] = LocalizationUtility::translate(
                        $GLOBALS['TCA']['sys_file_metadata']['columns'][$missingColumn]['label']
                    ) ?? $missingColumn;
                }

                // DataHandler called in CLI mode, too.
                // To prevent a message overflow, while
                // import, check for environment
                if (!Environment::isCli()) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        LocalizationUtility::translate(
                            'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.notSet.body',
                            null,
                            [
                                $sysFile['name'],
                                implode('", "', $missingColumnsLabels)
                            ]
                        ),
                        LocalizationUtility::translate(
                            'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.notSet.header'
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
            ArrayUtility::mergeRecursiveWithOverrule($dataHandler->datamap, $data);
        }
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
                'file' => $id,
            ]
        );
        $metaData = $result->fetchAssociative();

        return $metaData ?: null;
    }

    /**
     * @return array<int|string, mixed>
     * @throws Exception
     * @throws DBALException
     */
    private function detectReference(int $id): array
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $db->getRestrictions()->removeByType(HiddenRestriction::class);
        $result = $db
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $db->expr()->eq('uid', $db->createNamedParameter($id, Connection::PARAM_INT))
            );
        $reference = $result->executeQuery()->fetchAssociative();

        return $reference ?: [];
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

    /**
     * @return array<int|string, mixed>
     */
    private function getFileRecord(int $fileId): array
    {
        return BackendUtility::getRecord(
            'sys_file',
            $fileId
        ) ?? [];
    }
}
