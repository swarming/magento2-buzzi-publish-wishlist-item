<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishWishlistItem\Model;

use Magento\Framework\DataObject;

class DataBuilder
{
    const EVENT_TYPE = 'buzzi.ecommerce.wishlist-item';

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Base
     */
    protected $dataBuilderBase;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Customer
     */
    protected $dataBuilderCustomer;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Product
     */
    protected $dataBuilderProduct;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventDispatcher;

    /**
     * @param \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase
     * @param \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer
     * @param \Buzzi\Publish\Helper\DataBuilder\Product $dataBuilderProduct
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Framework\Event\ManagerInterface $eventDispatcher
     */
    public function __construct(
        \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase,
        \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer,
        \Buzzi\Publish\Helper\DataBuilder\Product $dataBuilderProduct,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Framework\Event\ManagerInterface $eventDispatcher
    ) {
        $this->dataBuilderBase = $dataBuilderBase;
        $this->dataBuilderCustomer = $dataBuilderCustomer;
        $this->dataBuilderProduct = $dataBuilderProduct;
        $this->customerRegistry = $customerRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return mixed[]
     */
    public function getPayload($wishlist, $wishlistItem)
    {
        $customer = $this->customerRegistry->retrieve($wishlist->getCustomerId());

        $payload = $this->dataBuilderBase->initBaseData(self::EVENT_TYPE);
        $payload['customer'] = $this->dataBuilderCustomer->getCustomerData($customer);
        $payload['product'] = $this->dataBuilderProduct->getProductData($wishlistItem->getProduct());

        $transport = new DataObject(['wishlist' => $wishlist, 'wishlist_item' => $wishlistItem, 'payload' => $payload]);
        $this->eventDispatcher->dispatch('buzzi_publish_wishlist_item_payload', ['transport' => $transport]);

        return (array)$transport->getData('payload');
    }
}
