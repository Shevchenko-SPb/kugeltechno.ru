<?php
namespace ImportCompaniesFromDaDaTa\lib;

use Bitrix\Crm\Service\Container;
use ImportCompaniesFromDaDaTa\Settings;
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


            $parent = new ItemIdentifier(\CCrmOwnerType::Company, $companyId);
            $child = new ItemIdentifier(\CCrmOwnerType::Company, 222674);
            $result = Container::getInstance()->getRelationManager()->bindItems($parent, $child);
            return $result->isSuccess();
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
            if (!$res_dadata['suggestions']) { // Если в ответе нет данных о компании
                $countRevenueNotFound++;
                $errorsLog[] = "Данных не найдено для компании с ID: {$companyId}";
                continue;
            }
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
            if($main = $arResult['main']) {
                $this->addRequisite($companyId, $main);
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
                'RQ_IIN' => $fields['inn'] ?? '',
                'RQ_OKVED' => $fields['okved'] ?? '',
                'RQ_OGRN' => $fields['ogrn'] ?? '',
                'RQ_KPP' => $fields['kpp'] ?? '',
            ];
        $entityRequisite->add($fieldsNewRequisite);
    }
}

