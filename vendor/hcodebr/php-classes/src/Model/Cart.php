<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;


class Cart extends Model
{

	// para manter o carrinho e seu id ao longo de toda a sessão e saber onde fazer o
	// Insert/Update no DB doc carrinho
	const SESSION = "Cart";

	// cosnt de erro no carrinho
	const SESSION_ERROR = "CartError";

	// método para saber se o carrinho já existe, se precisa ser criado, se a sessão acabou porém ainda tem o session ID etc
	public static function getFromSession()
	{

		$cart = new Cart();

		// verifica se o carrinho já está na sessão
		if(isset($_SESSION[Cart::SESSION]) // se a sessão tem nome
		   &&
		   // verifica se tem ID do carrinho, pois pode ter sido definida a sessão porém está vazia
		   (int)$_SESSION[Cart::SESSION]['idcart'] > 0)
		{

			// carrinho já está na sessão e foi inserido no banco, apenas carregamos o carrinho
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

		} else { 

			// o carrinho ainda não existe, carregar o carrinho pelo session ID, caso exista
			$cart->getFromSessionID();

			// caso não seja possível retornar nenhum carrinho, criamos um carrinho novo
			if (!(int)$cart->getidcart() > 0) 
			{

				// cria os dados a se incluir no novo carrinho
				$data = [
					'dessessionid'=>session_id() // sessão do carrinho

				];

				if (User::checkLogin(false)) // param = false pois a rota n é adm
				{

					// embora estar logado não seja obrigatório para mexer no carrinho,
					// é interessante saber o id dele caso esteja logado para enviar e-mail
					// de produto no carrinho ou promoção ou etc...
					$user = User::getFromSession();

					// recebe o id do user e cria um carrinho dizendo quem é o user
					$data['iduser'] = $user->getiduser();

				}
				
				// colocando os dados dentro da variável cart
				$cart->setData($data);

				$cart->save();

				// como o carrinho é novo, colocamos numa sessão para acesso posterior
				$cart->setToSession();

			}

		}

		return $cart;

	}

	public function setToSession()
	{

		$_SESSION[Cart::SESSION] = $this->getValues();

	}

	// recupera o carrinho já definido em uma sessão
	public function getFromSessionID()
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>session_id()
		]);

		if (count($results) > 0) 
		{

			$this->setData($results[0]);

		}

	}

	// recupera o carrinho já definido em uma sessão
	public function get($idcart)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			':idcart'=>$idcart
		]);

		if (count($results) > 0) 
		{

			$this->setData($results[0]);

		}

	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser,
								:deszipcode, :vlfreight, :nrdays)", [
									':dessessionid'=>$this->getdessessionid(),
									':iduser'=>$this->getiduser(),
									':deszipcode'=>$this->getdeszipcode(),
									':vlfreight'=>$this->getvlfreight(),
									':nrdays'=>$this->getnrdays(),
									':idcart'=>$this->getidcart(),
								]);

		$this->setData($results[0]);

	}

	public function addProduct(Product $product)
	{

		$sql = new Sql();

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
			':idcart'=>$this->getidcart(),
			':idproduct'=>$product->getidproduct()

		]);

		$this->getCalculatedTotal();

	}

	// param all vai dizer se retiramos um ou todos itens iguais do carrinho
	public function removeProduct(Product $product, $all = false)
	{

		$sql = new Sql();

		if($all) // se a pessoa deseja retirar TODOS produtos do carrinho
		{

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
						 WHERE idcart = :idcart
						 AND
						 idproduct = :idproduct
						 AND
						 dtremoved IS NULL", [ // NULL pois evita que fique setando a data de tupla que já tem esse valor setado
						 	':idcart'=>$this->getidcart(),
						 	':idproduct'=>$product->getidproduct()
						 ]);

		} else { // se a pessoa vai excluir um item por vez

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
						 WHERE idcart = :idcart
						 AND
						 idproduct = :idproduct
						 AND
						 dtremoved IS NULL LIMIT 1", [
						 	':idcart'=>$this->getidcart(),
						 	':idproduct'=>$product->getidproduct()
						 ]);

		}

		$this->getCalculatedTotal();

	}

	public function getProducts()
	{

		$sql = new Sql();

		$rows = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight,
									 b.vllength, b.vlweight, b.desurl, 
									 COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
							  FROM tb_cartsproducts a
							  INNER JOIN tb_products b ON a.idproduct = b.idproduct
							  WHERE a.idcart = :idcart AND a.dtremoved IS NULL
							  GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
							  ORDER BY b.desproduct", [
							  	':idcart'=>$this->getidcart()

							  ]);

		return Product::checkList($rows); // checkList verifica o problema da foto
		
	}

	// método que trás todas as somas de todos itens do carrinho
	public function getProductsTotals()
	{

		$sql = new Sql();

		$results = $sql->select("SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth,
									   SUM(vlheight) AS vlheight, SUM(vllength) AS vllength,
									   SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
								FROM tb_products a
								INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
								WHERE b.idcart = :idcart AND dtremoved IS NULL", [
									'idcart'=>$this->getidcart()
								]);

		// verificando se tem algum item no carrinho a ser avaliado
		if(count($results) > 0)
		{
			return $results[0];
		} else {
			return [];
		}

	}

	public function setFreight($nrzipcode)
	{

		// verificando e o user passou o - no CEP e retirando-o
		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductsTotals();

		// verificando se existe algo no carrinho
		if($totals['nrqtd'] > 0)
		{

			// regra de negócio da API
			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
			
			// regra de negócio da API
			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

			// fazendo a query string para passar na API do Correio
			$qs = http_build_query([
				'nCdEmpresa'=>'', // nome da empresa
				'sDsSenha'=>'', 
				'nCdServico'=>'40010', // cód do serviço da API do Correio
				'sCepOrigem'=>'75096695',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'], 
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'

			]);

			// o web service do correio retorna um XML, então precisamos ler essa entrada
			$xml = simplexml_load_file('http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?'.$qs);	

			$result = $xml->Servicos->cServico;

			// se a API do Correio detectar algum erro na operação
			if ($result->MsgErro != '') 
			{
				
				Cart::setMsgError($result->MsgErro);

			} else {

				Cart::clearMsgError();

			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;

		} else {



		}

	}

	public static function formatValueToDecimal($value):float
	{

		$value = str_replace('.', '', $value);
		return str_replace(',', '.', $value);

	}

	public static function setMsgError($msg)
	{

		$_SESSION[Cart::SESSION_ERROR] = $msg;

	}

	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : '';

		Cart::clearMsgError();

		return $msg;

	}

	public static function clearMsgError()
	{

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}

	public function updateFreight()
	{

		// se houver CEP para frete a ser calculado
		if ($this->getdeszipcode() != '') 
		{
			
			$this->setFreight($this->getdeszipcode());

		}

	}

	public function getValues()	
	{

		$this->getCalculatedTotal();

		return parent::getValues();

	}

	public function getCalculatedTotal()
	{

		$this->updateFreight();

		$totals = $this->getProductsTotals();

		$this->setvlsubtotal($totals['vlprice']);

		$this->setvltotal($totals['vlprice'] + $this->getvlfreight());

	}

}

?>