<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use \FluentForm\App\Services\Integrations\IntegrationManager;
use \FluentForm\Framework\Foundation\Application;
use \FluentForm\Framework\Helpers\ArrayHelper;

class FF_BuilderCMS extends IntegrationManager
{
    public function __construct(Application $app = null)
    {
        parent::__construct(
            $app,
            'BuilderCMS',
            'builder_cms',
            '_fluentform_builder_cms_settings',
            'fluentform_builder_cms_feed',
            16
        );

        $this->logo = plugin_dir_url( __DIR__ ) . '/assets/buildercms_logo.png';
		$this->category = 'crm';
        $this->description = 'Create signup forms in WordPress and connect to BuilderCMS';

        $this->registerAdminHooks();

        // uncomment below to turn off async requests for debugging (useful on local environments or when WP-CHRON is not active)
        // add_filter('fluentform_notifying_async_builder_cms', '__return_false');
    }

    public function getGlobalFields($fields)
    {
        return [
            'logo' => $this->logo,
            'menu_title' => __('BuilderCMS API Settings', 'ff_builder_cms'),
            'menu_description' => __('BuilderCMS is a small business CRM. Use Fluent Forms to collect customer information and automatically add it to your BuilderCMS account. If you don\'t have an BuilderCMS account, you can <a href="https://www.buildercms.com/" target="_blank">sign up for one here.</a>', 'ff_builder_cms'),
            'valid_message' => __('Your BuilderCMS configuration is valid', 'ff_builder_cms'),
            'invalid_message' => __('Your BuilderCMS configuration is invalid', 'ff_builder_cms'),
            'save_button_text' => __('Save Settings', 'ff_builder_cms'),
            'fields' => [
                'apiMode' => [
                    'type' => 'select',
                    'label' => __('API Mode', 'ff_builder_cms'),
                    'label_tips' => __('Select which BuilderCMS API to use. Use "Legacy" for older BuilderCMS accounts with ProspectImport endpoint, or "Modern REST API" for newer accounts.', 'ff_builder_cms'),
                    'options' => [
                        'modern' => __('Modern REST API (POST)', 'ff_builder_cms'),
                        'legacy' => __('Legacy ProspectImport (GET)', 'ff_builder_cms')
                    ]
                ],
                'apiUser' => [
                    'type' => 'text',
                    'placeholder' => 'username',
                    'label_tips' => __("Please provide your BuilderCMS API User name (Modern API only)", 'ff_builder_cms'),
                    'label' => __('BuilderCMS API User Name', 'ff_builder_cms'),
                ],
                'apiKey' => [
                    'type' => 'password',
                    'placeholder' => 'password',
                    'label_tips' => __("Please enter your BuilderCMS API password (Modern API only)", 'ff_builder_cms'),
                    'label' => __('BuilderCMS API Password', 'ff_builder_cms'),
                ],
                'clientID' => [
                    'type' => 'text',
                    'placeholder' => 'Community Number',
                    'label_tips' => __('Enter your BuilderCMS Community Number (CID). Use "215" for testing.', 'ff_builder_cms'),
                    'label' => __('Community Number (CID)', 'ff_builder_cms'),
                ]
            ],
            'hide_on_valid' => true,
            'discard_settings' => [
                'section_description' => 'Your BuilderCMS API is connected',
                'button_text' => 'Disconnect BuilderCMS',
                'data' => [
                    'apiKey' => ''
                ],
                'show_verify' => true
            ]
        ];
    }

    public function getGlobalSettings($settings)
    {
        $globalSettings = get_option($this->optionKey);
        if (!$globalSettings) {
            $globalSettings = [];
        }
        $defaults = [
            'apiMode' => 'modern',
            'apiKey' => '',
            'apiUser' => '',
            'clientID' => '',
            'status' => ''
        ];

        return wp_parse_args($globalSettings, $defaults);
    }

