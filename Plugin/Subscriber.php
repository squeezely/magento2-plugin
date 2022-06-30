<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Newsletter\Model\Subscriber as Subject;
use Squeezely\Plugin\Api\Config\System\BackendEventsInterface as BackendEventsRepository;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * After save subscriber plugin
 */
class Subscriber
{
    public const EVENT_NAME = 'EmailOptIn';

    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * @var BackendEventsRepository
     */
    private $backendEventsRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var State
     */
    private $state;
    /**
     * @var RepositoryInterface
     */
    private $requestRepository;

    /**
     * Subscriber constructor.
     *
     * @param DataLayerInterface $dataLayer
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param BackendEventsRepository $backendEventsRepository
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param RepositoryInterface $requestRepository
     * @param State $state
     */
    public function __construct(
        DataLayerInterface $dataLayer,
        FrontendEventsRepository $frontendEventsRepository,
        BackendEventsRepository $backendEventsRepository,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        RepositoryInterface $requestRepository,
        State $state
    ) {
        $this->dataLayer = $dataLayer;
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->backendEventsRepository = $backendEventsRepository;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->requestRepository = $requestRepository;
        $this->state = $state;
    }

    /**
     * @param Subject $subscriber
     * @param Subject $result
     * @return Subject
     */
    public function afterSave(Subject $subscriber, Subject $result): Subject
    {
        switch ($this->getAreaCode()) {
            case 'adminhtml':
                $this->executeBackendEvent($subscriber);
                break;
            case 'frontend':
                $this->executeFrontendEvent($subscriber);
                break;
        }

        return $result;
    }

    /**
     * @param Subject $subscriber
     */
    private function executeBackendEvent(Subject $subscriber)
    {
        if ($this->backendEventsRepository->isEnabled()
            && in_array(
                RepositoryInterface::EMAIL_OPT_IN_EVENT_NAME,
                $this->backendEventsRepository->getEnabledEvents()
            )
        ) {
            $email = $subscriber->getEmail();
            $data = [
                'event' => RepositoryInterface::EMAIL_OPT_IN_EVENT_NAME,
                'email' => $email
            ];
            if ((int)$subscriber->getStatus() == Subject::STATUS_SUBSCRIBED) {
                $data['newsletter'] = 'yes';
            } else {
                $data['newsletter'] = 'no';
            }
            $this->logRepository->addDebugLog(
                'EmailOptInEvent',
                'Event data: ' . $this->jsonSerializer->serialize($data)
            );
            try {
                $this->requestRepository->sendCompleteRegistration($data);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('CustomerAccountManagement', $exception->getMessage());
            }
        }
    }

    /**
     * Execute frontend event
     *
     * @param Subject $subscriber
     */
    private function executeFrontendEvent(Subject $subscriber)
    {
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Start'));
            $email = $subscriber->getEmail();
            if ($email) {
                $this->addEvent($email, $subscriber);
            }
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }
    }

    /**
     * Add event to data layer
     *
     * @param string $email
     * @param Subject $subscriber
     */
    private function addEvent(string $email, Subject $subscriber)
    {
        if ($email && $subscriber->isStatusChanged()) {
            $eventData = ['email' => hash('sha256', $email)];
            switch ($subscriber->getStatus()) {
                case Subject::STATUS_SUBSCRIBED:
                    $eventData['newsletter'] = 'yes';
                    break;
                case Subject::STATUS_UNSUBSCRIBED:
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

    /**
     * Get area code
     *
     * @return string|null
     */
    private function getAreaCode(): ?string
    {
        try {
            return $this->state->getAreaCode();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
