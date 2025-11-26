<?php

namespace ServiceChargeManager\Services;

class SmsService
{
    /**
     * Send SMS using WordPress HTTP API
     * 
     * @param string $phone Phone number
     * @param string $message Message to send
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function sendSms($phone, $message)
    {
        // Example using a generic SMS API (you'll need to replace with your SMS provider's API)
        $api_key = get_option('scm_sms_api_key', '');
        $sender_id = get_option('scm_sms_sender_id', 'SCM');

        // For testing, just log the message
        error_log("SMS would be sent to $phone: $message");

        if (defined('WP_DEBUG') && WP_DEBUG) {
            return array(
                'success' => true,
                'message' => 'SMS logged (debug mode)',
                'debug' => array(
                    'to' => $phone,
                    'message' => $message,
                    'sender' => $sender_id
                )
            );
        }

        // TODO: Implement your SMS provider's API here
        // Example implementation:
        /*
        $response = wp_remote_post('https://your-sms-provider.com/api/send', array(
            'body' => array(
                'api_key' => $api_key,
                'sender' => $sender_id,
                'phone' => $phone,
                'message' => $message
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body;
        */

        return array('success' => true, 'message' => 'SMS would be sent in production');
    }

    /**
     * Format phone number for SMS sending
     * 
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    public function formatPhone($phone)
    {
        // Remove any non-digit characters except leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If starts with 0, replace with +88
        if (strpos($phone, '0') === 0) {
            $phone = '+88' . substr($phone, 1);
        }
        // If doesn't start with +, add +88
        elseif (strpos($phone, '+') !== 0) {
            $phone = '+88' . $phone;
        }

        return $phone;
    }
}
