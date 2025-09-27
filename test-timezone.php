<?php

/**
 * Тестовый скрипт для проверки корректности настройки часового пояса
 */

echo "=== Проверка настроек часового пояса ===\n";
echo "Системный часовой пояс: " . date_default_timezone_get() . "\n";
echo "Текущее время (UTC): " . gmdate('Y-m-d H:i:s') . "\n";
echo "Текущее время (локальное): " . date('Y-m-d H:i:s') . "\n";
echo "Часовой пояс PHP: " . ini_get('date.timezone') . "\n";

// Проверяем Laravel конфигурацию, если доступна
if (file_exists(__DIR__ . '/config/app.php')) {
    $config = include __DIR__ . '/config/app.php';
    echo "Laravel часовой пояс: " . $config['timezone'] . "\n";
}

// Создаем объект DateTime для проверки
$dt = new DateTime();
echo "DateTime объект: " . $dt->format('Y-m-d H:i:s T') . "\n";

$moscow_dt = new DateTime('now', new DateTimeZone('Europe/Moscow'));
echo "Московское время: " . $moscow_dt->format('Y-m-d H:i:s T') . "\n";

echo "=== Тест завершен ===\n";
