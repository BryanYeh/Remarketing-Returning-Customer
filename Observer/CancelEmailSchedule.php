<?php

namespace Gss\EmailEvent\Observer;

class CancelEmailSchedule implements \Magento\Framework\Event\ObserverInterface
{

    protected $_emailFactory;
    protected $_order;
    protected $_storeScope; 

    public function __construct(
        \Magento\Sales\Model\Order $order,
		\Gss\EmailEvent\Model\SendEmailFactory $emailFactory, 
		\Magento\Framework\App\Config\ScopeConfigInterface $storeScope
	){
        $this->_order = $order;
        $this->_emailFactory = $emailFactory;
        $this->_storeScope = $storeScope;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
	{
        if($this->_storeScope->getValue('emailevent/general/active') == 0){
			return $this;
        }
        
        //get order id
        $orderId = $observer->getEvent()->getOrderIds()[0];

        //get order
        $order = $this->_order->load( $orderId);

        //get customer's email
        $email = $order->getCustomerEmail(); 

        $schedule = $this->_emailFactory->create();
        $scheduleDelete = $schedule->load($email,'email');
        if($scheduleDelete->getData()){
            $scheduleDelete->delete();
        }
        return $this;
    }
}