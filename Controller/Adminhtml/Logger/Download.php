<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Adminhtml\Logger;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Download controller for log files
 */
class Download extends Action
{

    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Squeezely_Plugin::config';

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var File
     */
    private $ioFilesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Download constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param File $ioFilesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        File $ioFilesystem,
        DirectoryList $directoryList
    ) {
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ioFilesystem = $ioFilesystem;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    /**
     * Execute function for download of the log files.
     * @throws \Exception
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        $path = sprintf('%s/var/log/squeezely/%s.log', $this->directoryList->getRoot(), $type);
        $fileInfo = $this->ioFilesystem->getPathInfo($path);
        return $this->fileFactory->create(
            $fileInfo['basename'],
            [
                'type'  => 'filename',
                'value' => $path
            ]
        );
    }
}
