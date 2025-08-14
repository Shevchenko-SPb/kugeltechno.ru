<?php
namespace ImportCompaniesRevenue\lib;

/* УДАЛИТЬ */
//require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
//require_once './../Settings.php';
use Bitrix\Main\Loader;
Loader::includeModule('crm');
/*         */

use Bitrix\Crm\Service\Container;
use ImportCompaniesRevenue\Settings;

class Filter
{
    function getFilters()
    {
        $arFiltersCode = Settings::UF_COMPANY_FILTER_CODES;
        $factory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $UFS = $factory->getUserFieldsInfo();

        $filterData = [];
        foreach ($arFiltersCode as $code) {
            if(strncmp($code, 'UF', 2) != 0){
                // Не является пользовательским полем
                if($res = $this->getValsFromFixField($code)) {
                    $filterData[$code] = $res;
                }
                continue;
            }
            if(!$UFS[$code]) {
                // Несуществующее поле
                continue;
            }
            $fieldData = $UFS[$code];

            $type = $fieldData['TYPE'];
            $filterData[$code]['type'] = $type;
            $filterData[$code]['title'] = $fieldData['TITLE'];
            $filterData[$code]['multiple'] = false;
            if($arAttributes = $fieldData['ATTRIBUTES']) {
                if(is_array($arAttributes)) {
                    if(in_array('MUL', $arAttributes)) {
                        $filterData[$code]['multiple'] = true;
                    }
                }
            }
            if($type == 'iblock_element') {
                if($iblockId = $fieldData['SETTINGS']['IBLOCK_ID']) {
                    $filterData[$code]['vals'] = $this->getValFromIblockId($iblockId);
                    continue;
                }
            }
            if($type == 'enumeration') {
                if($items = $fieldData['ITEMS']) {
                    foreach ($items as $item) {
                        $filterData[$code]['vals'][] =
                            [
                                'id' => $item['ID'],
                                'title' => $item['VALUE']
                            ];
                    }
                    continue;
                }
            }
            if($type == 'string') {
                continue;
            }
            // Неизвестный формат. Удаляем из фильтра.
            unset($filterData[$code]);
        }
        return $filterData;
    }
    function getValFromIblockId($iblockId)
    {
        $rs = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
            ],
        );
        $result = [];
        while($el = $rs->fetch()) {
            $result[] = [
                'id' => $el['ID'],
                'title' => $el['NAME']
            ];
        }
        return $result;
    }

    function getValsFromFixField($code)
    {
        if($code == 'ASSIGNED_BY_ID') {
            return [
                'type' => 'user',
                'multiple' => false,
                'vals' => $this->getUsers(),
                'title' => 'Ответственный'
            ];
        }

        return null;
    }
    function getUsers()
    {
        $filter =
        [
            "ACTIVE"              => "Y",
            "GROUPS_ID"           => [1,3,4,12]
        ];
        $rsUsers = \CUser::GetList('', '' , $filter); // выбираем пользователей
        $users = [];
        while ($user = $rsUsers->Fetch())
        {
            $users[] = [
                'id' => $user['ID'],
                'title' => $user['NAME'] . ' ' . $user['LAST_NAME'],
            ];
        }
        return $users;
    }
}

