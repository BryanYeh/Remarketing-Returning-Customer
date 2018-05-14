<?php

namespace Gss\EmailEvent\Observer;

use Magento\Store\Model\ScopeInterface;

class ScheduleEmail implements \Magento\Framework\Event\ObserverInterface
{

	protected $_customerRepositoryInterface;
	protected $_storeManager;
	protected $_setup;
	protected $_customerLogger;
	protected $_customerLog;
	protected $_date;
	protected $_subscriber;
	protected $_emailFactory;
	protected $_scopeConfig;

	public function __construct(
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Customer\Model\Logger $customerLogger,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\Newsletter\Model\Subscriber $subscriber,
		\Gss\EmailEvent\Model\SendEmailFactory $emailFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig 

	){
		$this->_customerRepositoryInterface = $customerRepositoryInterface;
		$this->_storeManager     = $storeManager;
		$this->_customerLogger = $customerLogger;
		$this->_date = $date;
		$this->_subscriber= $subscriber;
		$this->_emailFactory = $emailFactory;
		$this->_scopeConfig = $scopeConfig;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		
		if($this->_scopeConfig->getValue('emailevent/general/active') == 0){
			return $this;
		}
		
		$email_template_id = $this->_scopeConfig->getValue('emailevent/email/template');
		if(!is_int($email_template_id)){
			$email_template_id = 0;
		}

		//get website id
		$websiteId  = $this->_storeManager->getWebsite()->getWebsiteId();

		//get email
		$username = $observer->getRequest()->getParams()['login']['username'];

		if (filter_var($username, FILTER_VALIDATE_EMAIL)){

			//get customer by email
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customer = $objectManager->create('Magento\Customer\Model\Customer');
			$customer->setWebsiteId($websiteId);
			$customer->loadByEmail($username);

			//last login at date
			$last_login_date = $this->getCustomerLastLogin($customer->getId());
			
			//today's date
			$todays_date = $this->_date->gmtDate();

			$time_amount = $this->_scopeConfig->getValue('emailevent/time/time_amount');
			$time_type = $this->_scopeConfig->getValue('emailevent/time/time_type');

			//days between current login and last login
			//60 : minutes
			//60*60 : hours
			//60*60*24 : days			
			$time_conversion;
			switch ($time_type) {
				case 'minutes':
					$time_conversion = 60;
					break;
				case 'hours':
					$time_conversion = 60*60;
					break;
				case 'days':
					$time_conversion = 60*60*24;
					break;
				default:
					$time_conversion = 60*60;
			}


			$time_difference = floor(abs(strtotime($todays_date) - strtotime($last_login_date))/($time_conversion));

			//is customer a newsletter subscriber
			$is_subscribed = $this->_subscriber->loadByEmail($username)->isSubscribed();
							//$this->_subscriber->loadByCustomerId($customer->getId()); //use customer id
			
			if($time_difference >= $time_amount && $is_subscribed){
				$time_increased = strtotime("+$time_amount $time_type", strtotime($todays_date));
				$time_increased = new \Zend_Date($time_increased, \Zend_Date::TIMESTAMP);

				$oldEmail = $this->_emailFactory->create();
				$emailUpdate = $oldEmail->load($username,'email');

				if($emailUpdate->getData()){
					$emailUpdate->setDateToSend($time_increased);
					$emailUpdate->setPostCheck(0);
					$emailUpdate->save();
				}
				else{
					$email = $this->_emailFactory->create();
					$email->setEmail($username);
					$email->setEmailTemplateId($email_template_id);
					$email->setDateToSend($time_increased);
					$email->save();
				}
			}
		}
		return $this;
	}

	/**
     * Retrieves customer log model
     *
     * @return \Magento\Customer\Model\Log
     */
    protected function getCustomerLastLogin($id)
    {
        if (!$this->_customerLog) {
            $this->_customerLog = $this->_customerLogger->get($id);
        }
        return $this->_customerLog->getLastLoginAt();
	}
}