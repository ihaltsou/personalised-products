<?php

namespace Richdynamix\PersonalisedProducts\Controller\Products;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\App\Action\Context;
use \Richdynamix\PersonalisedProducts\Model\ProductView as ProductViewModel;

/**
 * Class ProductView
 *
 * @category  Richdynamix
 * @package   PersonalisedProducts
 * @author    Steven Richardson (steven@richdynamix.com) @mage_gizmo
 */
class ProductView extends Action {

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * ProductView constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductViewModel $productView
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductViewModel $productView
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_productView = $productView;
        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();

        $productId = $this->getRequest()->getParam('id');
        if (empty($productId)) {
            return $result->setData(['error' => true, 'message' => 'Product ID has not been supplied']);
        }

        $pageViewResult = $this->_productView->processViews($productId);
        if (!$pageViewResult) {
            return $result->setData(['error' => true, 'message' => 'There was an error processing the product view.']);
        }

        return $result->setData(['success' => true, 'message' => 'Product view logged in PredictionIO']);
    }
}