<?php
namespace ImportCompaniesFromDaDaTa;

class Settings
{
    // Пользовательское поля
    public const UF_COMPANY_3_YEARS_REVENUE = 'UF_CRM_1754991065'; // Выручка за 3 года - Строка
    public const UF_COMPANY_LAST_YEAR_REVENUE = 'UF_CRM_1754997076'; // Выручка за последний год - Число
    public const UF_COMPANY_IS_CHECK_DA_DA_TA = 'UF_CRM_1755174086'; // [AT] Выполнена проверка DaDaTa - да/нет
    public const UF_COMPANY_MAIN_COMPANY = 'UF_CRM_1755671728'; // [AT] Основная компания - CRM Company not MUL
    public const UF_COMPANY_BRANCHES = 'UF_CRM_1755671756'; // [AT] Филиалы - CRM Company MUL
    public const UF_COMPANY_INN = 'UF_CRM_INN'; // ИНН Строка
    public const UF_COMPANY_KPP = 'UF_CRM_KPP'; // КПП Строка
    public const UF_COMPANY_OGRN = 'UF_CRM_OGRN'; // ОГРН Строка
    public const UF_COMPANY_COUNTRY = 'UF_CRM_1753805102'; // Страна // Список
    public const UF_COMPANY_FEDERAL_DISTRICT = 'UF_CRM_FEDERAL_DISTRICT_ID'; // РФ (Фед округ) Список id 17
    public const UF_COMPANY_OKVED = 'UF_CRM_OKVED'; // ОКВЭД (код)
    public const UF_COMPANY_OKVED_TITLE = 'UF_CRM_OKVED_TITLE'; // ОКВЭД (описание)
    public const UF_COMPANY_OKVED_MAIN = 'UF_CRM_OKVED_MAIN'; // Основной ОКВЭД (коротко)
    public const LIST_HOLDINGS_ID = 18;
    public const LIST_HOLDINGS_UF_COMPANIES = 'PROPERTY_66';
    public const DA_DA_TA_API_KEY = 'a97c3ecf0f83c1851d2605046b0e64531d703952'; // Ключ API для работы с сервисом dadata.ru
    public const UF_COMPANY_FILTER_CODES = [
        'ASSIGNED_BY_ID', // Ответственный // Привязка к пользователям // Множественное: нет
        'UF_CRM_REGION_ID', // Регион // Привязка к элементам инфоблоков // Множественное: нет
        'UF_CRM_FEDERAL_DISTRICT_ID', // РФ (Фед округ) // Привязка к элементам инфоблоков // Множественное: нет
        'UF_CRM_1754491226', // РБ (Области Беларуси) // Список // Множественное: нет
        'UF_CRM_OKVED', // ОКВЭД (код) // Строка // Множественное: нет
        'UF_CRM_OKVED_MAIN', // Основной ОКВЭД (коротко) // Строка // Множественное: нет
    ];

    public const AR_FED_DISTRICTS = [
        'Северо-Западный' => 'СЗФО',
        'Южный' => 'ЮФО',
        'Центральный' => 'ЦФО',
        'Приволжский' => 'ПФО',
        'Уральский' => 'УФО',
        'Сибирский' => 'СФО',
        'Дальневосточный' => 'ДФО',
        'Северо-Кавказский' => 'СКФО',
    ];
}
