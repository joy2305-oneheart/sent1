# Social Invite Network

WordPress plugin (slug: `social-invite-network`) that turns a site into a private, invite-based social network with admin-moderated registration, encrypted invite links, and relationship-based visibility. **No public member directory.**

With the **Sent One** theme (`one-1`), members use the front-end **invite page** and **sharing feed** (`story` posts). Network Hub and Network Posts (`sin_post`) have been removed from this build.

**Requirements:** WordPress 6.4+, PHP 8.1+. No Composer/npm dependencies.

## Installation

1. Copy the `social-invite-network` folder to `wp-content/plugins/`, or zip that folder and upload via **Plugins → Add New → Upload Plugin**.
2. Activate **Social Invite Network**.
3. In **Settings → Social Invite Network**, set a strong secret (or define `SIN_SECRET_KEY` in `wp-config.php`), configure email templates, and set **Registration page ID** (or create a page with slug `register`).
4. Set your public **Homepage** (Reading settings or the plugin’s homepage ID option).
5. Create a **Registration** page with `[sin_register]` if you use the shortcode (the Sent One theme also provides a themed register page).

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[sin_register]` | Registration form (name, email, password, confirm). Hidden `invite_code` field is filled from `?ref=` on the URL. |
| `[sin_invite_form]` | Points approved users to the theme **invite page**. |
| `[sin_feed]` | Points approved users to the theme **sharing feed** (stories). |
| `[sin_dashboard]` | Points approved users to the theme **invite page**. |

## Member tools (Sent One theme)

Approved members use the front-end app:

- **Invite page** — Send invitations by email, copy invite link, accept pending invitations (existing users).
- **Sharing feed** — View and publish **stories** visible to direct connections (`sin_relationships`).
- **Profile** — Followers / following and their stories.

## Invite links

Approved users receive a personal invite code (AES-128-ECB encrypted username, URL-safe). Shareable registration URL:

`https://yoursite.com/register/?ref=ENCRYPTED_CODE`

Replace `/register/` with your registration page’s path if different.

## Admin (site administrators)

- **Users → Pending Approvals** — Approve or reject new registrations.
- **Users → Manage Invitations** — Table of all invitations, re-send email, manual invitation creation.
- **Settings → Social Invite Network** — Email templates, rate limits, page IDs.
- **Dashboard** widget — Summary counts.

## Database

Custom tables (with your `$wpdb->prefix`, e.g. `wp_`):

- `{prefix}sin_invitations`
- `{prefix}sin_relationships`

User meta includes: `sin_invite_code`, `sin_invited_by`, `sin_account_status` (`pending` \| `approved` \| `rejected`), and `sin_invitation_inbox` (queue for existing users).

## Troubleshooting

- **Invitee cannot see inviter’s stories:** The feed only shows authors in your **direct** `sin_relationships` row. That row is created when the invitee is **approved** and we know who invited them. If they registered without `?ref=`, run: `wp eval 'SIN_Registration::sync_relationship_for_user( INVITEE_USER_ID );'`

## Uninstall

Deactivation does **not** drop tables. To remove data, delete the custom tables and user meta manually if you uninstall the plugin permanently.
