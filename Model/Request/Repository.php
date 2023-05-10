<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Request;

use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Api\Request\ServiceInterface as RequestService;

/**
 * Request Repository
 */
class Repository implements RequestRepository
{

    /**
     * @var RequestService
     */
    private $requestService;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Repository constructor.
     *
     * @param RequestService $requestService
     * @param LogRepository $logRepository
     */
    public function __construct(
        RequestService $requestService,
        LogRepository $logRepository
    ) {
        $this->requestService = $requestService;
        $this->logRepository = $logRepository;
    }

    /**
     * @inheritDoc
     */
    public function sendProducts(array $products): array
    {
        if (empty($products['products'])) {
            $this->logRepository->addDebugLog('Request', __('Skipped empty products'));
            return [
                'success' => false,
                'message' => 'skipped empty products',
                'created' => 0,
                'updated' => 0
            ];
        }
        return $this->requestService->execute($products, self::PRODUCT_END_POINT);
    }

    /**
     * @inheritDoc
     */
    public function sendDeleteProducts(array $products): array
    {
        return $this->requestService->execute($products, self::PRODUCT_DELETE_ENDPOINT, null, 'DELETE');
    }

    /**
     * @inheritDoc
     */
    public function sendToPlatform(array $eventData): array
    {
        return $this->requestService->execute($eventData, self::TRACKER_END_POINT);
    }

    /**
     * @inheritDoc
     */
    public function sendMagentoTokenToSqueezelyAndVerifyAuth(array $magentoToken, int $storeId): bool
    {
        $response = $this->requestService->execute(
            $magentoToken,
            self::VERIFY_API_LOGIN_END_POINT,
            $storeId
        );

        if (isset($response['verified']) && $response['verified'] == true) {
            return true;
        } else {
            $this->logRepository->addErrorLog('VerifyAuth', $response);
        }

        return false;
    }
}
