# Design & Code Audit — One-1 Theme

**Date:** June 18, 2026  
**Scope:** `wp-content/themes/one-1`, `social-invite-network`, `sent-one-social-login`

---

## Fixed in this pass

| ID | Area | Issue | Resolution |
|----|------|-------|------------|
| F-01 | Post modal | Heavy boxed card + insights sidebar | Flat modal layout, borderless content, subtle divider (IG/FB style) |
| F-02 | Profile | ⋮ below divider with bordered trigger | Moved to top-right, no divider, borderless trigger |
| F-03 | Invite page | Friends card narrower than email/link cards | Full-width grid column (`one-invite-card--full`) |
| F-04 | Invite page | No resend/remove on sent invitations | ⋮ menu + AJAX resend/remove |
| F-05 | Sidebar nav | Active + hover backgrounds overlapping | Flex gap, isolation, removed active box-shadow |
| F-06 | Profile edit | Cancel button, manual avatar save | Cancel removed; avatar auto-saves on upload |
| F-07 | Insights | Owner views counted | Owner excluded from record + count |
| F-08 | Public share | Instant copy, fixed 24h | Modal with duration picker (1h–7d) |
| F-09 | Delete/archive | Native `confirm()` only | Shared `OneConfirm` modal |
| F-10 | Insights UX | “Supported” unclear | Hint text added |

---

## ASAP — remaining design issues

### High priority

1. **Mobile post modal layout** — Insights stack below post on small screens; test ≤767px for scroll traps and long comment threads.
2. **Duplicate friends-manage CSS** — Rules exist in both `one-invite.css` and `one-public-story.css`; consolidate to avoid drift.
3. **Success feedback still uses `alert()`** — Archive/delete/notify success should use toast or inline status (consistent with confirm modals).
4. **Invite sent table on mobile** — Card-style responsive table may clip the new actions column; verify stacked layout.

### Medium priority

5. **Asset version drift** — Some enqueues still use `1.0.0` (public story page, join page); align with theme `$ver`.
6. **Composer edit from profile modal** — Closing post modal then opening composer is abrupt; consider stacked modals or inline edit transition.
7. **Profile edit without cancel** — Users cannot discard bio text changes without saving; add “Revert” or auto-save bio on blur if desired.
8. **Public share modal** — No revoke-existing-link UI; creating a new link revokes prior (backend OK, UI could explain this).

### Low priority

9. **Material icon load** — Google Fonts CDN on every app page; self-host or subset for performance.
10. **Insights viewer list cap** — Hard limit 50 with scroll; pagination or “View all” may be needed for active PUs.
11. **Sticky insights sidebar** — Disabled in modal; re-enable on desktop single-story page if desired.

---

## ASAP — code / architecture issues

### High priority

1. **No automated tests** — Critical flows (invite, share links, profile save, story remove) are manual-only.
2. **Direct `$wpdb` queries** — Views and share links bypass WP APIs; acceptable for custom tables but needs migration discipline.
3. **Invitation `cancelled` status** — New status value; ensure admin reports/filters handle it (may show as raw string in wp-admin).

### Medium priority

4. **Blast notify confirm** — Added confirm; success still uses alert-style OneConfirm with empty cancel — polish UX.
5. **Profile avatar auto-save** — Enters edit mode on upload even if user only wanted photo change; bio field appears (intentional but may confuse).
6. **Share link expiry display** — Uses browser locale for datetime; consider site timezone from WP settings.

### Low priority

7. **Git ignore** — Only custom plugins tracked; document which third-party plugins are intentionally excluded.
8. **Translatable expiry options** — Share modal duration labels are in PHP; good, but JS formatting strings need i18n pass.

---

## How “Supported” works

- Stored in post meta `one_story_supporters` (array of user IDs).
- When a circle member taps **Support** on a post, their user ID is added (toggle on/off via AJAX `one_story_support`).
- Insights **Who supported** lists those members — not donations, not views.
- Donation/support for fundraising posts is separate (donation form / Stripe).

---

## How views work (after fix)

- Recorded once per logged-in viewer per story (unique DB row).
- **Post owner views are not recorded or counted.**
- Public token views (`/view/?t=…`) are never counted.
- Insights total and “Who viewed” exclude the author.

---

## Recommended next sprint

1. Replace remaining `alert()` success paths with toast component.
2. Mobile QA pass on profile modal, invite table, composer.
3. Consolidate duplicate CSS files.
4. Add PHPUnit or Playwright smoke tests for invite + share link flows.
