<?php

namespace Mediact\Smile\Observer\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Mediact\Smile\Observer\ObserverAbstract;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class Update
    extends ObserverAbstract
{
    protected $customerSession;

    public function __construct(
        LoggerInterface $logger,
        Curl $curlAdapter,
        Data $jsonHelper,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;

        parent::__construct($logger, $curlAdapter, $jsonHelper, $scopeConfig, $messageManager);
    }

    /**
     * Return the event code for updating an order.
     *
     * @return string
     */
    public function getEventCode()
    {
        return 'order/updated';
    }

    /**
     * Return the data for the order that needs to be
     * synced with Smile.io
     *
     * @todo The payment status should be set to paid, after the order is actually paid
     * @param Observer $observer
     * @return array
     */
    public function getEventBody(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var Customer $customer */
        $customer = $this->getCustomer($order);

        $data = [
            "external_id" => $order->getId(),
            "subtotal" => $order->getSubtotal(),
            "grand_total" => $order->getGrandTotal(),
            "rewardable_total" => $order->getGrandTotal(),
            "external_created_at" => $order->getCreatedAt(),
            "external_updated_at" => $order->getUpdatedAt(),
            "payment_status" => "paid",
            "coupons" => $this->getCouponCodes($order),
            "customer" => [
                "external_id" => $customer->getId(),
                "first_name" => $customer->getFirstname(),
                "last_name" => $customer->getLastname(),
                "email" => $customer->getEmail(),
                "external_created_at" => $customer->getCreatedAt(),
                "external_updated_at" => $customer->getUpdatedAt(),
            ]
        ];

        return $data;
    }

    /**
     * @return bool|Customer
     */
    protected function getCustomer()
    {
        if (!$this->customerSession->getCustomerId()) {
            return false;
        }

        /** @var Customer $customer */
        $customer = $this->customerSession->getCustomer();

        return $customer;
    }

    /**
     * Fetch the used coupon codes. Smile.io has the possibility
     * to support several coupon codes per order, but since
     * Magento only supports one coupon code per order, we
     * don't need a loop here.
     *
     * @param Order $order
     * @return array
     */
    protected function getCouponCodes($order)
    {
        $couponCode = $order->getCouponCode();

        if (!$couponCode) {
            return [];
        }

        return ['code' => $couponCode];
    }
}