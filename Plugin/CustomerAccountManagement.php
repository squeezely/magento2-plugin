<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Newsletter\Model\Subscriber;
use Squeezely\Plugin\Api\Config\System\BackendEventsInterface as BackendEventsRepository;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class CustomerAccountManagement
 * Plugin for Customer AccountManagement model
 */
class CustomerAccountManagement
{

    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var RepositoryInterface
     */
    private $requestRepository;
    /**
     * @var BackendEventsRepository
     */
    private $backendEventsRepository;
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
     * CustomerAccountManagement constructor.
     *
     * @param Subscriber $subscriber
     * @param DataLayerInterface $dataLayer
     * @param RepositoryInterface $requestRepository
     * @param BackendEventsRepository $backendEventsRepository
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        Subscriber $subscriber,
        DataLayerInterface $dataLayer,
        RepositoryInterface $requestRepository,
        BackendEventsRepository $backendEventsRepository,
        FrontendEventsRepository $frontendEventsRepository,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->subscriber = $subscriber;
        $this->dataLayer = $dataLayer;
        $this->requestRepository = $requestRepository;
        $this->backendEventsRepository = $backendEventsRepository;
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Fire events after new customer registered
     *
     * @param AccountManagement $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterCreateAccountWithPasswordHash(
        AccountManagement $subject,
        CustomerInterface $result
    ) {
        $this->logRepository->addDebugLog('EmailOptInEvent', __('Start'));
        $customer = $result;
        if ($customer->getEmail()) {
            // Frontend event, to connect email_hash with cookie
            if ($this->frontendEventsRepository->isEnabled()) {
                $data = [
                    'email' => hash('sha256', $customer->getEmail())
                ];
                $this->dataLayer->addEventToQueue('CompleteRegistration', $data);
            }

            // Backend event, to add the raw email
            if ($this->backendEventsRepository->isEnabled()
                && in_array(
                    RepositoryInterface::EMAIL_OPT_IN_EVENT_NAME,
                    $this->backendEventsRepository->getEnabledEvents()
                )
            ) {
                $data = [
                    'event' => RepositoryInterface::EMAIL_OPT_IN_EVENT_NAME,
                    'email' => $customer->getEmail()
                ];
                $subscription = $this->subscriber->loadByCustomerId($customer->getId());
                if ((int)$subscription->getStatus() == Subscriber::STATUS_SUBSCRIBED) {
                    $data['newsletter'] = 'yes';
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
        $this->logRepository->addDebugLog('EmailOptInEvent', __('Finish'));
        return $customer;
    }
}
