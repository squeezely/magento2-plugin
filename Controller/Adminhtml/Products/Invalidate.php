<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as StoreSyncConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Service\Invalidate\ByStore as InvalidateByStore;

/**
 * Class Invalidate
 * Controller to invalidate all products
 */
class Invalidate extends Action
{

    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Squeezely_Plugin::config';

    /**
     * Error Message: not enabled
     */
    public const ERROR_MSG_ENABLED = 'Store sync not enabled for this store, please enable this first.';

    /**
     * Error Message
     */
    public const ERROR_MSG_NO_ITEMS = 'Something went wrong, please try again';

    /**
     * Success Message
     */
    public const SUCCESS_MSG = '%1 products were invalidated and queued for sync.';

    /**
     * No products to invalidate
     */
    public const NO_PRODUCTS_MSG = 'No products to invalidate.';

    /**
     * @var StoreSyncConfigRepository
     */
    private $storeSyncConfigRepository;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var InvalidateByStore;
     */
    private $invalidateByStore;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Invalidate constructor.
     *
     * @param Action\Context $context
     * @param StoreSyncConfigRepository $storeSyncConfigRepository
     * @param RequestRepository $requestRepository
     * @param InvalidateByStore $invalidateByStore
     */
    public function __construct(
        Action\Context $context,
        StoreSyncConfigRepository $storeSyncConfigRepository,
        RequestRepository $requestRepository,
        InvalidateByStore $invalidateByStore,
        RedirectInterface $redirect
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->storeSyncConfigRepository = $storeSyncConfigRepository;
        $this->requestRepository = $requestRepository;
        $this->invalidateByStore = $invalidateByStore;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $storeId = (int)$this->getRequest()->getParam('store_id');

        if (!$this->storeSyncConfigRepository->isEnabled($storeId)) {
            $msg = self::ERROR_MSG_ENABLED;
            $this->messageManager->addErrorMessage(__($msg));
            return $resultRedirect->setPath(
                $this->redirect->getRefererUrl()
            );
        }

        $result = $this->invalidateByStore->execute($storeId);
        if ($result['success']) {
            $this->messageManager->addSuccessMessage($result['msg']);
        } else {
            $this->messageManager->addErrorMessage($result['msg']);
        }
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }
}
