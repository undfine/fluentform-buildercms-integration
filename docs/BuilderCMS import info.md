# BuilderCMS Prospect Import Technical Specifications

This document outlines the procedures for importing prospect data from external websites into the CMS database.

## 1. Implementation Workflow

The following steps are required to move from testing to production:

* **Initial Review**: Review all technical documentation to understand the process.

* **Obtain Test Credentials**: Call CMS Technical Support at 561-214-4780 to receive a test login.


* **Test Integration**: Implement the code using the test Community ID parameter (**215* **Validation**: Contact technical support to validate successful test submissions.


* **Go Live**: Receive the production Community ID and conduct final testing with support.



## 2. Submission Methods

### Method A: Query String (GET Request)

Data is submitted via a concatenated, tilde-delimited string.

* **Base URL**: `https://www.buildercms.com/cms/custom/ProspectImport.aspx` 


* **Parameter**: `ProspectData` 


* **Format**: `Label:Value` pairs separated by tildes (`~`).


* **Encoding Requirement**: The data string must be URL-encoded (PHP: `urlencode()`; ASP.NET: `Server.UrlEncode()`).


* **Example URL Structure**: `https://www.buildercms.com/cms/custom/ProspectImport.aspx?ProspectData=FirstName%3AJohn%7ELastName%3ASmith...` 



### Method B: JSON Web Service (POST Request)

* **Endpoint**: `https://buildercms.com/cms/CmsService.svc/CMSProspectImport` 


* **Method**: HTTP POST 


* **Body Content**: Must include `Username`, `Password`, and prospect fields in the JSON body.



## 3. Data Integrity & Validation Rules

* **Spam Filter**: Submissions where the First and Last name match exactly are rejected as spam.


* **Validation**: Registration pages should validate email addresses, phone numbers, and zip codes before submission.


* **Double Submissions**: Disable the submission button after the first click or redirect to a result page to prevent duplicate entries.

* **Backup**: It is recommended to capture records in CSV format as a local backup.
  
**Response Verification* **Query String**: Check for the string `"ImportSuccessful"` in the HTML response.

* **JSON**: Check for the string `"Successfully Imported"`.



## 4. Field Specification (Selected Fields)

The following table highlights key fields from the CMS Prospect Import Spec:

| Field Name | Required | Notes |
| --- | --- | --- |
| **FirstName** | Yes | Freeform string (30). |
| **LastName** | Yes | Freeform string (50). |
| **CommunityNumber** | Yes | Constant provided by CMS (Test: 215). |
| **FollowupCode** | Yes | Specified constant (A, B, C, D, E, F). |
| Email | No | Freeform string (100). |
| Phone | No | Freeform string (20). |
| Zip | No | 5 digits; use 99999 for international addresses. |
| Comments | No | Freeform string (1000). |
| AdminEmail | No | Semi-colon delimited list for success/failure notifications. |
| Custom1–Custom6 | No | User-definable fields for non-standard data. |

## 5. Error Handling & Support
* **Failure Protocol**: If there is no response or an error, email `support@buildercms.com` with the submitted URL and any available error details.

* **Data Recovery**: At a minimum, the fully encoded submission string and timestamp must be archived locally to guarantee recovery during outages.

* **Contact**: CMS Technical Support: 561-214-4780.

Based on the provided documentation, here is the complete list of available fieldnames for the BuilderCMS prospect import. Please note that field names are not case-sensitive.

### Required Fields

The following fields must be included in every submission:

* **FirstName**: Freeform string (30 characters).
* **LastName**: Freeform string (50 characters).
* **CommunityNumber**: A constant value provided by CMS; use **215** for testing.
* **FollowupCode**: A specified constant (A, B, C, D, E, or F) provided by the customer.


---

### Standard Contact & Demographic Fields

* `Source`: The marketing source (e.g., "Newspaper").

* `SourceDetail`: Specific details about the source (e.g., "New York Times").


* `AutoFollowupPlan`: A specified constant for automated plans.


* `Email`: Freeform string (100 characters).


* `EmailOptOut`: Specified constant ("True" or "False").


* `Phone`: Main contact number (20 characters).


* `WorkPhone`: Work contact number (20 characters).


* `CellPhone`: Mobile contact number (20 characters).


* `BestContact`: Must match: Home, Work, Email, Mobile, Text, Broker, WhatsApp, or Other.


* `StreetAddress`: Primary address (500 characters).


* `City`: Primary city (50 characters).


* `State`: Primary state (2-character code).


* `Zip`: Primary zip/postal code; use 99999 for international.


* `International`: Concatenation of international city, state, and postal code.


* `Country`: ISO Standard two-digit country code.


* `WorkStreetAddress`: Business street address (500 characters).


* `WorkCity`: Business city (50 characters).


* `WorkState`: Business state (2-character code).


* `WorkZip`: Business zip code.


* `Comments`: Freeform notes (1000 characters).


* `Greeting`: Titles such as Mr., Mrs., etc..


* `MaritalStatus`: Must match: Single, Married, Divorced, or Widow.



---

### Preferences & Sales Criteria

* `PurchaseTimeframe`: Options include: Immediately, Less Than 3 Months, 3 to 6 Months, 6 to 12 Months, 1 to 2 Years, or More Than 2 Years.


* `PurchaseType`: Must match: Primary, Secondary, Investment, Other, or Broker.


* `IncomeLevel`: Property-specific constants (e.g., "Less Than $30k", "$150 to $200k").


* `PriceRange`: Property-specific constants (e.g., "< $150K", "$750k- $1m").


* `DesiredBedrooms`: Constants such as Studio, 1 Bedroom, 2 Bedroom, etc..



---

### Custom & Multi-Choice Fields

* **Custom1 through Custom6**: User-definable fields for data not covered by standard fields.


* **MultiChoice1 through MultiChoice6**: Semi-colon delimited strings (e.g., `;Condo; Single family;`) that must start and end with a semi-colon.



---

### System & Tracking Fields

* `SalesID`: Integer used to bypass the lead lottery and assign to a specific salesperson.


* `AdminEmail`: Semi-colon delimited list of emails for success/failure summaries.


* `AlwaysSendAdminEmail`: "True" or "False"; if false, emails only send on failure.


* `IPAddress`: The IP address of the registering prospect.


* `CMSCookielD`: Integer used to link website activity to the prospect record.


* `FirstVisitCode`: Integer (1-5) representing inquiry type (Default is 2 for Internet Inquiry).


* `Referrer`: The URL of the referring site or pay-per-click source.


  **UTM Tracking**: The following fields can be passed within a properly encoded Referrer string:

  * `utm_source`
  * `utm_content`
  * `utm_medium`
  * `utm_campaign`
  * `utm_term`