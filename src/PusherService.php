<?php
require_once __DIR__ . '/../vendor/autoload.php';

class PusherService {
    private $pusher;

    public function __construct() {
        $options = [
            'cluster' => getenv('PUSHER_APP_CLUSTER') ?: 'mt1',
            'useTLS'  => true
        ];
        $this->pusher = new Pusher\Pusher(
            getenv('PUSHER_APP_KEY') ?: 'missing',
            getenv('PUSHER_APP_SECRET') ?: 'missing',
            getenv('PUSHER_APP_ID') ?: 'missing',
            $options
        );
    }

    public function trigger($channel, $event, $data) {
        if (!$this->pusher) return false;
        return $this->pusher->trigger($channel, $event, $data);
    }
}