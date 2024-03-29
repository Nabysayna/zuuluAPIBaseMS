<?php

namespace AppBundle\Service;

use AppBundle\Service\BalanceService;
use AppBundle\Service\SDEGetterService;
use AppBundle\Service\SLCGetterService;
use AppBundle\Service\BillGetterService;
use AppBundle\Service\BillPayService;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Authorizedsessions;
use AppBundle\Entity\Patterns;

class HelloService
{

    private $blcServ;
    private $SDEServ;
    private $SLCServ;
    private $BillServ;
    private $PayBillServ;

    private $em ;

    public function __construct(EntityManagerInterface $em, BalanceService $blcServ, SDEGetterService $SDEServ, SLCGetterService $SLCServ, BillGetterService $BillServ, BillPayService $PayBillServ){

      $this->em = $em ;

      $this->blcServ = $blcServ ;
      $this->SDEServ = $SDEServ ;
      $this->SLCServ = $SLCServ ;
      $this->BillServ = $BillServ ;
      $this->PayBillServ =  $PayBillServ ;

    }



    public function connection($name)
    {


       $credentiales = json_decode($name) ;

       $dbuser = $this->em->getRepository('AppBundle:Users')->findOneBy(array('iduser' => $credentiales->login )) ;

       if (empty($dbuser) )
            return json_encode(array('codeRetour' => '0', 'baseDigest' => '___' ));


       $userId = $credentiales->login ;
       $userPwd = $dbuser->getPassword() ;


	$calculatedDigest = sha1($userId."".$userPwd."".$credentiales->timestamp) ;

        if($credentiales->digest == $calculatedDigest ){

          $baseDigest = base64_encode(sha1($credentiales->login."Da8H@dd_dh".$credentiales->digest)) ;

          $sessionStart = new \Datetime() ;

          $authorizedsession = new Authorizedsessions();
          $authorizedsession->setIduser($dbuser->getIduser());
          $authorizedsession->setCredzuulu($dbuser->getCredzuulu());
          $authorizedsession->setRandomdigest( $baseDigest );
          $authorizedsession->setDatecreation($sessionStart);
          $authorizedsession->setValidite(1);

          $this->em->persist($authorizedsession);
          $this->em->flush();

            return json_encode(array('codeRetour' => '1', 'baseDigest' => $baseDigest ));
        }
        else
            return json_encode(array('codeRetour' => '0', 'baseDigest' =>  '___' ));

    }


    public function heartbeat($name)
    {
	$rti = "" ;

        if (strcmp($this->blcServ->getBalance(), "0")==0)
		$rti = $rti."0";
	else
		$rti = $rti."1" ;

	if (strcmp(json_encode($this->SDEServ->getBillSDE("8691301356", "221766459226#040186", "SDE", "WEPPOI")), "0")==0)
		$rti = $rti."0";
	else
		$rti = $rti."1" ;

	if (strcmp(json_encode($this->SLCServ->getBillSLC("8691301356", "221766459226#040186", "SENELEC", "GEPPOI")), "0")==0)
		$rti = $rti."0";
	else
		$rti = $rti."1" ;

        return $rti ;

    }


    public function getBalance($param1, $currentDigest)
    {
       $dbAuthSession = $this->em->getRepository('AppBundle:Authorizedsessions')->findOneBy(array('randomdigest' => $currentDigest )) ;

       if (empty($dbAuthSession) )
            return json_encode(array('codeRetour' => '0', 'message' => 'Not authorized' ));
       else
          return  $this->blcServ->getBalance()  ;
    }


    public function getBillInfo($ref, $currentDigest)
    {

       $dbAuthSession = $this->em->getRepository('AppBundle:Authorizedsessions')->findOneBy(array('randomdigest' => $currentDigest )) ;

       if (empty($dbAuthSession) )
            return json_encode(array('codeRetour' => '0', 'message' => 'Not authorized' ));
       else{
	    $dbPattern =  $this->getPattern($ref);

	    if( strcmp('None', $dbPattern)==0 )
		return json_encode(array('codeRetour'=>-2, 'message'=> 'Invalid reference provided')) ;
            else{
 		$potentialBiller = json_decode($dbPattern) ;
		$bills = array() ;
                foreach ($potentialBiller as $potBill){
		    $returnedObject = $this->BillServ->getBill( $ref, $dbAuthSession->getCredzuulu(), $potBill->servicename, $potBill->servicedesignation )  ;
		    if (!empty($returnedObject["bills"]))
	     	        array_push($bills, $returnedObject ) ;
//	     	        array_push($bills, $this->BillServ->getBill( $ref, $dbAuthSession->getCredzuulu(), $potBill->servicename, $potBill->servicedesignation ) ) ;
		}

	    }

 	    if(sizeof($bills)>0)
		return json_encode( array('codeRetour'=>1, 'data'=> $bills )) ;
 	    else
		return json_encode( array('codeRetour'=>-3, 'data'=> [] )) ;

       }
    }


    public function payBill($reference, $invoiceNumber, $currentDigest)
    {

       $dbAuthSession = $this->em->getRepository('AppBundle:Authorizedsessions')->findOneBy(array('randomdigest' => $currentDigest )) ;

       if (empty($dbAuthSession) )
            return json_encode(array('codeRetour' => '0', 'message' => 'Not authorized' ));
       else{
	    return $this->PayBillServ->payBillSDE($reference, $invoiceNumber, '') ;
            return json_encode(array('codeRetour' => '1', 'message' => 'Pay Bill ::) '.$reference.' '.$invoiceNumber)) ;
       }

    }




   public function getPattern($ref)
   {

    $refSize = strlen($ref) ;    

    $query = $this->em->createQuery(
        'SELECT p.servicename, p.servicedesignation
        FROM AppBundle\Entity\Patterns p
        WHERE p.sizemin <= :refSize and  p.sizemax >= :refSize'
    )->setParameter('refSize', $refSize);

    $patterns = $query->execute() ;

    if (empty($patterns))
	return 'None' ;
    else
      return json_encode($patterns) ;


   }



}

