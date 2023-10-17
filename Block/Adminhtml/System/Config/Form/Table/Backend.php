<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Squeezely\Plugin\Block\Adminhtml\System\Config\Form\Table;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\View\Element\Template;

/**
 * Logs Render Block
 */
class Backend extends Template implements RendererInterface
{

    /**
     * Template file name
     *
     * @var string
     */
    protected $_template = 'Squeezely_Plugin::system/config/fieldset/backend.phtml';

    /**
     * @var File
     */
    private $ioFilesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Feeds constructor.
     *
     * @param Context $context
     * @param File $ioFilesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        File $ioFilesystem,
        DirectoryList $directoryList
    ) {
        $this->ioFilesystem = $ioFilesystem;
        $this->directoryList = $directoryList;
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
     * @inheritDoc
     */
    public function getCacheLifetime()
    {
        return null;
    }
}
