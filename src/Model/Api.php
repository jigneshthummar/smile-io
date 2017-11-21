<?php

namespace Mediact\Smile\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Api
 *
 * @package Mediact\Smile\Model
 */
class Api
{
    const API_EVENT_URL = 'https://api.sweettooth.io/v1/events';

    private $scopeConfig;
    private $curlAdapter;
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
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->curlAdapter = $curlAdapter;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param array $data
     *
     * @return boolean
     */
    public function synchroniseCustomer(array $data)
    {
        $headers = $this->getHeaders();
        $content = $this->generateBody('customer/updated', $data);
        return $this->call($headers, $content);
    }

    /**
     * @param array $data
     *
     * @return boolean
     */
    public function synchroniseOrder(array $data)
    {
        $headers = $this->getHeaders();
        $content = $this->generateBody('order/updated', $data);
        return $this->call($headers, $content);
    }

    /**
     * @param $headers
     * @param $content
     *
     * @return boolean
     */
    private function call($headers, $content)
    {
        // Make sure we only receive the body of the response
        $this->curlAdapter->setConfig(['header' => '']);
        $this->curlAdapter->write(
            'POST',
            self::API_EVENT_URL,
            '1.1',
            $headers,
            $this->jsonHelper->serialize($content)
        );

        $response = $this->curlAdapter->read();
        $headerCode = $this->curlAdapter->getInfo(CURLINFO_HTTP_CODE);

        $this->curlAdapter->close();

        return $headerCode >= 200 && $headerCode <= 202;
    }

    /**
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
     *
     * @return mixed
     */
    private function getToken(): string
    {
        return $this->scopeConfig->getValue(
            'smile/settings/private_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $trigger
     * @param $data
     *
     * @return array
     */
    private function generateBody($trigger, $data): array
    {
        return [
            'event' => [
                'topic' => $trigger,
                'data' => $data
            ]
        ];
    }
}