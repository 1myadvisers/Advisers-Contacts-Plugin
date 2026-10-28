=== Advisers Contacts ===
Contributors: 1myadvisers
Tags: contact form, lead generation, form builder, drag and drop, leads
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lead generation contact form builder: drag-and-drop fields, lead scoring, UTM tracking, email alerts, CSV export and WhatsApp links.

== Description ==

Advisers Contacts is a multipurpose contact and lead generation form for any business. Choose the ready-made Lead Generation layout for a digital marketing enquiry form, or build your own from 30 built-in fields. Every field can be reordered, relabeled, resized, switched off, deleted and restored, so the form fits your business instead of the other way round.

**Professional form design**

* Modern card layout, two fields per row on desktop and one on mobile.
* Four color themes: Ocean Blue, Emerald Green, Royal Violet and Slate Dark.
* Editable form name, form description and thank-you message.
* Override the heading, intro and theme per page: `[advisers_contact_form theme="emerald" title="Get a free quote"]`.

**Form builder**

* Drag-and-drop field ordering, half or full width per field.
* Edit labels, placeholders, help text and dropdown or radio choices without code.
* 30 ready-made fields including Full Name, Email, Mobile Number, Company Name, Job Title, Industry, Company Size, Service Interested In, Monthly Budget, Start Timeline, Preferred Contact Method, Best Time to Contact, How Did You Hear About Us, Referred By, WhatsApp opt-in, Consent checkbox, City, Country, Subject, Website URL, Upload Files and more.
* Field types: text, textarea, email, phone, URL, date, dropdown, radio buttons, checkbox and file upload.
* One-click Lead Generation layout, and add unlimited custom fields.
* Guests can submit the form; no account is needed.

**Lead generation tools**

* Automatic campaign tracking: UTM source, medium, campaign, term and content, Google and Facebook click IDs, landing page and referring site are saved with every lead.
* Lead score from 0 to 100 with Hot, Warm and Cold ratings, based on contact details, company, budget, timeline and message.
* Lead pipeline stages: New, Contacted, Qualified, Won and Lost.
* Email alert to you for every new lead, plus an optional auto-reply to the visitor.
* Export the filtered list to CSV for Excel, Google Sheets or your CRM.

**Submissions dashboard**

* Search, filters for stage, lead score, approval status, referrer and date, sortable columns and pagination.
* Detailed view with inline editing.
* Approve, reject, delete, change stage and mark important, one at a time or in bulk.
* Mobile numbers open WhatsApp chat links and email addresses open your mail app, straight from the dashboard.

**Referral tracking**

* See every referrer, how many referrals each has sent, and the approved / rejected / pending history of each referral.
* Mark each referral as rewarded once you have paid the referrer, and see rewarded and not-yet-rewarded totals.
* Keep payout details (UPI, bank or PayPal) for each referrer, visible to administrators only.

**Contact page**

* A "Contact Us" page carrying the form is created on first use, unless your site already has one: an existing page with the form is reused and a page already at /contact/ is never touched. Edit the title, slug and text from the plugin, or choose any existing page to carry the form.

**Built the WordPress way**

* Dedicated database tables, not post meta.
* Uploaded files are stored in the Media Library.
* Full WordPress Multisite support (per-site or Network Activation).
* Privacy tools: suggested privacy policy text, plus support for the WordPress personal data exporter and eraser.
* Developer hooks: see the Developer Hooks section below.

= Shortcode =

`[advisers_contact_form]`

= Developer Hooks =

`advc_after_submission( $id, $data )` (action) fires right after a submission is stored. `$id` is the new submission ID and `$data` the stored row values. Use it for email notifications or CRM sync.

`advc_capability( $capability )` (filter) changes the capability required to manage the plugin. Default `manage_options`.

`advc_max_upload_bytes( $bytes )` (filter) changes the maximum size of each uploaded file. Default 5 MB.

`advc_rate_limit( $count )` (filter) changes how many submissions one visitor can send per hour. Default 5.

`advc_lead_score( $score, $core, $extra )` (filter) changes the 0-100 lead score calculated for a new submission.

The lead score uses the field keys `budget_range` and `timeline`: put the budget choices in ascending order and the timeline choices from most to least urgent.

Data is stored per site in the `{prefix}advc_submissions` and `{prefix}advc_fields` tables, removed when the plugin is deleted.

== External services ==

This plugin does not send any data to external services on its own and does not load remote scripts, fonts or tracking code.

The submissions dashboard shows each mobile number as a link in the form `https://wa.me/<number>`. Nothing is sent unless a site administrator clicks the link, at which point the browser opens WhatsApp (a service provided by WhatsApp LLC / Meta Platforms, Inc.) with that number. Terms of service: https://www.whatsapp.com/legal/terms-of-service. Privacy policy: https://www.whatsapp.com/legal/privacy-policy.

Email addresses are shown as standard `mailto:` links that open the administrator's own mail application.

