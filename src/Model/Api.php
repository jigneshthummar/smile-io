<?php

namespace Mediact\Smile\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Api
 */
class Api
{
    const API_EVENT_URL = 'https://api.sweettooth.io/v1/events';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Curl */
    private $curlAdapter;

    /** @var Json */
    private $jsonHelper;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl                 $curlAdapter
     * @param Json                 $jsonHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curlAdapter,
        Json $jsonHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curlAdapter = $curlAdapter;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Synchronise the customer to Smile.io.
     *
     * @param array $data
     *
     * @return bool
     */
    public function synchronizeCustomer(array $data): bool
    {
        $content = $this->generateBody('customer/updated', $data);

        return $this->call($content);
    }

    /**
     * Synchronise the order to Smile.io.
     *
     * @param array $data
     *
     * @return bool
     */
    public function synchronizeOrder($data): bool
    {
        $content = $this->generateBody('order/updated', $data);

        return $this->call($content);
    }

    /**
     * Make the call to the Smile.io API URL and sync the content send to this
     * method.
     *
     * @param array $content
     *
     * @return bool
     */
    private function call(array $content): bool
    {
        $headers = $this->getHeaders();

        // Make sure we only receive the body of the response
        $this->curlAdapter->write(
            'POST',
            self::API_EVENT_URL,
            '1.1',
            $headers,
            $this->jsonHelper->serialize($content)
        );

        $this->curlAdapter->read();
        $headerCode = $this->curlAdapter->getInfo(CURLINFO_HTTP_CODE);
        $this->curlAdapter->close();

        return (int) $headerCode === 202;
    }

    /**
     * Generate an array containing the headers that are required by the Smile.io
     * API.
     *
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ];
    }

    /**
     * Get the private key, used as the token, from the Magento configuration.
     *
     * @return string
     */
    private function getToken(): string
    {
        return $this->scopeConfig->getValue(
            'smile/settings/private_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Generate the body for the API call to Smile.io, based on the given
     * data by the customer or order synchronisation script.
     *
     * @param string $trigger
     * @param array $data
     *
     * @return array
     */
    private function generateBody(string $trigger, array $data): array
    {
        return [
            'event' => [
                'topic' => $trigger,
                'data' => $data
            ]
        ];
    }
}
