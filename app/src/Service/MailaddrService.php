<?php

namespace App\Service;

class MailaddrService {

    /**
     *
     * @param type $email
     * @return int
     */
    public static function computePriority($email = "") {

        $priority = 5; //default priority for email
        //in case domain
        if (substr(trim($email), 0, 1) == '@') {
            $domain = substr($email, 1);
            if ($domain == '.') {
                $priority = 0; //in case @.
            }
            $subdomain = explode('.', $domain);
            if (count($subdomain) == "2") {
                if ($subdomain[0] == '') {//in case @.com
                    $priority = 1;
                } else {//in case @example.com
                    $priority = 5; // to be confirme
                }
            } elseif (count($subdomain) == "3") {
                if ($subdomain[0] == '') {//in case @.example.com
                    $priority = 2;
                } else {//in case @sub.example.com
                    $priority = 5;
                }
            } elseif (count($subdomain) == "4") {
                if ($subdomain[0] == '') {//in case @.sub.example.com
                    $priority = 3;
                }
            }
        } else {
            $priority = 6;
            //todo user (priority 6) / user+foo (priority 7) / user@sub.example.com (priority 8) / user+foo@sub.example.com (priority 9)
        }
        return $priority;
    }

}
