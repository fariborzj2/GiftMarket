<?php

class TelegramAPI {
    private $token;
    private $apiUrl = "https://api.telegram.org/bot";

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * Send a message to a specific chat/channel
     */
    public function sendMessage($chatId, $text, $parseMode = 'Markdown') {
        if (empty($this->token)) {
            return ['ok' => false, 'description' => 'Bot token is missing'];
        }

        $url = $this->apiUrl . $this->token . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        return $this->request($url, $data);
    }

    /**
     * Get bot information to verify token
     */
    public function getMe() {
        if (empty($this->token)) {
            return ['ok' => false, 'description' => 'Bot token is missing'];
        }

        $url = $this->apiUrl . $this->token . "/getMe";
        return $this->request($url, []);
    }

    /**
     * Internal request handler using cURL
     */
    private function request($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'description' => $error];
        }

        return json_decode($response, true);
    }
}
