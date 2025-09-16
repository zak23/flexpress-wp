<?php
/**
 * FlexPress Verotel Integration Class
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include Verotel client
require_once FLEXPRESS_PATH . '/includes/verotel/src/Verotel/FlexPay/Exception.php';
require_once FLEXPRESS_PATH . '/includes/verotel/src/Verotel/FlexPay/Brand/Base.php';
require_once FLEXPRESS_PATH . '/includes/verotel/src/Verotel/FlexPay/Brand/Verotel.php';
require_once FLEXPRESS_PATH . '/includes/verotel/src/Verotel/FlexPay/Brand.php';
require_once FLEXPRESS_PATH . '/includes/verotel/src/Verotel/FlexPay/Client.php';

/**
 * FlexPress Verotel Integration Class
 */
class FlexPress_Verotel {
    /**
     * Verotel settings
     *
     * @var array
     */
    private $settings;

    /**
     * FlexPay client instance
     *
     * @var \Verotel\FlexPay\Client|null
     */
    private $client;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('flexpress_verotel_settings', array());
        
        // Initialize FlexPay client if settings are available
        if (!empty($this->settings['verotel_shop_id']) && 
            !empty($this->settings['verotel_signature_key']) &&
            !empty($this->settings['verotel_merchant_id'])) {
            
            try {
                $brand = \Verotel\FlexPay\Brand::create_from_merchant_id($this->settings['verotel_merchant_id']);
                $this->client = new \Verotel\FlexPay\Client(
                    $this->settings['verotel_shop_id'],
                    $this->settings['verotel_signature_key'],
                    $brand
                );
            } catch (\Exception $e) {
                error_log('Verotel FlexPay Error: ' . $e->getMessage());
                $this->client = null;
            }
        }
    }

    /**
     * Get purchase URL for one-time payments
     *
     * @param float  $price       Price amount.
     * @param string $currency    Currency code.
     * @param string $description Product description.
     * @param array  $args        Additional arguments.
     * @return string Purchase URL.
     */
    public function get_purchase_url($price, $currency, $description, $args = array()) {
        if (!$this->client) {
            return '';
        }

        try {
            $purchase_params = array(
                'priceAmount' => $price,
                'priceCurrency' => $currency,
                'description' => $description,
                'successURL' => home_url('/my-account'),
                'declineURL' => home_url('/'),
            );

            // Merge additional arguments (email, custom fields, URLs, etc.)
            if (!empty($args)) {
                // Override default URLs if provided
                if (isset($args['successURL'])) {
                    $purchase_params['successURL'] = $args['successURL'];
                }
                if (isset($args['returnUrl'])) {
                    $purchase_params['successURL'] = $args['returnUrl']; // Map returnUrl to successURL
                }
                if (isset($args['declineURL'])) {
                    $purchase_params['declineURL'] = $args['declineURL'];
                }
                if (isset($args['cancelUrl'])) {
                    $purchase_params['declineURL'] = $args['cancelUrl']; // Map cancelUrl to declineURL
                }
                if (isset($args['ipnUrl'])) {
                    $purchase_params['ipnUrl'] = $args['ipnUrl'];
                }
                
                // Add user details and custom fields
                if (isset($args['email'])) {
                    $purchase_params['email'] = $args['email'];
                }
                if (isset($args['custom1'])) {
                    $purchase_params['custom1'] = $args['custom1'];
                }
                if (isset($args['custom2'])) {
                    $purchase_params['custom2'] = $args['custom2'];
                }
                if (isset($args['custom3'])) {
                    $purchase_params['custom3'] = $args['custom3'];
                }
                
                // Add reference ID if provided
                if (isset($args['referenceID'])) {
                    $purchase_params['referenceID'] = $args['referenceID'];
                }
                
                // Add product description override
                if (isset($args['productDescription'])) {
                    $purchase_params['description'] = $args['productDescription'];
                }
            }

            return $this->client->get_purchase_URL($purchase_params);
        } catch (\Exception $e) {
            error_log('Verotel FlexPay Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get subscription URL for recurring payments
     *
     * @param float  $price       Price amount.
     * @param string $currency    Currency code.
     * @param string $description Product description.
     * @param array  $args        Additional arguments.
     * @return string Subscription URL.
     */
    public function get_subscription_url($price, $currency, $description, $args = array()) {
        if (!$this->client) {
            return '';
        }

        try {
            $subscription_params = array(
                'subscriptionType' => 'recurring',
                'name' => $description,
                'priceAmount' => $price,
                'priceCurrency' => $currency,
                'period' => 'P30D', // 30 days period
                'successURL' => home_url('/my-account'),
                'declineURL' => home_url('/membership'),
            );

            // Merge additional arguments (email, custom fields, URLs, etc.)
            if (!empty($args)) {
                // Override default URLs if provided
                if (isset($args['successURL'])) {
                    $subscription_params['successURL'] = $args['successURL'];
                }
                if (isset($args['returnUrl'])) {
                    $subscription_params['successURL'] = $args['returnUrl']; // Map returnUrl to successURL
                }
                if (isset($args['declineURL'])) {
                    $subscription_params['declineURL'] = $args['declineURL'];
                }
                if (isset($args['cancelUrl'])) {
                    $subscription_params['declineURL'] = $args['cancelUrl']; // Map cancelUrl to declineURL
                }
                if (isset($args['ipnUrl'])) {
                    $subscription_params['ipnUrl'] = $args['ipnUrl'];
                }
                
                // Add user details and custom fields
                if (isset($args['email'])) {
                    $subscription_params['email'] = $args['email'];
                }
                if (isset($args['custom1'])) {
                    $subscription_params['custom1'] = $args['custom1'];
                }
                if (isset($args['custom2'])) {
                    $subscription_params['custom2'] = $args['custom2'];
                }
                if (isset($args['custom3'])) {
                    $subscription_params['custom3'] = $args['custom3'];
                }
                
                // Add product description override
                if (isset($args['productDescription'])) {
                    $subscription_params['name'] = $args['productDescription'];
                }
                
                // Add period override
                if (isset($args['period'])) {
                    $subscription_params['period'] = $args['period'];
                }
            }

            // Add trial period if specified
            if (isset($args['trial_amount']) && isset($args['trial_period'])) {
                $subscription_params['trialAmount'] = $args['trial_amount'];
                $subscription_params['trialPeriod'] = $args['trial_period'];
            }

            return $this->client->get_subscription_URL($subscription_params);
        } catch (\Exception $e) {
            error_log('Verotel FlexPay Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get upgrade subscription URL for existing subscriptions
     *
     * @param string $preceding_sale_id Existing sale ID to upgrade.
     * @param float  $price             New price amount.
     * @param string $currency          Currency code.
     * @param string $description       Product description.
     * @param array  $args              Additional arguments.
     * @return string Upgrade URL.
     */
    public function get_upgrade_subscription_url($preceding_sale_id, $price, $currency, $description, $args = array()) {
        if (!$this->client || empty($preceding_sale_id)) {
            return '';
        }

        try {
            $upgrade_params = array(
                'precedingSaleID' => $preceding_sale_id,
                'name' => $description,
                'priceAmount' => $price,
                'priceCurrency' => $currency,
                'period' => 'P30D', // Default 30 days
                'upgradeOption' => 'extend', // Add remaining time to new subscription
                'successURL' => home_url('/my-account?upgrade=success'),
                'declineURL' => home_url('/my-account?upgrade=failed'),
            );

            // Merge additional arguments
            if (!empty($args)) {
                // Override default URLs if provided
                if (isset($args['successURL'])) {
                    $upgrade_params['successURL'] = $args['successURL'];
                }
                if (isset($args['declineURL'])) {
                    $upgrade_params['declineURL'] = $args['declineURL'];
                }
                if (isset($args['ipnUrl'])) {
                    $upgrade_params['ipnUrl'] = $args['ipnUrl'];
                }
                
                // Add user details and custom fields
                if (isset($args['email'])) {
                    $upgrade_params['email'] = $args['email'];
                }
                if (isset($args['custom1'])) {
                    $upgrade_params['custom1'] = $args['custom1'];
                }
                if (isset($args['custom2'])) {
                    $upgrade_params['custom2'] = $args['custom2'];
                }
                if (isset($args['custom3'])) {
                    $upgrade_params['custom3'] = $args['custom3'];
                }
                
                // Period and upgrade options
                if (isset($args['period'])) {
                    $upgrade_params['period'] = $args['period'];
                }
                if (isset($args['upgradeOption'])) {
                    $upgrade_params['upgradeOption'] = $args['upgradeOption'];
                }
                
                // Product description override
                if (isset($args['productDescription'])) {
                    $upgrade_params['name'] = $args['productDescription'];
                }
            }

            return $this->client->get_upgrade_subscription_URL($upgrade_params);
        } catch (\Exception $e) {
            error_log('Verotel FlexPay Upgrade Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get cancel subscription URL
     *
     * @param string $sale_id Sale ID.
     * @return string Cancel URL.
     */
    public function get_cancel_subscription_url($sale_id) {
        if (!$this->client || empty($sale_id)) {
            return '';
        }

        try {
            return $this->client->get_cancel_subscription_URL(array(
                'saleID' => $sale_id,
                'returnUrl' => home_url('/my-account?subscription=cancelled')
            ));
        } catch (\Exception $e) {
            error_log('Verotel FlexPay Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Validate signature
     *
     * @param array $data Request data.
     * @return bool True if signature is valid.
     */
    public function validate_signature($data) {
        if (!$this->client) {
            return false;
        }

        try {
            return $this->client->validate_signature($data);
        } catch (\Exception $e) {
            error_log('Verotel FlexPay Error: ' . $e->getMessage());
            return false;
        }
    }
} 