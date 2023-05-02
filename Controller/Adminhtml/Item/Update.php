<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Squeezely\Plugin\Service\ItemUpdate\SyncByItemIds;

class Update extends Action
{
    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Squeezely_Plugin::general_item';

    /**
     * @var SyncByItemIds
     */
    private $syncByItemIds;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @param Action\Context $context
     * @param SyncByItemIds $syncByItemIds
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        SyncByItemIds $syncByItemIds,
        RedirectInterface $redirect
    ) {
        $this->syncByItemIds = $syncByItemIds;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $itemId = $this->getRequest()->getParam('item_id');

        $result = $this->syncByItemIds->execute($itemId);
        if ($result['success']) {
            $this->messageManager->addSuccessMessage(__($result['message']));
        } else {
            $this->messageManager->addErrorMessage(__($result['message']));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }
}
