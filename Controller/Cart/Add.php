<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class Add
 */
class Add extends Action
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var FormKey
     */
    private $formKey;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var Product
     */
    private $product;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Add constructor.
     *
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param FormKey $formKey
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Product $product
     * @param RedirectInterface $redirectInterface
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        Product $product,
        RedirectInterface $redirectInterface,
        LogRepository $logRepository
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        $this->resultFactory = $resultFactory;
        $this->productRepository = $productRepository;
        $this->redirect = $redirectInterface;
        $this->logRepository = $logRepository;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $quantity = $this->getRequest()->getParam('quantity', 1);
        $productId = $this->getRequest()->getParam('product');
        $productId = $productId ?: $this->getRequest()->getParam('productid', null);
        if ($productId !== null) {
            try {
                $product = $this->productRepository->get($productId);
                if ($product) {
                    $params = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $product->getId(),
                        'qty'   => $quantity ?: 1,
                    ];
                    $product = $this->product->load($product->getId());
                    $this->cart->addProduct($product, $params);
                    $this->cart->save();
                }
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('AddToCart', $e->getMessage());
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        $referer = $this->redirect->getRefererUrl();
        if ($referer) {
            $resultRedirect->setRefererUrl();
        }
        return $resultRedirect;
    }
}
