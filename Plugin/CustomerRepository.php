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
use Magento\Framework\Stdlib\CookieManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * CustomerRepository Plugin
 */
class CustomerRepository
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var State
     */
    private $state;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * CustomerRepository constructor.
     *
     * @param ConfigRepository $configRepository
     * @param State $state
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        ConfigRepository $configRepository,
        State $state,
        ProcessingQueueRepository $processingQueueRepository,
        CookieManagerInterface $cookieManager
    ) {
        $this->configRepository = $configRepository;
        $this->state = $state;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->cookieManager = $cookieManager;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param mixed ...$args
     * @return mixed
     */
    public function aroundSave(Subject $subject, callable $proceed, ...$args)
    {
        list($customer) = $args;
        if ($customer->getId()) {
            try {
                $prevCustomerData = $subject->getById($customer->getId());
            } catch (\Exception $e) {
                return $proceed(...$args);
            }
        } else {
            return $proceed(...$args);
        }

        switch ($this->getAreaCode()) {
            case 'adminhtml':
                $this->executeBackendEvents($prevCustomerData, $customer);
                break;
            case 'frontend':
                $this->executeFrontendEvents($prevCustomerData, $customer);
                break;
        }

        return $proceed(...$args);
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
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::CRM_UPDATE_EVENT)) {
            $this->sendCRMUpdateEvent($prevCustomerData, $newCustomerData);
            $this->sendEmailOptInEvent($prevCustomerData, $newCustomerData);
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
            $process = $this->processingQueueRepository->create();
            $process->setType('crm_update')
                ->setStoreId($newCustomerData->getStoreId())
                ->setProcessingData([
                    "event" => "CRMUpdate",
                    "userid" => $newCustomerData->getId(),
                    "email" => $newCustomerData->getEmail(),
                    "firstname" => $newCustomerData->getFirstname(),
                    "lastname" => $newCustomerData->getLastname(),
                    'gender' => $this->getGender($newCustomerData),
                    "birthdate" => $newCustomerData->getDob()
                ]);
            $this->processingQueueRepository->save($process);
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

        $process = $this->processingQueueRepository->create();
        $process->setType('registration')
            ->setStoreId($newCustomerData->getStoreId())
            ->setProcessingData([
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'userid' => $newCustomerData->getId(),
                'email' => $newCustomerData->getEmail()
            ]);
        $this->processingQueueRepository->save($process);
    }

    /**
     * Execute frontend events
     *
     * @param CustomerInterface $prevCustomerData
     * @param CustomerInterface $newCustomerData
     */
    private function executeFrontendEvents(CustomerInterface $prevCustomerData, CustomerInterface $newCustomerData)
    {
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::EMAIL_OPT_IN_EVENT) &&
            ($prevCustomerData->getEmail() != $newCustomerData->getEmail())) {
            $this->addFrontendEvent($newCustomerData->getEmail(), $newCustomerData);
        }
    }

    /**
     * @param string $email
     * @param CustomerInterface $savedCustomer
     * @return void
     */
    private function addFrontendEvent(string $email, CustomerInterface $savedCustomer): void
    {
        $process = $this->processingQueueRepository->create();
        $process->setType('email_optin')
            ->setStoreId($savedCustomer->getStoreId())
            ->setProcessingData([
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'email' => hash('sha256', $email),
                'userid' => $savedCustomer->getId(),
                'sqzly_cookie' => $this->cookieManager->getCookie(
                    ConfigRepository::SQUEEZELY_COOKIE_NAME
                )
            ]);
        $this->processingQueueRepository->save($process);
    }
}
