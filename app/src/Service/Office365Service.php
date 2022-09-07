<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function GuzzleHttp\json_decode;

class Office365Service
{

  //  private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }


    public function getAccestoken(){
        $guzzle = new Client();
        $tenantId = 'cbacea7a-c8b5-4559-a2e2-073295800e9c';
        $clientId = 'a25058e2-9845-41f8-ab20-10f5039a8f76';
        $clientSecret = 'wlx8Q~WLK~qCrktgBUKm0iE2vzfQ4Cq0ci0nnaMU';
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $token = json_decode($guzzle->post($url, [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ])->getBody()->getContents());
        return $token->access_token;        
    }
}
