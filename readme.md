=== Fluent Forms for BuilderCMS CRM ===
Contributors: undfine
Tags: fluent forms, fluent forms pro, fluent forms pro integration, BuilderCMS
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4.0
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
* **Inactive Condition**: Send a contact as Active = False based on a form value (e.g. "not right now")
* **Is Broker Conditions**: Set PurchaseType = Broker from a form value
* **Tracking**: Captures the BuilderCMS tracking cookie, submission IP address and referrer (with UTMs) for BuilderCMS source attribution
* **Debug Mode**: Test integration without sending data; the exact request is written to the entry's Submission Logs

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
- Best for: Older BuilderCMS installations not requiring authentication

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
   - **Email Opt Out**: Map a field to send `EmailOptOut`. Values like `no`, `0` or `false` opt in; any other non-empty value (e.g. a checked checkbox) opts out
   - **Set IP Address**: Sends the IP address recorded with the submission as `IPAddress`
   - **Referrer URL**: URL sent as `Referrer`; BuilderCMS reads UTMs from it (e.g. a UTMost Attribution smartcode such as `{utmost_last_attribution}`). If empty, the page the form was submitted from is sent
   - **Inactive condition**: Contacts are Active by default. Add conditions to send `Active = False` (see below)
   - **Is Broker**: Set `PurchaseType = Broker` (see below)
6. Enable the feed and save

= Condition Settings (Inactive condition, Is Broker) =

These two settings take one condition per line. If any line matches, the setting applies.

    inputs.best_contact = none
    inputs.contact_ok is empty

* Format: `inputs.field_name operator value`
* Operators: `=`, `!=`, `is empty`, `not empty` (case-insensitive; `==` and `is not empty` also work)
* `{inputs.field_name}` and `names[first_name]` style field names are accepted. Checkbox groups match if any selected value equals the value.
* A missing or unchecked field counts as empty.
* Lines that aren't valid conditions are ignored and listed in the entry's Submission Logs.
* **Inactive condition**: when a line matches, `Active=False` is sent. Otherwise `Active` is not sent (BuilderCMS defaults to active). `Active` can also be sent directly through Additional Contact Fields (`true`/`false`).
* **Is Broker**: when a line matches, `PurchaseType=Broker` is sent. Existing values still work: `true`, or a mapped field whose value contains "broker".

== Field Mapping ==

= Standard Fields =
* Email Address (required)
* First Name
* Last Name
* Phone
* Address Line 1 & 2
* City, State, Zip, Country

Address Line 2 is appended to the single BuilderCMS `StreetAddress` field.

= Tracking =
* **CMSCookieID**: The visitor's `buildercms` cookie is stored with the submission automatically (only on forms with a BuilderCMS feed; no hidden field needed) and sent as `CMSCookieID`, so it still works when feeds run asynchronously.
* **IPAddress** and **Referrer** come from the stored entry for the same reason.

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

= 1.4.0 =
* Added "Inactive condition" setting: sends Active = False when a form value matches (e.g. `inputs.best_contact = none`)
* "Is Broker" now accepts condition lines such as `inputs.interests = broker` (existing true / "broker" values still work)
* Added "Active" to the Additional Contact Fields options
* Unrecognized condition lines are noted in the entry's Submission Logs

= 1.3.1 =
* Captures the BuilderCMS cookie ID (`CMSCookieID`) at submission and sends it with the contact
* IP address and referrer are now read from the saved entry, so they are correct when feeds run asynchronously
* Referrer falls back to the page the form was submitted from; supports UTMost Attribution smartcodes
* Added Email Opt Out mapping (`EmailOptOut`) and Address Line 2 (appended to `StreetAddress`)
* Debug mode now writes the exact request to the entry's Submission Logs and doesn't send it
* Added support for legacy ProspectImport endpoint
* Added API mode selection (Modern vs Legacy)
* Improved field mapping compatibility with Elementor integration
* Added Source Detail and Admin Email fields for legacy mode
* Enhanced debugging capabilities
* Updated validation for different API modes

= 1.2.6 =
* Initial BuilderCMS integration (REST API only)

== Upgrade Notice ==

= 1.4.0 =
Adds Inactive condition and Is Broker condition settings. Existing feeds keep working unchanged.

= 1.3.1 =
This version adds support for the legacy BuilderCMS ProspectImport endpoint. If you're migrating from an Elementor BuilderCMS integration, select "Legacy" mode in the settings
