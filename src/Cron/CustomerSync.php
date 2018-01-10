<?php

namespace Mediact\Smile\Cron;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Mediact\Smile\Model\Api;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

use function date;

/**
 * Class CustomerSync
 */
class CustomerSync
{
    /** @var LoggerInterface */
    private $logger;

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var CustomerResource */
    private $customerResource;

    /** @var Api */
    private $apiModel;

    /**
     * Constructor.
     *
     * @param LoggerInterface   $logger
     * @param CustomerFactory   $customerFactory
     * @param CustomerResource  $customerResource
     * @param Api               $apiModel
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        Api $apiModel
    ) {
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->apiModel = $apiModel;
    }

    /**
     * Get the Smile.io API model.
     *
     * @return Api
     */
    private function getApi(): Api
    {
        return $this->apiModel;
    }

    /**
     * Update the customer collection to Smile.io. Only customers that are updated
     * since the last synchronisation with Smile.io will be processed.
     *
     * @return void
     */
    public function update()
    {
        /** @var CustomerFactory $collection */
        $collection = $this->getCustomerCollection();

        /** @var CustomerInterface $customer */
        foreach ($collection as $customer) {
            $data = [
                "external_id" => $customer->getId(),
                "first_name" => $customer->getFirstname(),
                "last_name" => $customer->getLastname(),
                "email" => $customer->getEmail(),
                "external_created_at" => $customer->getCreatedAt(),
                "external_updated_at" => $customer->getUpdatedAt(),
            ];

            if ($this->getApi()->synchronizeCustomer($data)) {
                $this->customerResource->getConnection()->update(
                    $this->customerResource->getTable('customer_entity'),
                    ['smileio_synchronised_at' => date('Y-m-d H:i:s')],
                    $this->customerResource->getConnection()
                        ->quoteInto('entity_id = ?', $customer->getId())
                );
            }
        }
    }

    /**
     * Get a collection of the customers that are updated after they're last
     * synced with Smile.io.
     *
     * @return CustomerCollection
     */
    private function getCustomerCollection(): CustomerCollection
    {
        $collection = $this->customerFactory->create();
        $collection->addFieldToFilter(
            'smileio_synchronised_at',
            [
                ['lt' => new Zend_Db_Expr('updated_at')],
                ['null' => true]
            ]
        );

        return $collection;
    }
}
