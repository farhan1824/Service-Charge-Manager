<?php

namespace ServiceChargeManager\Includes;

class Debug
{
    public static function log($message, $data = null)
    {
        if (WP_DEBUG) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            $timestamp = current_time('mysql');
            $data_str = $data ? print_r($data, true) : '';
            error_log("[$timestamp] SCM Debug: $message $data_str\n", 3, $log_file);
        }
    }
}
