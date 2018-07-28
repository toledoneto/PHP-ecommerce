<?php

namespace Hcode\PagSeguro;

use Exception;
use DOMDocument;
use DOMElement;
use Hcode\PagSeguro\Payment\Method;

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

	public function __construct(
		string $reference,
		Sender $sender,
		Shipping $shipping,
		float $extraAmount = 0
	)
	{

		$this->sender = $sender;
		$this->shipping = $shipping;
		$this->reference = $reference;
		$this->extraAmount = number_format($extraAmount, 2, '.', '');

	}

	public function addItem(Item $item)
    {
        array_push($this->items, $item);
    }

    // método de pgto em CC
    public function setCreditCard(CreditCard $creditCard)
    {
        $this->creditCard = $creditCard;
        $this->method = Method::CREDIT_CARD;
    }
    
    // método de pgto em Débito bancário
    public function setBank(Bank $bank)
    {
        $this->bank = $bank;
        $this->method = Method::DEBIT;
    }

    // método de pgto em Boleto
    public function setBoleto()
    {
        $this->method = Method::BOLETO;
    }

	public function getDOMDocument():DOMDocument
	{

		$dom = new DOMDocument('1.0', 'ISO-8859-1');



		return $dom;

	}

}

?>