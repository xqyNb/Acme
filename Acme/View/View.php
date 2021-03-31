<?php


namespace Acme\View;

use Acme\App;
use Acme\DataBase\Redis\RedisManager;
use Acme\Lib\Language\Language;
use Acme\Lib\Method\FileMethod;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Util\Console;

/**
 * Class View 视图类
 * @package Acme\View
 * @author Billion
 * @time 2021-01-20 15:16:02
 */
class View {

    private string $templateName = '';
    private string $templateSuffix;
    private array $templateConfig;
    private array $assignData = [];

    // 构造函数
    public function __construct() {
        $this->templateConfig = App::appConfigParam('TEMPLATE');
        $this->templateSuffix =$this->templateConfig['suffix'];
    }

    /**
     * 分配数据
     * @param string $key
     * @param string $content
     */
    public function assign(string $key,string $content){
        $this->assignData[$key] = $content;
    }

    /**
     * 获取显示模板
     * @param string $templateName
     * @param Language|null $language
     * @return string
     */
    public function getDisplay(string $templateName,?Language $language=NULL) : string{
//        Console::out("templateName : $templateName");
        // 设置模板
        $this->templateName = $templateName;
        // 加载嵌套模板
        $html = $this->loadTemplate();
        // 分配数据到模板
        $config = $this->templateConfig;
        // 合并模板的默认变量
        $assignData = array_merge($this->assignData,$config['variables']);
        foreach ($assignData as $key => $value){
            $search = $config['leftOperator'].$key.$config['rightOperator'];
            $html = str_replace($search,$value,$html);
        }
        // 处理语言
        if($language !== NULL){
            $html = $language->laguageTranslate($html);
        }

        return $html;
    }

    // 加载嵌套模板
    private function loadTemplate(string $templateName='',string $templateSuffix=''):string{
        // 获取模板内容
        $html = $this->templateContent($templateName,$templateSuffix); // 页面内容
        // 检测是否有模板嵌套
        $html = preg_replace_callback_array([
            '-{include:(.+)}-' => function($match)use($templateSuffix){
                $subTempateName = $match[1];
                // 加载子模板
                return $this->loadTemplate($subTempateName,$templateSuffix);
            }
        ],$html);

        // 返回模板内容
        return $html;
    }


    // 获取模板内容
    private function templateContent(string $templateName='',string $templateSuffix='') : string{
        // 设置模板和后缀
        $templateName = $templateName ?: $this->templateName;
        $templateSuffix = $templateSuffix ?: $this->templateSuffix;
        // 设置文件名
        $fileName = ACME_APP.ACME_DS.'View'.ACME_DS.$templateName.$templateSuffix;
//        Console::out($fileName);
        // 判断文件是否存在
        if(file_exists($fileName)){
            // 获取修改时间
            $modifyTime = FileMethod::getFileModifyTime($fileName);

            // 判断是否开启模板缓存
            if($this->templateConfig['cache']){
                $cacheName = $this->templateConfig['cacheName'];
                // 从缓存中读取
                $redis = RedisManager::redis();
                // 判断是否存在
                if($redis->hExists($cacheName,$templateName)){ // 存在 - 取内容
                    $templateInfoString = $redis->hGet($cacheName,$templateName);
                    [
                        'modifyTime' => $lastModifyTime,
                        'html' => $html,
                    ] = $templateInfo = json_decode($templateInfoString,true);
                    // 对比2次修改时间
                    if($lastModifyTime != $modifyTime){ // 已做修改
                        $html = $this->getTemplateFile($fileName);
                        // 重新缓存文件信息
                        $templateInfo['modifyTime'] = $modifyTime;
                        $templateInfo['html'] = $html;
                        $redis->hSet($cacheName,$templateName,json_encode($templateInfo));
                        // 返回新文件信息
//                        Console::out('已做修改 - 已更新缓存!');
                        return $html;
                    }else{ // 未做修改
//                        Console::out('未做修改 - 缓存读取!');
                        return $html;
                    }
                }else{ // 不存在 - 读取文件
                    $html = $this->getTemplateFile($fileName);
                    // 文件读取成功 - 保存到缓存
                    $templateInfo = [
                        'modifyTime' => $modifyTime,
                        'html' => $html,
                    ];
                    $redis->hSet($cacheName,$templateName,json_encode($templateInfo));
                    // 返回文件信息
//                    Console::out('没有缓存 - 已设置缓存!');
                    return $html;
                }

            }else{ // 关闭缓存 - 文件读取
//                Console::out('未设置缓存 - 文件读取!');
                return $this->getTemplateFile($fileName);
            }

        }else{
            Console::appPrint("模板文件不存在! -> [ $fileName ]");
        }
        return '';
    }

    // 从模板文件中获取内容
    private function getTemplateFile(string $fileName):string{
        $html = file_get_contents($fileName);
        if($html !== false){
            return $html;
        }else{
            Console::appPrint("模板文件无法读取!请检查是否有权限! -> [ $fileName ]");
        }
        return '';
    }

}