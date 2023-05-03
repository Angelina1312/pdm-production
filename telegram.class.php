<?php
class telegram{
    private $engine = false;
    private $token = false;
    private $chats = [];
    private $chatsSize = [];

    public function __construct($engine){
        $this->engine = $engine;
        $this->token = engine::CONFIG()['main']['telegramBotToken'];
        $this->chats = engine::CONFIG()['main']['telegramGroups'];
        $this->chatsSize = engine::CONFIG()['main']['telegramGroupsSize'];
    }


    public function sendMessage($msg, $silent = true){
        if(engine::CONFIG()['main']['telegramEnabled'] === false) return false;
        try{
            foreach($this->chats as $chatId){

                $url='https://api.telegram.org/bot'.$this->token.'/sendMessage';
                $data=array('chat_id'=>$chatId,'text'=>$msg, 'parse_mode' => 'html');
                if($silent) $data['disable_notification'] = 1;

                $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
                $context=stream_context_create($options);
                $result=@file_get_contents($url,false,$context);
                return $result;
            }
        }catch (Exception $ex){
            return false;
        }
        return true;
    }

    public function sendMessageSize($msg, $silent = true){
        if(engine::CONFIG()['main']['telegramEnabled'] === false) return false;
        try{
            foreach($this->chatsSize as $chatId){

                $url='https://api.telegram.org/bot'.$this->token.'/sendMessage';
                $data=array('chat_id'=>$chatId,'text'=>$msg, 'parse_mode' => 'html');
                if($silent) $data['disable_notification'] = 1;

                $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
                $context=stream_context_create($options);
                $result=@file_get_contents($url,false,$context);
                return $result;
            }
        }catch (Exception $ex){
            return false;
        }
        return true;
    }
}