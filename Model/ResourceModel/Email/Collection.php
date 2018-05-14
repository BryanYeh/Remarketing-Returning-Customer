<?php
namespace Gss\EmailEvent\Model\ResourceModel\Email;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'id';
	protected $_eventPrefix = 'gss_emailevent_send_email_collection';
	protected $_eventObject = 'send_email_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Gss\EmailEvent\Model\SendEmail', 'Gss\EmailEvent\Model\ResourceModel\Email');
	}

}
