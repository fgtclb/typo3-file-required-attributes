<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Backend\Form\Element;

use FGTCLB\FileRequiredAttributes\Service\MetadataService;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class VirtualFieldElement extends AbstractFormElement
{
    private EventDispatcher $eventDispatcher;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    /**
     * @return array{
     *     additionalJavaScriptPost: array<array-key, string>,
     *     additionalHiddenFields: array<array-key, string>,
     *     additionalInlineLanguageLabelFiles: array<array-key, string>,
     *     stylesheetFiles: array<array-key, string>,
     *     requireJsModules: array<array-key, string|array<non-empty-string, non-empty-string>>,
     *     inlineData: array<array-key, mixed>,
     *     html: string
     * }
     */
    public function render(): array
    {
        $fieldInformationResult = $this->renderFieldInformation();
        $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);
        $parameters = $this->data['parameterArray'];
        $originalField = $parameters['fieldConf']['config']['parameters']['originalField'];
        $originalConfiguration = $parameters['fieldConf']['config']['parameters']['originalConfiguration'];
        $defaultNodeData = $this->data;

        $defaultNodeData['fieldName'] = $originalField;
        $defaultNodeData['databaseRow'] = $this->getMetadataRow();
        $defaultNodeData['parameterArray']['itemFormElValue'] = $defaultNodeData['databaseRow'][$originalField];
        $defaultNodeData['parameterArray']['fieldConf']['config'] = $originalConfiguration['config'];

        $overrideHtml = '';
        if (empty($defaultNodeData['databaseRow'][$originalField])) {
            $defaultNodeData['renderType'] = $originalConfiguration['config']['renderType'] ?? $originalConfiguration['config']['type'];

            $defaultNodeData['parameterArray']['fieldConf']['config']['eval'] = 'required';
        } else {
            $defaultNodeData['renderType'] = 'none';

            $manipulateData = $this->data;
            $manipulateData['renderType'] = $originalConfiguration['config']['renderType'] ?? $originalConfiguration['config']['type'];
            $manipulateData['fieldName'] = $originalField;
            $manipulateData['databaseRow'] = $this->getMetadataRow();
            $manipulateData['parameterArray']['itemFormElValue'] = null;
            $manipulateData['parameterArray']['fieldConf']['label'] = $originalConfiguration['label'];
            if ($this->data['parameterArray']['fieldConf']['config']['parameters']['override'] === true) {
                $manipulateData['parameterArray']['fieldConf']['description'] = 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.description';
            }
            $manipulateData['parameterArray']['fieldConf']['config'] = $originalConfiguration['config'];
            $manipulateData['parameterArray']['fieldConf']['config']['mode'] = 'useOrOverridePlaceholder';
            $manipulateData['parameterArray']['fieldConf']['config']['eval'] = 'null';
            $originalNode = $this->nodeFactory->create($manipulateData);
            $overrideHtml = $originalNode->render()['html'];
        }

        $defaultNode = $this->nodeFactory->create($defaultNodeData);

        $resultArray['html'] = $defaultNode->render()['html'] . $overrideHtml;
        return $resultArray;
    }

    private function getMetadataRow(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(MetadataService::SYS_FILE_METADATA);
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $query = $queryBuilder
            ->select('*')
            ->from(MetadataService::SYS_FILE_METADATA)
            ->where(
                $queryBuilder->expr()->eq(
                    'file',
                    $queryBuilder->createNamedParameter($this->data['databaseRow']['uid_local'][0]['row']['uid'] ?? 0, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);
        $result = $query->executeQuery();
        $row = $result->fetchAssociative();
        return $row ?: [];
    }
}
