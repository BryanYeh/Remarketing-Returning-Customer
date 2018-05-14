<?php

namespace Gss\EmailEvent\Cron;

use \Magento\Store\Model\ScopeInterface;

class Run
{
	protected $_storeManager;
	protected $_date;
	protected $_emailFactory;
	protected $_ruleFactory; 
	protected $_storeScope; 
	protected $_transportBuilder; 
	protected $_inlineTranslation;
	protected $_productloader;
	protected $_currencyFactory;
	protected $_productCollectionFactory;
	protected $_stockItemRepository;
	protected $_stockRegistry;
	protected $_subscriber;

    
    public function __construct(
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Gss\EmailEvent\Model\SendEmailFactory $emailFactory,
		\Magento\SalesRule\Model\RuleFactory $ruleFactory, 
		\Magento\Framework\App\Config\ScopeConfigInterface $storeScope, 
		\Magento\Framework\Translate\Inline\StateInterface $inlineTranslation, 
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Catalog\Model\ProductFactory $productloader,
		\Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
		\Magento\Directory\Model\CurrencyFactory $currencyFactory,        
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
		\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
		\Magento\Newsletter\Model\Subscriber $subscriber
	){
		$this->_storeManager = $storeManager;
		$this->_date = $date;
		$this->_emailFactory = $emailFactory;
		$this->_ruleFactory = $ruleFactory; 
		$this->_storeScope = $storeScope; 
		$this->_inlineTranslation = $inlineTranslation; 
		$this->_transportBuilder = $transportBuilder;
		$this->_productloader = $productloader;
		$this->_currencyFactory = $currencyFactory;
		$this->_productCollectionFactory = $productCollectionFactory;
		$this->_stockItemRepository = $stockItemRepository;
		$this->_stockRegistry = $stockRegistry;
		$this->_subscriber = $subscriber;
    }
    
