<?php
namespace php_interface\cust_events\onBefore\crm\entity;
class Company
{
    public $fields;

    function __construct($arFields)
    {
        $this->fields = $arFields;
    }

    function addFieldsInSession()
    {
        $instance = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $instance->getFactory(\CCrmOwnerType::Company);
        $company = $factory->getItem($this->fields['ID']);
        $_SESSION[\Consts::SESSION_KEY_OLD_FIELDS_COMPANY] = serialize($company->getData());
    }
}
