<?php

namespace Squeezely\Plugin\Observer;

use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use mysql_xdevapi\Exception;
use Psr\Log\LoggerInterface as Logger;
use Squeezely\Plugin\Helper\SqueezelyApiHelper as SqueezelyApiHelper;
use Squeezely\Plugin\Helper\Data as SqueezelyDataHelper;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class EditConfigAdmin implements ObserverInterface
{

    protected $_logger;
    protected $_storeManager;
    protected $_messageManager;
    protected $productRepository;
    protected $filterBuilder;

    private $_squeezelyHelperApi;
    private $_squeezelyDataHelper;
    private $_objectManager;
    private $_searchCriteriaBuilder;
    private $productUrlPathGenerator;

    public function __construct(
        Logger $logger,
        SqueezelyApiHelper $squeezelyHelperApi,
        SqueezelyDataHelper $squeezelyDataHelper,
        ObjectManager $objectManager,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        FilterBuilder $filterBuilder,
        ProductUrlPathGenerator $productUrlPathGenerator
    ) {
        $this->_logger = $logger;
        $this->_squeezelyHelperApi = $squeezelyHelperApi;
        $this->_squeezelyDataHelper = $squeezelyDataHelper;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->filterBuilder = $filterBuilder;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
    }

    public function execute(EventObserver $observer)
    {
        $this->createMagentoIntegration();
    }

    private function createMagentoIntegration()
    {
        $name = "Squeezely Integration";

        $endPoint = "https://hattardev.sqzly.nl/callback/magento2"; // TODO: Change url to production url
//        $endPoint = "https://squeezely.tech/callback/magento2";

        $integrationExists = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($name, 'name')->getData();

        if (!empty($integrationExists)) {
            $removeIntegration = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($name, 'name');
            $removeIntegration->delete();
        }

        $integrationData = [
            'name' => $name,
            'endpoint' => $endPoint,
            'status' => '1',
            'setup_type' => '0',
        ];
        try {
            // Create Integration
            $integrationFactory = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create();
            $integration = $integrationFactory->setData($integrationData);
            $integration->save();
            $integrationId = $integration->getId();
            $consumerName = 'Integration ' . $integrationId;

            // Create consumer
            $oauthService = $this->_objectManager->get('Magento\Integration\Model\OauthService');
            $consumer = $oauthService->createConsumer(['name' => $consumerName]);
            $consumerId = $consumer->getId();
            $integration->setConsumerId($consumer->getId());
            $integration->save();

            // Grant permission
            $authrizeService = $this->_objectManager->get('Magento\Integration\Model\AuthorizationService');
            $authrizeService->grantAllPermissions($integrationId);

            // Activate and Authorize
            $token = $this->_objectManager->get('Magento\Integration\Model\Oauth\Token');
            $uri = $token->createVerifierToken($consumerId);
            $token->setType('access');
            $token->save();

            $storeInformationAndToken = array_merge($this->getStoreInformation(), $token->toArray());
            $isVerified = $this->_squeezelyHelperApi->sendMagentoTokenToSqueezelyAndVerifyAuth($storeInformationAndToken);

            if($isVerified) {
                $this->_messageManager->addSuccessMessage("Squeezely credentials are successfully verified");
            }
            else {
                $this->_messageManager->addErrorMessage("Could not verify given Squeezely credentials, please try again later or contact support@squeezely.tech.");
            }
        }
        catch (Exception $e) {
            $this->_logger->error("LOGGER ERROR INFO: " . $e->getMessage());
            $this->_messageManager->addErrorMessage("Could not verify given Squeezely credentials, please try again later or contact support@squeezely.tech.");
        }
    }

    private function getStoreInformation()
    {
        $storeInformation= [
            'webshopName' => $this->_storeManager->getStore()->getName() . " - Magento 2",
            'webshopUrl' => $this->_storeManager->getStore()->getBaseUrl(),
            'webshopSuffix' => $this->getStoreSuffix()
        ];

        $this->_logger->info("Webstore information: ", $storeInformation);
        return $storeInformation;
    }

    private function getStoreSuffix()
    {
        // Get a random product so we can get the url suffix and format it
        $filter = [$this->filterBuilder
            ->setField('sku')
            ->setConditionType('neq')
            ->setValue('NoSKU')
            ->create()];

        $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filter)->setPageSize(1)->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();

        if(isset($products[1])) {
            $urlWithSuffix = $this->productUrlPathGenerator->getUrlPathWithSuffix($products[1], $this->_storeManager->getStore()->getId());
            $urlKey = $products[1]->getUrlKey();
            $formattedString = str_replace($urlKey, "", $urlWithSuffix);
            return $formattedString;
        }

        return null;
    }

}