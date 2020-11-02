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
        $this->_squeezelyDataLayerHelper->addEventToQueue('EmailOptIn', ['abc' => 1]);

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber ? $subscriber->getSubscriberEmail() : null;

        if($email && $subscriber) {
            $eventData = ['email' => hash('sha256', $email)];

            if($subscriber->isSubscribed()) {
                $eventData['newsletter'] = 'yes';
            }
            else {
                $eventData['newsletter'] = 'no';
            }

            $this->_squeezelyDataLayerHelper->addEventToQueue('EmailOptIn', $eventData);
        }
    }
}
