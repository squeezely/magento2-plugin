<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Framework\App\State;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Newsletter\Model\Subscriber as Subject;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * After save subscriber plugin
 */
class Subscriber
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
     * Subscriber constructor.
     * @param ConfigRepository $configRepository
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param State $state
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        ConfigRepository $configRepository,
        ProcessingQueueRepository $processingQueueRepository,
        State $state,
        CookieManagerInterface $cookieManager
    ) {
        $this->configRepository = $configRepository;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->state = $state;
        $this->cookieManager = $cookieManager;
    }

    /**
     * @param Subject $subscriber
     * @param Subject $result
     * @return Subject
     */
    public function afterSave(Subject $subscriber, Subject $result): Subject
    {
        $this->executeBackendEvent($subscriber);
        if ($this->getAreaCode() == 'frontend') {
            $this->executeFrontendEvent($subscriber);
        }

        return $result;
    }

    /**
     * @param Subject $subscriber
     */
    private function executeBackendEvent(Subject $subscriber)
    {
        $storeId = (int) $subscriber->getStoreId();
        if (!$this->configRepository->isBackendEventEnabled(ConfigRepository::EMAIL_OPT_IN_EVENT, $storeId)) {
            return;
        }

        $process = $this->processingQueueRepository->create();
        $process->setType('registration')
            ->setStoreId($subscriber->getStoreId())
            ->setProcessingData([
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'email' => $subscriber->getEmail(),
                'newsletter' => (int)$subscriber->getStatus() == Subject::STATUS_SUBSCRIBED ? 'yes' : 'no'
            ]);
        $this->processingQueueRepository->save($process);
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

    /**
     * Execute frontend event
     *
     * @param Subject $subscriber
     */
    private function executeFrontendEvent(Subject $subscriber)
    {
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::EMAIL_OPT_IN_EVENT)) {
            if ($email = $subscriber->getEmail()) {
                $this->addEvent($email, $subscriber);
            }
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
            $eventData = [
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'email' => hash('sha256', $email),
                'sqzly_cookie' => $this->cookieManager->getCookie(
                    ConfigRepository::SQUEEZELY_COOKIE_NAME
                )
            ];
            switch ($subscriber->getStatus()) {
                case Subject::STATUS_SUBSCRIBED:
                    $eventData['newsletter'] = 'yes';
                    break;
                case Subject::STATUS_UNSUBSCRIBED:
                    $eventData['newsletter'] = 'no';
                    break;
            }
            $process = $this->processingQueueRepository->create();
            $process->setType('email_optin')
                ->setStoreId($subscriber->getStoreId())
                ->setProcessingData($eventData);
            $this->processingQueueRepository->save($process);
        }
    }
}
