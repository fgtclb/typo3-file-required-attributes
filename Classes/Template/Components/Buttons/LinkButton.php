<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace WebVision\FileRequiredAttributes\Template\Components\Buttons;

/**
 * LinkButton
 *
 * This button type renders a regular anchor tag with TYPO3s way to render a
 * button control.
 *
 * EXAMPLE USAGE TO ADD A BUTTON TO THE FIRST BUTTON GROUP IN THE LEFT BAR:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $saveButton = $buttonBar->makeLinkButton()
 *      ->setHref('#')
 *      ->setDataAttributes([
 *          'foo' => 'bar'
 *      ])
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
 *      ->setTitle('Save');
 * $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 */
final class LinkButton extends \TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton
{
    /**
     * Validates the current button
     *
     * @return bool
     */
    public function isValid()
    {
        if (
            trim($this->getHref()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === self::class
            && $this->getIcon() !== null
        ) {
            return true;
        }
        return false;
    }

    /**
     * Renders the markup for the button
     *
     * @return string
     */
    public function render()
    {
        $html = parent::render();

        if (str_contains($html, 'required-attributes-missing')) {
            $html = str_replace('required-attributes-missing', '', $html);
            $html = str_replace('btn-default', 'btn-danger', $html);
        }

        return $html;
    }
}
