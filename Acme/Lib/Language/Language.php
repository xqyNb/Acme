<?php


namespace Acme\Lib\Language;

use Acme\App;
use Acme\Lib\Util\Loader;

/**
 * Class Language 语言工具类
 * @package Acme\Lib\Language
 * @author Billion
 * @time 2021-01-21 02:54:55
 */
class Language {
    private string $lang; // 当前语言
    private array $laguage = []; // 语言

    /**
     * 获取当前语言
     * @return string
     */
    public function lang():string{
        return $this->lang;
    }

    /**
     * 获取语言word
     * @param int $index 语言索引编号
     * @return string
     */
    public function word(int $index):string{
        // 加载语言
        [
            'words' => $words,
        ] = $this->loadLanguage();
        // 判断有没有
        if(isset($words[$index])){
            return $words[$index];
        }
        return '';
    }

    /**
     * 语言转换
     * @param string $html
     * @return string
     */
    public function laguageTranslate(string $html):string{
        $html = preg_replace_callback_array([
            '-{lang:(\d)}-' => function($match){
                // 获取语言word
                return $this->word($match[1]);
            }
        ],$html);

        return $html;
    }

    /**
     * 解析语言控制器
     * @param string $controller
     * @return string
     */
    public function parseController(string $controller):string{
        [
            'default' => $defaultLanguage,
            'langList' => $langList,
        ] = $this->config();
        // 解析语言
        [$path] = explode('\\',$controller);
        $lang = strtolower($path);
        // 判断是否包含语言
        if(isset($langList[$lang])){ // 语言集合
            $this->lang = $lang; // 设置指定语言
            // 重置控制器
            return str_replace($path.'\\','',$controller);
        }
        // 设置语言为默认
        $this->lang = $defaultLanguage;
        // 非语言集合
        return $controller;
    }

    // 是否支持语言功能
    public function isSupport():bool{
        $config = $this->config();
        return $config['support'];
    }

    // 读取语言配置
    public function config():array{
        return App::appConfigParam('LANGUAGE');
    }

    /**
     * 获取语言包的lang
     * @return string
     */
    public function getLang() : string{
        $language = $this->loadLanguage();
        return $language['lang'];
    }


    // 加载语言
    private function loadLanguage() : array{
        // 判断有没有
        if(empty($this->laguage)){
            $path = ACME_ROOT . "/Language/$this->lang.php";
            $this->laguage = Loader::loadPhpConfig($path);
        }

        return $this->laguage;
    }

}