    public function execute()
    {
		if($this->_storeScope->getValue('emailevent/general/active') == 0){
			return $this;
		}

		$sender_type =  $this->_storeScope->getValue('emailevent/email/sender_email_identity', ScopeInterface::SCOPE_STORE);

        //today's date
		$todays_date = $this->_date->gmtDate();

		//get rules (discounts)
		$rules = $this->_ruleFactory->create()->getCollection();
		$rules->getSelect()
				->where("is_active = true AND from_date <= '$todays_date' AND to_date >= '$todays_date'")
                ->orWhere("is_active = true AND from_date IS NULL AND to_date IS NULL")
                ->orWhere("is_active = true AND from_date IS NULL AND to_date >= '$todays_date'")
                ->orWhere("is_active = true AND from_date <= '$todays_date' AND to_date IS NULL")
				->order('discount_amount DESC');
				
		if(count($rules) > 0){
			//only need 1
			$discount = $rules->getFirstItem();

			//get discount info
			$discount_name = $discount->getName();
			$discount_description = $discount->getDescription();
			$discount_amount = $discount->getDiscountAmount();
			$discount_code = $discount->getCode();

			//get only ready to send email
			$schedules = $this->_emailFactory->create()->getCollection();
			$schedules->addFieldToFilter('post_check',1);
			$schedules->addFieldToFilter('date_to_send',array('lt'=>$todays_date));

			//get store info
			$storeId = $this->_storeManager->getStore()->getId();
			$store_email = $this->_storeScope->getValue("trans_email/ident_$sender_type/email", ScopeInterface::SCOPE_STORE);
    		$store_name  = $this->_storeScope->getValue("trans_email/ident_$sender_type/name", ScopeInterface::SCOPE_STORE);

			$items = $schedules->getItems();

			// Instance of Currency Model
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

			//Currency Manage
			$currencyModel = $objectManager->create('Magento\Directory\Model\Currency'); 
			//get currency symbol by currency code
			$currencyCode = 'USD';
			$currencySymbol = $currencyModel->load($currencyCode)->getCurrencySymbol();
			$precision = 2;   // for displaying price decimals 2 point

			//if there are emails to be sent get percentage discount from products
			if(count($items) > 0){
				$products = $this->_productCollectionFactory->create()->addAttributeToSelect('*');
				$products->addAttributeToFilter('special_price',['gt'=>0])
					->addAttributeToFilter('status',['eq'=>true]);

				$special_products = [];

				foreach($products as $product){
					
					$special_from = $product->getSpecialFromDate();
					$special_to = $product->getSpecialToDate();
					$saved_amount = abs((($product->getPrice() - $product->getSpecialPrice()) / $product->getPrice()) * 100);

					$stockRegistry = $this->_stockRegistry;
					$productStock = $stockRegistry->getStockItem($product->getId());
					
					if(
						($productStock->getIsInStock() && $productStock->getQty() > 0) &&
						(
							(is_null($special_from) && is_null($special_to)) ||
							(is_null($special_from)  && $special_to > $todays_date ) ||
							($special_from < $todays_date  && is_null($special_to)) ||
							($special_from < $todays_date  && $special_to > $todays_date)
						)
					){
						$price = $product->getPrice();
						$price = $currencyModel->format($price, ['symbol' => $currencySymbol, 'precision'=> $precision], false, false);	
						$special_price = $product->getSpecialPrice();
						$special_price = $currencyModel->format($special_price, ['symbol' => $currencySymbol, 'precision'=> $precision], false, false);	
						$productUrl = $product->getProductUrl();
						$image = $this->getProductThumbnail($product);
						$image= str_replace("\\","/",$image);

						$special_products[] = array(
							'name' => $product->getName(),
							'price' => $price,
							'special_price' => $special_price,
							'image' => $image,
							'url' => $productUrl,
							'saved_amount' => $saved_amount
						);
					}
				}
				
				//sort by saved_amount (higest on top)
				$sortArray = array();
				foreach($special_products as $person){ 
					foreach($person as $key=>$value){ 
						if(!isset($sortArray[$key])){ 
							$sortArray[$key] = array(); 
						} 
						$sortArray[$key][] = $value; 
					} 
				} 
				$orderby = "saved_amount";
				array_multisort($sortArray[$orderby],SORT_DESC,$special_products);
			}

			foreach($items as $item){
				//get customer info
				$email = $item->getEmail();
				$first_name = $item->getFirstName();
				$last_name = $item->getLastName();
				$template_id = ($item->getEmailTemplateId() == 0 ? 'emailevent_email_template' : $item->getEmailTemplateId());
				$customer_id = $item->getCustomerId();
				
				//get latest viewed products
				$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
				$connection = $resource->getConnection();

				$select = $connection->select()
					->from(
						['r_e' => $resource->getTableName('report_event')],
						['event_id','product_id' => 'object_id', 'customer_id' => 'subject_id','event_type_id']
					)
					->where("subject_id = $customer_id AND event_type_id = 1")
					->order('event_id DESC')
					->group('object_id');
				$lastest_viewed = $connection->fetchAll($select);

				$templateVars = [
						'discount_name' => $discount_name,
						'discount_description' => $discount_description,
						'discount_amount' => (int) $discount_amount,
						'discount_code' => $discount_code,
						'customer_first_name' => $first_name,
						'customer_last_name' => $last_name,
						'unsubscribe' => $this->_subscriber->loadByEmail($email)->getUnsubscriptionLink()
					];

				$current = 1;
				foreach($lastest_viewed as $lastest){
					$product = $this->_productloader->create()->load($lastest['product_id']);

					if($product->getId()){
						$special_from = $product->getSpecialFromDate();
						$special_to = $product->getSpecialToDate();

						$stockRegistry = $this->_stockRegistry;
						$productStock = $stockRegistry->getStockItem($product->getId());
						
						if(
							($productStock->getIsInStock() && $productStock->getQty() > 0) &&
							(
								(is_null($special_from) && is_null($special_to)) ||
								(is_null($special_from)  && $special_to > $todays_date ) ||
								($special_from < $todays_date  && is_null($special_to)) ||
								($special_from < $todays_date  && $special_to > $todays_date)
							)
						){
							$name = $product->getName();
							$productUrl = $product->getProductUrl();
							
							$image = $this->getProductThumbnail($product);
							$image= str_replace("\\","/",$image);

							$price =  $product->getPrice(); //Your Price
							//get formatted price by currency
							$price = $currencyModel->format($price, ['symbol' => $currencySymbol, 'precision'=> $precision], false, false);		
							
							$specialPrice = $product->getSpecialPrice();
							$specialPrice = $currencyModel->format($specialPrice, ['symbol' => $currencySymbol, 'precision'=> $precision], false, false);

							$templateVars['name_'.$current] = $name;
							$templateVars['price_'.$current] = $price;
							$templateVars['special_price_'.$current] = $specialPrice;
							$templateVars['image_'.$current] = $image;
							$templateVars['url_'.$current] = $productUrl;
							
							$current++;						
						}

						if($current > 3){
							break;
						}						
					}
				}

				// if latest viewed is < 3 add remainder products to get 3 products
				$total_veiwed = $current;
				if($total_veiwed <= 3 ){
					foreach($special_products as $special_product){
						if(
							((array_key_exists("url_1",$templateVars) && $special_product['url'] != $templateVars['url_1']) && 
							(array_key_exists("url_2",$templateVars) && $special_product['url'] != $templateVars['url_2'])) ||

							(!array_key_exists("url_2",$templateVars) && 
							array_key_exists("url_1",$templateVars) && $special_product['url'] != $templateVars['url_1']) ||

							(!array_key_exists("url_1",$templateVars))
						
						){
							$templateVars['name_'.$total_veiwed] = $special_product['name'];
							$templateVars['price_'.$total_veiwed] = $special_product['price'];
							$templateVars['special_price_'.$total_veiwed] = $special_product['special_price'];
							$templateVars['image_'.$total_veiwed] = $special_product['image'];
							$templateVars['url_'.$total_veiwed] = $special_product['url'];

							$total_veiwed++;

							if($total_veiwed > 3){
								break;
							}
						}
					}
				}

				$templateOptions = [
					'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
					'store' => $storeId
				];

				$from = ['email' => $store_email, 'name' => $store_name];
				$to= $email;
				$this->_inlineTranslation->suspend();
				$transport = $this->_transportBuilder->setTemplateIdentifier($template_id)
					->setTemplateOptions($templateOptions)
					->setTemplateVars($templateVars)
					->setFrom($from)
					->addTo($to)
					->getTransport();
				$transport->sendMessage();
				$this->_inlineTranslation->resume();
				$item->delete();
			}
		}
		return $this;
	}
	
	public function getProductThumbnail($product){
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
    }
}