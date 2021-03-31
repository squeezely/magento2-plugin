<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Events;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Squeezely\Plugin\Service\Api\DataLayer;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Get
 * Ajax controller to get queued events
 */
class Get extends Action implements HttpPostActionInterface
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var DataLayer
     */
    private $dataLayer;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Get constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param DataLayer $dataLayer
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        DataLayer $dataLayer,
        JsonSerializer $jsonSerializer
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataLayer = $dataLayer;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $events = $this->dataLayer->getQueuedEvents();
        $this->dataLayer->clearQueuedEvents();
        $result->setData($this->jsonSerializer->serialize($events));
        return $result;
    }
}
