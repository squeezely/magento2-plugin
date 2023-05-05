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
use Squeezely\Plugin\Service\ItemUpdate\SyncAll;

class RunQueue extends Action
{

    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Squeezely_Plugin::general_item';

    /**
     * @var SyncAll
     */
    private $syncAll;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * RunQueue constructor.
     * @param Action\Context $context
     * @param SyncAll $syncAll
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        SyncAll $syncAll,
        RedirectInterface $redirect
    ) {
        $this->syncAll = $syncAll;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $results = $this->syncAll->execute();
        foreach ($results as $result) {
            if (!empty($result['success'])) {
                $this->messageManager->addSuccessMessage(__($result['message']));
            } else {
                $this->messageManager->addErrorMessage(__($result['message']));
            }
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }
}
