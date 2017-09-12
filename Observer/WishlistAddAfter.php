<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishWishlistItem\Observer;

use Magento\Framework\Event\Observer;
use Buzzi\PublishWishlistItem\Model\DataBuilder;

class WishlistAddAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    protected $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    protected $queue;

    /**
     * @var \Buzzi\PublishWishlistItem\Model\DataBuilder
     */
    protected $dataBuilder;

    /**
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishWishlistItem\Model\DataBuilder $dataBuilder
     */
    public function __construct(
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishWishlistItem\Model\DataBuilder $dataBuilder
    ) {
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $wishlistItems = (array)$observer->getData('items');

        if (empty($wishlistItems[0])
            || !$wishlistItems[0] instanceof \Magento\Wishlist\Model\Item
            || !$wishlistItems[0]->getWishlist() instanceof \Magento\Wishlist\Model\Wishlist
        ) {
            return;
        }

        /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
        $wishlist = $wishlistItems[0]->getWishlist();
        $storeId = $wishlist->getStore()->getId();

        if (!$this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE, $storeId)) {
            return;
        }

        foreach ($wishlistItems as $wishlistItem) {
            $this->processWishlistItem($wishlist, $wishlistItem, $storeId);
        }
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @param int $storeId
     * @return void
     */
    protected function processWishlistItem($wishlist, $wishlistItem, $storeId)
    {
        $payload = $this->dataBuilder->getPayload($wishlist, $wishlistItem);

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE, $payload, $storeId);
        }
    }
}
