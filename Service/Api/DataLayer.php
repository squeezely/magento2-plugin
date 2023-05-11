<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Api;

use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use stdClass;

/**
 * Class DataLayer
 */
class DataLayer implements DataLayerInterface
{

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * DataLayer constructor.
     *
     * @param JsonSerializer $jsonSerializer
     * @param Escaper $escaper
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        Escaper $escaper
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->escaper = $escaper;
    }

    /**
     * @inheritDoc
     */
    public function generateDataScript(stdClass $object)
    {
        $dataScript = PHP_EOL;
        $dataScript .= '<script type="text/javascript">'
            . PHP_EOL
            . 'window._sqzl = _sqzl || []; _sqzl.push('
            . $this->jsonSerializer->serialize($this->getSafeData($object)) . ')'
            . PHP_EOL
            . '</script>';

        return $dataScript;
    }

    /**
     * @param $object
     * @return array|bool|float|int|mixed|string|null
     */
    protected function getSafeData($object)
    {
        $data = $this->jsonSerializer->unserialize($this->jsonSerializer->serialize($object));
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $data[$key] = $this->escaper->escapeXssInUrl($val);
            }
        }

        return $data;
    }
}
