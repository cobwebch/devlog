<?php
namespace Devlog\Devlog\Template\Components\Buttons;

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

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;

/**
 * Extends the \TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton class
 * with a "target" attribute for the button link.
 *
 * @package Devlog\Devlog\Template\Components\Buttons
 */
class ExtendedLinkButton extends LinkButton
{
    /**
     * @var string Target for the button hyperlink
     */
    protected $target = '';

    /**
     * Sets the value of the target attribute.
     *
     * @param $target
     * @return ExtendedLinkButton
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Returns the value of the target attribute.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Overrides the parent render method to insert the target attribute in the button link.
     *
     * @return string
     */
    public function render()
    {
        // Get the rendered button and insert the target attribute
        $button = parent::render();
        if ($this->target !== '') {
            $button = preg_replace('/(href=".*?")/', '$1 target="' . $this->target .'"', $button);
        }
        return $button;
    }

    /**
     * Validates the current button.
     *
     * Since a class check is included, we need to override the parent class validation.
     *
     * @return bool
     */
    public function isValid()
    {
        if (
            trim($this->getHref()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === ExtendedLinkButton::class
            && $this->getIcon() !== null
        ) {
            return true;
        }
        return false;
    }
}