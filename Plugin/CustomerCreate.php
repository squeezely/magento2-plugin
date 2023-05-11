<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\Account\CreatePost;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * Class CustomerCreate
 * Plugin for Customer account creation
 */
class CustomerCreate
{

    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;
    /**
     * @var LocaleResolver
     */
    private $localeResolver;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * CustomerCreate constructor.
     *
     * @param Subscriber $subscriber
     * @param ConfigRepository $configRepository
     * @param LogRepository $logRepository
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param LocaleResolver $localeResolver
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Subscriber $subscriber,
        ConfigRepository $configRepository,
        LogRepository $logRepository,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        ProcessingQueueRepository $processingQueueRepository,
        LocaleResolver $localeResolver,
        CookieManagerInterface $cookieManager
    ) {
        $this->subscriber = $subscriber;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->session = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->localeResolver = $localeResolver;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Fire events after new customer registered
     *
     * @param CreatePost $subject
     * @param AbstractResult $result
     * @return AbstractResult
     */
    public function afterExecute(
        CreatePost $subject,
        AbstractResult $result
    ): AbstractResult {
        try {
            $customerEmail = $this->session->getCustomerFormData()['email'] ?? null;
            $customer = $customerEmail ? $this->customerRepository->get($customerEmail) : null;
        } catch (\Exception $e) {
            $this->logRepository->addDebugLog('EmailOptInEvent', $e->getMessage());
            return $result;
        }

        if (!$customerEmail || !$customer->getEmail()) {
            return $result;
        }

        // Backend event, to connect email_hash with cookie
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::COMPLETE_REGISTRATION_EVENT)) {
            $this->registrationEvent($customer);
        }

        // Backend event, to add the raw email
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::EMAIL_OPT_IN_EVENT)) {
            $this->optInEvent($customer);
        }

        return $result;
    }

    /**
     * @param CustomerInterface $customer
     * @return void
     */
    private function registrationEvent(CustomerInterface $customer)
    {
        $process = $this->processingQueueRepository->create();
        $process->setType('complete_registration')
            ->setProcessingData([
                'event' => ConfigRepository::COMPLETE_REGISTRATION_EVENT,
                'email' => hash('sha256', $customer->getEmail()),
                'language' => $this->getLanguage(),
                'sqzly_cookie' => $this->cookieManager->getCookie(
                    ConfigRepository::SQUEEZELY_COOKIE_NAME
                )
            ]);
        $this->processingQueueRepository->save($process);
    }

    /**
     * @return string
     */
    private function getLanguage(): string
    {
        $locale = $this->localeResolver->getLocale()
            ?: $this->localeResolver->getDefaultLocale();
        return str_replace('_', '-', $locale);
    }

    /**
     * @param CustomerInterface $customer
     * @return void
     */
    private function optInEvent(CustomerInterface $customer): void
    {
        $subscription = $this->subscriber->loadByCustomerId($customer->getId());
        $process = $this->processingQueueRepository->create();
        $process->setType('registration')
            ->setProcessingData([
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'email' => $customer->getEmail(),
                'newsletter' => (int)$subscription->getStatus() == Subscriber::STATUS_SUBSCRIBED ? 'yes' : 'no'
            ]);
        $this->processingQueueRepository->save($process);
    }
}
