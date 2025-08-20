<?php
namespace ImportCompaniesFromDaDaTa\lib;

use Bitrix\Crm\Service\Container;
use ImportCompaniesFromDaDaTa\Settings;
use Bitrix\Main\Loader;
use Bitrix\Crm\ItemIdentifier;


class Company
{
    public $companyFactory;
    public $companyUFs;

    function __construct()
    {
        $this->companyFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $this->companyUFs = $this->companyFactory->getUserFieldsInfo();
    }
    public function uploadRevenue($companyIds)
    {

        $countErrors = 0;
        $countUpdatedInn = 0;
        $countUpdatedRevenue = 0;
        $countRevenueNotFound = 0;
        $countInnNotFound = 0;
        $balance = 0;
        $requests = 0;
        $errorsLog = [];

        foreach ($companyIds as $companyId)
        {
            $mainCompany = $this->companyFactory->getItem($companyId);
            if(!$mainCompany) {
                $countErrors++;
                $errorsLog[] = "Не удалось получить компанию с ID: {$companyId}";
                continue;
            }
            $mainCompanyFields = $mainCompany->getData();
            $inn = $mainCompanyFields[Settings::UF_COMPANY_INN];
            // Если ИНН не найден, то пытаемся найти его по названию компании
            if(!$inn) {
                $title = $mainCompany->getTitle();
                if(str_contains($title, 'ИНН:')) {
                    $arTitle = explode('ИНН:', $title);
                    $inn = (int)$arTitle[1];
                }
            }
            if(!$inn) {
                $countInnNotFound++;
                $errorsLog[] = "Не найден ИНН для компании с ID: {$companyId}";
                continue;
            }
            if($inn == '1234567890') {
                $countInnNotFound++;
                $errorsLog[] = "ИНН 1234567890 для компании с ID: {$companyId} Загрузка прервана";
                continue;
            }

            $DaDaTa = new \DaDaTa();
            $res_dadata = $DaDaTa->getDataFromDaDaTa($inn);
            if ($res_dadata['error']) {
                $countErrors++;
                $errorsLog[] = "Ошибка при загрузке данных из DaDaTa для компании с ID: {$companyId}. Сообщение: \"{$res_dadata['message']}\"";
                continue;
            }
            if (!$res_dadata['suggestions']) {
                // Если в ответе нет данных о компании
                $mainCompany->set(Settings::UF_COMPANY_IS_CHECK_DA_DA_TA, true);
                $updateOperation = $this->companyFactory->getUpdateOperation($mainCompany);
                $updateOperation->launch();
                $countRevenueNotFound++;
                $errorsLog[] = "Данных не найдено для компании с ID: {$companyId}";
                continue;
            }
            $mainCompanyTitle = $mainCompany->getTitle();
            $arData = $res_dadata['suggestions'];
            $arResult = [];
            foreach ($arData as $data) {
                $rs = [];
                $rs['title'] = $data['value'];
                if($data = $data['data']) {
                    $rs['inn'] = $data['inn'];
                    $rs['kpp'] = $data['kpp'];
                    $rs['ogrn'] = $data['ogrn'];
                    $rs['okato'] = $data['okato'];
                    $rs['okved'] = $data['okved'];

                    if($arAddress = $data['address']) {
                        $address['address']= $arAddress['value'] ?? '';
                        $address['address_full'] = $arAddress['unrestricted_value'] ?? ''; // с кодом
                        if($dataAdr = $arAddress['data']) {
                            $address['postal_code'] = $dataAdr['postal_code'] ?? '';
                            $address['country'] = $dataAdr['country'] ?? '';
                            $address['region_with_type'] = $dataAdr['region_with_type'] ?? '';
                            $address['city'] = $dataAdr['city'] ?? '';
                            $address['street_with_type'] = $dataAdr['street_with_type'] ?? '';
                            $address['house_type'] = $dataAdr['house_type'] ?? '';
                            $address['house'] = $dataAdr['house'] ?? '';
                            $address['federal_district'] = $dataAdr['federal_district'] ?? ''; // Северо-Западный
                            $address['country_iso_code'] = $dataAdr['country_iso_code'] ?? ''; // RU
                        }
                        $rs['address'] = $address;
                    }
                    if($arManagement = $data['management']) {
                        $management['name'] = $arManagement['name'] ?? ''; // Управление
                        $management['post'] = $arManagement['post'] ?? ''; // Управление
                        if($dateStart = $arManagement['start_date']) {
                            $management['start_date'] = date("d.m.Y", (int)$dateStart/1000);
                        }
                        $rs['management'] = $management;
                    }
                    if($state = $data['state']) {
                        if($regDate = $state['registration_date']) {
                            $rs['registration_date'] = date("d.m.Y", (int)$regDate/1000);
                        }
                    }
                    if($name = $data['name']) {
                        $rs['name'] = $name['short_with_opf'];
                        $rs['full_name'] = $name['full_with_opf'];
                    }
                    if($data['branch_type'] === 'BRANCH') {
                        $arResult['branch'][] = $rs;
                    }
                    if($data['branch_type'] === 'MAIN') {
                        $arResult['main'] = $rs;
                    }
                }
            }
            $branchCompaniesIds = [];
            $holdingElId = null;
            if($branches = $arResult['branch']) {
                // Проверяем есть ли основная компания в списке Холдинги
                $holdingElId = $this->checkCompanyIsInHoldingListAndGetElId($companyId);
                if(!$holdingElId) {
                    $result = $this->createHoldingEl($companyId, $mainCompanyTitle); // Создание элемента списка "Холдинги" и добавление туда основной компании
                    if($result['error']) {
                        $countErrors++;
                        $errorsLog[] = "Ошибка при создании элемента списка \"Холдинги\" для компании с ID: {$companyId}. Сообщение: \"{$result['error']}\"";
                        continue;
                    }
                    $holdingElId = $result['id']; // Получаем id созданного элемента списка "Холдинги"
                }
                foreach ($branches as $branch) {
                    $result = $this->getBranchCompanyId($companyId, $branch);
                    if($result['error']) {
                        $errorsLog[] = $result['error'];
                        $countErrors++;
                        continue;
                    }
                    $branchCompanyId = $result['id'];
                    if($branchCompanyId) {
                        $branchCompaniesIds[] = $branchCompanyId;
                        $this->addRequisite($branchCompanyId, $branch);
                        $this->addBranchCompanyInHoldingList($holdingElId, $branchCompanyId);
                    }
                }
            }
            // Обновляем данные об основной компании
            if($main = $arResult['main']) {
                $country = $main['address']['country'];
                $district = $main['address']['federal_district'];
                $userFields = $this->companyUFs;
                $this->addRequisite($companyId, $main);
                foreach ($mainCompanyFields as $key => $value) {
                    if ($key == Settings::UF_COMPANY_BRANCHES) {
                        if(!is_array($value)) {
                            $value = [];
                        }
                        if($branchCompaniesIds) {
                            $mainCompany->set(Settings::UF_COMPANY_BRANCHES,
                                array_unique(array_merge($value, $branchCompaniesIds)) ?? []);
                        }
                        continue;
                    }
                    if ($value) {
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_IS_CHECK_DA_DA_TA) {
                        $mainCompany->set(Settings::UF_COMPANY_IS_CHECK_DA_DA_TA, true);
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_INN) {
                        $mainCompany->set(Settings::UF_COMPANY_INN, $main['inn'] ?? '');
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_KPP) {
                        $mainCompany->set(Settings::UF_COMPANY_KPP, $main['kpp'] ?? '');
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_OKVED) {
                        $mainCompany->set(Settings::UF_COMPANY_OKVED, $main['okved'] ?? '');
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_COUNTRY) {
                        if($country) {
                            if($UFCountry = $userFields[Settings::UF_COMPANY_COUNTRY]) {
                                if($items = $UFCountry['ITEMS']) {
                                    $country = mb_strtolower(str_replace(' ', '', $country), 'UTF-8');
                                    foreach ($items as $item) {
                                        $val = mb_strtolower(str_replace(' ', '', $item['VALUE']), 'UTF-8');
                                        if($val == $country) {
                                            $mainCompany->set(Settings::UF_COMPANY_COUNTRY, $item['ID']);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        continue;
                    }
                    if ($key == Settings::UF_COMPANY_FEDERAL_DISTRICT) {
                        if($districtCode = Settings::AR_FED_DISTRICTS[$district]) {
                            if($UFDistrict = $userFields[Settings::UF_COMPANY_FEDERAL_DISTRICT]) {
                                if($iBlockId = $UFDistrict['SETTINGS']['IBLOCK_ID']) {
                                    $CIBlockElement = new \CIBlockElement;
                                    $arFilter = [
                                        'IBLOCK_ID' => $iBlockId,
                                    ];
                                    $arSelect = ['ID', 'NAME'];
                                    $rs = $CIBlockElement::GetList([], $arFilter, false, [], $arSelect);
                                    while ($el = $rs->fetch()) {
                                        if($districtCode == $el['NAME']) {
                                            $mainCompany->set(Settings::UF_COMPANY_FEDERAL_DISTRICT, $el['ID']);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        continue;
                    }
                }
                $updateOperation = $this->companyFactory->getUpdateOperation($mainCompany);
                $updateOperation->launch();

                if($holdingElId) {
                    $this->runListBP(Settings::LIST_HOLDINGS_ID, $holdingElId);
                }
            }
        }
        return [
            'companies_updated_inn' => $countUpdatedInn, // Обновлено ИНН у компаний
            'companies_inn_not_found' => $countInnNotFound, // Не найдено ИНН у компании
            'companies_updated_revenue' => $countUpdatedRevenue, // Количество компаний у которых обновлены данные об обороте
            'companies_revenue_not_found' => $countRevenueNotFound, // Количество компаний у которых не найдено данных об обороте
            'errors' => $countErrors, // Количество ошибок
            'errors_log' => $errorsLog, // Описание ошибок
            'balance' => $balance, // Баланс на счете checko.ru
            'today_request_count' => $requests, // Количество запросов за сегодня на сервис checko.ru
        ];
    }

    public function addBranchCompanyInHoldingList($elId, $companyId)
    {
        // Проверка нахождения компании в списке "Холдинги"
        $CIBlockElement = new \CIBlockElement;
        $arFilter = [
            'IBLOCK_ID' => Settings::LIST_HOLDINGS_ID,
            'ID' => $elId,
            Settings::LIST_HOLDINGS_UF_COMPANIES => $companyId
        ];
        $arSelect = ['ID', Settings::LIST_HOLDINGS_UF_COMPANIES];
        $rs = $CIBlockElement::GetList([], $arFilter, false, [], $arSelect);
        if($rs->fetch()) {
            // Компания уже в списке "Холдинги"
            return null;
        }

        if($el = $CIBlockElement::GetByID($elId)) {
            if($elObj = $el->GetNextElement()) {
                $props = $elObj->GetProperties();
                $name = $elObj->GetFields()['NAME'];
                $newProps = [];
                $fieldCompaniesId = str_replace('PROPERTY_' , '', Settings::LIST_HOLDINGS_UF_COMPANIES);
                foreach ($props as $code => $prop) {
                    $newVal = $prop['VALUE'];
                    if($prop['ID'] == $fieldCompaniesId) {
                        if(!is_array($newVal)) {
                            $newVal = [];
                        }
                        if(!in_array($companyId, $newVal)) {
                            $newVal[] = $companyId;
                        }
                    }
                    $newProps[$code] = $newVal;
                }
                $arFields = [
                    "MODIFIED_BY"    => 1,
                    "IBLOCK_SECTION_ID" => false,
                    "IBLOCK_ID"      => Settings::LIST_HOLDINGS_ID,
                    "NAME"           => $name,
                    "ACTIVE"         => "Y",
                    "PROPERTY_VALUES"=> $newProps,
                ];
                $el = new \CIBlockElement;
                $el->Update($elId, $arFields);
            }
        }
    }

    public function getBranchCompanyId($mainCompanyId, $fields)
    {
        // Проверяем существует ли такая компания или нет
        if(!$fields['kpp'] || !$fields['ogrn']) {
            return ['error' => 'Не указан KPP или OGRN'];
        }
        $params = [
            'filter' => [
                Settings::UF_COMPANY_KPP => $fields['kpp'],
                Settings::UF_COMPANY_OGRN => $fields['ogrn'],
            ],
            'select' => ['ID'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1,
            'offset' => 0
        ];
        $companies = $this->companyFactory->getItems($params);
        $company = $companies[0];
        $companyId = null;
        if($company) {
            $companyId = $company->getId();
            if($companyId) {
                return ['id' => $companyId];
            }
        }
        // Компания не найдена - создаём новую
        if(!$companyId) {
            return $this->createCompany($mainCompanyId, $fields);
        }
    }
    public function createCompany($mainCompanyId, $fields)
    {
        $title = $fields['title'] ?? $fields['name'] ?? $fields['full_name'] ?? 'Без названия';
        $kpp = $fields['kpp'];
        $ogrn = $fields['ogrn'];
        $okved = $fields['okved'];
        $country = $fields['address']['country'];
        $district = $fields['address']['federal_district'];

        $userFields =  $this->companyUFs;

        // Проверка пользовательских полей
        if(!$userFields[Settings::UF_COMPANY_KPP]
            || !$userFields[Settings::UF_COMPANY_OGRN]
            || !$userFields[Settings::UF_COMPANY_IS_CHECK_DA_DA_TA]
            ) {
            return ['error' => 'Отсутствую обязательные пользовательские поля КПП или ОГРН'];
        }

        $fields = [
            'TITLE' => $title,
            'ASSIGNED_BY_ID' => 1,
            'CREATED_BY' => 1,
            'UPDATED_BY' => 1,
            Settings::UF_COMPANY_KPP => $kpp,
            Settings::UF_COMPANY_OGRN => $ogrn,
            Settings::UF_COMPANY_IS_CHECK_DA_DA_TA, true
        ];
        if($country) {
            if($UFCountry = $userFields[Settings::UF_COMPANY_COUNTRY]) {
                if($items = $UFCountry['ITEMS']) {
                    $country = mb_strtolower(str_replace(' ', '', $country), 'UTF-8');
                    foreach ($items as $item) {
                        $val = mb_strtolower(str_replace(' ', '', $item['VALUE']), 'UTF-8');
                        if($val == $country) {
                            $fields[Settings::UF_COMPANY_COUNTRY] = $item['ID'];
                            break;
                        }
                    }
                }
            }
        }
        if($district) {
            if($districtCode = Settings::AR_FED_DISTRICTS[$district]) {
                if($UFDistrict = $userFields[Settings::UF_COMPANY_FEDERAL_DISTRICT]) {
                    if($iBlockId = $UFDistrict['SETTINGS']['IBLOCK_ID']) {
                        $CIBlockElement = new \CIBlockElement;
                        $arFilter = [
                            'IBLOCK_ID' => $iBlockId,
                        ];
                        $arSelect = ['ID', 'NAME'];
                        $rs = $CIBlockElement::GetList([], $arFilter, false, [], $arSelect);
                        while ($el = $rs->fetch()) {
                            if($districtCode == $el['NAME']) {
                                $fields[Settings::UF_COMPANY_FEDERAL_DISTRICT] = $el['ID'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        if($okved) {
            if($userFields[Settings::UF_COMPANY_OKVED]) {
                $fields[Settings::UF_COMPANY_OKVED] = $okved;
            }
        }
        if($userFields[Settings::UF_COMPANY_MAIN_COMPANY]) {
            $fields[Settings::UF_COMPANY_MAIN_COMPANY] = $mainCompanyId;
        }
        // Создаём компанию
        $company = $this->companyFactory->createItem($fields);
        $operation = $this->companyFactory->getAddOperation($company);
        $result = $operation->launch();
        if ($result->isSuccess()) {
            $companyId = $company->getId(); // Получаем ID созданной компании
            return [
                'id' => $companyId
            ];
        } else {
            return [
                'error' => "Создание компании ОГРН $ogrn, Название $title " . $result->getError()
            ];
        }
    }
    public function createHoldingEl($companyId, $title)
    {
        $CIBlockElement = new \CIBlockElement;
        $propId = str_replace('PROPERTY_', '', Settings::LIST_HOLDINGS_UF_COMPANIES);
        $fields = [
            'NAME' => $title,
            'IBLOCK_ID' => Settings::LIST_HOLDINGS_ID,
            'PROPERTY_VALUES' => [
                $propId => [$companyId]
            ]
        ];


        $elId = $CIBlockElement->Add($fields);
        if(!$elId) {
            return ['error' => $CIBlockElement->LAST_ERROR];
        }
        return ['id' => $elId];
    }
    public function checkCompanyIsInHoldingListAndGetElId($companyId)
    {
        $CIBlockElement = new \CIBlockElement;
        $arFilter = [
            'IBLOCK_ID' => Settings::LIST_HOLDINGS_ID,
            Settings::LIST_HOLDINGS_UF_COMPANIES => $companyId
        ];
        $arSelect = ['ID', Settings::LIST_HOLDINGS_UF_COMPANIES];
        $rs = $CIBlockElement::GetList([], $arFilter, false, [], $arSelect);
        if($result = $rs->fetch()) {
            // Компания уже в списке "Холдинги"
            return $result['ID'];
        }
        return false;
    }
    public function runListBP($listId, $elementId)
    {
        // Бизнес процесс
        if (Loader::IncludeModule('bizproc')) {
            $arWorkflowTemplates = \CBPDocument::GetWorkflowTemplatesForDocumentType([
                'lists', 'Bitrix\Lists\BizprocDocumentLists', 'iblock_' . $listId
            ]);
            foreach ($arWorkflowTemplates as $arTemplate) {
                /**
                 * AUTO_EXECUTE = 1 - запускать при создании
                 * AUTO_EXECUTE = 2 - запускать при изменении
                 * AUTO_EXECUTE = 3 - запускать при изменении и создании
                 */
                $arErrorsTmp = [];
                if ($arTemplate['AUTO_EXECUTE'] == 3) {
                    $wfId = \CBPDocument::StartWorkflow(
                        $arTemplate['ID'],
                        [ 'lists', 'Bitrix\Lists\BizprocDocumentLists', $elementId ],
                        [],
                        $arErrorsTmp
                    );

                    if (count($arErrorsTmp) > 0) {
                        foreach ($arErrorsTmp as $e) {
                            $errorMessage .= "[".$e["code"]."] ".$e["message"]."";
                        }
                    }
                }
            }
        }
    }

    public function addRequisite($companyId, $fields)
    {
        /* Проверка есть ли реквизиты у компании */
        $params = [
            'filter' => [
                'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                'ENTITY_ID' => $companyId
            ]
        ];
        $entityRequisite = new \Bitrix\Crm\EntityRequisite;
        $rs = $entityRequisite->getList($params);
        if($rs->fetch()) {
            // Реквизиты уже есть
            return;
        }
        $addrs = $fields['address'];
        $address = '';
        if($addrs['street_with_type']) {
            $address = $addrs['street_with_type'] . ', ';
        }
        if($addrs['house_type']) {
            $address .= $addrs['house_type'] . '. ';
        }
        if($addrs['house']) {
            $address .= $addrs['house'];
        }
        $arRQAddr['6'] = [ //1 - Физический адрес, 6 -Юридический адрес
            'ADDRESS_1' => $address,
            'POSTAL_CODE' => $addrs['postal_code'] ?? '',
            'PROVINCE' => $addrs['region_with_type'] ?? '',
            'CITY' => $addrs['city'] ?? '',
            'COUNTRY' => $addrs['country'] ?? '',
        ];
        $name = $fields['title'] ?? $fields['name'] ?? $fields['full_name'] ?? '';
        $fieldsNewRequisite =
            [
                'PRESET_ID' => 1,
                'NAME' => $name,
                'ACTIVE' => 'Y',
                'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                'ENTITY_ID' => $companyId,
                'RQ_ADDR' => $arRQAddr,
                'RQ_COMPANY_FULL_NAME' => $fields['full_name'] ?? $fields['title'] ?? '',
                'RQ_COMPANY_NAME' => $fields['name'] ?? $fields['title'] ?? 'Организация',
                'RQ_COMPANY_REG_DATE' => $fields['registration_date'] ?? '',
                'RQ_INN' => $fields['inn'] ?? '',
                'RQ_OKVED' => $fields['okved'] ?? '',
                'RQ_OGRN' => $fields['ogrn'] ?? '',
                'RQ_KPP' => $fields['kpp'] ?? '',
            ];
        $entityRequisite->add($fieldsNewRequisite);
    }
}

