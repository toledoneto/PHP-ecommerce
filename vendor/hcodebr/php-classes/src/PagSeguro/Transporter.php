<?php

namespace Hcode\PagSeguro;

use \GuzzleHttp\Client;

class Transporter
{
	
	public static function createSession()
	{

		$client = new Client();

		$res = $client->request('POST', Config::getUrlSessions() . "?" . http_build_query(Config::getAuthentication()));

		// como o retorno é XML, carregamos ele no formato string
		$xml = simplexml_load_string($res->getBody()->getContents());

		return ((string)$xml->id);

	}
	
}

?>