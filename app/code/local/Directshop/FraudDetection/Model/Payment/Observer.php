<?php
/**
 *
 * @category   Directshop
 * @package    Directshop_FraudDetection
 * @author     Ben James
 * @copyright  Copyright (c) 2008-2010 Directshop Pty Ltd. (http://directshop.com.au)
 */
 
class Directshop_FraudDetection_Model_Payment_Observer
{
	
	public function call_maxmind($observer)
	{
		$event = $observer->getEvent();
		$payment = $event->getPayment();
		$order = $payment->getOrder();
		
		$res = Mage::helper('frauddetection')->normaliseMaxMindResponse(Mage::helper('frauddetection')->getMaxMindResponse($payment));
		
		// don't save the order if there's a fatal error
		if (empty($res['err']) || !in_array($res['err'], Directshop_FraudDetection_Model_Result::$fatalErrors))
		{
			
			Mage::helper('frauddetection')->saveFraudData($res, $order);
			$order->setFraudDataTemp($res);
			
			if (Mage::getStoreConfig('frauddetection/general/holdwhenflagged') && 
				$res['ourscore'] >= Mage::getStoreConfig('frauddetection/general/threshold') && 
				$order->canHold())
			{
				$order->hold();
			}
		}
	}
	
}