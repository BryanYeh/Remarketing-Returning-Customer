<?php

namespace Gss\EmailEvent\Observer;

class CustomerLogin implements \Magento\Framework\Event\ObserverInterface
{

    protected $_emailFactory;
    protected $_storeScope; 

    public function __construct(
		\Gss\EmailEvent\Model\SendEmailFactory $emailFactory, 
		\Magento\Framework\App\Config\ScopeConfigInterface $storeScope
	){
        $this->_emailFactory = $emailFactory;
        $this->_storeScope = $storeScope;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
	{
        if($this->_storeScope->getValue('emailevent/general/active') == 0){
			return $this;
        }

        $customer = $observer->getCustomer();
        $email = $customer->getEmail();
        $first_name = $customer->getFirstname();
        $last_name = $customer->getLastname();
        $id = $customer->getId();

        $schedule = $this->_emailFactory->create();
        $scheduleUpdate = $schedule->load($email,'email');
        if($scheduleUpdate->getData()){
            $scheduleUpdate->setFirstName($first_name);
            $scheduleUpdate->setLastName($last_name);
            $scheduleUpdate->setCustomerId($id);
            $scheduleUpdate->setPostCheck(1);
            $scheduleUpdate->save();
        }
        return $this;
    }
}