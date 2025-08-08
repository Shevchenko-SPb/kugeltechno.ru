<?php

class Settings
{
    // Пользовательское поля
    public const UF_CONTACT_INN_COMPANY = 'UF_CRM_1753953016'; // [AT] ИНН Компании // string
    public const UF_CONTACT_COUNTRY = 'UF_CRM_1754548956'; // [AT] Страна // string
    public const UF_CONTACT_FED_REGION = 'UF_CRM_1754549070'; // [AT] РФ (Фед округ) // string
    public const UF_CONTACT_WORK_PHONE = 'UF_CRM_1754549124'; // [AT] Телефон (рабочий) // string
    public const UF_CONTACT_MOBILE_PHONE = 'UF_CRM_1754549146'; // [AT] Телефон (мобильный) // string
    public const UF_COMPANY_COUNTRY = 'UF_CRM_1753805102'; // Страна // Список
    public const UF_COMPANY_FED_REGION = 'UF_CRM_FEDERAL_DISTRICT_ID'; // РФ (Фед округ) // Привязка к элементам инфоблоков

    // Этот порядок используется в UploadContacts для проверки очерёдности столбцов;
    public const AR_FIELDS = [
        'Контрагент', // Наименование полное название Компании
        'ИНН', // ИНН компании (обязательное поле)
        'Страна', // Страна компании
        'КонтактноеЛицо', // ФИО контактного лица (объединенное поле)
        'Должность', // Должность контактного лица
        'Телефон', // Рабочий телефон
        'Мобильный телефон', // Мобильный телефон
        'Email', // Электронная почта
    ];
}