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
class BackendEvents implements OptionSourceInterface
{

    /**
     * Available backend events
     */
    public const EVENTS = [
        ConfigRepository::EMAIL_OPT_IN_EVENT,
        ConfigRepository::PURCHASE_EVENT,
        ConfigRepository::CRM_UPDATE_EVENT,
        ConfigRepository::ADD_TO_CART_EVENT,
        ConfigRepository::REMOVE_FROM_CART_EVENT,
        ConfigRepository::COMPLETE_REGISTRATION_EVENT,
        ConfigRepository::PRODUCT_DELETE
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
