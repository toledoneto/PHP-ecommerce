<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;

$app->get('/admin/orders/:idorder/delete', function($idorder){

	User::verifyLogin();

	$order = new Order();

	// verificando se o pedido ainda está no BD
	$order->get((int)$idorder);

	$order->delete();

	header("Location: /admin/orders");
	exit;

});

$app->get('/admin/orders', function (){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("orders", [
		"orders"=>Order::listAll()
	]);

});

?>