<?php

class engine
{
    private
        $db,
        $moySklad,
        $modules = [],
        $timeStart,
        $templateEngine = false,
        $users = false,
        $factory = false,
        $warehouse = false;
    public
        $config = false;
    public static $engineDir = __DIR__;
    private static $engineInstance = false;
    private $ajax = false;
    private static $additionalClasses = [];
    public static $currentTime, $currentDate;

    public function __construct($ajax = false)
    {
        $this->ajax = $ajax;
        self::$engineInstance = $this;

        $this->timeStart = microtime(true);
        define('LGFactory', true);

        require_once 'mysql.class.php';
        require_once 'config.php';
        require_once 'moysklad.class.php';
        require_once 'templateEngine.class.php';
        require_once 'users.class.php';
        require_once 'permissions.class.php';
        require_once 'factory.class.php';
        require_once 'helper.class.php';
        require_once 'telegram.class.php';
        require_once 'paginator.class.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
        //require_once $_SERVER['DOCUMENT_ROOT'] . '/engine/libs/fpdf/fpdf.php';
        require_once  'warehouse.class.php';

        self::$currentDate = date('Y-m-d');
        self::$currentTime = time();

        $this->config = $config;


        if ($this->config['main']['debug']) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        $db = new MySQL($this->config['database']);
        if (!$db) throw new Exception('Can\'t connect to the Database');
        $this->db = $db;


        if(!$ajax) $this->templateEngine = new templateEngine($this);
        $this->users = new users($this);

        $this->moySklad = new moySklad($this);
        $this->factory = new factory($this);
        $this->telegram = new telegram($this);
        $this->warehouse = new warehouse($this);
    }

    public static function getCache($param)
    {
        $a = engine::DB()->getRow('SELECT * FROM @[prefix]cache WHERE id=?s', strtolower($param));
        return ($a) ? ['id' => $a['id'], 'value' => ((json_decode($a['json'], true))['d']), 'lastUpdate' => $a['lastUpdate']] : false;
    }

    public static function setCache($param, $value)
    {
        $data = ['d' => $value];
        $data = json_encode($data);
        $a = engine::DB()->query('INSERT INTO @[prefix]cache (id, json, lastUpdate) VALUES (?s, ?s, NOW()) ON DUPLICATE KEY UPDATE json=?s, lastUpdate=NOW()', strtolower($param), $data, $data);
    }

    public static function removeCache($param)
    {
        $a = engine::DB()->query('DELETE FROM @[prefix]cache WHERE id=?s', $param);
        return ($a) ? true : false;
    }

    public static function debug_printArray($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }

    //public static function saveFile($fileContent, $fileName, $userId)
    //{
    //    $fileData = base64_encode($fileContent);
    //    $a = engine::DB()->getRow('SELECT * FROM @[prefix]files WHERE fileName = ?s AND `data` = ?s', $fileName, $fileData);
    //    if($a) return $a['id'];
    //    engine::DB()->query('INSERT INTO @[prefix]files (uploadDate, uploadedBy, fileName, `data`) VALUES (NOW(), ?i, ?s, ?s)', $userId, $fileName, $fileData);
    //    return enginea = b
    //}

    public static function saveFile($index, $fileType){
        if(!array_key_exists($index, $_FILES)) throw new Exception('File with index \''.$index.'\' not found in $_FILES');
        if(!is_dir(engine::getUploadsDir().strtolower($fileType).'/')) mkdir(engine::getUploadsDir().strtolower($fileType).'/', 0777, true);
        if(!$_FILES[$index]['tmp_name']) throw new Exception('Неправильное имя файла');
        $hash = md5_file($_FILES[$index]['tmp_name']);
        $explodedName = explode('.', $_FILES[$index]['name']);
        $ext = $explodedName[count($explodedName) - 1];
        move_uploaded_file($_FILES[$index]['tmp_name'], engine::getUploadsDir().strtolower($fileType).'/'.$hash.'.'.$ext);
        engine::DB()->query('INSERT INTO @[prefix]files (uploadDate, uploadedBy, fileName, filePath, contentType, `size`) VALUES (NOW(), ?i, ?s, ?s, ?s, ?i)', engine::USERS()->getCurrentUser()['id'], $_FILES[$index]['name'], strtolower($fileType).'/'.$hash.'.'.$ext, $_FILES[$index]['type'], $_FILES[$index]['size']);

        return engine::DB()->insertId();
    }

    public static function getFile($fileId)
    {
        $a = engine::DB()->getRow('SELECT * FROM @[prefix]files WHERE id=?i', $fileId);
        return ($a) ? $a : false;
    }

    public static function getFileContent($fileId)
    {
        $a = engine::DB()->getRow('SELECT * FROM @[prefix]files WHERE id=?i', $fileId);
        if($a) $a = @file_get_contents(engine::getUploadsDir().$a['filePath']);
        return ($a) ? $a : false;
    }

    public static function removeFile($fileId)
    {
        $a = engine::DB()->query('DELETE FROM @[prefix]files WHERE id=?i', $fileId);
        return ($a) ? true : false;
    }


    public static function getUploadsDir(){
        return str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/uploads/');
    }


    /**
     * @return array
     */
    public static function CONFIG()
    {
        return self::$engineInstance->config;
    }

    /**
     * @return templateEngine
     */
    public static function TEMPLATE()
    {
        return self::$engineInstance->templateEngine;
    }

    /**
     * @return users
     */
    public static function USERS()
    {
        return self::$engineInstance->users;
    }

    /**
     * @return MySQL
     */
    public static function DB()
    {
        return self::$engineInstance->db;
    }

    /**
     * @return moySklad
     */
    public static function moySklad()
    {
        return self::$engineInstance->moySklad;
    }

    /**
     * @return int
     */
    public static function getTimeStart()
    {
        return self::$engineInstance->timeStart;
    }

    /**
     * @return factory
     */
    public static function FACTORY()
    {
        return self::$engineInstance->factory;
    }

    public function PAGINATOR()
    {
        return self::$engineInstance->Paginator;
    }

    public function productPaginator()
    {
        return self::$engineInstance->productPaginator;
    }

    /**
     * @return warehouse
     */
    public static function WHS(){
        return self::$engineInstance->warehouse;
    }

    /**
     * @return telegram
     */
    public static function TG(){
        return self::$engineInstance->telegram;
    }

    public static function format_price($s){
        $subtotal = number_format($s, 2, ',', ' ');         // 61 305
        return $subtotal;
    }


    public static function format_datetime($input){
        $d = date("Y-m-d H:i:s", strtotime($input));
        return $d;
    }


    public static function getClass($classname){
        if(array_key_exists($classname, self::$additionalClasses))
            return self::$additionalClasses[$classname];

        $f = __DIR__.'/'.$classname.'.class.php';

        if(file_exists($f)) {
            require_once $f;
            $class = new $classname(self::$engineInstance);
            self::$additionalClasses[$classname] = $class;
            return self::$additionalClasses[$classname];
        }else{
            throw new Exception('Class `'.$classname.'` not found');
        }
    }


    private static $userscache = [];

    public static function format_user($userId){
        if(array_key_exists($userId, engine::$userscache)) $specificationCreatedBy = engine::$userscache[$userId];
        else {
            $specificationCreatedBy = engine::USERS()->getUserById($userId);
            engine::$userscache[$userId] = $specificationCreatedBy;
        }

        return $specificationCreatedBy['lastname'] . ' ' . mb_substr($specificationCreatedBy['firstname'], 0, 1) . '. (<a href="#">' . $specificationCreatedBy['username'] . '</a>)';
    }

    public function __destruct()
    {

    }
}