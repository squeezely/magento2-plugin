<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * Class Quote
 * Plugin for Invoice model
 */
class Invoice
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;

    /**
     * Invoice constructor.
     *
     * @param ConfigRepository $configRepository
     * @param ProcessingQueueRepository $processingQueueRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        ProcessingQueueRepository $processingQueueRepository
    ) {
        $this->configRepository = $configRepository;
        $this->processingQueueRepository = $processingQueueRepository;
    }

    /**
     * Fire event after invoice created
     *
     * @param InvoiceModel $subject
     * @param InvoiceModel $result
     *
     * @return InvoiceModel
     */
    public function afterPay(
        InvoiceModel $subject,
        InvoiceModel $result
    ) {
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::PURCHASE_EVENT)) {
            return $result;
        }

        $order = $subject->getOrder();
        if ($subject->getState() === Order\Invoice::STATE_PAID) {
            $process = $this->processingQueueRepository->create();
            $process->setType('order')
                ->setProcessingData([
                    'order_id' => $order->getId()
                ]);
            $this->processingQueueRepository->save($process);
        }

        return $result;
    }
}
