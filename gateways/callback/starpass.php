<?php

use WHMCS\Module\Gateway;
use WHMCS\Terminus;

include "../../../init.php";
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/functions.php';
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/gatewayfunctions.php';
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/invoicefunctions.php';

$gatewayModule = 'starpass';

$gateway = new Gateway();
if (!$gateway->isActiveGateway($gatewayModule) || !$gateway->load($gatewayModule)) {
    Terminus::getInstance()->doDie('Module not Active');
}

class SinencoCallbackAction {

    const NAME = "Starpass";

    private $admin = null;
    private $currencies = null;

    function SinencoCallbackAction() {
        
    }

    private function loadAdmin() {
        if ($this->admin != null) {
            return;
        }
        $result = mysql_query("SELECT username FROM tbladmins LIMIT 0,1");
        if (!$result) {
            die('Request Error: ' . mysql_error());
        }
        while ($row = mysql_fetch_assoc($result)) {
            $this->admin = $row["username"];
        }
        if ($this->admin == null) {
            die('Can\'t retrieve admin user');
        }
    }

    public function getInvoice($invoiceid) {
        $this->loadAdmin();
        $command = "getinvoice";
        $values["invoiceid"] = $invoiceid;


        $invoiceXML = localAPI($command, $values, $this->admin);

        if (!isset($invoiceXML["result"]) || $invoiceXML["result"] != "success") {
            echo "INVOICE_DOESNT_EXIST";
            die;
        }

        return $invoiceXML;
    }

    function getUserDetails($userid) {
        $this->loadAdmin();
        $command = "getclientsdetails";
        $values["clientid"] = $userid;
        $values["stats"] = true;
        $values["responsetype"] = "xml";

        $clientDetails = localAPI($command, $values, $this->admin);
        return $clientDetails["client"];
    }

    function getCurrencies() {
        $this->loadAdmin();
        if ($this->currencies == null) {
            $command = "getcurrencies";
            $currencies = localAPI($command, $values, $this->admin);
            $this->currencies = $currencies["currencies"];
        }
        return $this->currencies;
    }

    function addCredit($userid, $amount) {
        $this->loadAdmin();
        $command = "addcredit";
        $values["clientid"] = $userid;
        $values["description"] = "Adding funds via " . self::NAME;
        $values["amount"] = $amount;

        $results = localAPI($command, $values, $this->admin);
        return $results;
    }

    function applyCredit($invoiceid, $amount, $userBalance) {
        $this->loadAdmin();

        $command = "applycredit";
        $values["invoiceid"] = $invoiceid;
        $values["amount"] = ($userBalance < $amount) ? $userBalance : $amount;

        $results = localAPI($command, $values, $this->admin);
        return $results;
    }

    function convertAmountWithCurrencies($amount, $idTo, $codeFrom) {
        $this->loadAdmin();
        foreach ($this->getCurrencies() as $currencyRow) {
            if ($currencyRow["id"] == $idTo) {
                $invoice_currency = $currencyRow;
            }
            if ($currencyRow["code"] == $codeFrom) {
                $txn_currency = $currencyRow;
            }
        }

        if (isset($invoice_currency)) {
            if (isset($txn_currency)) {
                // La c'est bien, on converti
                $amount = round($amount * $txn_currency["rate"] / $invoice_currency["rate"], 2);
            } else {
                // !!problem, Transaction currency not found, writing the previous amount
            }
        } else {
            // !!problem, Invoice currency not found, writing the previous amount
        }
        return $amount;
    }
    
    function addLogContribution($user_id, $amount, $txn_id){
    	$query = "INSERT INTO contribute_logs VALUE('','".$user_id."','".time()."','".$amount."','".$txn_id."')" ;
        $result = mysql_query($query);
        return $result;
    }

}

function checkCallback($datas, $currency, $amount, $txn_id) {
    $callbackAction = new SinencoCallbackAction();
    if (strstr($datas, "contribute")) {
        $userid = substr($datas, 11);
        $clientDetails = $callbackAction->getUserDetails($userid);
        $currency_id = $clientDetails["currency"];

        $finalAmount = $callbackAction->convertAmountWithCurrencies($amount, $currency_id, $currency);
        $callbackAction->addCredit($userid, $amount);
        $callbackAction->addLogContribution($userid, $amount, $txn_id);
    } else {
        $invoiceid = $datas;
        $invoiceDetails = $callbackAction->getInvoice($invoiceid);

        $userid = $invoiceDetails["userid"];
        $balance = $invoiceDetails["balance"];

        $clientDetails = $callbackAction->getUserDetails($userid);

        $currency_id = $clientDetails["currency"];

        $finalAmount = $callbackAction->convertAmountWithCurrencies($amount, $currency_id, $currency);

        $callbackAction->addCredit($userid, $amount);
        $callbackAction->applyCredit($invoiceid, $finalAmount, $balance);
    }
}

if (isset($_GET['codes']) AND isset($_GET['datas'])) {

    $GATEWAY = $gateway->getParams();

    $idp = $GATEWAY['idp'];
    $idd = $GATEWAY['idd'];
    $ids = '';
    $ident = $idp . ";" . $ids . ";" . $idd;
    $ident = urlencode($ident);

    $codes = $_GET['codes'];
    $datas = $_GET['datas'];

    $get_f = @file("http://script.starpass.fr/check_php.php?ident=$ident&codes=$codes&DATAS=$datas");
    if (!$get_f) {
        exit("Votre serveur n'a pas accès au serveur de StarPass, merci de contacter votre hébergeur.");
    }

    $tab = explode("|", $get_f[0]);

    if (!$tab[1]) {
        echo "KO";
        exit;
    } else
        $url = $tab[1];

    // dans $pays on a le pays de l'offre. exemple "fr"
    $pays = $tab[2];
    // dans $palier on a le palier de l'offre. exemple "Plus A"
    $palier = urldecode($tab[3]);
    // dans $id_palier on a l'identifiant de l'offre
    $id_palier = urldecode($tab[4]);
    // dans $type on a le type de l'offre. exemple "sms", "audiotel, "cb", etc.
    $type = urldecode($tab[5]);
    // vous pouvez à tout moment consulter la liste des paliers à l'adresse : 
    // Si $tab[0] ne répond pas "OUI" l'accès est refusé
    // On redirige sur l'URL d'erreur
    if (substr($tab[0], 0, 3) != "OUI") {
        echo "KO2";
        exit;
    } else {
        if ($id_palier != '') {
            $xml = file_get_contents("http://script.starpass.fr/palier.php");
            $xml = str_replace('</bracket>', '', $xml);
            $xml_tab = explode('<bracket>', $xml);
            foreach ($xml_tab as $line) {
                if (preg_match('|<id_palier>' . $id_palier . '</id_palier>|', $line, $match)) {
                    if (preg_match('|<price>(.*)</price>|', $line, $match)) {
                        $prix = $match[1];
                    }
                    if (preg_match('|<currency>(.*)</currency>|', $line, $match)) {
                        $txnCurrency = $match[1];
                    }
                    if (preg_match('|<country>(.*)</country>|', $line, $match)) {
                        $country = $match[1];
                    }//echo $line.'<br /><br />' ;
                }
            }
        } else { // Si c'est un code de test
            $prix = '0.04';
            $txnCurrency = 'EUR';
            $country = 'fr';
        }
    }


    $date = date('Y-m-d');


    $amount = $prix;
//$customer_country = $retour[5] ;
    $txn_id = $code = $codes;
//$transaction_id = $retour[7] ;
//$date = $retour[8] ;

    checkCallback($datas, $txnCurrency, $amount, $txn_id);

    echo 'OK';
}
