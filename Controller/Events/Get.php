<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Events;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Service\Api\DataLayer;

/**
 * Class Get
 * Ajax controller to get queued events
 */
class Get implements HttpGetActionInterface
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
     * @param JsonFactory $resultJsonFactory
     * @param DataLayer $dataLayer
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        DataLayer $dataLayer,
        JsonSerializer $jsonSerializer
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataLayer = $dataLayer;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return Json
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
