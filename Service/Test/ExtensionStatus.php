<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Test;

use Magento\Framework\App\Request\Http as HttpRequest;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Extension status test class
 */
class ExtensionStatus
{

    /**
     * Test type
     */
    public const TYPE = 'extension_status';
    /**
     * Test description
     */
    public const TEST = 'Check if the extension is enabled in the configuration.';
    /**
     * Visibility
     */
    public const VISIBLE = true;
    /**
     * Message on test success
     */
    public const SUCCESS_MSG = 'Extension is enabled';
    /**
     * Message on test failed
     */
    public const FAILED_MSG = 'Extension disabled, please enable it!';
    /**
     * Expected result
     */
    public const EXPECTED = true;

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * Repository constructor.
     *
     * @param ConfigRepository $configRepository
     * @param HttpRequest $request
     */
    public function __construct(
        ConfigRepository $configRepository,
        HttpRequest $request
    ) {
        $this->configRepository = $configRepository;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE
        ];

        $storeId = (int)$this->request->getParam('store_id');
        if ($this->configRepository->isEnabled($storeId) == self::EXPECTED) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result += ['result_code' => 'success'];
        } else {
            $result['result_msg'] = self::FAILED_MSG;
            $result += ['result_code' => 'failed'];
        }

        return $result;
    }
}
