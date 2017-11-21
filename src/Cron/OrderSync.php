<?php

namespace Mediact\Smile\Cron;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\OrderRepository;
use Mediact\Smile\Model\Api;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

use function date;

/**
 * Class OrderSync
 */
class OrderSync
{
    /** @var LoggerInterface */
    private $logger;

    /** @var CollectionFactory */
    private $orderCollection;

    /** @var OrderFactory */
    private $orderFactory;

    /** @var Api */
    private $apiModel;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /**
     * Constructor.
     *
     * @param LoggerInterface   $logger
     * @param CollectionFactory $orderCollection
     * @param OrderFactory      $orderFactory
     * @param Api               $apiModel
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $orderCollection,
        OrderFactory $orderFactory,
        Api $apiModel,
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->logger = $logger;
        $this->orderCollection = $orderCollection;
        $this->orderFactory = $orderFactory;
        $this->apiModel = $apiModel;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get the Smile.io API model.
     *
     * @return Api
     */
    private function getApi()
    {
        return $this->apiModel;
    }

    /**
     * Update the order collection to Smile.io. Only orders that are updated
     * since the last synchronisation with Smile.io will be processed.
     *
     * @return void
     */
    public function update()
    {
        /** @var CollectionFactory $collection */
        $collection = $this->getOrderCollection();

        /** @var Order $order */
        foreach ($collection as $order) {
            if (!$order->getCustomerId()) {
                continue;
            }

            /** @var CustomerInterface $customer */
            $customer = $this->getCustomer($order->getCustomerId());
            $data = [
                "external_id" => $order->getId(),
                "subtotal" => $order->getSubtotal(),
                "grand_total" => $order->getGrandTotal(),
                "rewardable_total" => $order->getGrandTotal(),
                "external_created_at" => $order->getCreatedAt(),
                "external_updated_at" => $order->getUpdatedAt(),
                "payment_status" => $this->getOrderPaymentStatus($order),
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

            if ($this->getApi()->synchroniseOrder($data)) {
                /** @todo This should be fixed using service contracts */
                $order->setData('smileio_synchronised_at', date('Y-m-d H:i:s'));
                $order->save();
            }
        }
    }

    /**
     * Get a collection of the orders that are updated after they're last
     * synced with Smile.io.
     *
     * @return Collection
     */
    private function getOrderCollection(): Collection
    {
        $collection = $this->orderCollection->create();
        $collection->addFieldToFilter(
            'smileio_synchronised_at',
            [
                ['lt' => new Zend_Db_Expr('updated_at')],
                ['null' => true]
            ]
        );

        return $collection;
    }


    /**
     * Load the customer based on the customer_id of the current order.
     *
     * @param $customerId
     *
     * @return CustomerInterface
     */
    private function getCustomer($customerId): CustomerInterface
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->getById($customerId);

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
    private function getCouponCodes($order): array
    {
        $couponCode = $order->getCouponCode();

        if (!$couponCode) {
            return [];
        }

        return ['code' => $couponCode];
    }

    /**
     * Get the payment status used in Smile.io of the order based on the
     * grand, invoiced and refunded total. If an order is completely invoiced,
     * the status is paid. If the entire order is fully refunded, the status is
     * refunded.
     *
     * @param Order $order
     *
     * @return null|string
     */
    private function getOrderPaymentStatus($order)
    {
        if ((float) $order->getTotalInvoiced() === (float) $order->getGrandTotal()) {
            return 'paid';
        }

        if ((float) $order->getTotalRefunded() === (float) $order->getGrandTotal()) {
            return 'refunded';
        }

        return null;
    }
}