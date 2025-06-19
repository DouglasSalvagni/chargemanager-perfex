<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ChargeManager Helper Functions
 * Funções utilitárias para o módulo ChargeManager
 */

if (!function_exists('chargemanager_format_currency')) {
    /**
     * Format currency value for display
     * @param float $value
     * @param string $currency
     * @return string
     */
    function chargemanager_format_currency($value, $currency = 'BRL')
    {
        $value = (float) $value;
        
        switch ($currency) {
            case 'BRL':
                return 'R$ ' . number_format($value, 2, ',', '.');
            case 'USD':
                return '$' . number_format($value, 2, '.', ',');
            default:
                return number_format($value, 2, '.', ',');
        }
    }
}

if (!function_exists('chargemanager_format_date')) {
    /**
     * Format date for display
     * @param string $date
     * @param string $format
     * @return string
     */
    function chargemanager_format_date($date, $format = 'd/m/Y')
    {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        
        return date($format, strtotime($date));
    }
}

if (!function_exists('chargemanager_get_status_label')) {
    /**
     * Get status label with CSS class
     * @param string $status
     * @param string $type
     * @return string
     */
    function chargemanager_get_status_label($status, $type = 'billing_group')
    {
        $labels = [];
        
        if ($type === 'billing_group') {
            $labels = [
                'open' => ['class' => 'label-info', 'text' => _l('chargemanager_status_open')],
                'processing' => ['class' => 'label-warning', 'text' => _l('chargemanager_status_processing')],
                'completed' => ['class' => 'label-success', 'text' => _l('chargemanager_status_completed')],
                'partial' => ['class' => 'label-info', 'text' => _l('chargemanager_status_partial')],
                'paid' => ['class' => 'label-success', 'text' => _l('chargemanager_status_paid')],
                'cancelled' => ['class' => 'label-danger', 'text' => _l('chargemanager_status_cancelled')]
            ];
        } elseif ($type === 'charge') {
            $labels = [
                'pending' => ['class' => 'label-warning', 'text' => _l('chargemanager_charge_status_pending')],
                'received' => ['class' => 'label-success', 'text' => _l('chargemanager_charge_status_received')],
                'overdue' => ['class' => 'label-danger', 'text' => _l('chargemanager_charge_status_overdue')],
                'cancelled' => ['class' => 'label-default', 'text' => _l('chargemanager_charge_status_cancelled')]
            ];
        }
        
        if (isset($labels[$status])) {
            return '<span class="label ' . $labels[$status]['class'] . '">' . $labels[$status]['text'] . '</span>';
        }
        
        return '<span class="label label-default">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('chargemanager_get_status_class')) {
    /**
     * Get status CSS class for badge
     * @param string $status
     * @return string
     */
    function chargemanager_get_status_class($status)
    {
        switch ($status) {
            case 'open':
                return 'info';
            case 'processing':
                return 'warning';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }
}

if (!function_exists('chargemanager_get_billing_type_name')) {
    /**
     * Get billing type display name
     * @param string $billing_type
     * @return string
     */
    function chargemanager_get_billing_type_name($billing_type)
    {
        $types = [
            'BOLETO' => _l('chargemanager_billing_type_boleto'),
            'PIX' => _l('chargemanager_billing_type_pix'),
            'CREDIT_CARD' => _l('chargemanager_billing_type_credit_card')
        ];
        
        return $types[$billing_type] ?? $billing_type;
    }
}

if (!function_exists('chargemanager_sanitize_cpf_cnpj')) {
    /**
     * Sanitize CPF/CNPJ removing special characters
     * @param string $document
     * @return string
     */
    function chargemanager_sanitize_cpf_cnpj($document)
    {
        return preg_replace('/[^0-9]/', '', $document);
    }
}

if (!function_exists('chargemanager_format_cpf_cnpj')) {
    /**
     * Format CPF/CNPJ for display
     * @param string $document
     * @return string
     */
    function chargemanager_format_cpf_cnpj($document)
    {
        $document = chargemanager_sanitize_cpf_cnpj($document);
        
        if (strlen($document) === 11) {
            // CPF
            return substr($document, 0, 3) . '.' . 
                   substr($document, 3, 3) . '.' . 
                   substr($document, 6, 3) . '-' . 
                   substr($document, 9, 2);
        } elseif (strlen($document) === 14) {
            // CNPJ
            return substr($document, 0, 2) . '.' . 
                   substr($document, 2, 3) . '.' . 
                   substr($document, 5, 3) . '/' . 
                   substr($document, 8, 4) . '-' . 
                   substr($document, 12, 2);
        }
        
        return $document;
    }
}

if (!function_exists('chargemanager_validate_cpf_cnpj')) {
    /**
     * Validate CPF/CNPJ
     * @param string $document
     * @return bool
     */
    function chargemanager_validate_cpf_cnpj($document)
    {
        $document = chargemanager_sanitize_cpf_cnpj($document);
        
        if (strlen($document) === 11) {
            return chargemanager_validate_cpf($document);
        } elseif (strlen($document) === 14) {
            return chargemanager_validate_cnpj($document);
        }
        
        return false;
    }
}

if (!function_exists('chargemanager_validate_cpf')) {
    /**
     * Validate CPF
     * @param string $cpf
     * @return bool
     */
    function chargemanager_validate_cpf($cpf)
    {
        $cpf = chargemanager_sanitize_cpf_cnpj($cpf);
        
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}

if (!function_exists('chargemanager_validate_cnpj')) {
    /**
     * Validate CNPJ
     * @param string $cnpj
     * @return bool
     */
    function chargemanager_validate_cnpj($cnpj)
    {
        $cnpj = chargemanager_sanitize_cpf_cnpj($cnpj);
        
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        $sequence1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sequence2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $sequence1[$i];
        }
        
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        
        if ($cnpj[12] != $digit1) {
            return false;
        }
        
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $sequence2[$i];
        }
        
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        
        return $cnpj[13] == $digit2;
    }
}

if (!function_exists('chargemanager_sanitize_phone')) {
    /**
     * Sanitize phone number
     * @param string $phone
     * @return string
     */
    function chargemanager_sanitize_phone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

if (!function_exists('chargemanager_format_phone')) {
    /**
     * Format phone number for display
     * @param string $phone
     * @return string
     */
    function chargemanager_format_phone($phone)
    {
        $phone = chargemanager_sanitize_phone($phone);
        
        if (strlen($phone) === 11) {
            // Mobile with area code
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 1) . ' ' . 
                   substr($phone, 3, 4) . '-' . 
                   substr($phone, 7, 4);
        } elseif (strlen($phone) === 10) {
            // Landline with area code
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 4) . '-' . 
                   substr($phone, 6, 4);
        }
        
        return $phone;
    }
}

