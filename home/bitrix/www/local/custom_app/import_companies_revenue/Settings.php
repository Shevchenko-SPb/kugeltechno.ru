<?php
namespace ImportCompaniesRevenue;

class Settings
{
    // Пользовательское поля
    public const UF_COMPANY_3_YEARS_REVENUE = 'UF_CRM_1754991065'; // Выручка за 3 года - Строка
    public const UF_COMPANY_LAST_YEAR_REVENUE = 'UF_CRM_1754997076'; // Выручка за последний год - Число
    public const UF_COMPANY_INN = 'UF_CRM_INN'; // ИНН Строка
    public const CHECKO_API_KEY = '1CXWAe7BkhMmkZba'; // Ключ API для работы с сервисом checko.ru
    public const UF_COMPANY_FILTER_CODES = [
        'ASSIGNED_BY_ID', // Ответственный // Привязка к пользователям // Множественное: нет
        'UF_CRM_REGION_ID', // Регион // Привязка к элементам инфоблоков // Множественное: нет
        'UF_CRM_FEDERAL_DISTRICT_ID', // РФ (Фед округ) // Привязка к элементам инфоблоков // Множественное: нет
        'UF_CRM_1754491226', // РБ (Области Беларуси) // Список // Множественное: нет
        'UF_CRM_OKVED', // ОКВЭД (код) // Строка // Множественное: нет
        'UF_CRM_OKVED_MAIN', // Основной ОКВЭД (коротко) // Строка // Множественное: нет
    ];
}
