<?php

namespace Richdynamix\PersonalisedProducts\Block\Product\ProductList;

use \Magento\Framework\View\Element\Template;
use \Magento\Catalog\Block\Product\Context as Context;
use \Magento\Checkout\Model\ResourceModel\Cart as Cart;
use \Magento\Catalog\Model\Product\Visibility as Visibility;
use \Magento\Checkout\Model\Session as Session;
use \Magento\Framework\Module\Manager as Manager;
use Richdynamix\PersonalisedProducts\Helper\Config as Config;
use \Magento\Catalog\Model\ProductFactory as ProductFactory;
use \Magento\Customer\Model\Session as CustomerSession;
use \Richdynamix\PersonalisedProducts\Model\Frontend\Catalog\Product\ProductList\Upsell as PersonalisedUpsell;

/**
 * Rewrite product upsell block to switch out product collection
 * for one returned from PredictionIO
 *
 * @category  Richdynamix
 * @package   PersonalisedProducts
 * @author    Steven Richardson (steven@richdynamix.com) @mage_gizmo
 */
class Upsell extends \Magento\Catalog\Block\Product\ProductList\Upsell
{
    /**
     * @var Config
     */
    private $_config;

    /**
     * @var ProductFactory
     */
    private $_productFactory;

    /**
     * @var PersonalisedUpsell
     */
    private $_upsell;

    /**
     * @var bool
     */
    protected $_isPredictedResults = false;

    /**
     * Upsell constructor.
     * @param Context $context
     * @param Cart $checkoutCart
     * @param Visibility $productVisibility
     * @param Session $checkoutSession
     * @param Manager $moduleManager
     * @param ProductFactory $productFactory
     * @param Config $config
     * @param PersonalisedUpsell $upsell
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Cart $checkoutCart,
        Visibility $productVisibility,
        Session $checkoutSession,
        Manager $moduleManager,
        ProductFactory $productFactory,
        Config $config,
        PersonalisedUpsell $upsell,
        CustomerSession $customerSession,



        array $data = []
    ) {
        $this->_config = $config;
        $this->_productFactory = $productFactory;
        $this->_upsell = $upsell;
        $this->_customerSession = $customerSession;
        parent::__construct(
            $context,
            $checkoutCart,
            $productVisibility,
            $checkoutSession,
            $moduleManager,
            $data
        );
    }

    /**
     * Rewrite parent _prepareData method to use PredictionIO results when available
     *
     * @return $this
     */
    protected function _prepareData()
    {

        if (!$this->_config->isEnabled()) {
            return parent::_prepareData();
        }

        $product = $this->_coreRegistry->registry('product');
        $categoryIds = $this->_upsell->getCategoryIds($product);
        $personalisedIds = $this->_upsell->getProductCollection([$product->getId()], $categoryIds);

        if (!$personalisedIds) {
            return parent::_prepareData();
        }

        $this->_isPredictedResults = true;

        $collection = $this->_upsell->getPersonalisedProductCollection($personalisedIds);

        $this->_itemCollection = $collection;

        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }

        $this->_eventManager->dispatch(
            'catalog_product_upsell',
            ['product' => $product, 'collection' => $this->_itemCollection, 'limit' => null]
        );

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    /**
     * Get the URL for product upsells block via ajax
     *
     * @return string
     */
    public function getUpsellAjaxUrl()
    {
        return $this->getBaseUrl() . "personalised/products/upsellAjax/id/" . $this->getProduct()->getId();
    }

    /**
     * Check if the displayed results are from PredictionIO
     *
     * @return bool
     */
    public function isPredictedResults()
    {
        return $this->_isPredictedResults;
    }
}
