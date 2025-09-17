<?php
/**
 * Payout Display Helper Functions
 * 
 * Functions to format and display payout details in a user-friendly way
 * 
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format payout details for display
 * 
 * @param string $payout_method The payout method
 * @param string $payout_details JSON string of payout details
 * @return string Formatted HTML for display
 */
function flexpress_format_payout_details($payout_method, $payout_details) {
    if (empty($payout_details)) {
        return '<em>' . __('No details provided', 'flexpress') . '</em>';
    }
    
    // Try to decode JSON, fall back to plain text for legacy data
    $details = json_decode($payout_details, true);
    if (!$details) {
        return '<div class="payout-detail-legacy">' . esc_html($payout_details) . '</div>';
    }
    
    $output = '<div class="payout-details-formatted">';
    
    switch ($payout_method) {
        case 'paypal':
            $output .= '<strong>' . __('PayPal Email:', 'flexpress') . '</strong> ' . esc_html($details['paypal_email'] ?? '');
            break;
            
        case 'crypto':
            $crypto_type = $details['crypto_type'] ?? '';
            if ($crypto_type === 'other') {
                $crypto_type = $details['crypto_other'] ?? 'Unknown';
            }
            $output .= '<strong>' . __('Cryptocurrency:', 'flexpress') . '</strong> ' . esc_html(ucfirst($crypto_type)) . '<br>';
            $output .= '<strong>' . __('Wallet Address:', 'flexpress') . '</strong> <code>' . esc_html($details['crypto_address'] ?? '') . '</code>';
            break;
            
        case 'aus_bank_transfer':
            $output .= '<strong>' . __('Bank:', 'flexpress') . '</strong> ' . esc_html($details['aus_bank_name'] ?? '') . '<br>';
            $output .= '<strong>' . __('BSB:', 'flexpress') . '</strong> ' . esc_html($details['aus_bsb'] ?? '') . '<br>';
            $output .= '<strong>' . __('Account:', 'flexpress') . '</strong> ' . esc_html($details['aus_account_number'] ?? '') . '<br>';
            $output .= '<strong>' . __('Account Holder:', 'flexpress') . '</strong> ' . esc_html($details['aus_account_holder'] ?? '');
            break;
            
        case 'yoursafe':
            $output .= '<strong>' . __('Yoursafe IBAN:', 'flexpress') . '</strong> ' . esc_html($details['yoursafe_iban'] ?? '');
            break;
            
        case 'ach':
            $output .= '<strong>' . __('Bank:', 'flexpress') . '</strong> ' . esc_html($details['ach_bank_name'] ?? '') . '<br>';
            $output .= '<strong>' . __('Account:', 'flexpress') . '</strong> ' . esc_html($details['ach_account_number'] ?? '') . '<br>';
            $output .= '<strong>' . __('ABA Routing:', 'flexpress') . '</strong> ' . esc_html($details['ach_aba'] ?? '') . '<br>';
            $output .= '<strong>' . __('Account Holder:', 'flexpress') . '</strong> ' . esc_html($details['ach_account_holder'] ?? '');
            break;
            
        case 'swift':
            $output .= '<strong>' . __('Bank:', 'flexpress') . '</strong> ' . esc_html($details['swift_bank_name'] ?? '') . '<br>';
            $output .= '<strong>' . __('SWIFT/BIC:', 'flexpress') . '</strong> ' . esc_html($details['swift_code'] ?? '') . '<br>';
            $output .= '<strong>' . __('IBAN/Account:', 'flexpress') . '</strong> ' . esc_html($details['swift_iban_account'] ?? '') . '<br>';
            $output .= '<strong>' . __('Account Holder:', 'flexpress') . '</strong> ' . esc_html($details['swift_account_holder'] ?? '') . '<br>';
            $output .= '<strong>' . __('Bank Address:', 'flexpress') . '</strong> ' . esc_html($details['swift_bank_address'] ?? '') . '<br>';
            $output .= '<strong>' . __('Beneficiary Address:', 'flexpress') . '</strong> ' . esc_html($details['swift_beneficiary_address'] ?? '');
            
            if (!empty($details['swift_intermediary_swift']) || !empty($details['swift_intermediary_iban'])) {
                $output .= '<br><em>' . __('Intermediary Details:', 'flexpress') . '</em><br>';
                if (!empty($details['swift_intermediary_swift'])) {
                    $output .= '<strong>' . __('Intermediary SWIFT:', 'flexpress') . '</strong> ' . esc_html($details['swift_intermediary_swift']) . '<br>';
                }
                if (!empty($details['swift_intermediary_iban'])) {
                    $output .= '<strong>' . __('Intermediary IBAN:', 'flexpress') . '</strong> ' . esc_html($details['swift_intermediary_iban']);
                }
            }
            break;
            
        default:
            $output .= '<em>' . __('Unknown payout method', 'flexpress') . '</em>';
            break;
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get payout method display name with fee information
 * 
 * @param string $payout_method The payout method key
 * @return string Display name with fee info
 */
function flexpress_get_payout_method_display($payout_method) {
    $methods = [
        'paypal' => __('PayPal (Free)', 'flexpress'),
        'crypto' => __('Cryptocurrency (Free)', 'flexpress'),
        'aus_bank_transfer' => __('Australian Bank Transfer (Free)', 'flexpress'),
        'yoursafe' => __('Yoursafe (Free)', 'flexpress'),
        'ach' => __('ACH - US Only ($10 USD Fee)', 'flexpress'),
        'swift' => __('Swift International ($30 USD Fee)', 'flexpress')
    ];
    
    return $methods[$payout_method] ?? ucfirst(str_replace('_', ' ', $payout_method));
}

/**
 * Get payout method fee amount
 * 
 * @param string $payout_method The payout method key
 * @return float Fee amount in USD
 */
function flexpress_get_payout_method_fee($payout_method) {
    $fees = [
        'paypal' => 0,
        'crypto' => 0,
        'aus_bank_transfer' => 0,
        'yoursafe' => 0,
        'ach' => 10,
        'swift' => 30
    ];
    
    return $fees[$payout_method] ?? 0;
}

/**
 * Check if payout method has fees
 * 
 * @param string $payout_method The payout method key
 * @return bool True if method has fees
 */
function flexpress_payout_method_has_fee($payout_method) {
    return flexpress_get_payout_method_fee($payout_method) > 0;
}
