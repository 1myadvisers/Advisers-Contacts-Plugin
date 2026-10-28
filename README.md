# Advisers Contacts

A WordPress contact form plugin with a drag-and-drop field builder, a submissions dashboard, referrer tracking and WhatsApp/email quick-links. Developed by [My Advisers](https://myadvisers.in).

- **Plugin page:** https://myadvisers.in/advisers-contacts/
- **WordPress.org:** https://wordpress.org/plugins/advisers-contacts

## Features

- Drag-and-drop form builder with custom fields and layout widths
- Submissions dashboard with search, filters, bulk actions and CSV export
- Lead scoring and UTM / source capture on every submission
- Referrer tracking with reward and payout status
- One-click WhatsApp and email quick-links, plus a "Share this lead" bar
- Auto-generated Contact Us page and the `[advisers_contact_form]` shortcode
- Spam protection (honeypot and rate limiting) and optional file uploads
- WordPress privacy tools (personal data export and erase)
- Multisite compatible, with per-site data

## Requirements

- WordPress 6.4 or higher
- PHP 7.4 or higher

## Installation

1. Install from **Plugins > Add New** by searching for "Advisers Contacts", or upload the `advisers-contacts` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open **Advisers Contacts** in the admin menu to build your form.
4. Add the form to any page with the shortcode `[advisers_contact_form]`.

## Hooks

| Hook | Type | Purpose |
|------|------|---------|
| `advc_capability` | filter | Change the capability required to manage the plugin |
| `advc_rate_limit` | filter | Change the submission rate limit |

More hooks are documented in `readme.txt`.

## External services

The plugin does not load any third-party scripts. WhatsApp, email, LinkedIn, X and Facebook share links only open when someone clicks them. See `readme.txt` for details.

## Privacy and terms

- Privacy Policy: https://myadvisers.in/privacy-policy
- Terms and Conditions: https://myadvisers.in/terms-conditions

## License

GPL v2 or later. See [LICENSE](LICENSE).
