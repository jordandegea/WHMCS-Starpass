WHMCS - Starpass Gateway
========================

# Introduction

 - Work on WHMCS 6.x

# Installation

## On Starpass

In Starpass Website : 
 - Create a Starpass Document
 - You can fill information as you want, excepted for : 
 	- product access URL : http://`YOURWEBSITE`/index.php?m=starpass
 - When complete, save your IDP and IDD, you will need them

## Your FTP 

Copy folders :
 - copy the content of "gateways" in "modules/gateways/" of your FTP. 
 - copy the content of "addons" in "modules/addons/" of your FTP. 

## Your Back Office

Back Office Install : 
 - BackOffice -> Setup -> Addon -> Starpass Addon -> Activate
 	- You have nothing else to do with the Addon. 
 	- You can change the template if you want. It is in the addons/starpass folder. 

 - BackOffice -> Setup -> Payments -> Payments Gateway -> Starpass -> Activate
 	- Fill information



PS : This module use your currencies. 
To be sure you manage all currencies, add them in your BackOffice. 
If you don't want any problem with currency, avoid to add all country in your starpass document



## Contribute

Feel free to help giving your code improvements.

Any donation is a great help, you can follow this link to donate with paypal: 
[Paypal Donation](https://www.paypal.com/cgi-bin/webscr&cmd=_s-xclick&hosted_button_id=EM33UJFXQFFUN)


