<?php
/**
 * 	Created By : Rakesh 
 *	
 */

class RSquare_GoogleShoppingFeed_Model_Datafeed extends Mage_Core_Model_Abstract {

    //public $_Feedfiles =    array();

    public function generateFeed(){
        $this->_prepareFeed();
    }

	public function generateGoogleInventoryFeed(){
        $this->_prepareGoogleInventoryFeed();
    }

    public function uploadGoogleInventoryFeed(){
        set_time_limit(300);
        $this->_uploadFeed('inventoryfeed.csv');    
    }

    // Google Data Feed
    protected function _prepareFeed(){
        $free_shipping  	=   Mage::getStoreConfig('carriers/freeshipping/free_shipping_subtotal');
        $filename       	=   'Google_Data_feed.txt';
        $collection 		= Mage::getModel('catalog/product')->getCollection();
//        $collection->addAttributeToFilter('list_in_google', array('eq' => 1));
        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
		$collection->addUrlRewrite();
		$collection->addStoreFilter(1);
        $fd     			= fopen(Mage::getBaseDir().'/media/productsfeed/'.$filename,'w');
        $header 			= array('id','title','description','google product category','product type','link','mobile link','image link','condition','availability','price','sale_price','sale price effective date','brand','gtin','mpn','identifier exists','tax','shipping','shipping label','shipping weight','multipack','expiration date');
        $header 			= implode('|',$header);
        $header 			= $header."\n";
        fputs($fd,$header);
        foreach($collection as $product){
            $_product       					= 	Mage::getModel('catalog/product')->load($product->getEntityId());
            $feed                       		= 	array();
            $feed['id']                         =   $_product->getSku();
            $feed['title']                      =   $_product->getData('name');
            $feed['description']                =   preg_replace('(\r|\n|\t|[|])', ' ', $_product->getData('description'));
            $feed['google product category']    =   '';
            $category_ids                       =   array_reverse($_product->getCategoryIds());
            $category_name                      =   array();
            $i=0;
            foreach($category_ids as $category){
        		if(!in_array($category,array(1,2))){
        		        $category_name[]= Mage::getModel('catalog/category')->load($category)->getName();
        		        $i=$i+1;
        		        if($i>5)
        		            break;
        		}
            }
            $feed['product type']               =   implode(',',$category_name);
            unset($category_name);
            $feed['link']                       =   $_product->getProductUrl();
            $feed['mobile link']                =   $_product->getProductUrl();
            $feed['image link']                 =   Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$_product->getData('image');
            $feed['condition']                  =   'new';
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
            $feed['availability']               =   ($stock->getQty()>0)?'in stock':'out of stock';
            $feed['price']                      =   $_product->getFinalPrice().' USD';
            $feed['sale_price']                 =   '';
            $feed['sale price effective date']  =   '';
            $feed['brand']                      =   '';//$_product->getAttributeText('brand');
            $feed['gtin']                       =   $_product->getData('sku');
            $feed['mpn']                        =   '';
            $feed['identifier exists']          =   'true';
            $feed['tax']                        =   '';
			$feed['shipping']                   =   'US:::0.00 USD';
			$feed['shipping label']             =   'Free Shipping';
            $feed['shipping weight']            =   $_product->getData('weight');
			$feed['tax']						= 	($_product->getAttributeText('tax_class_id')=='Taxable Goods')?'US:FL:6:n':'US:FL:0:n';
            $feed['multipack']                  =   '';
            $feed['expiration date']            =   date('Y-m-d', strtotime("+28 days"));

            $feed = implode('|',$feed);
            $feed=$feed."\n";
            fputs($fd,$feed);
        }
		Mage::log('Feed Generated','','google_data_feed.log');
        fclose($fd);
        echo 'Feed Generated';
    }

    // Google Inventory Feed
    protected function _prepareGoogleInventoryFeed(){
        $collection =Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect(array("sku","price","is_in_stock"))
                    ->addAttributeToFilter('status', array('eq' => 1))
                    //->addAttributeToFilter('list_in_google', array('eq' => 1))
                    ->load();
        $fd  = fopen(Mage::getBaseDir().'/media/productsfeed/inventoryfeed.csv','w');
        $fileContent ='"id","price","availability"'."\n";
        fputs($fd,$fileContent);
        foreach($collection as $product){
            $content = array();
            $content['id']              =   $product->getData('sku');
            $content['price']           =   $product->getFinalPrice();
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $content['availability']    =   ($stock->getQty()>0)?'in stock':'out of stock';
            $fileContent = implode('","',$content);
            $fileContent='"'.$fileContent.'"'."\n";
            fputs($fd,$fileContent);
        }
        fclose($fd);
    }

    protected function _uploadFeed($filename = 'Google_Data_feed.txt'){
        
        $gfile           = Mage::getBaseDir().'/media/productsfeed/'.$filename;
        $file            = Mage::getBaseDir().'/'.$filename;
        copy($gfile, $file);
        if(count(file($file)) > 28000){
		    $remote_file    = $filename;
		    $ftp_server     = 'uploads.google.com';
		    $ftp_user_name  = ''; // Enter Google FTP Server username
		    $ftp_user_pass  = ''; // Enter Google FTP Server password 

		    // set up basic connection
		    $conn_id = ftp_connect($ftp_server,21);

		    // login with username and password
		    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

            // turn passive mode on
            ftp_pasv($conn_id, true);

		    // upload a file
		        if (ftp_put($conn_id, $remote_file, $file, FTP_ASCII)) {
		            echo "successfully uploaded $file\n";
		        } else {
		            echo "There was a problem while uploading $file \n";
		        }
		    // close the connection
		    ftp_close($conn_id);
            unlink($file);
		}
    }
}