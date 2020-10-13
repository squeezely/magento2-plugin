<?php
namespace Squeezely\Plugin\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action
{

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
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface
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

                    $params = array(
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $product->getId(),
                        'qty'   => $quantity ?: 1,
                    );

                    $product = $this->product->load($product->getId());
                    $this->cart->addProduct($product, $params);
                    $this->cart->save();
                }
            } catch (\Exception $e) {
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