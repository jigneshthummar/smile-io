<?php

namespace Mediact\Smile\Observer\Customer;

use Magento\Framework\Event\Observer;
use Mediact\Smile\Observer\ObserverAbstract;
use Magento\Customer\Model\Customer;

class Update
    extends ObserverAbstract
{
    /**
     * Return the event code for updating a customer.
     *
     * @return string
     */
    public  function getEventCode()
    {
        return 'customer/updated';
    }

    /**
     * Return the data for this customer that needs to be
     * synced with Smile.io
     *
     * @param Observer $observer
     * @return array
     */
    public function getEventBody(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getEvent()->getData('customer');

        $data = [
            "external_id" => $customer->getId(),
            "first_name" => $customer->getFirstname(),
            "last_name" => $customer->getLastname(),
            "email" => $customer->getEmail(),
            "external_created_at" => $customer->getCreatedAt(),
            "external_updated_at" => $customer->getUpdatedAt(),
        ];

        return $data;
    }
}
