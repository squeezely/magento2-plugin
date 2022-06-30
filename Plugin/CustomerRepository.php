<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository as Subject;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Api\Config\System\BackendEventsInterface as BackendEventsRepository;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * CustomerRepository Plugin
 */
class CustomerRepository
{
    /**
     * @var BackendEventsRepository
     */
    private $backendEventsRepository;
    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var State
     */
    private $state;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * CustomerRepository constructor.
     *
     * @param BackendEventsRepository $backendEventsRepository
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param DataLayerInterface $dataLayer
     * @param RequestRepository $requestRepository
     * @param State $state
     * @param JsonSerializer $jsonSerializer
     * @param LogRepository $logRepository
     */
    public function __construct(
        BackendEventsRepository $backendEventsRepository,
        FrontendEventsRepository $frontendEventsRepository,
        DataLayerInterface $dataLayer,
        RequestRepository $requestRepository,
        State $state,
        JsonSerializer $jsonSerializer,
        LogRepository $logRepository
    ) {
        $this->backendEventsRepository = $backendEventsRepository;
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->dataLayer = $dataLayer;
        $this->requestRepository = $requestRepository;
        $this->state = $state;
        $this->jsonSerializer = $jsonSerializer;
        $this->logRepository = $logRepository;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @return mixed
     */
    public function aroundSave(Subject $subject, callable $proceed, CustomerInterface $customer)
    {
        if ($customer->getId()) {
            try {
                $prevCustomerData = $subject->getById($customer->getId());
            } catch (\Exception $e) {
                return $proceed($customer);
            }
        } else {
            return $proceed($customer);
        }

        switch ($this->getAreaCode()) {
            case 'adminhtml':
                $this->executeBackendEvents($prevCustomerData, $customer);
                break;
            case 'frontend':
                $this->executeFrontendEvents($prevCustomerData, $customer);
                break;
        }

        return $proceed($customer);
    }

    /**
     * Get executable area code
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

    /**
     * Execute backend events
     *
     * @param CustomerInterface $prevCustomerData
     * @param CustomerInterface $newCustomerData
     */
    private function executeBackendEvents(CustomerInterface $prevCustomerData, CustomerInterface $newCustomerData)
    {
        if (!$this->backendEventsRepository->isEnabled()
            || !in_array(
                RequestRepository::CRM_UPDATE_EVENT_NAME,
                $this->backendEventsRepository->getEnabledEvents()
            )
        ) {
            return;
        }

        $this->sendCRMUpdateEvent($prevCustomerData, $newCustomerData);
        $this->sendEmailOptInEvent($prevCustomerData, $newCustomerData);
    }

    /**
     * Execute frontend events
     *
     * @param CustomerInterface $prevCustomerData
     * @param CustomerInterface $newCustomerData
     */
    private function executeFrontendEvents(CustomerInterface $prevCustomerData, CustomerInterface $newCustomerData)
    {
        if ($this->frontendEventsRepository->isEnabled() &&
            ($prevCustomerData->getEmail() != $newCustomerData->getEmail())) {
            $this->logRepository->addDebugLog(RequestRepository::EMAIL_OPT_IN_EVENT_NAME, __('Start'));
            $this->addFrontendEvent($newCustomerData->getEmail(), $newCustomerData);
            $this->logRepository->addDebugLog(RequestRepository::EMAIL_OPT_IN_EVENT_NAME, __('Finish'));
        }
    }

    /**
     * Send CRMUpdate event
     *
     * @param CustomerInterface $prevCustomerData
     * @param CustomerInterface $newCustomerData
     */
    private function sendCRMUpdateEvent(CustomerInterface $prevCustomerData, CustomerInterface $newCustomerData)
    {
        if (($prevCustomerData->getEmail() != $newCustomerData->getEmail()) ||
            ($prevCustomerData->getFirstname() != $newCustomerData->getFirstname()) ||
            ($prevCustomerData->getLastname() != $newCustomerData->getLastname()) ||
            ($prevCustomerData->getDob() != $newCustomerData->getDob()) ||
            ($prevCustomerData->getGender() != $newCustomerData->getGender())
        ) {
            $this->logRepository->addDebugLog('CRMUpdate event', __('Start'));
            $data = [
                "event" => "CRMUpdate",
                "userid" => $newCustomerData->getId(),
                "email" => $newCustomerData->getEmail(),
                "firstname" => $newCustomerData->getFirstname(),
                "lastname" => $newCustomerData->getLastname(),
                'gender' => $this->getGender($newCustomerData),
                "birthdate" => $newCustomerData->getDob()
            ];
            try {
                $this->requestRepository->sendCRMUpdate($data);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('after customer save plugin', $exception->getMessage());
            }
            $this->logRepository->addDebugLog('CRMUpdate event', __('Finish'));
        }
    }

    /**
     * Send backend EmailOptIn event
     *
     * @param CustomerInterface $prevCustomerData
     * @param CustomerInterface $newCustomerData
     */
    private function sendEmailOptInEvent(CustomerInterface $prevCustomerData, CustomerInterface $newCustomerData)
    {
        if ($prevCustomerData->getEmail() == $newCustomerData->getEmail()) {
            return;
        }

        $data = [
            'event' => RequestRepository::EMAIL_OPT_IN_EVENT_NAME,
            'userid' => $newCustomerData->getId(),
            'email' => $newCustomerData->getEmail()
        ];
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

    /**
     * Get Gender. Properly works only with default magento attribute and options.
     *
     * @param CustomerInterface $customer
     * @return string
     */
    private function getGender(CustomerInterface $customer): string
    {
        switch ($customer->getGender()) {
            case 1:
                return 'M';
            case 2:
                return 'F';
            default:
                return "U";
        }
    }

    /**
     * @param string $email
     * @param CustomerInterface $savedCustomer
     * @return void
     */
    private function addFrontendEvent(string $email, CustomerInterface $savedCustomer): void
    {
        $eventData = [
            'email' => hash('sha256', $email),
            'userid' => $savedCustomer->getId()
        ];
        $this->dataLayer->addEventToQueue(RequestRepository::EMAIL_OPT_IN_EVENT_NAME, $eventData);
        $this->logRepository->addDebugLog(
            RequestRepository::EMAIL_OPT_IN_EVENT_NAME,
            'Event data: ' . $this->jsonSerializer->serialize($eventData)
        );
    }
}
