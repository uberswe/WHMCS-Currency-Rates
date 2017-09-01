<?php
/**
 * Copyright Anveto AB
 * Author: Markus Tenghamn
 * Date: 27/03/15
 * Time: 21:42
 * This is not to be removed.
 */


class Anveto_Hooks {

    protected static $instance = NULL;

    public static function get_instance()
    {
        if ( NULL === self::$instance )
            self::$instance = new self;

        return self::$instance;
    }

    function __construct() {
        $anvetofiles = array("anveto_currency_rates.php");
        foreach ($anvetofiles as $af) {
            if (file_exists(__DIR__.'/'.$af)) {
                include_once(__DIR__.'/'.$af);
            }
        }
    }

    function update() {
        Anveto_Currency_Rates::get_instance()->cron();
    }

    function saveConfig($vars) {

    }

}

function anveto_currency_rates_update()
{
    Anveto_Hooks::get_instance()->update();
}

add_hook("AfterCronJob",1,"anveto_currency_rates_update");
