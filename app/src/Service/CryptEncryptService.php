<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CryptEncryptService
{

  //  private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }


    /**
     * Encrypt URL for password reinitialization
     * @param type $url
     * @return type
     */
    public function encrypt(string $str)
    {
        $salt = $this->params->get('salt');
        $iv = $this->params->get('iv');
        $crypt = openssl_encrypt($str, 'aes-256-cbc', $salt, 0, $iv);
        //add date for revocation
        $date = time() + 7 * 24 * 3600;
        return base64_encode($date . $crypt);
    }

    /* retourne un tableau, le premier élément est le cryptage du nom et le deuxième est la date de validité */

    /**
     * Decrypt url and return an array which first element is date of the validity of the token
     * @param type $token
     * @return type
     */
    public function decryptUrl($str)
    {
        $salt = $this->params->get('salt');
        $iv = $this->params->get('iv');
        $cryptdate = substr(base64_decode($str), 0, 10);
        $crypt = substr_replace(base64_decode($str), '', 0, 10);
        $decrypted = openssl_decrypt($crypt, 'aes-256-cbc', $salt, 0, $iv);
        return array($cryptdate, $decrypted);
    }
}
