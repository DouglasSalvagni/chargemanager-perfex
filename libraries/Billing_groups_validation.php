<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Billing Groups Validation Library
 * Biblioteca de validação de grupos de cobrança
 */
class Billing_groups_validation
{
    private $CI;
    
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('chargemanager_billing_groups_model');
    }
    
    /**
     * Validate billing group creation data (new format)
     * @param array $data
     * @return array
     */
    public function validate_create($data)
    {
        $errors = [];
        
        // Validate required fields
        if (empty($data['client_id']) || !is_numeric($data['client_id'])) {
            $errors[] = _l('chargemanager_client_id_required');
        }
        
        if (empty($data['contract_id']) || !is_numeric($data['contract_id'])) {
            $errors[] = _l('chargemanager_contract_required');
        }
        
        // Validate contract exists and belongs to client
        if (!empty($data['contract_id']) && !empty($data['client_id'])) {
            if (!$this->contract_exists($data['contract_id'])) {
                $errors[] = _l('chargemanager_contract_not_found');
            } elseif (!$this->contract_belongs_to_client($data['contract_id'], $data['client_id'])) {
                $errors[] = _l('chargemanager_contract_not_belongs_client');
            } elseif ($this->contract_in_billing_group($data['contract_id'])) {
                $errors[] = _l('chargemanager_contract_already_in_billing_group');
            }
        }
        
        // Validate charges
        if (empty($data['charges']) || !is_array($data['charges'])) {
            $errors[] = _l('chargemanager_charges_required');
        } else {
            $total_amount = 0;
            foreach ($data['charges'] as $index => $charge) {
                $charge_num = $index + 1;
                
                // Validate amount
                if (empty($charge['amount']) || !is_numeric($charge['amount']) || $charge['amount'] <= 0) {
                    $errors[] = sprintf(_l('chargemanager_charge_amount_invalid'), $charge_num);
                } else {
                    $total_amount += (float)$charge['amount'];
                }
                
                // Validate due date
                if (empty($charge['due_date']) || !$this->validate_date($charge['due_date'])) {
                    $errors[] = sprintf(_l('chargemanager_charge_due_date_invalid'), $charge_num);
                }
                
                // Validate billing type
                if (empty($charge['billing_type']) || !in_array($charge['billing_type'], ['BOLETO', 'CREDIT_CARD', 'PIX'])) {
                    $errors[] = sprintf(_l('chargemanager_charge_billing_type_invalid'), $charge_num);
                }
            }
            
            // Validate total amount against contract value (if we can get it)
            if (!empty($data['contract_id']) && $this->contract_exists($data['contract_id'])) {
                $contract_value = $this->get_contract_value($data['contract_id']);
                if ($contract_value && abs($total_amount - $contract_value) >= 0.01) {
                    $errors[] = _l('chargemanager_total_amount_mismatch');
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate billing group creation data (old format)
     * @param array $data
     * @return array
     */
    public function validate_billing_group($data)
    {
        $errors = [];
        
        // Validate required fields
        if (empty($data['billing_group_name'])) {
            $errors[] = 'Nome do grupo de cobrança é obrigatório';
        }
        
        if (empty($data['client_id'])) {
            $errors[] = 'Cliente é obrigatório';
        }
        
        if (empty($data['contract_ids']) || !is_array($data['contract_ids'])) {
            $errors[] = 'Pelo menos um contrato deve ser selecionado';
        }
        
        if (empty($data['total_value']) || !is_numeric($data['total_value']) || $data['total_value'] <= 0) {
            $errors[] = 'Valor total deve ser maior que zero';
        }
        
        if (empty($data['due_date']) || !$this->validate_date($data['due_date'])) {
            $errors[] = 'Data de vencimento inválida';
        }
        
        if (empty($data['billing_type']) || !in_array($data['billing_type'], ['BOLETO', 'CREDIT_CARD', 'PIX'])) {
            $errors[] = 'Tipo de cobrança inválido';
        }
        
        // Validate contract selection
        if (!empty($data['contract_ids']) && is_array($data['contract_ids'])) {
            $contract_validation = $this->validate_contracts($data['client_id'], $data['contract_ids']);
            if (!$contract_validation['success']) {
                $errors = array_merge($errors, $contract_validation['errors']);
            }
        }
        
        // Validate description length
        if (!empty($data['description']) && strlen($data['description']) > 500) {
            $errors[] = 'Descrição não pode ter mais de 500 caracteres';
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate contract selection
     * @param int $client_id
     * @param array $contract_ids
     * @return array
     */
    public function validate_contracts($client_id, $contract_ids)
    {
        $errors = [];
        
        if (empty($contract_ids) || !is_array($contract_ids)) {
            return [
                'success' => false,
                'errors' => ['Nenhum contrato selecionado']
            ];
        }
        
        // Check if contracts exist and belong to client
        foreach ($contract_ids as $contract_id) {
            if (!is_numeric($contract_id)) {
                $errors[] = "ID de contrato inválido: {$contract_id}";
                continue;
            }
            
            if (!$this->contract_exists($contract_id)) {
                $errors[] = "Contrato não encontrado: {$contract_id}";
                continue;
            }
            
            if (!$this->contract_belongs_to_client($contract_id, $client_id)) {
                $errors[] = "Contrato {$contract_id} não pertence ao cliente selecionado";
                continue;
            }
            
            if ($this->contract_in_billing_group($contract_id)) {
                $errors[] = "Contrato {$contract_id} já está em outro grupo de cobrança ativo";
                continue;
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate billing group update data
     * @param int $billing_group_id
     * @param array $data
     * @return array
     */
    public function validate_billing_group_update($billing_group_id, $data)
    {
        $errors = [];
        
        // Check if billing group exists
        if (!$this->billing_group_exists($billing_group_id)) {
            $errors[] = 'Grupo de cobrança não encontrado';
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Check if billing group can be updated
        $billing_group = $this->CI->chargemanager_billing_groups_model->get($billing_group_id);
        if ($billing_group && in_array($billing_group->status, ['paid', 'cancelled'])) {
            $errors[] = 'Grupo de cobrança não pode ser alterado no status atual';
        }
        
        // Validate fields if provided
        if (isset($data['billing_group_name']) && empty($data['billing_group_name'])) {
            $errors[] = 'Nome do grupo de cobrança é obrigatório';
        }
        
        if (isset($data['total_value']) && (!is_numeric($data['total_value']) || $data['total_value'] <= 0)) {
            $errors[] = 'Valor total deve ser maior que zero';
        }
        
        if (isset($data['due_date']) && !$this->validate_date($data['due_date'])) {
            $errors[] = 'Data de vencimento inválida';
        }
        
        if (isset($data['billing_type']) && !in_array($data['billing_type'], ['BOLETO', 'CREDIT_CARD', 'PIX'])) {
            $errors[] = 'Tipo de cobrança inválido';
        }
        
        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors[] = 'Descrição não pode ter mais de 500 caracteres';
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate if billing group can be cancelled
     * @param int $billing_group_id
     * @return array
     */
    public function validate_billing_group_cancellation($billing_group_id)
    {
        $errors = [];
        
        if (!$this->billing_group_exists($billing_group_id)) {
            $errors[] = 'Grupo de cobrança não encontrado';
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        $billing_group = $this->CI->chargemanager_billing_groups_model->get($billing_group_id);
        
        if (!$billing_group) {
            $errors[] = 'Grupo de cobrança não encontrado';
        } elseif (in_array($billing_group->status, ['paid', 'cancelled'])) {
            $errors[] = 'Grupo de cobrança não pode ser cancelado no status atual';
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate charge creation data
     * @param array $data
     * @return array
     */
    public function validate_charge_creation($data)
    {
        $errors = [];
        
        if (empty($data['billing_group_id']) || !is_numeric($data['billing_group_id'])) {
            $errors[] = 'ID do grupo de cobrança inválido';
        }
        
        if (empty($data['customer_id'])) {
            $errors[] = 'ID do cliente no gateway é obrigatório';
        }
        
        if (empty($data['billing_type']) || !in_array($data['billing_type'], ['BOLETO', 'CREDIT_CARD', 'PIX'])) {
            $errors[] = 'Tipo de cobrança inválido';
        }
        
        if (empty($data['value']) || !is_numeric($data['value']) || $data['value'] <= 0) {
            $errors[] = 'Valor da cobrança deve ser maior que zero';
        }
        
        if (empty($data['due_date']) || !$this->validate_date($data['due_date'])) {
            $errors[] = 'Data de vencimento inválida';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Descrição da cobrança é obrigatória';
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
        /**
     * Check if contract exists
     * @param int $contract_id
     * @return bool
     */
    private function contract_exists($contract_id)
    {
        $this->CI->db->where('id', $contract_id);
        return $this->CI->db->get(db_prefix() . 'contracts')->num_rows() > 0;
    }

    /**
     * Check if contract belongs to client
     * @param int $contract_id
     * @param int $client_id
     * @return bool
     */
    private function contract_belongs_to_client($contract_id, $client_id)
    {
        $this->CI->db->where('id', $contract_id);
        $this->CI->db->where('client', $client_id);
        return $this->CI->db->get(db_prefix() . 'contracts')->num_rows() > 0;
    }
    
    /**
     * Check if contract is already in active billing group
     * @param int $contract_id
     * @return bool
     */
    private function contract_in_billing_group($contract_id)
    {
        $this->CI->db->where('contract_id', $contract_id);
        $this->CI->db->where_in('status', ['open', 'processing']);
        return $this->CI->db->get(db_prefix() . 'chargemanager_billing_groups')->num_rows() > 0;
    }

    /**
     * Get contract value
     * @param int $contract_id
     * @return float|null
     */
    private function get_contract_value($contract_id)
    {
        $this->CI->db->select('contract_value');
        $this->CI->db->where('id', $contract_id);
        $result = $this->CI->db->get(db_prefix() . 'contracts')->row();
        
        return $result ? (float)$result->contract_value : null;
    }
    
    /**
     * Check if billing group exists
     * @param int $billing_group_id
     * @return bool
     */
    private function billing_group_exists($billing_group_id)
    {
        $this->CI->db->where('id', $billing_group_id);
        return $this->CI->db->get('chargemanager_billing_groups')->num_rows() > 0;
    }
    
    /**
     * Validate date format
     * @param string $date
     * @return bool
     */
    private function validate_date($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Sanitize input data
     * @param array $data
     * @return array
     */
    public function sanitize_input($data)
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = array_map('trim', $value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Format error messages for display
     * @param array $errors
     * @return string
     */
    public function format_errors($errors)
    {
        if (empty($errors)) {
            return '';
        }
        
        return '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
    }
} 