The optional "Share this form" buttons below the form are plain links. Nothing is sent when the page loads. Only when a visitor clicks a button does their browser open that service in a new tab with the page address (and the form title) pre-filled. Services: WhatsApp (WhatsApp LLC / Meta Platforms, Inc.; terms and privacy links above), Facebook (Meta Platforms, Inc.), LinkedIn (LinkedIn Corporation; privacy policy: https://www.linkedin.com/legal/privacy-policy) and X (X Corp.; privacy policy: https://x.com/en/privacy). The buttons can be switched off under Settings.

The "Share this lead" buttons on a single submission screen work the same way for administrators: WhatsApp opens with the lead's details pre-filled only after the administrator clicks the button, and the email button opens the administrator's own mail application.

== Privacy ==

When a visitor submits the form, the plugin stores in your site database the values entered in the enabled fields, any uploaded files (in the Media Library), the visitor's IP address, the browser user agent and marketing source details (UTM campaign parameters, ad click IDs, the landing page address and the referring site). The IP address is also used for a simple per-hour rate limit that helps prevent spam. A short-lived cookie and transient are used only to show the visitor a confirmation or error message after submitting.

If you use the referral reward tools, the payout details you type for each referrer are stored in your database and are never shown on the website. Delete a referrer's details by clearing the box.

Advisers Contacts is developed by My Advisers (https://myadvisers.in). Privacy policy: https://myadvisers.in/privacy-policy. Terms and conditions: https://myadvisers.in/terms-conditions.

The plugin adds suggested text to Settings -> Privacy -> Policy Guide, and registers with Tools -> Export Personal Data and Tools -> Erase Personal Data so that you can respond to access and erasure requests by email address.

== Installation ==

1. Upload the `advisers-contacts` folder to `/wp-content/plugins/`, or install the plugin through the **Plugins -> Add New** screen.
2. Activate the plugin through the **Plugins** screen. On a multisite network you can Network Activate it from Network Admin -> Plugins.
3. A "Contact Us" page is created on activation unless a page already uses the /contact/ address. Open **Advisers Contacts -> Contact Page** to edit its title, slug and content.
4. Open **Advisers Contacts -> Field Builder** to arrange the form fields.
5. New messages appear under **Advisers Contacts -> Submissions**.

== Frequently Asked Questions ==

= Do visitors need an account to submit the form? =

No. The form accepts submissions from logged-out visitors as well as logged-in users.

= How does the lead score work? =

Each new lead gets 0 to 100 points for a valid email, a phone number, a company name, a chosen service, a message of 50+ characters, the budget choice and how soon they want to start. 70 and above is Hot, 40 to 69 is Warm and below 40 is Cold. Developers can adjust it with the `advc_lead_score` filter.

= How do I set up the lead generation form? =

Open Advisers Contacts -> Field Builder and click Apply Lead Generation Layout. Then use Settings to change the heading, theme and email notifications.

= Can I remove the default fields? =

Yes. Every field, including Full Name, Mobile Number, Email ID and Submit, can be disabled, reordered, relabeled or deleted from the Field Builder. Deleted default fields can be added back from the "Restore a Default Field" section. If the Submit field is deleted, the form still shows a working submit button.

= Where do uploaded files go? =

Into the standard WordPress Media Library, like any other upload. Accepted types are images, PDF, Word, Excel and ZIP files up to 5 MB each; developers can change the limit with the `advc_max_upload_bytes` filter.

= Who can manage submissions? =

Users with the `manage_options` capability (Administrators). Developers can change this with the `advc_capability` filter.

= How do I export or delete one person's data? =

Use Tools -> Export Personal Data or Erase Personal Data and enter the email address the person used in the form.

= What happens to my data if I deactivate the plugin? =

Nothing. Deactivating keeps all tables and settings. Data is removed only when you delete the plugin from the Plugins screen. The Contact Us page is never deleted.

= Does it work on WordPress Multisite? =

Yes. Activate it per site or Network Activate it. Network Activation provisions every existing site and any site created afterwards. Submissions, fields and the Contact Us page are stored separately for each site.

== Changelog ==

= 1.4.8 =
* Fixed the "Mark Referrer Rewarded" and "Unmark Rewarded" bulk actions, which did nothing.
* A visitor's validation mistakes no longer count towards the hourly submission limit; the limit can be changed with the new `advc_rate_limit` filter.
* The form's styles and script now also load when the shortcode is used outside the main page content.
* The Contact Us page is no longer re-created behind your back after you delete it; use the Contact Page screen to create it again.
* Submitted values are no longer unslashed twice, and nested array input is ignored safely.
* Default country code accepts digits only; an empty notification address falls back to the site admin email.
* Flash-message cookie is now HttpOnly, SameSite and Secure on HTTPS.
* Hidden submission screen registered with an empty parent slug (PHP 8.1 compatible).
* Plugin header license text now matches the readme.

= 1.4.7 =
* Readme: tidied the External services section and added My Advisers privacy policy and terms links.

= 1.4.6 =
* Referrer detail screen now shows correct Rewarded / Not Yet Rewarded totals for any number of referrals and paginates the list.
* Removed an unused core include from the upload handler.
* Input handling tidy-up and clearer code comments.

= 1.4.5 =
* Renamed the main plugin class to use the plugin prefix (Plugin Check naming warning).

= 1.4.4 =
* Fixed shared lead details running together; each field is now on its own line.

= 1.4.3 =
* Added a "Share this lead" bar on the submission detail screen (copy details, WhatsApp, email).
* Removed "Tested up to" from the plugin header; it now lives only in this readme.

= 1.4.2 =
* Removed the pre-filled service names. Add your own services under Settings -> Services; the Service dropdown stays hidden until you add at least one. Dropdown and radio fields with no choices are never shown on the form.

= 1.4.1 =
* The plugin no longer creates a second contact page: an existing page that already contains the form is reused, and nothing is created if a page already uses the /contact/ address.
* The Contact Page screen now edits the page the site actually uses, shows its real address, lets you change the slug, pick any existing page for the form, or create a new one on request.

= 1.4.0 =
* Referrers screen now shows total referrals, rewarded and not-yet-rewarded counts for every referrer.
* Mark each referred lead as rewarded (one at a time or in bulk), with a Reward column and filter on the submissions list.
* Payout Details box for each referrer, saved by the site owner and visible only to administrators.
* CSV export now includes the rewarded status and the referrer's payout details.

= 1.3.1 =
* Added "Share this form" buttons (WhatsApp, Email, Facebook, LinkedIn, X and Copy link) below the form, with a Settings switch and a `share="no"` shortcode option.
* Stronger form border for a more professional look.

= 1.3.0 =
* New professional card design with two-column layout, mobile support and four color themes.
* Turned the form into a multipurpose lead generation form: 15 new fields (company, job title, industry, company size, service, budget, timeline, contact method and time, how you heard about us, city, country, WhatsApp opt-in and consent) and one-click Lead Generation layout.
* New checkbox and radio field types, half/full field width, placeholder and help text, and editable dropdown choices in the Field Builder.
* Automatic UTM and marketing source capture.
* Lead score (Hot, Warm, Cold) and pipeline stages with filters and bulk actions.
* CSV export of filtered submissions.
* Email alert for new leads and optional visitor auto-reply.
* New Settings: form name, form description, theme and thank-you message.
* Fixed the submissions Filter button, which did not apply filters, and the field Delete button, which could submit the wrong form.
* Deleted default fields are no longer re-added when the plugin is reactivated.

= 1.2.1 =
* Fixed all Plugin Check errors and warnings: database queries now use prepared statements with `%i` identifiers and object caching, form output is escaped, nonce checks are verified inline in every handler, and templates no longer create global variables.
* Every file-type field on the form now accepts uploads, not only the default Upload Files field.
* Removed the developer markdown file from the plugin package; hooks are documented in this readme.

= 1.2.0 =
* Renamed all internal prefixes to `advc_` / `ADVC_` to meet WordPress.org naming requirements. Database tables and options now use the `advc_` prefix; data stored by earlier pre-release builds (1.0.x and 1.1.x) is not migrated.
* Added suggested privacy policy text and WordPress personal data exporter and eraser support.
* Fixed the file upload handler so it no longer removes other plugins' `upload_mimes` filters.
* Removed the manual translation loader; translations are loaded by WordPress.org automatically.
* Improved readme with external services and privacy disclosures.

= 1.1.0 =
* Added WordPress Multisite support.
* Every field, including the defaults, can now be deleted and restored.
* Added the capability filter.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.8 =
Fixes the rewarded bulk actions and several security and compatibility details.

= 1.4.7 =
Documentation update only.

= 1.4.6 =
Fixes referrer reward totals for referrers with many leads.

= 1.4.5 =
Internal naming clean-up; no visible changes.

= 1.4.4 =
Shared lead details now show one field per line.

= 1.4.3 =
Adds sharing of a lead's details from the submission screen.

= 1.4.2 =
The default service names are removed. Add your own under Settings -> Services.

= 1.4.1 =
Contact page handling is safer: no duplicate pages, and you can choose which page carries the form.

= 1.4.0 =
Adds referral reward tracking and referrer payout details. A new database table is created automatically.

= 1.3.1 =
Adds share buttons and a stronger form border.

= 1.3.0 =
New lead generation features and a redesigned form. Your existing fields and submissions are kept; the new fields appear under Available Fields in the Field Builder.

= 1.2.1 =
Compliance and security hardening release. No data changes.

= 1.2.0 =
Internal prefixes were renamed. Data saved by earlier pre-release builds is not carried over, so export anything you need before installing.
