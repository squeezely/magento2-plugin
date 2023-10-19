<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Block\Adminhtml\System\Design;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;
use Squeezely\Plugin\Model\ProcessingQueue\CollectionFactory as ProcessingQueueCollectionFactory;

/**
 * Logs Render Block
 */
class BackendQueue extends Template implements RendererInterface
{

    /**
     * Template file name
     *
     * @var string
     */
    protected $_template = 'Squeezely_Plugin::system/config/fieldset/backend.phtml';

    /**
     * @var ProcessingQueueCollectionFactory
     */
    private $processesQueueCollectionFactory;

    /**
     * Feeds constructor.
     *
     * @param Context $context
     * @param ProcessingQueueCollectionFactory $processesQueueCollectionFactory
     */
    public function __construct(
        Context $context,
        ProcessingQueueCollectionFactory $processesQueueCollectionFactory
    ) {
        $this->processesQueueCollectionFactory = $processesQueueCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $this->setData('element', $element);
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getProcessQueueUrl(): string
    {
        return $this->getUrl('sqzl/ProcessingQueue/process');
    }

    /**
     * @return int
     */
    public function getNumberOfRecords(): int
    {
        return $this->processesQueueCollectionFactory->create()->getSize();
    }

    /**
     * @inheritDoc
     */
    public function getCacheLifetime()
    {
        return null;
    }
}
