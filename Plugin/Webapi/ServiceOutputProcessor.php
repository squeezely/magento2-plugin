<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin\Webapi;

use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Webapi\ServiceOutputProcessor as Subject;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Plugin for ServiceOutputProcessor
 *
 * This plugin rewrites the functionality of the ServiceOutputProcessor::convertValue() method.
 * The core Magento webapi response returns a numbered array. In the Squeezely responses we need an associative array,
 * that's why we added array keys to output.
 */
class ServiceOutputProcessor
{
    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var Request
     */
    private $request;

    /**
     * ServiceOutputProcessor constructor.
     *
     * @param DataObjectProcessor $dataObjectProcessor
     * @param Request $request
     */
    public function __construct(
        DataObjectProcessor $dataObjectProcessor,
        Request $request
    ) {
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->request = $request;
    }

    /**
     * Override convertValue(), add array keys if Squeezely request
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param mixed $data
     * @param string $type
     *
     * @return array|mixed
     */
    public function aroundConvertValue(Subject $subject, callable $proceed, $data, string $type)
    {
        if (strpos($this->request->getRequestUri(), 'squeezely') === false) {
            return $proceed($data, $type);
        }
        if (is_array($data)) {
            $result = [];
            $arrayElementType = substr($type, 0, -2);
            foreach ($data as $key => $datum) {
                if (is_object($datum)) {
                    $datum = $this->processDataObject(
                        $this->dataObjectProcessor->buildOutputDataArray($datum, $arrayElementType)
                    );
                }
                $result[$key] = $datum;
            }
            return $result;
        } elseif (is_object($data)) {
            return $this->processDataObject(
                $this->dataObjectProcessor->buildOutputDataArray($data, $type)
            );
        } elseif ($data === null) {
            return [];
        } else {
            /** No processing is required for scalar types */
            return $data;
        }
    }

    /**
     * Convert data object to array and process available custom attributes
     *
     * @param array $dataObjectArray
     * @return array
     */
    protected function processDataObject($dataObjectArray)
    {
        if (isset($dataObjectArray[AbstractExtensibleObject::CUSTOM_ATTRIBUTES_KEY])) {
            $dataObjectArray = ExtensibleDataObjectConverter::convertCustomAttributesToSequentialArray(
                $dataObjectArray
            );
        }
        //Check for nested custom_attributes
        foreach ($dataObjectArray as $key => $value) {
            if (is_array($value)) {
                $dataObjectArray[$key] = $this->processDataObject($value);
            }
        }
        return $dataObjectArray;
    }
}
