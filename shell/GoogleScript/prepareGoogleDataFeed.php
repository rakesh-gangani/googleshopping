<?php
	ini_set('memory_limit', '3069M');
	require_once "../../app/Mage.php";
	Mage::app('1');
	echo 'Process Started';
	Mage::getModel('RSquare_GoogleShoppingFeed/datafeed')->generateFeed();
	echo 'Process Ended';
?>