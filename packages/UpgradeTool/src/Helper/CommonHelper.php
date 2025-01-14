<?php

namespace XinYin\UpgradeTool\Helper;

use Illuminate\Support\Facades\Config;

class CommonHelper
{
    /**
     * 獲得環境設定參數
     *
     * @return array
     */
    public static function getEnvSettings(): array
    {
        return Config::get('custommessagehook.env', Config::get('MessageHook.env'));
    }

    /**
     * 獲得 hook url 設定參數
     */
    public static function getUrlSettings(): string
    {
        return Config::get('custommessagehook.url', Config::get('MessageHook.url'));
    }

    /**
     * 獲得 是否使用討論串參數
     */
    public static function getShouldThreadSettings(): string
    {
        return Config::get('custommessagehook.should_thread', Config::get('MessageHook.should_thread'));
    }

    /**
     * 發送webhook
     *
     * @param string $text
     * @param string $thread_key
     * @return void
     */
    public static function sendWebHook(string $text, string $thread_key)
    {
        $curl = curl_init();

        $message = ['text' => self::processEOL($text)];

        $url = self::getUrlSettings();

        if (self::getShouldThreadSettings()) {
            $message['thread'] = [
                'threadKey' => $thread_key
            ];

            $url = "{$url}&messageReplyOption=REPLY_MESSAGE_FALLBACK_TO_NEW_THREAD";
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($message),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        if (! $response) {
            echo "Send Webhook Fail";
        } else {
            echo "Send Webhook OK";
        }
    }

    /**
     * 透過版本號處理討論串key
     * @param string $version
     * @return string
     */
    public static function getThreadKey(string $version): string
    {
        return implode('.', array_slice(explode('.', $version), 0, 2));
    }

    /**
     * 處理換行符號
     *
     * @param $message
     * @return string
     */
    public static function processEOL(string $message): string
    {
        $message = str_replace('\n', PHP_EOL, $message);
        $message = str_replace('&&', PHP_EOL, $message);

        return $message;
    }
}
