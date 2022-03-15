<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Backend Events Option Source model
 */
class BackendEvents implements OptionSourceInterface
{

    /**
     * Available backend events
     */
    public const EVENTS = ['EmailOptIn', 'Purchase'];

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
