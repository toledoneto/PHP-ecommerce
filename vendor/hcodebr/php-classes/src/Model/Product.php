<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model{

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");

	}

     public static function checkList($list)
     {

          foreach ($list as &$row) 
          {
               
               $prod = new Product();
               $prod->setData($row);
               $row = $prod->getValues();

          }

          return $list;

     }

	public function save()
	{

		$sql = new Sql();

		$results =$sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
				":idproduct"=>$this->getidproduct(),
				":desproduct"=>$this->getdesproduct(),
				":vlprice"=>$this->getvlprice(),
				":vlwidth"=>$this->getvlwidth(),
				":vlheight"=>$this->getvlheight(),
				":vllength"=>$this->getvllength(),
				":vlweight"=>$this->getvlweight(),
				":desurl"=>$this->getdesurl()

		));

		$this->setData($results[0]);

	}

	public function get($idproduct)
    {
     
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct;", array(
		":idproduct"=>$idproduct
		));

		$this->setData($results[0]);
     
     }

     public function delete()
     {

		$sql = new Sql();

     	$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct;", array(
			":idproduct"=>$this->getidproduct()
		));

     }

     public function checkPhoto()
     {

     	// caso exista a ft
     	if (file_exists(
     		$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
     		"res" . DIRECTORY_SEPARATOR .
     		"site" . DIRECTORY_SEPARATOR .
     		"img" . DIRECTORY_SEPARATOR .
     		"products" . DIRECTORY_SEPARATOR .
     		$this->getidproduct() . ".jpg"
     	)) 
     	{

     		// n se usa o DIRECTORY_SEPARATOR pois abaixo é uma URL não path de SO
     		$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";

     	} else {
     	// se a ft n existir, coloca img padrão

     		$url = "/res/site/img/product.jpg";

     	}

     	return $this->setdesphoto($url);

     }

     public function getValues()
     {

     	// método para verificar a existência de foto e retornar uma ft padrão em caso de n existir
     	$this->checkPhoto();

     	$values = parent::getValues();

     	return $values;

     }

     public function setPhoto($file)
     {

     	// como o user que vai cadastrar uma img n sabe necessariamente que DEVE ser um jpg,
     	// fazemos uma conversão do arquivo que não for do tipo correto

     	// detectando tipo de arqv-> transformando o nome em array separando pelo pto
     	$extension = explode('.', $file["name"]);
     	$extension = end($extension);

     	switch ($extension) {
     		case 'jpg':
     		case 'jpeg':
     			$image = imagecreatefromjpeg($file["tmp_name"]);
     			break;

     		case 'gif':
     			$image = imagecreatefromgif($file["tmp_name"]);
     			break;

     		case 'png':
     			$image = imagecreatefrompng($file["tmp_name"]);
     			break;

     	}

     	$dest = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
		     		"res" . DIRECTORY_SEPARATOR .
		     		"site" . DIRECTORY_SEPARATOR .
		     		"img" . DIRECTORY_SEPARATOR .
		     		"products" . DIRECTORY_SEPARATOR .
		     		$this->getidproduct() . ".jpg";

     	imagejpeg($image, $dest);

     	imagedestroy($image);

     	$this->checkPhoto();

     }

     public function getFromURL($desurl)
     {

          $sql = new Sql();

          $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [

               ':desurl'=>$desurl

          ]);

          $this->setData($rows[0]);

     }

     public function getCategories()
     {

          $sql = new Sql();

          return $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_productscategories b
                               ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct", [

                                   ':idproduct'=>$this->getidproduct()

                               ]);

     }

}

?>