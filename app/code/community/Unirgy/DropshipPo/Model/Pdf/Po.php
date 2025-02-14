<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Model_Pdf_Po extends Mage_Sales_Model_Order_Pdf_Abstract
{
    protected function insertLogo(&$page, $store = null)
    {
        if (Mage::helper('udropship')->compareMageVer('1.7.0.0', '1.12.0', '>=')) {
            return $this->insertLogo17($page, $store);
        } else {
            return $this->insertLogoLT17($page, $store);
        }
    }
    protected function insertLogoLT17(&$page, $store = null)
    {
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getStoreConfig('system/filesystem/media', $store) . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);
                $page->drawImage($image, 25, 800, 125, 825);
            }
        }
        //return $page;
    }
    protected function insertLogo17(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 800;
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image       = Zend_Pdf_Image::imageWithPath($image);
                $top         = 830; //top border of the page
                $widthLimit  = 270; //half of the page width
                $heightLimit = 270; //assuming the image is not a "skyscraper"
                $width       = $image->getPixelWidth();
                $height      = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width  = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width  = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 25;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }
    protected function _setFontRegular($object, $size = 7)
    {
        if (!$this->getUseFont()) {
            return parent::_setFontRegular($object, $size);
        }
        $font = Zend_Pdf_Font::fontWithName(constant('Zend_Pdf_Font::FONT_'.$this->getUseFont()));
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontBold($object, $size = 7)
    {
        if (!$this->getUseFont()) {
            return parent::_setFontBold($object, $size);
        }
        $font = Zend_Pdf_Font::fontWithName(constant('Zend_Pdf_Font::FONT_'.$this->getUseFont().'_BOLD'));
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontItalic($object, $size = 7)
    {
        if (!$this->getUseFont()) {
            return parent::_setFontItalic($object, $size);
        }
        $font = Zend_Pdf_Font::fontWithName(constant('Zend_Pdf_Font::FONT_'.$this->getUseFont().'_ITALIC'));
        $object->setFont($font, $size);
        return $font;
    }
    
    protected $_currentPo;
    
    public function getPdf($udpos = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('udpo');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        foreach ($udpos as $udpo) {
            if ($udpo->getStoreId()) {
                Mage::app()->getLocale()->emulate($udpo->getStoreId());
            }
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;
            
            $this->_currentPo = $udpo;

            $order = $udpo->getOrder();

            /* Add image */
            $this->insertLogo($page, $udpo->getStore());

            /* Add address */
            $this->insertAddress($page, $udpo->getStore());

            $top = $this->y ? $this->y : 815;
            /* Add head */
            $this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID, $order->getStoreId()));

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
            $this->_setFontRegular($page);
            $page->drawText(Mage::helper('udropship')->__('Purchase Order # ') . $udpo->getIncrementId(), 35, ($top-=25), 'UTF-8');

            /* Add table */
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);


            /* Add table head */
            $page->drawRectangle(25, $this->y, 570, $this->y-15);
            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.4, 0.4, 0.4));
            $page->drawText(Mage::helper('udropship')->__('Products'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('SKU'), 255, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Cost'), 380, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Qty'), 470, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Row Cost'), 535, $this->y, 'UTF-8');

            $this->y -=15;

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            /* Add body */
            foreach ($udpo->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y<75+10*count($this->getCustomTextArray($udpo))) {
                    $this->drawCustomText($udpo, $page);
                    $page = $this->newPage(array('table_header' => true));
                }

                /* Draw item */
                $page = $this->_drawItem($item, $page, $order);
            }
            $this->drawCustomText($udpo, $page);
            
            $page = $this->insertTotals($page, $udpo);
        }

        $this->_afterGetPdf();

        if ($udpo->getStoreId()) {
            Mage::app()->getLocale()->revert();
        }
        return $pdf;
    }
    
    public function insertTotals($page, $udpo)
    {
        $lineBlock = array(
            'height' => 15,
            'lines'  => array(array(
                array(
                    'text'      => Mage::helper('udropship')->__('Total Cost'),
                    'feed'      => 475,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
                array(
                    'text'      => $udpo->getOrder()->getBaseCurrency()->formatTxt($udpo->getTotalCost()),
                    'feed'      => 565,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
            ))
        );

        if ($udpo->getIsManual()) {
            $lineBlock[0]['lines'][] = array(
                array(
                    'text'      => Mage::helper('udropship')->__('Total Shipping'),
                    'feed'      => 475,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
                array(
                    'text'      => $udpo->getOrder()->getBaseCurrency()->formatTxt($udpo->getBaseShippingAmount()),
                    'feed'      => 565,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
            );
            $lineBlock[0]['lines'][] = array(
                array(
                    'text'      => Mage::helper('udropship')->__('Grand Total'),
                    'feed'      => 475,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
                array(
                    'text'      => $udpo->getOrder()->getBaseCurrency()->formatTxt($udpo->getTotalCost()+$udpo->getBaseShippingAmount()),
                    'feed'      => 565,
                    'align'     => 'right',
                    'font_size' => 7,
                    'font'      => 'bold'
                ),
            );
        }
        
        return $this->drawLineBlocks($page, array($lineBlock));
    }
    
    public function newPage(array $settings = array())
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;

        if (!empty($settings['table_header'])) {
            $this->_setFontRegular($page);
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 570, $this->y-15);
            $this->y -=10;

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.4, 0.4, 0.4));
            $page->drawText(Mage::helper('udropship')->__('Products'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('SKU'), 255, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Cost'), 380, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Qty'), 470, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Row Cost'), 535, $this->y, 'UTF-8');

            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $this->y -=20;
        }

        return $page;
    }
    
    protected function insertOrder(&$page, $order, $putOrderId = true)
    {
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        /* @var $order Mage_Sales_Model_Order */
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));

        $page->drawRectangle(25, $top, 570, $top-55);

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page);


        if ($putOrderId) {
            $page->drawText(Mage::helper('udropship')->__('Order # ').$order->getRealOrderId(), 35, ($top -= 10), 'UTF-8');
        }
        //$page->drawText(Mage::helper('udropship')->__('Order Date: ') . date( 'D M j Y', strtotime( $order->getCreatedAt() ) ), 35, 760, 'UTF-8');
        $page->drawText(Mage::helper('udropship')->__('Order Date: ') . Mage::helper('core')->formatDate($order->getCreatedAtStoreDate(), 'medium', false), 35, ($top -= 30), 'UTF-8');

        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, ($top -= 5), 275, ($top-25));
        $page->drawRectangle(275, $top, 570, ($top-25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress(
            Mage::helper('udropship')->formatCustomerAddress($order->getBillingAddress(), 'pdf', $this->_currentPo->getUdropshipVendor())
        );

        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->toPdf();
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key=>$value){
            if (strip_tags(trim($value))==''){
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress(
                Mage::helper('udropship')->formatCustomerAddress($order->getShippingAddress(), 'pdf', $this->_currentPo->getUdropshipVendor())
            );

            $shippingMethod  = $order->getShippingDescription();
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);
        $page->drawText(Mage::helper('udropship')->__('SOLD TO:'), 35, ($top-15) , 'UTF-8');

        if (!$order->getIsVirtual()) {
            $page->drawText(Mage::helper('udropship')->__('SHIP TO:'), 285, ($top-15) , 'UTF-8');
        }
        else {
            $page->drawText(Mage::helper('udropship')->__('Payment Method:'), 285, ($top-15) , 'UTF-8');
        }

        if (!$order->getIsVirtual()) {
            $y = ($top-25) - (max(count($billingAddress), count($shippingAddress)) * 10 + 5);
        }
        else {
            $y = ($top-25) - (count($billingAddress) * 10 + 5);
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, ($top-25), 570, $y);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);
        $this->y = ($top-35);

        foreach ($billingAddress as $value){
            if ($value!=='') {
                $page->drawText(strip_tags(ltrim($value)), 35, $this->y, 'UTF-8');
                $this->y -=10;
            }
        }

        if (!$order->getIsVirtual()) {
            $this->y = ($top-35);
            foreach ($shippingAddress as $value){
                if ($value!=='') {
                    $page->drawText(strip_tags(ltrim($value)), 285, $this->y, 'UTF-8');
                    $this->y -=10;
                }

            }

            $this->y = $y;
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y-25);
            $page->drawRectangle(275, $this->y, 570, $this->y-25);

            $this->y -=15;
            $this->_setFontBold($page);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('udropship')->__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('udropship')->__('Shipping Method:'), 285, $this->y , 'UTF-8');

            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments   = $this->y - 15;
        }
        else {
            $yPayments   = ($top-25);
            $paymentLeft = 285;
        }

        foreach ($payment as $value){
            if (trim($value)!=='') {
                $page->drawText(strip_tags(trim($value)), $paymentLeft, $yPayments, 'UTF-8');
                $yPayments -=10;
            }
        }

        if (!$order->getIsVirtual()) {
            $this->y -=15;

            $page->drawText($shippingMethod, 285, $this->y, 'UTF-8');

            $yShipments = $this->y;

            $curVendor = Mage::helper('udropship')->getVendor($this->_currentPo->getUdropshipVendor());
            if (!$curVendor->getHideUdpoPdfShippingAmount()) {
                $totalShippingChargesText = "(" . Mage::helper('udropship')->__('Total Shipping Charges') . " " . $order->formatPriceTxt($order->getShippingAmount()) . ")";
    
                $page->drawText($totalShippingChargesText, 285, $yShipments-7, 'UTF-8');
            }
            $yShipments -=10;
            $tracks = $order->getTracksCollection();
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(380, $yShipments, 380, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('udropship')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('udropship')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('udropship')->__('Number'), 385, $yShipments - 7, 'UTF-8');

                $yShipments -=17;
                $this->_setFontRegular($page, 6);
                foreach ($order->getTracksCollection() as $track) {

                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode!='custom')
                    {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    }
                    else
                    {
                        $carrierTitle = Mage::helper('udropship')->__('Custom Value');
                    }

                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $truncatedTitle = substr($track->getTitle(), 0, 45) . (strlen($track->getTitle()) > 45 ? '...' : '');
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 300, $yShipments , 'UTF-8');
                    $page->drawText($track->getNumber(), 395, $yShipments , 'UTF-8');
                    $yShipments -=7;
                }
            } else {
                $yShipments -= 7;
            }

            $currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $this->y + 15, 25, $currentY);
            $page->drawLine(25, $currentY, 570, $currentY);
            $page->drawLine(570, $currentY, 570, $this->y + 15);

            $this->y = $currentY;
            $this->y -= 15;
        }
    }

    public function getSpdTextWidth($string, $page)
    {
        $drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $string);
        $characters = array();
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }
        $font = $page->getFont();
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $page->getFontSize();
        return $stringWidth;
    }

    protected $_customTextArr = array();
    protected function getCustomTextArray($po)
    {
        if (!isset($this->_customTextArr[$po->getId()])) {
            $customText = Mage::getStoreConfig('udropship/purchase_order/udpo_pdf_custom_text', $po->getStoreId());
            $curVendor = Mage::helper('udropship')->getVendor($po->getUdropshipVendor());
            $vUsePSCT = $curVendor->getData('use_udpo_pdf_custom_text');
            if ($vUsePSCT==1) {
                $customText = $curVendor->getData('udpo_pdf_custom_text');
            } elseif ($vUsePSCT==0) {
                $customText = '';
            }
            $_customTextArr = preg_split("/\r\n|\r|\n/", $customText);
            $customTextArr = array();
            foreach ($_customTextArr as $_cti) {
                if (($_cti = trim($_cti))) {
                    $customTextArr[] = $_cti;
                }
            }
            $this->_customTextArr[$po->getId()] = $customTextArr;
        }
        return $this->_customTextArr[$po->getId()];
    }

    protected function drawCustomText($shipment, $page)
    {
        $this->_setFontBold($page);
        foreach ($this->getCustomTextArray($shipment) as $i => $__cti) {
            if (($_cti = preg_replace('/\[%\s*bold\s*%\]/', '', $__cti))!=$__cti) {
                $this->_setFontBold($page);
            } else {
                $this->_setFontRegular($page);
            }
            $_ctiWidth = $this->getSpdTextWidth($_cti, $page);
            $page->drawText($_cti, 35+(545-$_ctiWidth)/2, $this->y-(30+$i*10), 'UTF-8');
        }
        $this->_setFontRegular($page);
    }
}