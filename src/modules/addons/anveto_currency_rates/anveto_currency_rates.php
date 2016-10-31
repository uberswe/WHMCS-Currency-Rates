<?php
/**
 *
 * @author     Anveto <dev@anveto.com>
 * @copyright  Copyright (c) Anveto AB 2015
 * @version    $Id$
 * @link       http://anveto.com/
 *
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Anveto_Currency_Rates {

    function __construct() {

    }

    protected static $instance = NULL;

    public static function get_instance()
    {
        if ( NULL === self::$instance )
            self::$instance = new self;

        return self::$instance;
    }

    function cron() {
        $default = "";
        $defaultrate = "";
        $settings = $this->getSettings();
        $apiuser = "";
        if (isset($settings['apiuser'])) {
            $apiuser = $settings['apiuser'];
        }
        $currencies = $this->getCurrencies($apiuser);
        $url = "";
        if (isset($settings['updateurl'])) {
            $url = $settings['updateurl'];
        }
        $external = $this->getExternalRates($url);
        foreach ($currencies as $c) {
            if ($c['rate'] == 1.0) {
                $default = $c['code'];
            }
        }
        foreach ($external as $e) {
            if ($e->code == $default) {
                $defaultrate = $e->rate;
                break;
            }
        }

        foreach ($currencies as $c) {
            foreach ($external as $e) {
                if ($e->code == $c['code']) {
                    if (isset($settings[$c['code']]) && $settings[$c['code']] == "on") {
                        $code = $e->code;
                        $value = $e->rate/$defaultrate;
                        $table = "tblcurrencies";
                        $update = array("rate"=>$value);
                        $where = array("code"=>$code);
                        update_query($table,$update,$where);
                    break;
                    } else {
                    }
                }
            }
        }

    }

    function getCurrencies($apiuser) {
        $values = array();
        if ($apiuser = "") {
            $results = localAPI("getcurrencies");
        } else {
            $results = localAPI("getcurrencies", $values, $apiuser);
        }
        $currencies = $results['currencies']['currency'];
        return $currencies;
    }

    function getExternalRates($url = "") {
        if ($url == "") {
            $url = "https://bitpay.com/api/rates";
        }
        $json = file_get_contents($url);
        return json_decode($json);
    }

    function getSettings() {
        $table = "tbladdonmodules";
        $fields = "setting,value";
        $where = array("module"=>"anveto_currency_rates");
        $result = select_query($table,$fields,$where);
        $settings = array();
        while ($data = mysql_fetch_array($result))
        {
            $setting = $data["setting"];
            $value = $data["value"];
            $settings[$setting] = $value;
        }
        return $settings;
    }

    function getSidebar($vars) {
        $modulelink = $vars['modulelink'];
        $version = $vars['version'];
        $anvetoupdateproducts = $vars['anvetoupdateproducts'];
        $LANG = $vars['_lang'];

        $sidebar = '<span class="header">
            <img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" />
                '.$LANG['title'].'
                </span>
    <ul class="menu">
        <li>Version: '.$version.'</li>
    </ul>';
        return $sidebar;
    }

    function config() {
        $fields = array();
        $currencies = $this->getCurrencies();
        foreach ($currencies as $c) {
            $ccode = $c['code'];
            $fields[$ccode] =  array (
                "FriendlyName" => "Update ".$ccode." Rate",
                "Type" => "yesno",
                "Size" => "25",
                "Description" =>
                    "Do you want to update ".$ccode." rates?",
            );
        }
        $fields["apiuser"] =  array (
            "FriendlyName" => "API User",
            "Type" => "text",
            "Size" => "25",
            "Description" => "An admin user with WHMCS API access to run cron jobs.",
        );
        $fields["updateurl"] =  array (
            "FriendlyName" => "Update URL",
            "Type" => "text",
            "Size" => "25",
            "Description" =>
                "Set a url that returns currency rates in json format. See Anveto Currency Rate documentation for more information. (Leave blank for default)",
        );


        $configarray = array(
            "name" => "Anveto Currency Rates",
            "description" => "This addon will update additional currency rates and run when the daily cron runs or when the optional Anveto Cron runs.",
            "version" => "1.1",
            "author" => "Anveto",
            "language" => "english",
            "fields" => $fields,
            );
        return $configarray;
    }

    function activation() {
        // anveto_modulename_moduletablename

        //Table created for later use possibly

        $query = "CREATE TABLE mod_anveto_currencyrates_settings (id INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,currency TEXT NOT NULL )";
        if (function_exists("full_query")) {
            if (full_query($query)) {

                return array(
                    'status' => 'success',
                    'description' => 'Anveto Currency Rates has been activated.'
                );
            }
        }
        return array(
            'status'=>'error',
            'description'=>'Could not create database table'
        );

    }

    function deactivate() {
        $query = "DROP TABLE mod_anveto_currencyrates_settings";
        if (full_query($query)) {
            return array(
                'status'=>'success',
                'description'=>'Anveto Currency Rates has been deactivated'
            );
        }
        return array(
            'status'=>'error',
            'description'=>'Could not remove Anveto Currency Rates settings table'
        );
    }

    function upgrade($vars) {
        $version = $vars['version'];
        // For future updates
    }

    function pageOutput($vars) {
        $modulelink = $vars['modulelink'];
        $version = $vars['version'];
        $anvetoupdateproducts = $vars['anvetoupdateproducts'];
        $LANG = $vars['_lang'];

        echo 'Version: '.$version;

        echo '<h2>'.$LANG['documentation'].'</h2>';

        echo '<p>'.$LANG['doctext1'].'</p>';

        echo '<p>'.$LANG['doctext2'].'
            <a href="http://anveto.com/members/dl.php?type=d&id=3">Anveto Cron for WHMCS</a> '.$LANG['doctext3'].'</p>';

        // For page update, could show some stats regarding currencies and cron jobs
        // Echo stuff here
    }

}

function anveto_currency_rates_sidebar($vars) {
    return Anveto_Currency_Rates::get_instance()->getSidebar($vars);
}

function anveto_currency_rates_config() {
    return Anveto_Currency_Rates::get_instance()->config();
}

function anveto_currency_rates_activate() {
    return Anveto_Currency_Rates::get_instance()->activation();
}

function anveto_currency_rates_deactivate() {
    return Anveto_Currency_Rates::get_instance()->deactivate();
}

function anveto_currency_rates_upgrade($vars) {
    Anveto_Currency_Rates::get_instance()->upgrade($vars);
}

function anveto_currency_rates_output($vars) {
    Anveto_Currency_Rates::get_instance()->pageOutput($vars);
}