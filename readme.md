=== Fluent Forms for BuilderCMS CRM ===
Contributors: undfine
Tags: fluent forms, fluent forms pro, fluent forms pro integration, BuilderCMS
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An integration for Fluent Forms Pro to sync contacts with BuilderCMS CRM

== Description ==

An integration for Fluent Forms that allows automatic syncing of form submissions to BuilderCMS CRM. Supports both the modern BuilderCMS REST API and the legacy ProspectImport endpoint.

= Features =
* **Dual API Support**: Works with both modern REST API and legacy ProspectImport endpoints
* **Field Mapping**: Map form fields to BuilderCMS contact fields
* **Custom Attributes**: Add custom contact attributes
* **Conditional Logic**: Enable/disable integration based on form values
* **Contact Status Management**: Set initial contact status and type
* **Debug Mode**: Test integration without sending data

== Installation ==

1. Install and activate Fluent Forms Pro
2. Upload and activate this plugin
3. Navigate to Fluent Forms → Settings → Integrations → BuilderCMS
4. Configure your BuilderCMS API settings

== Configuration ==

= API Mode Selection =

This plugin supports two BuilderCMS API modes:

**Legacy ProspectImport (Recommended for existing BuilderCMS accounts)**
- Uses: `https://www.buildercms.com/cms/custom/ProspectImport.aspx`
- Method: GET request with URL-encoded data
- Authentication: Community Number only
- Best for: Older BuilderCMS installations, matches Elementor integration

**Modern REST API**
- Uses: `https://api.buildercms.com/Contact/CreateOrUpdate`
- Method: POST request with JSON data
- Authentication: Username, Password, Client ID, Database Name
- Best for: Newer BuilderCMS API accounts

= API Settings =

1. Go to **Fluent Forms → Settings → Integrations → BuilderCMS**
2. Choose your API Mode:
   - **Legacy**: Requires only Community Number
   - **Modern**: Requires Username, Password, Client ID, and Database Name
3. Click "Save Settings"

= Form Integration =

1. Edit your Fluent Form
2. Go to **Settings & Integrations → BuilderCMS**
3. Create a new BuilderCMS feed
4. Map your form fields to BuilderCMS fields:
   - Email Address (required)
   - First Name
   - Last Name
   - Phone
   - Address fields
   - And more...
5. Configure additional settings:
   - **Source Detail**: Form or page name for tracking (Legacy mode)
   - **Admin Email**: Receive import notifications (Legacy mode)
   - **Contact Status**: Initial prospect status
   - **Note**: Add notes to the contact record
6. Enable the feed and save

== Field Mapping ==

= Standard Fields =
* Email Address (required)
* First Name
* Last Name
* Phone
* Address Line 1 & 2
* City, State, Zip, Country

= Legacy Mode Additional Fields =
* Source Detail
* Admin Email
* Custom1 through Custom6
* Comments/Notes

= Modern API Additional Fields =
* Contact Attributes
* Contact Type & Status
* Lead Sources
* Multiple phone numbers
* Multiple email addresses
* Multiple physical addresses

== Changelog ==

= 1.3.0 =
* Added support for legacy ProspectImport endpoint
* Added API mode selection (Modern vs Legacy)
* Improved field mapping compatibility with Elementor integration
* Added Source Detail and Admin Email fields for legacy mode
* Enhanced debugging capabilities
* Updated validation for different API modes

= 1.2.6 =
* Initial BuilderCMS integration (REST API only)

== Upgrade Notice ==

= 1.3.0 =
This version adds support for the legacy BuilderCMS ProspectImport endpoint. If you're migrating from an Elementor BuilderCMS integration, select "Legacy" mode in the settings
