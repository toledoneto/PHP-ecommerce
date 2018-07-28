<?php

namespace Hcode\PagSeguro;

use \GuzzleHttp\Client;
use Hcode\Model\Order;
use Exception;

class Transporter {

    public static function createSession()
    {

        $client = new Client();

        $res = $client->request('POST', Config::getUrlSessions() . "?" . http_build_query(Config::getAuthentication()), [
            'verify'=>false
        ]);
        
        $xml = simplexml_load_string($res->getBody()->getContents());

        return ((string)$xml->id);

    }

    public static function sendTransaction(Payment $payment)
    {

        $client = new Client();

        $res = $client->request('POST', Config::getUrlTransaction() . "?" . http_build_query(Config::getAuthentication()), [
            //'verify'=>false,
            'headers'=>[
                'Content-Type'=>'application/xml'
            ],
            'body'=>$payment->getDOMDocument()->saveXml()
        ]);

        $xml = simplexml_load_string($res->getBody()->getContents());

        $order = new Order();

        $order->get((int)$xml->reference);
                
        $order->setPagSeguroTransactionRespose(
            (string)$xml->code,
            (float)$xml->grossAmount,
            (float)$xml->disccountAmount,
            (float)$xml->feeAmount,
            (float)$xml->netAmount,
            (float)$xml->extraAmount,
            (string)$xml->paymentLink
        );

        return $xml;

    }

    public function getNotification(string $code, string $type)
    {
        $url = "";
        
        switch ($type)
        {
            case 'transaction':
                $url = Config::getNotificationTransactionURL();
                break;
    
            default:
                throw new Exception("Notificação inválida.");
                break;
        }

        $client = new Client();
        
        $res = $client->request('GET', $url . $code . "?" . http_build_query(Config::getAuthentication()), [
            'verify'=>false
        ]);
        
        $xml = simplexml_load_string($res->getBody()->getContents());

        // var_dump($xml);
        
        $order = new Order();

        $order->get((int)$xml->reference);

        // verificando se o status enviado pelo PagS é diferente do Status do BD
        if ($order->getidstatus() !== (int)$xml->status)
        {

            $order->setidstatus((int)$xml->status);

            $order->save();

        }

        // fazendo um log das mudanças de status dos pedidos para podermos avaliar qualquer
        // problema
        $filename = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . date("YmdHis") . ".json";

        $file = fopen($filename, "a+");

        fwrite($file, json_encode([
            'post'=>$_POST,
            'xml'=>$xml
        ]));

        fclose($file);

        return $xml;
    }

}

?>