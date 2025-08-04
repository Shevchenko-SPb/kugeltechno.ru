<?php
require_once 'Settings.php';
require_once 'upload.php';

// Тестируем функцию сравнения заголовков
$testCases = [
    ['Должность', 'ДолжностьПоВизитке'],
    ['Мобильный телефон', 'МобильныйТелефон'],
    ['Мобильный телефон', 'Мобильный'],
    ['Email', 'Почта'],
    ['Контрагент', 'Компания'],
];

echo "Тестирование функции сравнения заголовков:\n\n";

foreach ($testCases as $case) {
    $expected = $case[0];
    $actual = $case[1];
    $result = isHeaderMatch($actual, $expected);
    
    echo "Ожидается: '{$expected}'\n";
    echo "Актуальный: '{$actual}'\n";
    echo "Результат: " . ($result ? "✅ СОВПАДАЕТ" : "❌ НЕ СОВПАДАЕТ") . "\n";
    echo "Нормализованные:\n";
    echo "  Ожидается: '" . normalizeHeaderText($expected) . "'\n";
    echo "  Актуальный: '" . normalizeHeaderText($actual) . "'\n";
    echo "---\n\n";
}

// Тестируем полный массив заголовков
echo "Тестирование полного массива заголовков:\n\n";

$testHeaders = [
    'Контрагент',
    'ИНН',
    '',
    'Фамилия',
    'Имя',
    'Отчество',
    'ДолжностьПоВизитке',
    'Телефон',
    '',
    '',
    'МобильныйТелефон',
    'Email',
];

$result = checkHeadersOrder($testHeaders);

echo "Результат проверки:\n";
echo "Есть ошибки: " . ($result['has_errors'] ? "Да" : "Нет") . "\n";
echo "Предупреждения:\n";
foreach ($result['warnings'] as $warning) {
    echo "- {$warning}\n";
}
?>