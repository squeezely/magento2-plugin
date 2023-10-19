<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Block\Adminhtml\System\Design;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * System Configration Module information Block
 */
class Header extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Squeezely_Plugin::system/config/fieldset/header.phtml';

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Header constructor.
     *
     * @param Context $context
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        Context $context,
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('squeezely');
        return $this->toHtml();
    }

    /**
     * Documentation link for extension.
     *
     * @return string
     */
    public function getDocumentationLink(): string
    {
        return $this->configRepository->getDocumentationLink();
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return $this->configRepository->getSupportLink();
    }

    /**
     * Api link for extension.
     *
     * @return string
     */
    public function getApiLink(): string
    {
        return $this->configRepository->getApiLink();
    }

    /**
     * Magmodules link for extension.
     *
     * @return string
     */
    public function getMagmodulesLink(): string
    {
        return $this->configRepository->getMagmodulesLink();
    }
}
