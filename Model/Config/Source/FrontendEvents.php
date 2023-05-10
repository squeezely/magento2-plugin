<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Backend Events Option Source model
 */
class FrontendEvents implements OptionSourceInterface
{

    /**
     * Available backend events
     */
    public const EVENTS = [
        ConfigRepository::VIEW_CONTENT_EVENT,
        ConfigRepository::VIEW_CATEGORY_EVENT,
        ConfigRepository::SEARCH_EVENT,
        ConfigRepository::ADD_TO_CART_EVENT,
        ConfigRepository::REMOVE_FROM_CART_EVENT,
        ConfigRepository::INITIATE_CHECKOUT_EVENT,
        ConfigRepository::PURCHASE_EVENT,
    ];

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            foreach (self::EVENTS as $event) {
                $this->options[] = ['value' => $event, 'label' => $event];
            }
        }
        return $this->options;
    }
}
