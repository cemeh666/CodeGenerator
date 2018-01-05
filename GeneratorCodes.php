<?php

/**
 * Created by PhpStorm.
 * User: cemeh666
 * Date: 04.01.18
 * Time: 16:55
 */
class GeneratorCodes
{
    /**
     * Устанавливаем начальные значения перед генерацией
     * или оставлям значения по умолчанию
     * GenerateCodes constructor.
     * @param int $length
     * @param int $chunk
     */
    function __construct($length = 8, $chunk = 0)
    {
        $this->length = $length;
        $this->chunk  = $chunk;

        $this->generate();
    }

    /**
     * Массив префиксов(первый символ в коде)
     * и количество необходимых кодов
     * @var array
     */
    public $prefix_count = [
        'C' => 6417131,
        'E' => 3020459,
        'F' => 9465477,
        'K' => 1859803,
        'M' => 2527939,
        'N' => 76251,
        'P' => 1309544,
        'R' => 2797052,
        'S' => 8695178,
        'T' => 1749493,
        'U' => 16369920,
        'W' => 6103018,
        'X' => 1515605,
        'Y' => 2831807,
        'Z' => 1776457
    ];

    /**
     * Набор символов из которых будет состоять коды
     * @var string
     */
    public $character = 'qWwEeRrTtYyUuPpFfHhJjKkXxCcVvNnMm3579';

    /**
     * Длина промо кода
     * @var int
     */
    private $length = 0;

    /**
     * Количество промо кодов в файле.
     * Если 0, то будет сгенерирован 1 файл
     * @var int
     */
    private $chunk  = 0;

    /**
     * Запуск генерации кодов
     */
    public function generate(){

        if($this->length > strlen($this->character))
            die("Длина кода не может быть больше длины строки символов...\n");

        $shuffle_character = $this->factorial(strlen($this->character));
        $shuffle_length    = $this->factorial(strlen($this->length));
        $shuffle_compare   = $this->factorial(strlen($this->character) - $this->length);

        //максимальное число перестановок(соченаний) из shuffle_character по shuffle_length
        $maximum_shuffle =  $shuffle_character / ($shuffle_length*$shuffle_compare);

        echo "\n================\n";
        echo "Начало генерации\n";

        $all_time   =  microtime(true);
        foreach ($this->prefix_count as $prefix => $count){
            $codes  = [];
            $start  = microtime(true);
            $memory = memory_get_usage(true);

            if($maximum_shuffle < $count)
                die("Невозможно сгенерировать $count кодов!\nМаксимальное количество сочетаний $maximum_shuffle\n");

            do{
                $codes[$this->randomCode($prefix)] = '';
                $count_codes  = count($codes);
            }while($count_codes < $count);

            echo "\nВремя генерации [$prefix]: ".(microtime(true) - $start)." сек.\n";

            $this->writeToFile($codes, $prefix);

            echo "Используемая память [$prefix]: ".((memory_get_usage(true) - $memory)/1024/1024)." Мбайт.\n";
        }

        echo "\nОбщее время выполнения: ".(microtime(true) - $all_time)." сек.\n";

        //проверяет на уникальность
        //необходима если изменится способ генерации
        //т.к. ключи массива обеспечивают уникальность
        //$this->checkUnique();

        echo "\nВсе коды были сгенерированы";
        echo "\n=============================\n";
    }

    /**
     * Проверка на уникальность кодов
     */
    private function checkUnique(){
        echo "\n=======================\n";
        echo "Проверка на уникальность\n";

        if($this->chunk) {
            foreach ($this->prefix_count as $prefix => $count){
                $chunks = scandir(__DIR__."/codes/$prefix");
                $contents = [];
                $i = 0;

                foreach ($chunks as $key => $chunk) {
                    if($key > 1){
                        $file = fopen(__DIR__."/codes/$prefix/Prefix-$prefix-$i.txt", "r");
                        $contents = array_merge($contents, explode("\n", fread($file, filesize(__DIR__."/codes/$prefix/Prefix-$prefix-$i.txt"))));
                        $i++;
                        fclose($file);
                    }
                }

                $count_unique = count(array_unique($contents));
                $result = "\n[$prefix] уникальных $count_unique => $count должно быть. ";
                $result.= ($count == $count_unique) ? '[OK]' : '[FAIL]';
                $result.= "\n";
                echo $result;
                echo "Используемая память [$prefix]: ".(memory_get_usage(true)/1024/1024)." Мбайт.\n";
            }

        }else{

            foreach ($this->prefix_count as $prefix => $count){

                $file = fopen(__DIR__."/codes/Prefix-$prefix.txt", "r");
                $contents = fread($file, filesize(__DIR__."/codes/Prefix-$prefix.txt"));

                $contents = explode("\n", $contents);
                $count_unique = count(array_unique($contents));
                $result = "\n[$prefix] уникальных $count_unique => $count должно быть. ";
                $result.= ($count == $count_unique) ? '[OK]' : '[FAIL]';
                $result.= "\n";
                echo $result;
                echo "Используемая память [$prefix]: ".(memory_get_usage(true)/1024/1024)." Мбайт.\n";

                fclose($file);
            }
        }
    }

    /**
     * Запись результата генерации в файл(ы) .txt
     * @param $codes
     * @param $prefix
     */
    public function writeToFile($codes, $prefix){
        $start = microtime(true);

        //если количество chunk <> 0, то записываем результат в разные файлы
        if($this->chunk){
            //перед запуском создаём директории с именами префиксов если их нет
            foreach ($this->prefix_count as $p => $count) {
                if(!is_dir(__DIR__."/codes/$p")){
                    $mkdir = mkdir(__DIR__."/codes/$p", 0777);
                    if (!$mkdir) {
                        die('Не удалось создать директории...');
                    }
                }
            }
            $codes = array_chunk($codes, $this->chunk, true);
            foreach ($codes as $key => $chunk){
                //Записываем всё содержимое ключей массива разбитых на строки в файлы
                file_put_contents(__DIR__."/codes/$prefix/Prefix-$prefix-$key.txt", implode("\n", array_keys($chunk)));
            }
        }else{
            //Записываем всё содержимое ключей массива разбитых на строки в файл
            file_put_contents(__DIR__."/codes/Prefix-$prefix.txt", implode("\n", array_keys($codes)));
        }
        echo "Время записи [$prefix]: ".(microtime(true) - $start)." сек.\n";

    }

    /**
     * Создание кода путём случайной перестановки
     * @param $prefix
     * @return string
     */
    private function randomCode($prefix){
        $chars = $this->character;
        return $prefix . substr(str_shuffle($chars), 0, $this->length);
    }

    /**
     * Вычисляем факториал
     * @param $length
     * @return int
     */
    private function factorial($length){
        if($length == 1) return 1;
        return $length * $this->factorial($length - 1);
    }

}