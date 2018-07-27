<?php

namespace Hcode\PagSeguro;

class Payment
{

	private $mode = "default";
	private $currency = "BRL";
	private $extraAmount = 0; // descontos ou acréscimos no valor
	private $reference = "";
	private $items = [];
	private $sender;
	private $shipping;
	private $method;
	private $creditCard;
	private $bank;

}

?>