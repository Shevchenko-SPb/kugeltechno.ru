<?php
class AuthController
{
    public static function login($userId)
    {
        // Проверяем, что Битрикс уже инициализирован
        if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
            define("NOT_CHECK_PERMISSIONS", true);
            require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
        }
        
        global $USER;
        if ($USER && method_exists($USER, 'Authorize')) {
            $USER->Authorize($userId);
        }
    }
}
