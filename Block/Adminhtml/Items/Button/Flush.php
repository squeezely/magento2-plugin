<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Block\Adminhtml\Items\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Flush Button
 */
class Flush implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * GenericButton constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Flush all products')->render(),
            'url' => $this->getUrl(),
            'class' => 'secondary',
            'sort_order' => 20
        ];
    }

    /**
     * Get URL for new account button
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/flush', []);
    }
}
