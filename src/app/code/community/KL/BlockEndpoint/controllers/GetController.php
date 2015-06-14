<?php
/**
 * Block Endpoint
 *
 * @category  KL
 * @package   KL_BlockEndpoint
 * @author    Andreas Karlsson <andreas@karlssonlord.com>
 * @copyright 2015 Karlsson & Lord AB
 * @license   http://opensource.org/licenses/MIT
 */

/**
 * Get controller
 *
 * @category  KL
 * @package   KL_BlockEndpoint
 * @author    Andreas Karlsson <andreas@karlssonlord.com>
 * @copyright 2015 Karlsson & Lord AB
 * @license   http://opensource.org/licenses/MIT
 */
class KL_BlockEndpoint_GetController extends Mage_Core_Controller_Front_Action
{
    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $request = $this->getRequest()->getParam('request');
        $request = json_decode($request, true);

        $this->_jsonResponse($this->_getBlocks($request));
    }

    /**
     * Get blocks
     *
     * @param array $blockNames Array with block names
     *
     * @return array
     */
    protected function _getBlocks($request)
    {
        $response = array();

        foreach ($request as $key => $block) {
            if (array_key_exists('register', $block)) {
                foreach ($block['register'] as $key => $value) {
                    switch ($key) {
                        case 'product':
                            $helper = Mage::helper('catalog/product_view');

                            // Prepare data
                            $productHelper = Mage::helper('catalog/product');
                            $params = new Varien_Object();

                            // Standard algorithm to prepare and rendern product view page
                            $product = $productHelper->initProduct($value, $this, $params);

                            if (!$product) {
                                throw new Mage_Core_Exception($this->__('Product is not loaded'), $this->ERR_NO_PRODUCT_LOADED);
                            }

                            Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());
                            break;
                        default:
                            Mage::register($key, $value);
                            break;
                    };
                };
            }
            if (array_key_exists('layout', $block) && array_key_exists('name', $block)) {
                $layout = $this->getLayout();

                $update = $layout->getUpdate();
                $update->load($block['layout']);

                $layout->generateXml();
                $layout->generateBlocks();

                $htmlBlock = $layout->getBlock($block['name']);

                if (array_key_exists('setData', $block)) {
                    $htmlBlock->setData($block['setData']);
                }

                $html = $htmlBlock->toHtml();
            } else if (array_key_exists('block', $block) && array_key_exists('template', $block)) {
                $layout = Mage::getSingleton('core/layout');
                $htmlBlock = $layout
                    ->createBlock($block['block'])
                    ->setTemplate($block['template']);

                if (array_key_exists('setData', $block)) {
                    $htmlBlock->setData($block['setData']);
                }

                $html = $htmlBlock->toHtml();
            } else {
                continue;
            }

            $response[$key] = $html;
        }

        return $response;
    }

    /**
     * Genereate JSON response
     *
     * @param array $data Block information
     *
     * @return void
     */
    protected function _jsonResponse($data = array())
    {
        $jsonData = Mage::helper('core')->jsonEncode($data);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
