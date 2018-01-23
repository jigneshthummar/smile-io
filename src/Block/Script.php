<?php

namespace Mediact\Smile\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Customer\Model\Session;
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
     * The config path under which the private key is stored
     */
    const CONFIG_PATH_SMILE_SETTINGS_PRIVATE_KEY = 'smile/settings/private_key';

    /** @var Session */
    protected $customerSession;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;

        parent::__construct($context, $data);
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

    /**
     * Get the current customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * Get the private key from the Magento configuration.
     * This key is required as authentication for the API
     * calls to Smile.io
     *
     * @return string
     */
    public function getToken(): string
    {
        /** @var string $token */
        $token = $this->_scopeConfig->getValue(
            self::CONFIG_PATH_SMILE_SETTINGS_PRIVATE_KEY,
            ScopeInterface::SCOPE_STORE
        );

        return $token;
    }

    /**
     * Generate the customer authentication digest using
     * the customer ID and the Smile.io token.
     *
     * @return string
     */
    public function getCustomerAuthDigest(): string
    {
        $customerId = $this->getCustomerId();
        $token = $this->getToken();

        $digest = md5($customerId . '#' . $token);

        return $digest;
    }
}
