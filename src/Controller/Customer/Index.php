<?php

namespace Mediact\Smile\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config;

class Index extends Action
{
    /**
     * The config path under which the private key is stored
     */
    const CONFIG_PATH_SMILE_SETTINGS_PRIVATE_KEY = 'smile/settings/private_key';

    /**
     * The config path under which the public key is stored
     */
    const CONFIG_PATH_SMILE_SETTINGS_PUBLIC_KEY = 'smile/settings/public_key';

    /** @var Session */
    private $customerSession;

    /** @var JsonFactory */
    private $jsonFactory;

    /** @var Config */
    private $scopeConfig;

    /**
     * Constructor
     *
     * @param Context     $context
     * @param Session     $customerSession
     * @param JsonFactory $jsonFactory
     * @param Config      $scopeConfig
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        JsonFactory $jsonFactory,
        Config $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $resultPage = $this->jsonFactory->create();
        $data = [
            'apiKey'     => $this->getPublicKey(),
            'customerId' => $this->getCustomerId(),
            'authDigest' => $this->getCustomerAuthDigest()
        ];

        return $resultPage->setData($data);
    }

    /**
     * Get the current customer ID
     *
     * @return int|null
     */
    private function getCustomerId()
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
    private function getPrivateKey(): string
    {
        /** @var string $token */
        $token = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SMILE_SETTINGS_PRIVATE_KEY,
            ScopeInterface::SCOPE_STORE
        );

        return $token;
    }

    /**
     * Get the public key from the Magento configuration.
     * This key is required as authentication for the API
     * calls to Smile.io
     *
     * @return string
     */
    private function getPublicKey(): string
    {
        /** @var string $token */
        $token = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SMILE_SETTINGS_PUBLIC_KEY,
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
    private function getCustomerAuthDigest(): string
    {
        $customerId = $this->getCustomerId();
        $token = $this->getPrivateKey();

        $digest = md5($customerId . '#' . $token);

        return $digest;
    }
}
