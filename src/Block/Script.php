<?php

namespace Mediact\Smile\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Script
 */
class Script extends Template implements BlockInterface
{
    /**
     * The config path under which the enabled setting is stored
     */
    const CONFIG_PATH_SMILE_SETTINGS_ENABLED = 'smile/settings/enabled';

    /**
     * Constructor.
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {

        parent::__construct($context, $data);
    }

    /**
     * If the extension is not enabled, the block doesn't need
     * to be loaded at all.
     *
     * @return string
     */
    public function toHtml()
    {
        if (!$this->isEnabled()) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * Check if the extension is enabled. If disabled, none of
     * the API calls should be triggered.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::CONFIG_PATH_SMILE_SETTINGS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