// Пример ответа на вызов метода getFilters();
//Array
//(
//    [ASSIGNED_BY_ID] => Array
//    (
//        [type] => user
//        [multiple] =>
//            [vals] => Array
//(
//    [0] => Array
//    (
//        [id] => 1
//                            [title] => admin
//                        )
//
//                    [1] => Array
//(
//    [id] => 3
//                            [title] => Антон Пащенко
//                        )
//
//                    [2] => Array
//(
//    [id] => 6
//                            [title] => 131 Павел Климук
//                        )
//
//                    [3] => Array
//(
//    [id] => 13
//                            [title] => Антон Шевченко
//                        )
//
//                    [4] => Array
//(
//    [id] => 21
//                            [title] => 218 Алексей Аникичев
//                        )
//
//                    [5] => Array
//(
//    [id] => 22
//                            [title] => 1100 Ирина Аликина
//                        )
//
//                    [6] => Array
//(
//    [id] => 23
//                            [title] => 1102 Кирилл Кузнецов
//                        )
//
//                    [7] => Array
//(
//    [id] => 24
//                            [title] => 118 Анастасия Бельская
//                        )
//
//                    [8] => Array
//(
//    [id] => 25
//                            [title] => 1103 Роман Артамонов
//                        )
//
//                    [9] => Array
//(
//    [id] => 26
//                            [title] => 219 Елена Толмачева
//                        )
//
//                    [10] => Array
//(
//    [id] => 27
//                            [title] => 255 Сергей Хрусталев
//                        )
//
//                    [11] => Array
//(
//    [id] => 28
//                            [title] => 206 Алексей Головистиков
//                        )
//
//                    [12] => Array
//(
//    [id] => 29
//                            [title] => 210 Елена Ковальская
//                        )
//
//                    [13] => Array
//(
//    [id] => 30
//                            [title] => 205 Виктор Молодцов
//                        )
//
//                    [14] => Array
//(
//    [id] => 31
//                            [title] => 208 Вадим Артёмов
//                        )
//
//                    [15] => Array
//(
//    [id] => 32
//                            [title] => 212 Денис Кашин
//                        )
//
//                    [16] => Array
//(
//    [id] => 33
//                            [title] => 203 Екатерина Короннова
//                        )
//
//                    [17] => Array
//(
//    [id] => 34
//                            [title] => 204 Татьяна Лескина
//                        )
//
//                    [18] => Array
//(
//    [id] => 35
//                            [title] => Марианна Штирой
//                        )
//
//                    [19] => Array
//(
//    [id] => 36
//                            [title] => 213 Екатерина Зобнина
//                        )
//
//                    [20] => Array
//(
//    [id] => 37
//                            [title] => 216 Екатерина Ростошинская
//                        )
//
//                    [21] => Array
//(
//    [id] => 38
//                            [title] => Муниря Мамкеева
//                        )
//
//                    [22] => Array
//(
//    [id] => 39
//                            [title] => 224 Ольга Рокало
//                        )
//
//                    [23] => Array
//(
//    [id] => 40
//                            [title] => Максим Князев
//                        )
//
//                    [24] => Array
//(
//    [id] => 41
//                            [title] => Вячеслав Салкин
//                        )
//
//                    [25] => Array
//(
//    [id] => 42
//                            [title] => 503 Анна Дунаева
//                        )
//
//                    [26] => Array
//(
//    [id] => 43
//                            [title] => Ирина Михалева
//                        )
//
//                    [27] => Array
//(
//    [id] => 44
//                            [title] => Лидия Омелькова
//                        )
//
//                    [28] => Array
//(
//    [id] => 45
//                            [title] => Александр Куклич
//                        )
//
//                    [29] => Array
//(
//    [id] => 46
//                            [title] => Наталья Фролова
//                        )
//
//                    [30] => Array
//(
//    [id] => 47
//                            [title] => 110 Андрей Курейчик
//                        )
//
//                    [31] => Array
//(
//    [id] => 48
//                            [title] => 103 Сергей Шакунов
//                        )
//
//                    [32] => Array
//(
//    [id] => 49
//                            [title] => 135 Антон Макс
//                        )
//
//                    [33] => Array
//(
//    [id] => 50
//                            [title] => 125 Евгений Полещук
//                        )
//
//                    [34] => Array
//(
//    [id] => 51
//                            [title] => 120 Олег Петрожицкий
//                        )
//
//                    [35] => Array
//(
//    [id] => 52
//                            [title] => 101 Ольга Черняева
//                        )
//
//                    [36] => Array
//(
//    [id] => 53
//                            [title] => 104 Дмитрий Минич
//                        )
//
//                    [37] => Array
//(
//    [id] => 54
//                            [title] => Наталья Мойсечик
//                        )
//
//                    [38] => Array
//(
//    [id] => 55
//                            [title] => Анастасия Молотобойцева
//                        )
//
//                    [39] => Array
//(
//    [id] => 56
//                            [title] => Марина Кушнерова
//                        )
//
//                    [40] => Array
//(
//    [id] => 57
//                            [title] => Людмила Амельченко
//                        )
//
//                    [41] => Array
//(
//    [id] => 58
//                            [title] => Екатерина Урбанайтес
//                        )
//
//                    [42] => Array
//(
//    [id] => 59
//                            [title] => Виктория Гончарик
//                        )
//
//                    [43] => Array
//(
//    [id] => 60
//                            [title] => 163 Ольга Рудевич
//                        )
//
//                    [44] => Array
//(
//    [id] => 61
//                            [title] => 112 Константин Правлуцкий
//                        )
//
//                    [45] => Array
//(
//    [id] => 62
//                            [title] => 168 Елизавета Синяк
//                        )
//
//                    [46] => Array
//(
//    [id] => 63
//                            [title] => 164 Анастасия Заикина
//                        )
//
//                    [47] => Array
//(
//    [id] => 64
//                            [title] => Эвелина Кудревич
//                        )
//
//                    [48] => Array
//(
//    [id] => 65
//                            [title] => Владимир Федориев
//                        )
//
//                    [49] => Array
//(
//    [id] => 66
//                            [title] => Максим Щербо
//                        )
//
//                    [50] => Array
//(
//    [id] => 67
//                            [title] => Глеб Шалимов
//                        )
//
//                    [51] => Array
//(
//    [id] => 68
//                            [title] => Елизавета Кислюк
//                        )
//
//                    [52] => Array
//(
//    [id] => 69
//                            [title] => 161 Дмитрий Гуринович
//                        )
//
//                    [53] => Array
//(
//    [id] => 70
//                            [title] => 167 Яков Хотенко
//                        )
//
//                    [54] => Array
//(
//    [id] => 71
//                            [title] => 109 Михаил Кульша
//                        )
//
//                    [55] => Array
//(
//    [id] => 72
//                            [title] => 129 Вероника Шахно
//                        )
//
//                    [56] => Array
//(
//    [id] => 73
//                            [title] => 111 Елена Плешка
//                        )
//
//                    [57] => Array
//(
//    [id] => 74
//                            [title] => 128 Виктория Малышева
//                        )
//
//                    [58] => Array
//(
//    [id] => 75
//                            [title] => 115 Олеся Чупина
//                        )
//
//                    [59] => Array
//(
//    [id] => 76
//                            [title] => 182 Александр Новик
//                        )
//
//                    [60] => Array
//(
//    [id] => 77
//                            [title] => 127 Михаил Пономаренко
//                        )
//
//                    [61] => Array
//(
//    [id] => 78
//                            [title] => 136 Игорь Ковалевский
//                        )
//
//                    [62] => Array
//(
//    [id] => 79
//                            [title] => 137 Вадим Чешко
//                        )
//
//                    [63] => Array
//(
//    [id] => 80
//                            [title] => 146 Даниил Сафин
//                        )
//
//                    [64] => Array
//(
//    [id] => 81
//                            [title] => 149 Владислав Княжевич
//                        )
//
//                    [65] => Array
//(
//    [id] => 82
//                            [title] => 130 Дмитрий Семенов
//                        )
//
//                    [66] => Array
//(
//    [id] => 83
//                            [title] => 134 Василий Отчик
//                        )
//
//                    [67] => Array
//(
//    [id] => 84
//                            [title] => Вячеслав Лесько
//                        )
//
//                )
//
//        )
//
//    [UF_CRM_FEDERAL_DISTRICT_ID] => Array
//(
//    [type] => iblock_element
//    [multiple] =>
//            [vals] => Array
//(
//    [0] => Array
//    (
//        [id] => 30
//                            [title] => СЗФО
//                        )
//
//                    [1] => Array
//(
//    [id] => 31
//                            [title] => ЦФО
//                        )
//
//                    [2] => Array
//(
//    [id] => 32
//                            [title] => ЮФО
//                        )
//
//                    [3] => Array
//(
//    [id] => 33
//                            [title] => СКФО
//                        )
//
//                    [4] => Array
//(
//    [id] => 34
//                            [title] => ПФО
//                        )
//
//                    [5] => Array
//(
//    [id] => 35
//                            [title] => УФО
//                        )
//
//                    [6] => Array
//(
//    [id] => 36
//                            [title] => СФО
//                        )
//
//                    [7] => Array
//(
//    [id] => 37
//                            [title] => ДФО
//                        )
//
//                )
//
//        )
//
//    [UF_CRM_1754491226] => Array
//(
//    [type] => enumeration
//    [multiple] =>
//            [vals] => Array
//(
//    [0] => Array
//    (
//        [id] => 86
//                            [title] => Минская обл
//                        )
//
//                    [1] => Array
//(
//    [id] => 87
//                            [title] => Брестская обл
//                        )
//
//                    [2] => Array
//(
//    [id] => 88
//                            [title] => Витебская обл
//                        )
//
//                    [3] => Array
//(
//    [id] => 89
//                            [title] => Гомельская обл
//                        )
//
//                    [4] => Array
//(
//    [id] => 90
//                            [title] => Гродненская обл
//                        )
//
//                    [5] => Array
//(
//    [id] => 91
//                            [title] => Могилёвская обл
//                        )
//
//                )
//
//        )
//
//    [UF_CRM_OKVED] => Array
//(
//    [type] => string
//    [multiple] =>
//        )
//
//    [UF_CRM_OKVED_MAIN] => Array
//(
//    [type] => string
//    [multiple] =>
//        )
//
//)
