<?php

class Company
{
    public $fields;

    function __construct($arFields)
    {
        $this->fields = $arFields;
    }

    function updateDataFromDaDaTa()
    {
        file_put_contents(__DIR__.'/test.txt', print_r($this->fields, 1));
    }
}