if (!function_exists('chargemanager_sanitize_postal_code')) {
    /**
     * Sanitize postal code (CEP)
     * @param string $postal_code
     * @return string
     */
    function chargemanager_sanitize_postal_code($postal_code)
    {
        return preg_replace('/[^0-9]/', '', $postal_code);
    }
}

if (!function_exists('chargemanager_format_postal_code')) {
    /**
     * Format postal code (CEP) for display
     * @param string $postal_code
     * @return string
     */
    function chargemanager_format_postal_code($postal_code)
    {
        $postal_code = chargemanager_sanitize_postal_code($postal_code);
        
        if (strlen($postal_code) === 8) {
            return substr($postal_code, 0, 5) . '-' . substr($postal_code, 5, 3);
        }
        
        return $postal_code;
    }
}

if (!function_exists('chargemanager_calculate_progress')) {
    /**
     * Calculate payment progress percentage
     * @param float $total_value
     * @param float $paid_value
     * @return float
     */
    function chargemanager_calculate_progress($total_value, $paid_value)
    {
        if ($total_value <= 0) {
            return 0;
        }
        
        return min(($paid_value / $total_value) * 100, 100);
    }
}

if (!function_exists('chargemanager_log_activity')) {
    /**
     * Log activity to ChargeManager logs
     * @param string $type
     * @param string $message
     * @param string $status
     * @param array $additional_data
     * @return bool
     */
    function chargemanager_log_activity($type, $message, $status = 'info', $additional_data = [])
    {
        $CI = &get_instance();
        $CI->load->model('chargemanager_model');
        
        $log_data = [
            'sync_type' => $type,
            'message' => $message,
            'status' => $status,
            'additional_data' => json_encode($additional_data),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $CI->chargemanager_model->add_sync_log($log_data);
    }
}

if (!function_exists('chargemanager_is_debug_mode')) {
    /**
     * Check if debug mode is enabled
     * @return bool
     */
    function chargemanager_is_debug_mode()
    {
        $CI = &get_instance();
        $CI->load->model('chargemanager_model');
        
        return $CI->chargemanager_model->get_asaas_setting('debug_mode') === '1';
    }
}

if (!function_exists('chargemanager_debug_log')) {
    /**
     * Log debug information if debug mode is enabled
     * @param string $message
     * @param array $data
     * @return bool
     */
    function chargemanager_debug_log($message, $data = [])
    {
        if (chargemanager_is_debug_mode()) {
            return chargemanager_log_activity('debug', $message, 'info', $data);
        }
        
        return false;
    }
}

if (!function_exists('chargemanager_generate_reference')) {
    /**
     * Generate unique reference for charges
     * @param int $billing_group_id
     * @param string $prefix
     * @return string
     */
    function chargemanager_generate_reference($billing_group_id, $prefix = 'CHARGE')
    {
        return $prefix . '_' . $billing_group_id . '_' . time() . '_' . rand(1000, 9999);
    }
}

if (!function_exists('chargemanager_get_client_data_for_gateway')) {
    /**
     * Get client data formatted for gateway
     * @param int $client_id
     * @return array
     */
    function chargemanager_get_client_data_for_gateway($client_id)
    {
        $CI = &get_instance();
        $CI->load->model('clients_model');
        
        $client = $CI->clients_model->get($client_id);
        
        if (!$client) {
            return [];
        }
        
        return [
            'name' => $client->company,
            'email' => $client->email,
            'cpfCnpj' => chargemanager_sanitize_cpf_cnpj($client->vat ?? ''),
            'phone' => chargemanager_sanitize_phone($client->phonenumber ?? ''),
            'mobilePhone' => chargemanager_sanitize_phone($client->phonenumber ?? ''),
            'address' => $client->address ?? '',
            'addressNumber' => '123', // Default as CRM doesn't separate number
            'complement' => '',
            'province' => $client->state ?? '',
            'city' => $client->city ?? '',
            'postalCode' => chargemanager_sanitize_postal_code($client->zip ?? '')
        ];
    }
} 