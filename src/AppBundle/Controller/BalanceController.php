<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class BalanceController extends Controller
{


    public function __construct() {
      ;
    }


    /**
     * @Route("/balc", name="balcpage")
     */
    public function indexAction()
    {

     $response = new Response();

      $requeteParams = '<?xml version="1.0" encoding="UTF-8" ?><Request FN="BALC" fromCustmer="221766459226" PIN="040186" deviceModel="SM-E7000" devicePlatform="Android" deviceVersion="4.4.2" deviceManufacturer="samsung" packageName="com.zuulu.zuulu" versionNumber="1.0.11" isVirtualDevice="false" geoLatitude="" geoLongitude="" appClientName="Samba Diallo" appType="production" deviceIP="154.124.94.88" ipLocationCode="SN" uniqueDeviceKey="1562157795903" LN="FR"></Request>' ;

      $curl = curl_init();
      curl_setopt_array($curl, array(
      CURLOPT_URL => "http://194.187.94.199:5053/zuulu",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_POST => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $requeteParams,
      CURLOPT_HTTPHEADER => array(
          "cache-control: no-cache",
          "content-type: application/xml",
      ), ));

      $responseCurl = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      if ($err) {
        $response->setContent($err);
      } else {
        $response->setContent($responseCurl);
      }

      return $response ;
    }
}
