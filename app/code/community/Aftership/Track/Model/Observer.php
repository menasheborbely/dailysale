<?php
require_once Mage::getBaseDir('lib').DS.'SweetTooth/pest/vendor/autoload.php';

class Aftership_Track_Model_Observer {

    const ENDPOINT_TRACKING = 'https://api.aftership.com/v4/trackings';
    const ENDPOINT_AUTHENTICATE = 'https://api.aftership.com/v4/couriers';

    const POSTED_NOT_YET = 0;
    const POSTED_DONE = 1;
    const POSTED_DISABLED = 2;

    private $_configs = array();

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer) {
        ob_start();

        $magento_track = $observer->getEvent()->getTrack();

        $magento_order = $magento_track->getShipment()->getOrder();
        $website_config = $this->_getWebsiteConfig($magento_order);

        $tracks = Mage::getModel('track/track')
            ->getCollection()
            ->addFieldToFilter('tracking_number', array('eq' => $this->_getTrackNo($magento_track)))
            ->addFieldToFilter('order_id', array('eq' => $magento_order->getIncrementId()))
            ->getItems();

        if (empty($tracks)) {
            $track = $this->_saveTrack($magento_track);
        }
        else {
            $track = reset($tracks);
        }

        if ($website_config->status) {
            $this->_sendTrack($track);
        }

        ob_end_clean();
    }

    /** For vendor
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderShipmentTrackSaveAfterVendor(Varien_Event_Observer $observer) {
        ob_start();

        $magento_track = $observer->getEvent()->getTrack();

        $magento_order = $magento_track->getShipment()->getOrder();
        $website_config = $this->_getWebsiteConfig($magento_order);

        $tracks = Mage::getModel('track/track')
            ->getCollection()
            ->addFieldToFilter('tracking_number', array('eq' => $this->_getTrackNo($magento_track)))
            ->addFieldToFilter('order_id', array('eq' => $magento_order->getIncrementId()))
            ->getItems();

        if (empty($tracks)) {
            $track = $this->_saveTrack($magento_track);
        }
        else {
            $track = reset($tracks);
        }

        if ($website_config->status) {
            $this->_sendTrack($track);
        }

        ob_end_clean();
    }

    /**
     * Cron to sync trackings
     */
    public function cron() {
        set_time_limit(0);

        $global_config = Mage::getStoreConfig('aftership_options/messages');

        $last_update = $global_config['last_update'];

        $debug_range = 1;

        if ($last_update == '0' || !$last_update) {
            $from = gmdate('Y-m-d H:i:s', time() - 3 * 60 * 60 * $debug_range); //past 3 hours
            $to = gmdate('Y-m-d H:i:s');
        } else {
            $from = gmdate('Y-m-d H:i:s', $last_update);
            $to = gmdate('Y-m-d H:i:s');
        }

        $track_collection = Mage::getResourceModel('sales/order_shipment_track_collection')
            ->addAttributeToFilter('created_at', array(
                'from' => $from,
                'to' => $to,
            ))
            ->addAttributeToSort('created_at', 'asc');

        foreach ($track_collection as $magento_track) {
            $order = Mage::getModel('sales/order')->load($magento_track->getOrderId(), 'increment_id');
            $website_config = $this->_getWebsiteConfig($order);

            if ($website_config->status) {
                $tracks = Mage::getModel('track/track')
                    ->getCollection()
                    ->addFieldToFilter('tracking_number', array('eq' => $this->_getTrackNo($magento_track)))
                    ->getItems();

                $is_send = false;

                if (empty($tracks)) {
                    // for the case that salesOrderShipmentTrackSaveAfter() is bypassed/crashed in shipment creation
                    $track = $this->_saveTrack($magento_track);
                    $is_send = true;
                }
                else {
                    $track = reset($tracks);
                    if ($track->getPosted() == self::POSTED_NOT_YET) {
                        // for the case that the tracking was somehow failed to send to aftership
                        $is_send = true;

                    }
                    // else its done or disabled, do nothing
                }

                if ($is_send) {
                    $this->_sendTrack($track);
                }
            }
        }

        //update time ONLY if success
        Mage::getModel('core/config')->saveConfig('aftership_options/messages/last_update', time());

        foreach (Mage::app()->getWebsites() as $website) {
            $website_id = $website->getId();
            $scope = 'websites';
            $scope_id = (int)Mage::getConfig()->getNode('websites/' . Mage::app()->getWebsite($website_id)->getCode() . '/system/website/id');

            Mage::getModel('core/config_data')
                ->setScope($scope)
                ->setScopeId($scope_id)
                ->setPath('aftership_options/messages/last_update')
                ->setValue(time())
                ->save();
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function adminSystemConfigChangedSectionAftership(Varien_Event_Observer $observer)
    {
        $post_data = Mage::app()->getRequest()->getPost();

        if (
            !isset($post_data['groups']['messages']['fields']['api_key']['inherit']) ||
            $post_data['groups']['messages']['fields']['api_key']['inherit'] != 1
        ) {
            $api_key = $post_data['groups']['messages']['fields']['api_key']['value'];

            $http_status = $this->_callApiAuthenticate($api_key);

            if ($http_status == '401') {
                Mage::throwException(Mage::helper('adminhtml')->__('Incorrect API Key'));
            }
            else if ($http_status != '200') {
                Mage::throwException(Mage::helper('adminhtml')->__('Connection error, please try again later.'));
            }
        }
    }


    /**
     * @param Mage_Sales_Model_Order_Shipment_Track $magento_track
     * @return mixed
     */
    private function _saveTrack(Mage_Sales_Model_Order_Shipment_Track $magento_track) {
        $magento_track_data = $magento_track->getData();
        $magento_order_data = $magento_track->getShipment()->getOrder()->getData();
        $magento_shipping_address_data = $magento_track->getShipment()->getOrder()->getShippingAddress()->getData();

        $track = Mage::getModel('track/track');
        $track->setTrackingNumber($this->_getTrackNo($magento_track));
        $track->setShipCompCode($magento_track_data['carrier_code']);
        $track->setTitle($magento_order_data['increment_id']);

        $track->setOrderId($magento_order_data['increment_id']);

        if ($magento_order_data['customer_email'] && $magento_order_data['customer_email'] != '') {
            $track->setEmail($magento_order_data['customer_email']);
        }

        if ($magento_shipping_address_data['telephone'] && $magento_shipping_address_data['telephone'] != '') {
            $track->setTelephone($magento_shipping_address_data['telephone']);
        }

        $config = $this->_getWebsiteConfig($magento_track->getShipment()->getOrder());
        if ($config->status) {
            $track->setPosted(self::POSTED_NOT_YET);
        }
        else {
            // mark it as disabled so that cron will not touch it
            $track->setPosted(self::POSTED_DISABLED);
        }

        $track->save();

        return $track;
    }

    /**
     * @param Aftership_Track_Model_Track $track
     * @return bool|mixed
     */
    private function _sendTrack(Aftership_Track_Model_Track $track) {
        if ($track->getPosted() != self::POSTED_NOT_YET) {
            return false;
        }

        $order = Mage::getModel('sales/order')->load($track->getOrderId(), 'increment_id');
        $website_config = $this->_getWebsiteConfig($order);

        $shipping_address = $order->getShippingAddress();

        $api_key = $website_config->api_key;
        $country_id = $shipping_address->getCountryId();
        $telephone = $shipping_address->getTelephone();
        $carrier_code = $track->getCarrierCode();
        $email = $order->getCustomerEmail();
        $title = $track->getOrderId();
        $order_id = $track->getOrderId();
        $customer_name = $shipping_address->getFirstname() . ' ' . $shipping_address->getLastname();

        $response = $this->_callApiCreateTracking($api_key, $track->getTrackingNumber(), $carrier_code, $country_id, $telephone, $email, $title, $order_id, $customer_name);
        $responseJson = Mage::helper('core')->jsonDecode($response);
        $http_status = $responseJson['meta']['code'];
        $data = $responseJson['data'];
        $configValue = Mage::getStoreConfig('aftership_options/messages/aftership_validation');
        //save, 422: repeated
        if($configValue){
            if ($http_status == '201' || $http_status == '422') {
                $track->setPosted(self::POSTED_DONE)->save();
            }else {
                if ($track->getPackageCount() > 1) {
                    foreach (Mage::getResourceModel('sales/order_shipment_track_collection')
                                 ->addAttributeToFilter('master_tracking_id', $track->getMasterTrackingId())
                             as $_track
                    ) {
                        $_track->delete();
                    }
                }
                if ($track->getPackTracking() !== '1') {
                    $track->setErrorTracking($responseJson['meta']['message']);
                    $track->save();
                    //Mage::throwException($responseJson['meta']['message']);
                } else {
                    $track->setErrorTracking($responseJson['meta']['message']);
                    $track->save();
                }
            }
            if($http_status == '201' && array_key_exists('tracking',$data)){
                $tracking = $data['tracking'];
                $track->setTrackingId(array_key_exists('id',$tracking)?$tracking['id']:0);
                $track->setSlug(array_key_exists('slug',$tracking)?$tracking['slug']:0);
                $track->setPackTracking(false);
                $track->save();
            }
        }

        return $http_status;
    }

    /**
     * Call API to create tracking
     * @param $api_key
     * @param $tracking_number
     * @param $carrier_code (NOT USING CURRENTLY)
     * @param $country_id
     * @param $telephone
     * @param $email
     * @param $title
     * @param $order_id
     * @param $customer_name
     * @return mixed
     */
    private function _callApiCreateTracking($api_key, $tracking_number, $carrier_code, $country_id, $telephone, $email, $title, $order_id, $customer_name) {
        $url_params = array('tracking'	=> array(
            'tracking_number'	        => $tracking_number,
            'destination_country_iso3'  => $country_id,
            'smses'				        => $telephone,
            'emails'			        => $email,
            'title'				        => $title,
            'order_id'			        => $order_id,
            'customer_name'		        => $customer_name,
            'source'			        => 'magento'
        ));

        $json_params = json_encode($url_params);

        $headers = array(
            'aftership-api-key: ' . $api_key,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_params)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_TRACKING);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //handle SSL certificate problem: unable to get local issuer certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    /**
     * Call API to authenticate
     * @param $api_key
     * @return HTTP status code
     */
    private function _callApiAuthenticate($api_key) {
        $headers = array(
            'aftership-api-key: ' . $api_key,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_AUTHENTICATE);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //handle SSL certificate problem: unable to get local issuer certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //the SSL is not correct
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_status;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    private function _getWebsiteConfig(Mage_Sales_Model_Order $order) {
        if (!isset($this->_configs[$order->getStore()->getWebsiteId()])) {
            $config = Mage::app()->getWebsite($order->getStore()->getWebsiteId())->getConfig('aftership_options');
            // object conversion to avoid config element object for easy comparing
            $this->_configs[$order->getStore()->getWebsiteId()] = (object)((array)$config['messages']);
        }

        return $this->_configs[$order->getStore()->getWebsiteId()];
    }

    /**
     * @param $magento_track
     * @return string
     */
    private function _getTrackNo($magento_track) {
        $track_data = $magento_track->getData();

        if (strlen(trim($track_data['track_number'])) > 0) {
            //1.6.2.0 or later
            $track_no = trim($track_data['track_number']);
        } else {
            //1.5.1.0
            $track_no = trim($track_data['number']);
        }

        return $track_no;
    }

    public function notificationMail(){

        $collectionVendor = Mage::getModel('udropship/vendor')->getCollection()->getItems();

        foreach($collectionVendor as $key => $vendor){
            //checking the quantity track number
            $sendMail = false;
            $collectionShipments = $this->getVendorShipmentCollection($vendor);
            foreach($collectionShipments as $shipment) {
                $trackCount = Mage::getModel('track/track')->getCollection()
                    ->addFieldToFilter('order_id', array('eq' => $shipment->getOrderIncrementId()))
                    ->addFieldToFilter('status', array('neq' => 'In Transit'))
                    ->count();
                if($trackCount){
                    $sendMail = true;
                }
            }

            if($sendMail){
                $io = new Varien_Io_File();
                $path = Mage::getBaseDir('var') . DS . 'emailTracking';
                $fileName = Mage::getModel('core/date')->gmtTimestamp().'_'.$key;
                $file = $path . DS . $fileName . '.csv';
                $io->setAllowCreateFolders(true);
                $io->open(array('path' => $path));
                $io->streamOpen($file, 'w+');
                $io->streamLock(true);
                $io->streamWriteCsv($this->_getHeadersForCsv());
                try{
                    $this->_getBodyTotalsForCsv($vendor, $io);
                }catch (Exception $ex){
                    Mage::logException($ex);
                }

                $io->streamUnlock();
                $io->streamClose();

                if(file_exists($file)){
                    $mail = new Zend_Mail ();
                    $mail->setBodyHtml("", "UTF-8");
                    $mail->addTo($vendor->getEmail(), $vendor->getVendorName());
                    $mail->setSubject("Aftership tracking number");
                    $mail->createAttachment(
                        file_get_contents($file),
                        Zend_Mime::TYPE_OCTETSTREAM,
                        Zend_Mime::DISPOSITION_ATTACHMENT,
                        Zend_Mime::ENCODING_BASE64,
                        $fileName.'.csv'
                    );
                    try {
                        $mail->send();
                    } catch (Exception $ex) {}
                    unlink($file);
                }
            }
        }
    }

    private function _getHeadersForCsv(){
        //$row = array('order_id','order_created_at','tracking_number','status','error_tracking');
        $row = array('Order id','PO ID','Tracking number','Carrier','Error tracking');
        return $row;
    }
    private function _getBodyTotalsForCsv($vendor, $io){
        $collectionShipments = $this->getVendorShipmentCollection($vendor);
        foreach($collectionShipments as $shipment){
            $trackNumbers = Mage::getModel('track/track')->getCollection()
                ->addFieldToFilter('order_id', array('eq' => $shipment->getOrderIncrementId()))
                ->addFieldToFilter('status', array('neq' => 'In Transit'))
                ->getItems();
            foreach($trackNumbers as $trackNumber){
                $row = array();
                $row[] = $trackNumber->getOrderId();
                $row[] = $shipment->getUdpoIncrementId();
                $row[] = "'".$trackNumber->getTrackingNumber()."'";
                $row[] = $trackNumber->getShipCompCode();

                if($trackNumber->getTrackingId()){
                    $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
                    $trackings = new AfterShip\Trackings($api_key);
                    $responseJson = $trackings->get_by_id($trackNumber->getTrackingId());
                    $status = $this->_getStatus($responseJson);
                    $trackNumber->setStatus($status);
                    $trackNumber->save();
                }

                if($trackNumber->getErrorTracking() == 'Tracking already exists.'){
                    $error = '';
                }else{
                    $error = $trackNumber->getErrorTracking();
                }
                $row[] = $error;
                if(count($row)){
                    $io->streamWriteCsv($row);
                }
                if($error){
                    $trackNumber->delete();
                }
                if($error == 'Not valid.'){
                    $trackNumber->delete();
                }
                if($trackNumber->getStatus() == 'In Transit'){
                    $trackNumber->delete();
                }
            }
        }
    }

    private function _getStatus($responseJson){
        $http_status = $responseJson['meta']['code'];
        $data = $responseJson['data'];
        if($http_status == '200' && array_key_exists('tracking',$data)){
            $tracking = $data['tracking'];
            return array_key_exists('tag',$tracking)?$tracking['tag']:'';
        }
        return '';
    }

    public function getVendorShipmentCollection($vendor)
    {
        $collection = Mage::getModel('sales/order_shipment')->getCollection();
        $sqlMap = array();
        if (!Mage::helper('udropship')->isSalesFlat()) {
            $collection
                ->addAttributeToSelect(array('order_id', 'total_qty', 'udropship_status', 'udropship_method', 'udropship_method_description'))
                ->joinAttribute('order_increment_id', 'order/increment_id', 'order_id')
                ->joinAttribute('order_created_at', 'order/created_at', 'order_id')
                ->joinAttribute('shipping_method', 'order/shipping_method', 'order_id');
        } else {
            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $sqlMap['order_increment_id'] = "$orderTableQted.increment_id";
            $sqlMap['order_created_at']   = "$orderTableQted.created_at";
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));
        }

        $collection->addAttributeToFilter('udropship_vendor', $vendor->getId());

        return $collection;
    }

    /**
     * Sending email with Invoice data
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function sendTrackingNotificationEmail($tracks)
    {
        if(!is_null($this->_trackingNumbersContent)) {
            $emailTemplate = Mage::getModel('core/email_template')
                ->loadDefault('aftership_tracking_email');
            $emailTemplateVariables = array();
            $emailTemplateVariables['object'] = $tracks;

            $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);
            $vendor = Mage::getSingleton('udropship/session')->getVendor();
            $emailTemplate->setSenderName($vendor->getVendorName());
            $emailTemplate->setSenderEmail($vendor->getEmail());
            $emailTemplate->setTemplateSubject($emailTemplateVariables);
            $emailTemplate->getMail()->createAttachment(
                file_get_contents('temp/temp.csv'),
                Zend_Mime::TYPE_OCTETSTREAM,
                Zend_Mime::DISPOSITION_ATTACHMENT,
                Zend_Mime::ENCODING_BASE64,
                'file.csv'
            );
            $emailTemplate->send($vendor->getEmail(), $vendor->getVendorName(), $emailTemplateVariables);
        }
    }

    public function coreLayoutBlockCreateAfter(Varien_Event_Observer $observer){
        $block = $observer->getBlock();
        if($block instanceof Mage_Adminhtml_Block_Sales_Order_View_Tab_History){
            $block->setTemplate('fiuze/aftership/sales/order/view/tab/history.phtml');
        }
    }

    /**
     * Sending email with Invoice data
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function changeStatusShip(){
        $collectionVendor = Mage::getModel('udropship/vendor')->getCollection()->getItems();

        foreach($collectionVendor as $key => $vendor){
            //checking the quantity track number
            $collectionShipments = $this->getVendorShipmentCollection($vendor);
            foreach($collectionShipments as $shipment){
                $trackNumbers = Mage::getModel('track/track')->getCollection()
                    ->addFieldToFilter('order_id', array('eq' => $shipment->getOrderIncrementId()))
                    ->getItems();
                $trackNumbers2 = Mage::getModel('sales/order_shipment_track')->getCollection()
                    ->addFieldToFilter('order_id', array('eq' => $shipment->getOrderId()))
                    ->getItems();
                foreach($trackNumbers2 as $track){
                    foreach ($trackNumbers as $a_track){
                        if(strcasecmp($a_track->getTrackingNumber(),$track->getTrackNumber()) === 0){
                            $fullTrack[]=array_merge($track->getData(),$a_track->getData());
                        }
                    }
                }

                $countDelivered = 0;
                $countPending = 0;
                foreach($fullTrack as $trackNumber){
                    if($trackNumber['tracking_id']){
                        $api_key = Mage::app()->getWebsite(0)->getConfig('aftership_options/messages/api_key');
                        $trackings = new AfterShip\Trackings($api_key);
                        $responseJson = $trackings->get_by_id($trackNumber['tracking_id']);
                        $status = $this->_getStatus($responseJson);
                        if($status === 'Delivered'){
                            $countDelivered++;
                        }
                        if($status=='Pending'){
                            $status = 'Pending Aftership';
                            $countPending++;
                        }
                        $trackNumberModel = Mage::getModel('track/track')->load($trackNumber['entity_id']);
                        $trackNumberModel->setStatus($status);
                        $trackNumberModel->save();
                    }
                }

                if (count($trackNumbers) > 0) {
                    if ($countPending == 0) {
                        if ($countDelivered == count($fullTrack)) {
                            //delivered
                            $shipment->setUdropshipStatus(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED);
                            $shipment->save();

                            //close order
                            $order = Mage::getModel('sales/order')->load($shipment->getOrderId());
                            $order->setData('state', "complete");
                            $order->setStatus("complete");
                            //complete shipment
                            $udpo = Mage::helper('udpo')->getShipmentPo($shipment);
                            $udpo->setUdropshipStatus(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_DELIVERED);
                            $udpo->save();
                            Mage::helper('udpo')->processPoStatusSave(Mage::helper('udpo')->getShipmentPo($shipment), Unirgy_DropshipPo_Model_Source::UDPO_STATUS_DELIVERED, true, $vendor);
                            $history = $order->addStatusHistoryComment('Order marked as complete because shipment is delivered.', false);
                            $history->setIsCustomerNotified(false);
                            $order->save();
                        } else {
                            //shipped
                            $shipment->setUdropshipStatus(Unirgy_Dropship_Model_Source::SHIPMENT_STATUS_SHIPPED);
                            $shipment->save();
                        }
                    }
                }
            }
        }
    }

    public function runCronJob(){
        $this->changeStatusShip();
        $this->notificationMail();
    }
}