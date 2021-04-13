<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Block\Adminhtml\System\Squeezely;

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
     * Image with extension and magento version.
     *
     * @return string
     */
    public function getImage(): string
    {
        return sprintf(
            'https://www.magmodules.eu/logo/%s/%s/%s/logo.png',
            ConfigRepository::SQUEEZELY_PLUGIN_NAME,
            $this->configRepository->getExtensionVersion(),
            $this->configRepository->getMagentoVersion()
        );
    }
}
