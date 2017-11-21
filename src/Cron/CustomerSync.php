<?php

namespace Mediact\Smile\Cron;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Mediact\Smile\Model\Api;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

use function date;

/**
 * Class Customer
 */
class CustomerSync
{
    /** @var LoggerInterface */
    private $logger;

    /** @var CollectionFactory */
    private $customerCollection;

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var Api */
    private $apiModel;

    /**
     * Constructor.
     *
     * @param LoggerInterface    $logger
     * @param CollectionFactory  $customerCollection
     * @param CustomerRepository $customerRepository
     * @param Api                $apiModel
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $customerCollection,
        CustomerFactory $customerFactory,
        Api $apiModel
    )
    {
        $this->logger = $logger;
        $this->customerCollection = $customerCollection;
        $this->customerFactory = $customerFactory;
        $this->apiModel = $apiModel;
    }

    /**
     *
     * @return Api
     */
    private function getApi()
    {
        return $this->apiModel;
    }

    /**
     *
     * @return void
     */
    public function update()
    {
        /** @var CollectionFactory $collection */
        $collection = $this->getCustomerCollection();

        /** @var Customer $customer */
        foreach ($collection as $customer) {
            $data = [
                "external_id" => $customer->getId(),
                "first_name" => $customer->getFirstname(),
                "last_name" => $customer->getLastname(),
                "email" => $customer->getEmail(),
                "external_created_at" => $customer->getCreatedAt(),
                "external_updated_at" => $customer->getUpdatedAt(),
            ];

            if ($this->getApi()->synchroniseCustomer($data)) {
                /** @todo This should be fixed using service contracts */
                $customer->setData('smileio_synchronised_at', date('Y-m-d H:i:s'));
                $customer->save();

                //$customerData = $customer->getDataModel();
                //$customerResource = $this->customerFactory->create();
                //
                //$customerData->setCustomAttribute();
                //$customer->updateData($customerData);
                //
                //$customerResource->saveAttribute($customer, 'smileio_synchronised_at');
            }
        }
    }

    /**
     *
     * @return Collection
     */
    private function getCustomerCollection(): Collection
    {
        $collection = $this->customerCollection->create();
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