<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
//require_once('./../Settings.php'); // Удалить из продакшена
use \Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;

Loader::IncludeModule('crm');
class UploadContacts
{
    public $contactFactory;
    public $companyFactory;

    function __construct()
    {
        $this->contactFactory = Container::getInstance()->getFactory(CCrmOwnerType::Contact);
        $this->companyFactory = Container::getInstance()->getFactory(CCrmOwnerType::Company);
    }
    public function run($contacts)
    {
        $countErrors = 0;
        $countAddCompanies = 0;
        $countAddContacts = 0;
        $countUpdateContacts = 0;
        $errorData = [];
        $errorsLog = [];
        foreach ($contacts as $contact) {
            // Порядок прописан в Settings;
            $companyName = $contact[0];
            $companyINN = $contact[1];

            // Проверяем на наличие ИНН компании
            if(empty($companyINN)) {
                $countErrors++;
                $errorData[] = $contact;
                continue;
            }
            // Проверяем на наличие компании по ИНН
            $companyId = $this->getCompanyIDByInn($companyINN);
            if(!$companyId) {
                // Если компания не найдена, то создаём её
                $rs = $this->createCompany($companyINN, $companyName);
                $companyId = $rs['id'];
                if(!$companyId) {
                    $countErrors++;
                    $errorData[] = $contact;
                    $errorsLog[] = $rs['errors'];
                    continue;
                }
                $countAddCompanies++;
            }

            $data = $this->getFields($contact, $companyId, $companyINN);
            $email = $data['email'];

            $contactId = null;

            // Ищем контакт по уникальному email
            if($email) {
                $contactId = $this->getContactIdByEmail($email);
            }
            // Ищем контакт по телефонам
            if(!$contactId) {
                $contactId = $this->getContactByData($data, $companyINN);
            }
            // Обновляем поля контакта если такой найден
            if($contactId) {
                $rs = $this->updateContact($contactId, $data['fields']);
                if(!$rs['id']) {
                    $countErrors++;
                    $errorData[] = $contact;
                    $errorsLog[] = $rs['errors'];
                }
                $countUpdateContacts++;
            }
            // Создаём контакт
            if(!$contactId) {
                $rs = $this->createContact($data);
                $contactId = $rs['id'];
                if(!$contactId) {
                    $countErrors++;
                    $errorData[] = $contact;
                    $errorsLog[] = $rs['errors'];
                    continue;
                }
                $countAddContacts++;
            }
            // Обновляем поля multi
            $this->addOrUpdateFieldMulti($contactId, $data);
        }

        return [
            'contacts_updated_count' => $countUpdateContacts, // Количество обновленных контактов
            'contacts_upload_count' => $countAddContacts, // Количество добавленных контактов
            'companies_upload_count' => $countAddCompanies, // Количество добавленных компаний
            'contacts_upload_error_count' => $countErrors, // Количество ошибок при добавлении контакта
            'contacts_upload_error_data' => $errorData ?: [], // Данные о контактах с ошибками для записи в файл
            'contacts_upload_error_log' => $errorsLog ?: [] // log ошибок
        ];
    }
    public function getContactByData($data)
    {
        // Ищем контакт по мобильным телефонам
        if($phones = $data['mobilePhones']) {
            foreach ($phones as $phone) {
                if($phone[0] != '+') {
                    continue;
                }
                $num = str_replace('+','', $phone);
                $filter = [
                    'ENTITY_ID' => 'CONTACT',
                    'VALUE' => "%$num%",
                ];
                $rs = $this->getFieldMultiByFilter($filter);
                while ($el = $rs->fetch()) {
                    if($el['ENTITY_ID'] === 'CONTACT' && $el['TYPE_ID'] == 'PHONE') {
                        return $el['ELEMENT_ID'];
                    }
                }
            }
        }
        // Проверяем по первому рабочему номеру
        if($phones = $data['workPhones']) {
            $phone = $phones[0];
            if($phone[0] === '+') {
                $num = str_replace('+','', $phone);
                $filter = [
                    'ENTITY_ID' => 'CONTACT',
                    'VALUE' => "%$num%",
                ];
                $rs = $this->getFieldMultiByFilter($filter);
                while ($el = $rs->fetch()) {
                    if($el['ENTITY_ID'] === 'CONTACT' && $el['TYPE_ID'] == 'PHONE') {
                        return $el['ELEMENT_ID'];
                    }
                }
            }
        }
        // Ищем по совпадению ИНН и Имени
        $items = $this->contactFactory->getItems([
            'filter' => [
                '=NAME' => $data['fields']['NAME'],
                Settings::UF_CONTACT_INN_COMPANY => $data['fields'][Settings::UF_CONTACT_INN_COMPANY]
            ],
            'select' => ['ID']
        ]);
        if($items[0]){
            return $items[0]->getId();
        }
        return null;
    }
    public function getFieldMultiByFilter($filter)
    {
        $order = ['ID' => 'DESC'];
        return CCrmFieldMulti::GetList(
            $order,
            $filter
        );
    }
    public function addOrUpdateFieldMulti($contactId, $data)
    {
        // проверяем на существование этих полей
        $order = ['ID' => 'DESC'];
        $filter = [
            'ENTITY_ID' => 'CONTACT',
            'ELEMENT_ID' => $contactId,
        ];
        $rs = CCrmFieldMulti::GetList(
            $order,
            $filter
        );
        $phones = [];
        $emails = [];
        while ($el = $rs->fetch()) {
            // Добавляем существующие имейлы
            if($el['TYPE_ID'] == 'EMAIL') {
                $emails[] = strtolower($el['VALUE']);
            }
            if($el['TYPE_ID'] == 'PHONE') {
                $phones[] = preg_replace('/[^0-9]/', '', $el['VALUE']);
            }
        }
        if($email = $data['email']) {
            if(!in_array($email, $emails)) {
                $this->addFieldMulti($contactId, $email, 'EMAIL', 'WORK');
            }
        }
        if($workPhones = $data['workPhones']) {
            foreach ($workPhones as $phone) {
                $clearPhone = preg_replace('/[^0-9]/', '', $phone);
                if(!in_array($clearPhone, $phones)) {
                    $this->addFieldMulti($contactId, $phone, 'PHONE', 'WORK');
                }
            }
        }
        if($mobilePhones = $data['mobilePhones']) {
            foreach ($mobilePhones as $phone) {
                $clearPhone = preg_replace('/[^0-9]/', '', $phone);
                if(!in_array($clearPhone, $phones)) {
                    $this->addFieldMulti($contactId, $phone, 'PHONE', 'MOBILE');
                }
            }
        }
    }
    public function addFieldMulti($contactId, $val, $type, $valueType)
    {
        $ds = new \CCrmFieldMulti;
        $ds->Add(
            array(
                'ENTITY_ID'  =>  'CONTACT', //
                'ELEMENT_ID' =>  $contactId, // id контакта
                'VALUE'      =>  $val, // значение
                'TYPE_ID'    =>  $type, // 'PHONE' 'EMAIL'
                'VALUE_TYPE' =>  $valueType, // 'WORK' 'HOME' 'MOBILE'
            )
        );
    }
    public function createContact($data)
    {
        // Создаём компанию
        $contact = $this->contactFactory->createItem(
            $data['fields']
        );
        // Сохраняем и получаем ID
        $result = $contact->save();

        if ($result->isSuccess()) {
            $contactId = $contact->getId(); // Получаем ID созданной компании
            return [
                'id' => $contactId
            ];
        } else {
            return [
                'id' => null,
                'errors' => ["Создание контакта ИНН #" .
                $data['fields'][Settings::UF_CONTACT_INN_COMPANY] .
                    " Имя " . $data['fields']['NAME']
                => $result->getErrorMessages()]
            ];
        }

    }
    public function updateContact($contactId, $fields)
    {
        $contact = $this->contactFactory->getItem($contactId);
        if(!$contact) {
            return false;
        }
        foreach ($fields as $code => $val) {
            if(in_array($code, ['ASSIGNED_BY_ID', 'CREATED_BY'])) {
                continue;
            }
            $contact->set($code, $val);
        }
        $result = $contact->save();
        if ($result->isSuccess()) {
            $contactId = $contact->getId(); // Получаем ID созданной компании
            return [
                'id' => $contactId
            ];
        } else {
            return [
                'id' => null,
                'errors' => ["Ошибка обновление контакта id $contactId" => $result->getErrorMessages()]
            ];
        }
    }
    public function getFields($contact, $companyId, $companyINN)
    {
        $result = [
            'mobilePhones' => [],
            'workPhones' => [],
            'email' => null,
        ];

        $lastName = $contact[3];
        $name = $contact[4];
        $secondName = $contact[5];
        $post = $contact[6];
        $workPhones = $contact[7];
        $mobilePhones = $contact[10];

        $result['fields'] = [
            'ASSIGNED_BY_ID' => 1, // Обязательно поле
            'CREATED_BY' => 1, // Обязательно поле
            'UPDATED_BY' => 1, // Обязательно поле
            'NAME' => $name ?: $lastName ?: $secondName ?: 'Без имени', // Имя
            'LAST_NAME' => $lastName ?: '', // Фамилия
            'SECOND_NAME' => $secondName ?: '', // Отчество
            'POST' => $post ?: '', // Пост
            'COMPANY_ID' => $companyId ?: '', // Компания
            Settings::UF_CONTACT_INN_COMPANY => $companyINN ?: '', // ИНН Компании
        ];

        // Устраняем дублирование имён
        if($result['fields']['NAME'] === $result['fields']['LAST_NAME']) {
            $result['fields']['LAST_NAME'] = '';
        }
        if($result['fields']['NAME'] === $result['fields']['SECOND_NAME']) {
            $result['fields']['SECOND_NAME'] = '';
        }

        if($contact[11]) {
            if(filter_var($contact[11], FILTER_VALIDATE_EMAIL)) {
                $result['email'] = strtolower($contact[11]);
            }
        }
        if($mobilePhones) {
            $arPhones = $this->formatPhones($mobilePhones);
            foreach ($arPhones as $phone) {
                if($phone[0] == '+') {
                    $result['mobilePhones'][] = $phone;
                }
            }
        }
        if($workPhones) {
            $arPhones = $this->formatPhones($workPhones);
            foreach ($arPhones as $phone) {
                if($phone[0] == '+') {
                    $result['workPhones'][] = $phone;
                }
            }
        }
        return $result;
    }
    public function getCompanyIDByInn($inn)
    {
        $filter = [
            'UF_CRM_INN' => $inn,
        ];
        $companies = $this->companyFactory->getItems([
            'filter' => $filter,
            'select' => ['ID']
        ]);
        foreach ($companies as $company) {
            return $company->getId();
        }
        return null;
    }
    public function createCompany($companyINN, $companyName)
    {
        if(!$companyName){
            $companyName = "ИНН $companyINN";
        }
        // Создаём компанию
        $company = $this->companyFactory->createItem([
            'TITLE' => $companyName,
            'UF_CRM_INN' => $companyINN,
            'ASSIGNED_BY_ID' => 1,
            'CREATED_BY' => 1,
            'UPDATED_BY' => 1,
        ]);
        // Сохраняем и получаем ID
        $result = $company->save();

        if ($result->isSuccess()) {
            $companyId = $company->getId(); // Получаем ID созданной компании
            return [
                'id' => $companyId
            ];
        } else {
            return [
                'id' => null,
                'errors' => ["Создание компании ИНН $companyINN" => $result->getErrorMessages()]
            ];
        }
    }
    public function getContactIdByEmail($email)
    {
        $order = ['ID' => 'DESC'];
        $filter = [
            'TYPE_ID' => 'EMAIL',
            'VALUE' => $email,
        ];
        $rs = CCrmFieldMulti::GetList(
            $order,
            $filter
        );
        while ($row = $rs->fetch()) {
            if($row['ENTITY_ID'] == 'CONTACT') {
                return $row['ELEMENT_ID'];
            }
        }
        return null;
    }
    public function formatPhones($data)
    {
        if(!$data) {
            return [];
        }
        $hasPlus = $data[0] === '+';
        $clearPhone = preg_replace('/[^0-9]/', '', $data);
        // телефон не может начинаться с 0
        $clearPhone = ltrim($clearPhone, '0');
        if(strlen($clearPhone) < 7) {
            return [];
        }
        // Обработка стандартных российских номеров
        if(strlen($clearPhone) == 11) {
            // Начинается с 7
            if($clearPhone[0] == '7') {
                return ['+'.$clearPhone];
            }
            // Начинается с 8
            if($clearPhone[0] == '8') {
                return ['+7'.substr($clearPhone, 1)];
            }
        }
        // пропущен код страны
        if(strlen($clearPhone) == 10) {
            return ['+7'.$clearPhone];
        }
        // Массив с возможными разделителями
        $arSeparators = [
            ';', '#', '/', 'д', 'в', ',', 'e', '*', ':'
        ];
        foreach ($arSeparators as $separator) {
            // Проверка разделителя номера с #
            if(str_contains($data, $separator)) {
                return $this->checkExtNum($data, $separator);
            }
        }
        if(str_contains($data, '(')) {
            // Частный случай, где добавочный номер записан в скобках
            $arNums = explode('(', $data);
            if($arNums[2] && !$arNums[3]) {
                // Номер с одним добавочным номером
                $num = $this->modifyCasualPhone($arNums[1]);
                $extNum = $this->modifyCasualPhone($arNums[2]);
                $fullNum = $num;
                if($extNum) {
                    $fullNum = $fullNum.';'.$extNum;
                }
                return [$fullNum];
            }
        }
        return [$clearPhone];
    }
    public function checkExtNum($data,$separator)
    {
        $arNums = explode($separator, $data);
        if(!$arNums[2]) {
            // Номер с одним добавочным номером
            if($separator == ';') {
                // Делаем проверку на запись номера формата +7 (4012) 92-04-16;92-03-43
                if($rs = $this->checkIsTwoNums($arNums)) {
                    return $rs;
                }
            }
            $num = $this->modifyCasualPhone($arNums[0]);
            $extNum = $this->modifyCasualPhone($arNums[1]);
            $fullNum = $num;
            if($extNum[0] == '+') {
                return [$num, $extNum];
            }
            if($extNum) {
                $fullNum = $num.';'.$extNum;
            }
            return [$fullNum];
        }

        $phones = [];
        $main = null;

        if(str_contains($arNums[0], ')')) {
            $arFirstNum = explode(')', $arNums[0]);
            $clearPhoneFirst = preg_replace('/[^0-9]/', '', $arNums[0]);
        }
        foreach ($arNums as $num) {
            if($clearPhoneFirst && $arFirstNum) {
                $clearPhoneSecond = preg_replace('/[^0-9]/', '', $arFirstNum[0] . $num);
                if(strlen($clearPhoneFirst) == strlen($clearPhoneSecond)) {
                    $firstNum = $this->modifyCasualPhone($clearPhoneFirst);
                    $secondNum = $this->modifyCasualPhone($clearPhoneSecond);
                    if(strlen($firstNum) == strlen($secondNum)) {
                        $num = $clearPhoneSecond;
                    }
                }
            }
            $numModify = $this->modifyCasualPhone($num);
            if($numModify[0] == '+') {
                if($main) {
                    $phones[] = $main;
                }
                $main = $numModify;
                continue;
            }
            if($main && $numModify) {
                $phones[] = $main . ';' . $numModify;
                $main = null;
            }
        }
        if($main) {
            if(!in_array($main, $phones)) {
                $phones[] = $main;
            }
        }
        return $phones;
    }
    public function checkIsTwoNums($arNums)
    {
        if(str_contains($arNums[0], ')') && !str_contains($arNums[1], ')')) {
            $arFirstNum = explode(')', $arNums[0]);
            $clearPhoneFirst = preg_replace('/[^0-9]/', '', $arNums[0]);
            $clearPhoneFirstSecondPart = preg_replace('/[^0-9]/', '', $arFirstNum[1]);
            $clearPhoneSecond = preg_replace('/[^0-9]/', '', $arFirstNum[0] . $arNums[1]);
            if(strlen($clearPhoneFirst) == strlen($clearPhoneSecond)) {
                $firstNum = $this->modifyCasualPhone($clearPhoneFirst);
                $secondNum = $this->modifyCasualPhone($clearPhoneSecond);
                if(strlen($firstNum) == strlen($secondNum)) {
                    return [$firstNum, $secondNum];
                }
            }
        }
        return false;
    }
    public function modifyCasualPhone($data)
    {
        if(!$data) {
            return '';
        }
        $data = ltrim($data);
        $hasPlus = $data[0] === '+';
        $clearPhone = preg_replace('/[^0-9]/', '', $data);
        // телефон не может начинаться с 0
        $clearPhone = ltrim($clearPhone, '0');
        if(strlen($clearPhone) < 7) {
            return $clearPhone;
        }
        // Обработка стандартных российских номеров
        if(strlen($clearPhone) == 11) {
            // Начинается с 7
            if($clearPhone[0] == '7') {
                return '+'.$clearPhone;
            }
            // Начинается с 8
            if($clearPhone[0] == '8') {
                return '+7'.substr($clearPhone, 1);
            }
        }
        // пропущен код страны
        if(strlen($clearPhone) == 10) {
            return '+7'.$clearPhone;
        }
        if($hasPlus) {
            return '+'.$clearPhone;
        }
        return $clearPhone;
    }
}

//$data = [
////    ['АРМАТОН ЗКПД ООО ТЕСТ', '8888888888888888888', '10', 'Шевченко', "Антон", "Александрович ТЕСТ", "Директор", "8 (938) 865 55 39; 8 (960) 471 59 90", "","","+79214204052 ; +78889958625","Shevchenko-SPb@yandex.ru"]
//    ['АРМАТОН ЗКПД ООО ТЕСТ', '8888888888888888888', '10', 'Шевченко', "Антон11", "Александрович ТЕСТ", "Директор", "", "","","","Shevchenko-SPb@yandex.ru"]
//];
//
//
//$class = new UploadContacts();
//$contactId = 14870;
////$email = 'test@test.ru';
////$data = '';
//echo '<pre>';
////print_r($class->getContactById($contactId));
////print_r($class->getContactIdByEmail($email));
//print_r($class->run($data));
//echo '</pre>';