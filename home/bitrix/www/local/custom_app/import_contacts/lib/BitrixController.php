<?php

class BitrixController
{
    public static function actionUploadContacts ($data)
    {
        $UploadContacts = new UploadContacts();
        return $UploadContacts->run($data);
    }
}