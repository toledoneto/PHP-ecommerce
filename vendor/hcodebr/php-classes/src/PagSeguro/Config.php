<?php

namespace Hcode\PagSeguro;

class Config
{

	const SANDBOX = true;

	const SANDBOX_EMAIL = "netotadeu@outlook.com";
	const PRODUCTION_EMAIL = "netotadeu@outlook.com";

	const SANDBOX_TOKEN = "9B600A4D62044AB28A0C992589BF8E94";
	const PRODUCTION_TOKEN = "seutoken";

	const SANDBOX_SESSIONS = "https://ws.sandbox.pagseguro.uol.com.br/v2/sessions";
	const PRODUCTION_SESSIONS = "https://ws.pagseguro.uol.com.br/v2/sessions";

	public static function getAuthentication()
	{

		if (Config::SANDBOX === true) 
		{
			
			return [
				"email"=>Config::SANDBOX_EMAIL,
				'token'=>Config::SANDBOX_TOKEN
			];
		
		} else {

			return [
				"email"=>Config::PRODUCTION_EMAIL,
				'token'=>Config::PRODUCTION_TOKEN
			];

		}

	}

	// retorna a URL correta dependendo da produção ou do SANDBOX
	public static function getUrlSessions():string
	{

		return (Config::SANDBOX === true) ? Config::SANDBOX_SESSIONS : Config::PRODUCTION_SESSIONS;

	}

}

?>