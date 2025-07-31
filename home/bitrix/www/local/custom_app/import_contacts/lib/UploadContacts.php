<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
class UploadContacts
{
    public function run($data)
    {
        // что-то делаем. Ждём 1 секунду, чтобы показать анимацию загрузки.
        sleep(1);

        $errorData = [
            [
                'УНТК (Уральский научно-технологический комплекс) АО',
                '10',
                '',
                '',
                '',
                '234',
                '234',
                '',
                '',
                '',
                '1@1.ru',
            ],
        ];
        return [
            'contacts_updated_count' => 1, // Количество обновленных контактов
            'contacts_upload_count' => 0, // Количество добавленных контактов
            'companies_upload_count' => 0, // Количество добавленных компаний
            'contacts_upload_error_count' => 1, // Количество ошибок при добавлении контакта
            'contacts_upload_error_data' => $errorData ?: [] // Данные о контактах с ошибками для записи в файл
        ];
    }

}