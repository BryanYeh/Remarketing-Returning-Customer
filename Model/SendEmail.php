<?php
namespace Gss\EmailEvent\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\DataObject\IdentityInterface;

class SendEmail extends AbstractModel implements IdentityInterface
{

	protected $_eventPrefix = 'gss_emailevent_send_email_collection';

	protected function _construct()
	{
		$this->_init('Gss\EmailEvent\Model\ResourceModel\Email');
	}
	public function getIdentities()
	{
		return [];
	}
	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}