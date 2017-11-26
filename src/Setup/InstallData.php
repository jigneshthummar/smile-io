<?php

namespace Mediact\Smile\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * Customer setup factory
     *
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * Constructor.
     *
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'smileio_synchronised_at',
            [
                'type' => 'static',
                'label' => 'Smile.io Synchronised At',
                'input' => 'date',
                'required' => false,
                'sort_order' => 101,
                'visible' => false,
                'system' => false,
            ]
        );
    }
}
