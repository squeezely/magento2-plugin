<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Observer\Newsletter;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class NewsletterSubscriberSaveAfter
 * Observer for newsletter_subscriber_save_after event
 */
class NewsletterSubscriberSaveAfter implements ObserverInterface
{
    const EVENT_NAME = 'EmailOptIn';

    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * NewsletterSubscriberSaveAfter constructor.
     *
     * @param DataLayerInterface $dataLayer
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        DataLayerInterface $dataLayer,
        FrontendEventsRepository $frontendEventsRepository,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->dataLayer = $dataLayer;
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Start'));
            $subscriber = $observer->getEvent()->getSubscriber();
            $email = $subscriber->getEmail();
            if ($email) {
                $this->addEvent($email, $subscriber);
            }
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }

        return $this;
    }

    /**
     * @param string $email
     * @param Subscriber $subscriber
     */
    protected function addEvent(string $email, Subscriber $subscriber)
    {
        if ($email && $subscriber->isStatusChanged()) {
            $eventData = ['email' => hash('sha256', $email)];
            switch ($subscriber->getStatus()) {
                case Subscriber::STATUS_SUBSCRIBED:
                    $eventData['newsletter'] = 'yes';
                    break;
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $eventData['newsletter'] = 'no';
                    break;
            }
            $this->dataLayer->addEventToQueue(self::EVENT_NAME, $eventData);
            $this->logRepository->addDebugLog(
                self::EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($eventData)
            );
        }
    }
}
