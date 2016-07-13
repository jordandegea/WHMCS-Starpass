<?php

function starpass_config() {
    $configarray = array(
        'name' => 'Starpass Addon',
        'description' => 'Use this addon with the gateway payment',
        'version' => '2.0', 'author' => 'Sinenco', 'fields' => array());
    return $configarray;
}

function starpass_activate() {
    return;
}

function starpass_deactivate() {
    return;
}

function starpass_clientarea($vars) {
    global $whmcs;

    if (isset($_GET['access_error']) AND isset($_GET['invoice_id'])) {
        $url = $whmcs->get_config("SystemURL") . '/viewinvoice.php?id=' . $_GET['invoice_id'];

        return array('pagetitle' => 'Paiement indisponible',
            'breadcrumb' => array('index.php?m=allopass' => 'Paiement indisponible'),
            'templatefile' => 'starpass_client', 'requirelogin' => false,
            'vars' => array('url' => $url, 'access_error' => true));
    }

    if (isset($_GET['idd']) && isset($_GET['contribute_id'])) {

        if (is_numeric($_GET['idd']) && is_numeric($_GET['contribute_id'])) {

            $idd = $_GET['idd'];
            $contributeid = $_GET['contribute_id'];

            $url = "https://script.starpass.fr/iframe/kit_default.php?";
            $url .= "background=FFFFFF&";
            $url .= "idd=" . $idd . "&";
            $url .= "verif_en_php=1&amp;last=1&";
            $url .= "datas=contribute_" . $contributeid;

            return array('pagetitle' => 'Procéder au paiement via Starpass',
                'breadcrumb' => array('index.php?m=starpass' => 'Procéder au paiement via Starpass'),
                'templatefile' => 'starpass_client', 'requirelogin' => false,
                'vars' => array('idd' => $idd, 'url' => $url, "purchase" => true));
        }
    }

    if (isset($_GET['idd']) && isset($_GET['invoice_id'])) {

        if (is_numeric($_GET['idd']) && is_numeric($_GET['invoice_id'])) {


            $idd = $_GET['idd'];
            $invoiceid = $_GET['invoice_id'];

            $url = "https://script.starpass.fr/iframe/kit_default.php?";
            $url .= "background=FFFFFF&";
            $url .= "idd=" . $idd . "&";
            $url .= "verif_en_php=1&amp;last=1&";
            $url .= "datas=" . $invoiceid;

            return array('pagetitle' => 'Procéder au paiement via Starpass',
                'breadcrumb' => array('index.php?m=starpass' => 'Procéder au paiement via Starpass'),
                'templatefile' => 'starpass_client', 'requirelogin' => false,
                'vars' => array('idd' => $idd, 'url' => $url, "purchase" => true));
        }
    }


    if (isset($_POST['DATAS']) AND isset($_POST['code1'])) {

        // retour d'un code
        $idp = $ids = $idd = $codes = $code1 = $code2 = $code3 = $code4 = $code5 = $datas = '';

        // On récupère le(s) code(s) sous la forme 'xxxxxxxx;xxxxxxxx'
        if (isset($_POST['code1']))
            $code1 = $_POST['code1'];
        if (isset($_POST['code2']))
            $code2 = ";" . $_POST['code2'];
        if (isset($_POST['code3']))
            $code3 = ";" . $_POST['code3'];
        if (isset($_POST['code4']))
            $code4 = ";" . $_POST['code4'];
        if (isset($_POST['code5']))
            $code5 = ";" . $_POST['code5'];
        $codes = $code1 . $code2 . $code3 . $code4 . $code5;
        // On récupère le champ DATAS
        if (isset($_POST['DATAS']))
            $datas = $_POST['DATAS'];
        // On encode les trois chaines en URL
        $codes = urlencode($codes);
        $datas = urlencode($datas);

        $urlCallback = $whmcs->get_config("SystemURL") . "/modules/gateways/callback/starpass.php?";
        $urlCallback .= "codes=$codes&";
        $urlCallback .= "datas=$datas";


        if (strstr(@file_get_contents($urlCallback), "OK")) {

            if (strstr($datas, "contribute")) {
                $url = $whmcs->get_config("SystemURL") . '/index.php?m=contribute&account=' . substr($datas, 11);
            } else {
                $url = $whmcs->get_config("SystemURL") . '/viewinvoice.php?id=' . $datas;
            }

            return array('pagetitle' => 'Paiement effectué',
                'breadcrumb' => array('index.php?m=starpass' => 'Paiement effectué'),
                'templatefile' => 'starpass_client', 'requirelogin' => false,
                'vars' => array('return' => true, 'url' => $url));
        }
    }
}

function starpass_output($vars) {
    return;
}

