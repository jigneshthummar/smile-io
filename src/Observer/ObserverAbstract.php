<?php

namespace Mediact\Smile\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Message\ManagerInterface;

abstract class ObserverAbstract
    implements ObserverInterface
{
    const API_EVENT_URL = 'https://api.sweettooth.io/v1/events';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Curl
     */
    protected $curlAdapter;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        LoggerInterface $logger,
        Curl $curlAdapter,
        Data $jsonHelper,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->curlAdapter = $curlAdapter;
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute the event observer. The required data
     * is defined by the actual called observers.
     *
     * If the extension is disabled in the configuration,
     * the script will stop immediately.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->isEnabled()) {
            return $this;
        }

        $this->debug(
            "Sending webhook for event " . $this->getEventCode() . " to " . self::API_EVENT_URL
        );

        /** @var string $eventCode */
        $eventCode = $this->getEventCode();

        /** @var array $eventBody */
        $eventBody = $this->getEventBody($observer);

        /** @var string $response */
        $response = $this->call($eventCode, $eventBody);

        $this->logResponse($response);

        return $this;
    }

    /**
     * Not yet sure if this is the
     *
     * @todo check if this method is required. We can only see that if we have a valid return.
     * @param array $response
     */
    protected function logResponse($response)
    {
        switch ($response['httpcode']) {
            case 200:
            case 202:
                $this->debug('Everything worked as expected', 200);
                break;

            default:
                $this->debug($response['response']['error']['message'], $response['httpcode']);
                break;
        }
    }

    /**
     * Add a debug message to the logs, based on the HTTP code.
     * If no HTTP code is returned, it will automatically be a
     * debug message.
     *
     * The used codes are the return codes defined in the Smile.io
     * documentation
     *
     * @see https://docs.smile.io/docs/errors
     * @param string $message
     * @param int $httpCode
     * @return $this
     */
    protected function debug($message, $httpCode = 100)
    {
        if (!$this->scopeConfig->getValue('smile/settings/debug')) {
            return $this;
        }

        switch ($httpCode) {
            case 100:
                $this->logger->debug($message);
                break;

            case 200:
            case 202:
                $this->logger->info($message);
                break;

            case 400:
            case 401:
            case 402:
            case 404:
            case 429:
                $this->logger->error($message);
                break;

            case 500:
                $this->logger->critical($message);
                break;
        }

        return $this;
    }

    /**
     * Make the actual API call to Smile.io.
     *
     * @param string $trigger
     * @param array $data
     * @return array
     */
    protected function call($trigger, $data)
    {
        /** @var array $body */
        $body = [
            'event' => [
                'topic' => $trigger,
                'data' => $data
            ]
        ];

        /** @var array $headers */
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ];

        // Make sure we only receive the body of the response
        $this->curlAdapter->setConfig(['header' => '']);
        $this->curlAdapter->write(
            'POST',
            self::API_EVENT_URL,
            '1.1',
            $headers,
            $this->jsonHelper->jsonEncode($body)
        );

        $response = $this->curlAdapter->read();
        $headerCode = $this->curlAdapter->getInfo(CURLINFO_HTTP_CODE);

        $this->curlAdapter->close();

        return [
            'httpcode' => $headerCode,
            'response' => !empty($response) ? $this->jsonHelper->jsonDecode($response) : ''
        ];
    }

    protected function parseHeaders($curl, $headers)
    {
        var_dump($headers); exit;
    }

    /**
     * Check if the extension is enabled. If disabled, none of
     * the API calls should be triggered.
     *
     * @return boolean
     */
    protected function isEnabled()
    {
        return $this->scopeConfig->getValue(
            'smile/settings/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the private key from the Magento configuration.
     * This key is required as authentication for the API
     * calls to Smile.io
     *
     * @return string
     */
    protected function getToken()
    {
        /** @var string $token */
        $token = $this->scopeConfig->getValue(
            'smile/settings/private_key',
            ScopeInterface::SCOPE_STORE
        );

        return $token;
    }

    /**
     * Get the event code. The possible codes are
     * defined by Smile.io
     *
     * @see https://docs.smile.io/docs/event
     * @return string
     */
    abstract public function getEventCode();

    /**
     * Get the body of the API call. The returned value
     * should be an array, containing the data required
     * by Smile.io.
     *
     * @see https://docs.smile.io/docs/event
     * @param Observer $observer
     * @return array
     */
    abstract public function getEventBody(Observer $observer);
}