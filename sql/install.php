<?php
/**
* 2014-2020 Uebix di Di Bella Antonino
*
* NOTICE OF LICENSE
*
* This source file is subject to the Uebix commercial License
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to info@uebix.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this PrestaShop Module to newer
* versions in the future. If you wish to customize this PrestaShop Module for your
* needs please refer to info@uebix.com for more information.
*
*  @author    Uebix <info@uebix.com>
*  @copyright 2020-2020 Uebix
*  @license   commercial use only, contact info@uebix.com for licence
*  International Registered Trademark & Property of Uebix di Di Bella Antonino
*/
$sql = array();

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
