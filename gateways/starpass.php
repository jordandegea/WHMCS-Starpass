<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function starpass_MetaData() {
    return array(
        'DisplayName' => 'Starpass Payment by Sinenco',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false
    );
}

function starpass_config() {
    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value" => "Starpass"),
        "idp" => array("FriendlyName" => "Starpass Account (IDP): ", "Type" => "text", "Size" => "20",),
        "idd" => array("FriendlyName" => "Starpass Document (IDD): ", "Type" => "text", "Size" => "20",),
    );
    return $configarray;
}

function starpass_link($params) {

    global $whmcs;

    $idd = $params['idd'];
    $idp = $params['idp'];
    $gatewaymodule = $params['FriendlyName'];
    $gatewaywallet = $params['ok_wallet'];
    $invoiceid = $params['invoiceid'];

    $code = '';
    $url = $whmcs->get_config("SystemURL") . '/index.php';

    $code = '<form action="' . $url . '" method="GET">'
            . '<input type="submit" value="Payer avec Starpass" />'
            . '<input type="hidden" name="m" value="starpass" />'
            . '<input type="hidden" name="invoice_id" value="' . $invoiceid . '" />'
            . '<input type="hidden" name="idd" value="' . $idd . '" />'
            . '</form>';

    return $code;
}
