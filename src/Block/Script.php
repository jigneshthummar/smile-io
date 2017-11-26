<?php

namespace Mediact\Smile\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Customer\Model\Session;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Script
 *
 * @package Mediact\Smile\Block
 */
class Script extends Template implements BlockInterface
{
    /**
     * @var Session
     */
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
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_scopeConfig->getValue(
            'smile/settings/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the current customer ID
     *
     * @return mixed
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
    public function getToken()
    {
        /** @var string $token */
        $token = $this->_scopeConfig->getValue(
            'smile/settings/private_key',
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
    public function getCustomerAuthDigest()
    {
        $customerId = $this->getCustomerId();
        $token = $this->getToken();

        $digest = md5($customerId . '#' . $token);

        return $digest;
    }
}
