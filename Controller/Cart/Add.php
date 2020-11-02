<?php
namespace Squeezely\Plugin\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Result\PageFactory;

class Add extends Action {

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $resultFactory;
    protected $formKey;
    protected $redirect;
    protected $cart;
    protected $product;
    protected $_productRepository;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        Product $product,
        RedirectInterface $redirectInterface
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->_productRepository = $productRepository;
        $this->redirect = $redirectInterface;
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute() {

        $quantity = $this->getRequest()->getParam('quantity', 1);
        $productId = $this->getRequest()->getParam('product');
        $productId = $productId ?: $this->getRequest()->getParam('productid', null);
        if($productId) {
            try {
                /** @var ProductInterface $product */
                $product = $this->_productRepository->get($productId);

                if($product) {

                    $params = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $product->getId(),
                        'qty' => $quantity ?: 1,
                    ];

                    $product = $this->product->load($product->getId());
                    $this->cart->addProduct($product, $params);
                    $this->cart->save();
                }
            }
            catch (\Exception $e) {
                //nothing
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        $referer = $this->redirect->getRefererUrl();
        if($referer) {
            $resultRedirect->setRefererUrl($referer);
        }
        return $resultRedirect;
    }

}