    public function saveGlobalSettings($settings)
    {
        // Set default API mode if not provided
        if (!isset($settings['apiMode'])) {
            $settings['apiMode'] = 'legacy';
        }
        
        // Validate required fields based on API mode
        $isLegacyMode = ($settings['apiMode'] === 'legacy');
        
        // Check required fields
        $missingField = false;
        if ($isLegacyMode) {
            // Legacy mode only requires clientID (Community Number)
            if (empty($settings['clientID'])) {
                $missingField = 'Community Number is required for Legacy mode';
            }
        } else {
            // Modern mode requires all API credentials
            if (empty($settings['apiKey']) || empty($settings['apiUser']) || empty($settings['clientID'])) {
                $missingField = 'API credentials are required for Modern API mode';
            }
        }
        
        // Return error if validation failed
        if ($missingField) {
            $integrationSettings = [
                'apiMode' => $settings['apiMode'],
                'apiKey' => '',
                'apiUser' => '',
                'clientID' => '',
                'status' => false
            ];
            update_option($this->optionKey, $integrationSettings, 'no');
            wp_send_json_error([
                'message' => __($missingField, 'ff_builder_cms'),
                'status' => false
            ], 400);
        }

        try {
            $settings['status'] = false;
            update_option($this->optionKey, $settings, 'no');
            
            // Skip auth test for legacy mode (requires actual form submission to test)
            if ($isLegacyMode) {
                $settings['status'] = true;
                update_option($this->optionKey, $settings, 'no');
                return wp_send_json_success([
                    'status' => true,
                    'message' => __('Legacy mode settings saved! Note: Validation requires a form submission.', 'ff_builder_cms')
                ], 200);
            }
            
            // Test modern API credentials
            $api = $this->getApiClient();
            if ($api->auth_test()) {
                $settings['status'] = true;
                update_option($this->optionKey, $settings, 'no');

                return wp_send_json_success([
                    'status' => true,
                    'message' => __('Your settings has been updated!', 'ff_builder_cms')
                ], 200);
            }
            throw new \Exception('Invalid Credentials', 400);

        } catch (\Exception $e) {
            wp_send_json_error([
                'status' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'title' => $this->title . ' Integration',
            'logo' => $this->logo,
            'is_active' => $this->isConfigured(),
            'configure_title' => 'Configuration required!',
            'global_configure_url' => admin_url('admin.php?page=fluent_forms_settings#general-builder_cms-settings'),
            'configure_message' => 'BuilderCMS is not configured yet! Please configure your BuilderCMS API first',
            'configure_button_text' => 'Set BuilderCMS API'
        ];
        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        return [
            'name' => '',
            'emailAddress' => 'email',
            'firstName' => '',
            'lastName' => '',
            'phone' => '',
            'address1' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => '',
            'fields' => (object)[],
            'contact_fields' => (object)[],
            'extra_fields' => [
                [
                    'item_value' => '',
                    'label' => ''
                ]
            ],
            'note' => '',
            'source_detail' => '',
            'admin_email' => '',
            'referrer' => '',
            'is_broker' => '',
            'conditionals' => [
                'conditions' => [],
                'status' => false,
                'type' => 'all'
            ],
            'enabled' => true,
            'debug' => false,
        ];
        
    }

    public function getSettingsFields($settings, $formId)
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => __('Name','ff_builder_cms'),
                    'required' => true,
                    'placeholder' => __('Your Feed Name','ff_builder_cms'),
                    'component' => 'text'
                ],
                [
                    'key' => 'contact_fields',
                    'require_list' => false,
                    'label' => __('Map Contact Fields', 'ff_builder_cms'),
                    'tips' => __('Select which Fluent Forms fields pair with their respective BuilderCMS fields.', 'ff_builder_cms'),
                    'component' => 'map_fields',
                    'field_label_remote' => 'BuilderCMS Field',
                    'field_label_local' => 'Form Field',
                    'primary_fileds' => [
                        [
                            'key' => 'emailAddress',
                            'label' => 'Email Address',
                            'required' => true,
                            'input_options' => 'emails'
                        ],
                        [
                            'key' => 'firstName',
                            'required' => true,
                            'label' => 'First Name',
                        ],
                        [
                            'key' => 'lastName',
                            'required' => true,
                            'label' => 'Last Name',
                        ],
                        [
                            'key' => 'phone',
                            'label' => 'Phone'
                        ],
                        [
                            'key' => 'address1',
                            'label' => 'Address Line 1'
                        ],
                        [
                            'key' => 'address2',
                            'label' => 'Address Line 2'
                        ],
                        [
                            'key' => 'city',
                            'label' => 'City'
                        ],
                        [
                            'key' => 'state',
                            'label' => 'State'
                        ],
                        [
                            'key' => 'zip',
                            'label' => 'Zip'
                        ],
                        [
                            'key' => 'country',
                            'label' => 'Country'
                        ],
                        [
                            'key' => 'email_opt_out',
                            'label' => 'Email Opt Out'
                        ],
                        [
                            'key' => 'referrer',
                            'label' => 'Referrer'
                        ],
                    ],
                ],
                [
                    'key'       => 'extra_fields',
                    'label'     => __('Additional Contact Fields', 'ff_builder_cms'),
                    'tips'        => __('Extra Contact fields to sync with BuilderCMS. Values should be in an acceptable form (String, Integer or Boolean)', 'ff_builder_cms'),
                    'component' => 'dropdown_many_fields',
                    'field_label_remote' => 'Contact Field',
                    'field_label_local'  => 'Form Field',
                    'options' => [
                        'Greeting' => '',
                        'WorkPhone' => '',
                        'CellPhone' => '',
                        'BestContact' => '',
                        'WorkStreetAddress' => '',
                        'WorkCity' => '',
                        'WorkState' => '',
                        'WorkZip' => '',
                        'MaritalStatus' => '',
                        'PurchaseTimeframe' => '',
                        'IncomeLevel' => '',
                        'PriceRange' => '',
                        'DesiredBedrooms' => '',
                        'Custom1' => '',
                        'Custom2' => '',
                        'Custom3' => '',
                        'Custom4' => '',
                        'Custom5' => '',
                        'Custom6' => '',
                        'MultiChoice1' => '',
                        'MultiChoice2' => '',
                        'MultiChoice3' => '',
                        'MultiChoice4' => '',
                        'MultiChoice5' => '',
                        'MultiChoice6' => '',
                    ]
                ],
                [
                    'key' => 'is_broker',
                    'label' => __('Is Broker', 'ff_builder_cms'),
                    'tips' => __('Set this to true to force the PurchaseType as "Broker". Or map this to a checkbox field with "broker" as one of the values.' , 'ff_builder_cms'),
                    'component' => 'value_text'
                ],
                [
                    'key' => 'note',
                    'label' => __('Note', 'ff_builder_cms'),
                    'tips' => __('You can write a note for this contact or add smart tags to include', 'ff_builder_cms'),
                    'component' => 'value_textarea'
                ],
                [
                    'key' => 'source_detail',
                    'label' => __('Source Detail', 'ff_builder_cms'),
                    'tips' => __('Optional: Details like form name or page name for record-keeping.', 'ff_builder_cms'),
                    'component' => 'value_text'
                ],
                [
                    'key' => 'set_ip_address',
                    'label' => __('Set IP Address', 'ff_builder_cms'),
                    'tips' => __('Sets the IP Address from the submission.', 'ff_builder_cms'),
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Sets the IP Address from the submission'
                ],  
                [
                    'key' => 'admin_email',
                    'label' => __('Admin Email', 'ff_builder_cms'),
                    'tips' => __('Optional: Email address to receive import summary from BuilderCMS.', 'ff_builder_cms'),
                    'component' => 'value_text'
                ],
                [
                    'key' => 'referrer',
                    'label' => __('Referrer URL', 'ff_builder_cms'),
                    'tips' => __('Optional: Use {http_referer} smart tag to capture the referring page URL.', 'ff_builder_cms'),
                    'component' => 'value_text'
                ],
                [
                    'key' => 'conditionals',
                    'label' => __('Conditional Logics', 'ff_builder_cms'),
                    'tips' => __('Allow BuilderCMS integration conditionally based on your submission values', 'ff_builder_cms'),
                    'component' => 'conditional_block'
                ],
                [
                    'key' => 'enabled',
                    'label' => __('Status', 'ff_builder_cms'),
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Enable This feed'
                ],
                [
                    'key' => 'debug',
                    'label' => __('Debug', 'ff_builder_cms'),
                    'tips' => __('Turning on Debug will prevent API submission and return the formatted Feed info', 'ff_builder_cms'),
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Debug this feed'
                ]
            ],
            'integration_title' => $this->title
        ];
    }

    /** REQUIRED Abstract Method
     * Merge Fields Fetcher
     */

    public function getMergeFields($list, $listId, $formId)
    {
        return [];
    }

    /**
     * Submission Broadcast Handler
     * NOTE: some fields require a JSON array to wrap the JSON object
     */

    public function notify($feed, $formData, $entry, $form)
    {
        $feedData = $feed['processedValues'];

        if (!is_email($feedData['emailAddress'])) {
            $feedData['emailAddress'] = ArrayHelper::get($formData, $feedData['emailAddress']);
        }

        if (!is_email($feedData['emailAddress'])) {
            do_action('ff_integration_action_result', $feed, 'failed', 'API call has been skipped because no valid email available');
            return;
        }
        
        // Get API settings to determine mode
        $settings = $this->getGlobalSettings([]);
        $apiMode = isset($settings['apiMode']) ? $settings['apiMode'] : 'modern';
        $isLegacyMode = ($apiMode === 'legacy');
        
        // Build unified flat field structure for BuilderCMS (works for both POST and GET)
        $communityNumber = $settings['clientID'] ?? '';
        $followUpCode = 'E';
        $source = 'Internet';
        $sourceDetail = $feedData['source_detail'] ?? '';
        $adminEmail = $feedData['admin_email'] ?? '';
        
        // Start with required fields
        $contactData = [
            'FirstName' => $feedData['firstName'],
            'LastName' => $feedData['lastName'],
            'Email' => $feedData['emailAddress'],
            'CommunityNumber' => $communityNumber,
            'FollowupCode' => $followUpCode,
            'Source' => $source,
        ];
        
        // Map optional form fields to BuilderCMS fields
        $fieldMapping = [
            'phone' => 'Phone',
            'address1' => 'StreetAddress',
            'city' => 'City',
            'state' => 'State',
            'zip' => 'Zip',
            'country' => 'Country',
            'note' => 'Comments',
        ];
        
        foreach ($fieldMapping as $formField => $cmsField) {
            if (!empty($feedData[$formField])) {
                $contactData[$cmsField] = $feedData[$formField];
            }
        }
        
        // Add additional optional fields
        if (!empty($sourceDetail)) {
            $contactData['SourceDetail'] = $sourceDetail;
        }

        // set Admin Email if provided
        if (!empty($adminEmail)) {
            $contactData['AdminEmail'] = $adminEmail;
            $contactData['AlwaysSendAdminEmail'] = 'True';
        }
        
        // Tracking fields (validated and sanitized)
        // Validate IP address format (IPv4 or IPv6)
        if ( !empty($feedData['set_ip_address']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
            if ($ipAddress !== false) {
                $contactData['IPAddress'] = $ipAddress;
            }
        }

        // Validate and sanitize cookie ID (alphanumeric, dashes, underscores only)
        if (isset($_COOKIE['buildercms'])) {
            $cookieId = sanitize_text_field($_COOKIE['buildercms']);
            // Additional validation: only allow alphanumeric, dashes, and underscores
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $cookieId)) {
                $contactData['CMSCookieID'] = $cookieId;
            }
        }

        // Validate and sanitize referrer URL from mapped field
        if (!empty($feedData['referrer'])) {
            $referrer = esc_url_raw($feedData['referrer']);
            // Additional check: ensure it's a valid URL with http/https scheme
            if (filter_var($referrer, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $referrer)) {
                $contactData['Referrer'] = $referrer;
            }
        }
        
        
        // Add extra fields from the form
        if (!empty($feedData['extra_fields'])) {
            foreach (ArrayHelper::get($feedData, 'extra_fields') as $item) {
                if (!empty($item['item_value']) && !empty($item['label'])) {
                    $contactData[$item['label']] = $item['item_value'];
                }
            }
        }
        
        // Filter out empty values
        $contactData = array_filter($contactData);
        
        // Check required fields for BuilderCMS
        $requiredFields = ['Email', 'FirstName', 'LastName', 'CommunityNumber', 'FollowupCode'];
        foreach ($requiredFields as $field) {
            if (empty($contactData[$field])) {
                do_action('fluentform/integration_action_result', $feed, 'failed', sprintf(__('Missing required field: %s', 'ff_builder_cms'), $field));
                return false;
            }
        }
        
        // If IsBroker field is set, check for 'broker' value or boolean true
        if (!empty($feedData['is_broker'])) {
            $isBrokerValue = $feedData['is_broker'];
            
            // Check if it's a boolean string "true" or "1"
            $isTrue = filter_var($isBrokerValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            // Check if value contains "broker" (case-insensitive) for checkbox options
            $containsBroker = is_string($isBrokerValue) && stripos($isBrokerValue, 'broker') !== false;
            
            if ($isTrue === true || $containsBroker) {
                $contactData['PurchaseType'] = 'Broker';
            }
        }
        
        // Debug mode
        if (!empty($feedData['debug'])) {
            error_log('BuilderCMS ' . ($isLegacyMode ? 'Legacy' : 'Modern') . ' Mode Data: ' . print_r($contactData, true));
        }
        
        // Add filter hooks
        $contactData = apply_filters('fluentform_integration_data_'.$this->integrationKey, $contactData, $feed, $entry);

        // Get the API ready
        $api = $this->getApiClient();
        
        // Send to BuilderCMS using appropriate method
        if ($isLegacyMode) {
            $response = $api->import_prospect($contactData, 'GET');
        } else {
            $response = $api->import_prospect($contactData, 'POST');
        }
        
        // Handle response
        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            do_action('fluentform/integration_action_result', $feed, 'failed', $message);
            return false;
        }
        
        // Success
        $message = $isLegacyMode 
            ? __('Contact sent to BuilderCMS ProspectImport', 'ff_builder_cms')
            : __('Contact sent to BuilderCMS', 'ff_builder_cms');
        
        do_action('fluentform/integration_action_result', $feed, 'success', $message);
        return true;
    }


    protected function getApiClient()
    {
        $settings = get_option($this->optionKey);
        return new BuilderCMS_API($settings);
    }
}
