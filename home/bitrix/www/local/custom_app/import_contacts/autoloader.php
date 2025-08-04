<?php
/**
 * Автолоадер для подключения классов из корневой папки, папок lib и src
 */

class CustomAutoloader
{
    private static $libPath;
    private static $srcPath;
    private static $rootPath;
    private static $loadedClasses = [];
    
    /**
     * Инициализация автолоадера
     */
    public static function init()
    {
        self::$libPath = __DIR__ . '/lib/';
        self::$srcPath = __DIR__ . '/src/';
        self::$rootPath = __DIR__ . '/';
        
        // Регистрируем автолоадер
        spl_autoload_register([__CLASS__, 'loadClass']);
        
        // Предварительно загружаем все классы из папок lib, src и корневой папки
        self::preloadAllClasses();
    }
    
    /**
     * Загрузка класса по имени
     * 
     * @param string $className Имя класса
     * @return bool
     */
    public static function loadClass($className)
    {
        // Проверяем, не загружен ли уже класс
        if (isset(self::$loadedClasses[$className])) {
            return true;
        }
        
        // Ищем файл класса в корневой папке
        $classFile = self::$rootPath . $className . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            self::$loadedClasses[$className] = true;
            return true;
        }
        
        // Ищем файл класса в папке lib
        $classFile = self::$libPath . $className . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            self::$loadedClasses[$className] = true;
            return true;
        }
        
        // Ищем файл класса в папке src
        $classFile = self::$srcPath . $className . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            self::$loadedClasses[$className] = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Предварительная загрузка всех классов из папок lib, src и корневой папки
     */
    private static function preloadAllClasses()
    {
        // Загружаем классы из корневой папки
        if (is_dir(self::$rootPath)) {
            $files = glob(self::$rootPath . '*.php');
            
            foreach ($files as $file) {
                $className = basename($file, '.php');
                
                // Пропускаем сам автолоадер и другие служебные файлы
                if ($className !== 'autoloader' && self::isClassFile($file)) {
                    require_once $file;
                    self::$loadedClasses[$className] = true;
                }
            }
        }
        
        // Загружаем классы из папки lib
        if (is_dir(self::$libPath)) {
            $files = glob(self::$libPath . '*.php');
            
            foreach ($files as $file) {
                $className = basename($file, '.php');
                
                // Пропускаем файлы, которые не являются классами (например, функции)
                if (self::isClassFile($file)) {
                    require_once $file;
                    self::$loadedClasses[$className] = true;
                }
            }
        }
        
        // Загружаем классы из папки src
        if (is_dir(self::$srcPath)) {
            $files = glob(self::$srcPath . '*.php');
            
            foreach ($files as $file) {
                $className = basename($file, '.php');
                
                // Пропускаем файлы, которые не являются классами (например, функции)
                if (self::isClassFile($file)) {
                    require_once $file;
                    self::$loadedClasses[$className] = true;
                }
            }
        }
    }
    
    /**
     * Проверяет, содержит ли файл определение класса
     * 
     * @param string $filePath Путь к файлу
     * @return bool
     */
    private static function isClassFile($filePath)
    {
        $content = file_get_contents($filePath);
        
        // Проверяем наличие ключевых слов class, interface или trait
        return preg_match('/\b(class|interface|trait)\s+\w+/i', $content);
    }
    
    /**
     * Получить список загруженных классов
     * 
     * @return array
     */
    public static function getLoadedClasses()
    {
        return array_keys(self::$loadedClasses);
    }
    
    /**
     * Проверить, загружен ли класс
     * 
     * @param string $className Имя класса
     * @return bool
     */
    public static function isClassLoaded($className)
    {
        return isset(self::$loadedClasses[$className]);
    }
}

// Инициализируем автолоадер при подключении файла
CustomAutoloader::init();