<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Api;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Squeezely\Plugin\Model\Api\CurlExtra as Curl;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface as AdvancedOptionsConfigRepository;
use Squeezely\Plugin\Api\Request\ServiceInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class Request
 */
class Request implements ServiceInterface
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var AdvancedOptionsConfigRepository
     */
    private $advancedOptionsConfigRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Request constructor.
     *
     * @param ConfigRepository $configRepository
     * @param AdvancedOptionsConfigRepository $advancedOptionsRepository
     * @param JsonSerializer $jsonSerializer
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param LogRepository $logRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        AdvancedOptionsConfigRepository $advancedOptionsRepository,
        JsonSerializer $jsonSerializer,
        StoreManagerInterface $storeManager,
        Curl $curl,
        LogRepository $logRepository
    ) {
        $this->configRepository = $configRepository;
        $this->advancedOptionsConfigRepository = $advancedOptionsRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->logRepository = $logRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        array $fields,
        string $endpoint,
        $storeId = null,
        string $method = 'POST',
        bool $allowSoftFail = true
    ) {
        $storeId = $storeId ?: $this->storeManager->getStore()->getId();
        $accountId = $this->configRepository->getAccountId((int)$storeId);
        $apiKey = $this->configRepository->getApiKey((int)$storeId);
        $json = $this->jsonSerializer->serialize($fields);
        $url = $this->advancedOptionsConfigRepository->getApiRequestUri() . $endpoint;

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
        $this->curl->setOption(CURLOPT_TIMEOUT, 30);
        $this->curl->setHeaders(
            [
                "X-AUTH-ACCOUNT" => $accountId,
                "X-AUTH-APIKEY" => $apiKey,
                "Content-Type" => 'application/json',
                "Content-Length" => strlen($json)
            ]
        );

        $this->logRepository->addDebugLog(
            'Request',
            sprintf('%s %s - %s', $method, $url, $json)
        );

        if ($method == 'POST') {
            $this->curl->post($url, $json);
        } elseif ($method == 'DELETE') {
            $this->curl->del($url, $json);
        }

        $this->logRepository->addDebugLog(
            'Response',
            sprintf('%s - %s', $this->curl->getStatus(), $this->curl->getBody())
        );

        $response = $this->jsonSerializer->unserialize($this->curl->getBody());
        $httpStatus = $this->curl->getStatus();

        // We should also treat $httpStatus = 400 as success on set of softFail flag
        if ($allowSoftFail && $httpStatus = 400) {
            $httpStatus = 200;
        }

        if ($httpStatus == 401 || $httpStatus == 403) {
            $error = $this->checkForErrorMessage($response) ?: 'Authentication Failed';
            throw new AuthenticationException(__($error));
        }

        if ($httpStatus !== 200 && $httpStatus !== 201) {
            $error = $this->checkForErrorMessage($response) ?: 'Unknown response from API';
            throw new LocalizedException(__($error));
        }

        return $response;
    }

    /**
     * @param array $response
     * @return mixed|void
     */
    private function checkForErrorMessage(array $response)
    {
        if (!empty($response['errors']) && !empty($response['errors'][0])) {
            return $response['errors'][0];
        }
    }
}
