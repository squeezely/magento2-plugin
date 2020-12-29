<?php
namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Squeezely\Plugin\Helper\Data;
use Squeezely\Plugin\Helper\SqueezelyDataLayerHelper;

class NewsletterSubscriberSaveAfter implements ObserverInterface {
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var SqueezelyDataLayerHelper
     */
    private $_squeezelyDataLayerHelper;

    public function __construct(
        LoggerInterface $logger,
        SqueezelyDataLayerHelper $squeezelyDataLayerHelper
    ) {
        $this->_logger = $logger;
        $this->_squeezelyDataLayerHelper = $squeezelyDataLayerHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer) {
        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber ? $subscriber->getSubscriberEmail() : null;

        if($email && $subscriber) {
            $eventData = ['email' => hash('sha256', $email)];

            switch($subscriber->getStatus()) {
                case Subscriber::STATUS_SUBSCRIBED:
                    $eventData['newsletter'] = 'yes';
                    break;
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $eventData['newsletter'] = 'no';
                    break;
                default: // Don't do anything with the other status
                    return false;
            }

            $this->_squeezelyDataLayerHelper->addEventToQueue('EmailOptIn', $eventData);
        }
    }
}
