<?php

namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use mysql_xdevapi\Exception;
use Psr\Log\LoggerInterface as Logger;
use Squeezely\Plugin\Helper\SqueezelyApiHelper as SqueezelyApiHelper;
use Squeezely\Plugin\Helper\Data as SqueezelyDataHelper;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use \stdClass;

class EditConfigAdmin implements ObserverInterface {

    protected $_logger;
    private $_request;
    private $_configWriter;
    private $_squeezelyHelperApi;
    private $_squeezelyDataHelper;
    private $_objectManager;

    public function __construct(
        RequestInterface $request,
        WriterInterface $configWriter,
        Logger $logger,
        SqueezelyApiHelper $squeezelyHelperApi,
        SqueezelyDataHelper $squeezelyDataHelper,
        ObjectManager $objectManager
    ) {
        $this->_request = $request;
        $this->_configWriter = $configWriter;
        $this->_logger = $logger;
        $this->_squeezelyHelperApi = $squeezelyHelperApi;
        $this->_squeezelyDataHelper = $squeezelyDataHelper;
        $this->_objectManager = $objectManager;
    }
it
    public function execute(EventObserver $observer) {
        $goeieArr = ['apiKeySQZLY: ' => $this->_squeezelyDataHelper->getSqueezelyApiKey()];

//        $this->_logger->info("JA JA GOEIE MAaaaaINNNNNNNsssdNNNNNNNNNNNNNNNNNNNNNNNNNNmnnnnnnnnNNNNN");
        $this->createMagentoIntegration();
    }

    private function verifySqueezelyAuth() {

    }

    private function createMagentoIntegration() {
        $name = "Squeezely Integration";
        $endPoint = "https://hattardev.sqzly.nl/callback/magento2"; // TODO: Change url to production url

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
            // Code to create Integration
            $integrationFactory = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create();
            $integration = $integrationFactory->setData($integrationData);
            $integration->save();
            $integrationId = $integration->getId();
            $consumerName = 'Integration ' . $integrationId;

            // Code to create consumer
            $oauthService = $this->_objectManager->get('Magento\Integration\Model\OauthService');
            $consumer = $oauthService->createConsumer(['name' => $consumerName]);
            $consumerId = $consumer->getId();
            $integration->setConsumerId($consumer->getId());
            $integration->save();

            // Code to grant permission
            $authrizeService = $this->_objectManager->get('Magento\Integration\Model\AuthorizationService');
            $authrizeService->grantAllPermissions($integrationId);

            // Code to Activate and Authorize
            $token = $this->_objectManager->get('Magento\Integration\Model\Oauth\Token');
            $uri = $token->createVerifierToken($consumerId);
            $token->setType('access');
            $token->save();

            $this->_logger->info("token info: ", $token->toArray());

            $this->_logger->info("URL SQUEEZELY INFO GHELOGGOODODOD: ", ['apiHelper' => $this->_squeezelyHelperApi->sendMagentoTokenToSqueezely($token->toArray())]);

        }
        catch (Exception $e) {
            $this->_logger->error("JA JA ERRORRR: " . $e->getMessage());

            echo "Error : " . $e->getMessage();
        }

//        $this->_logger->info("Integration token bestaat INFOOOOO: ", $integrationExists);
//        $this->_logger->info("Integration token bestaat al!!!!");

    }

}