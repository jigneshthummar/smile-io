<?php

namespace Mediact\Smile\Observer\Order;

use Magento\Framework\Event\Observer;
use Mediact\Smile\Observer\ObserverAbstract;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Customer;

class Update
    extends ObserverAbstract
{
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
     * @param Observer $observer
     * @return array
     */
    public function getEventBody(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var Customer $customer */
        $customer = $order->getCustomer();

        $data = [
            "external_id" => $order->getId(),
            "subtotal" => $order->getSubtotal(),
            "grand_total" => $order->getGrandTotal(),
            "rewardable_total" => $order->getGrandTotal(),
            "external_created_at" => $order->getCreatedAt(),
            "external_updated_at" => $order->getUpdatedAt(),
            "payment_status" => $order->getStatus(),
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