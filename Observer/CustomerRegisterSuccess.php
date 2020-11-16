<?php
namespace Squeezely\Plugin\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Squeezely\Plugin\Helper\Data;
use Squeezely\Plugin\Helper\SqueezelyApiHelper;
use Squeezely\Plugin\Helper\SqueezelyDataLayerHelper;

class CustomerRegisterSuccess implements ObserverInterface {
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var Subscriber
     */
    private $_subscriber;
    /**
     * @var SqueezelyApiHelper
     */
    private $_squeezelyApiHelper;
    /**
     * @var SqueezelyDataLayerHelper
     */
    private $_squeezelyDataLayerHelper;

    public function __construct(
        LoggerInterface $logger,
        SqueezelyApiHelper $squeezelyApiHelper,
        SqueezelyDataLayerHelper $squeezelyDataLayerHelper,
        Subscriber $subscriber
    ) {
        $this->_logger = $logger;
        $this->_squeezelyApiHelper = $squeezelyApiHelper;
        $this->_subscriber = $subscriber;
        $this->_squeezelyDataLayerHelper = $squeezelyDataLayerHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer) {
        /** @var Customer $customer */
        $customer = $observer->getCustomer();

        $subscription = $this->_subscriber->loadByCustomerId($customer->getId());

        if($customer->getEmail()) {
            // Frontend event, to connect email_hash with cookie
            $data = [
                'email' => hash('sha256', $customer->getEmail())
            ];
            if($subscription->isSubscribed()) {
                $data['newsletter'] = 'yes';
            }
            $this->_squeezelyDataLayerHelper->addEventToQueue('CompleteRegistration', $data);

            // Backend event, to add the raw email
            $data = [
                'event' => 'CompleteRegistration',
                'email' => $customer->getEmail()
            ];
            if($subscription->isSubscribed()) {
                $data['newsletter'] = 'yes';
            }

            $this->_squeezelyApiHelper->sendCompleteRegistration($data);

        }
    }
}
