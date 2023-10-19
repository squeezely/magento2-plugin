<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Controller\Adminhtml\ProcessingQueue;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Service\ProcessingQueue\Process as ProcessQueue;

class Process extends Action
{

    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Squeezely_Plugin::general';

    /**
     * @var ProcessQueue
     */
    private $processQueue;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @param Action\Context $context
     * @param ProcessQueue $processQueue
     * @param LogRepository $logRepository
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        ProcessQueue $processQueue,
        LogRepository $logRepository,
        RedirectInterface $redirect
    ) {
        $this->processQueue = $processQueue;
        $this->logRepository = $logRepository;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $this->processQueue->cleanupQueue();
            $this->processQueue->execute();
            $this->messageManager->addSuccessMessage(__('Queue processed'));
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('ProcessQueue Cron', $exception->getMessage());
            $this->messageManager->addErrorMessage(__('Queue issue: ' . $exception->getMessage()));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }
}
