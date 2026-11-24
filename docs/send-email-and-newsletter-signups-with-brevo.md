---
title: Send email and collect newsletter signups with Brevo
tags: brevo, email, newsletter, signup, mailing list, list id, opt-in, mail provider
page: admin/settings/brevo
---

**Brevo** sends your site's email, and can add newsletter signups to a Brevo list.

## Send site email

1. Open **Brevo Settings** and enter your **API Key**. Press **Save Settings**.
2. Open the **Email** settings page and choose **Brevo** as the **Provider**.

Brevo only sends from a verified sender, so the From address must be verified in your Brevo account.

## Newsletter signups

1. On **Brevo Settings**, tick **Take newsletter signups**.
2. Enter your **List ID**.
3. Press **Save Settings**.

Visitors who tick the opt-in box are added to that list. Your site's design needs a signup form that sends to `/api/brevo-newsletter/` with the fields `email` and `consent`, so ask your developer to set that up. You can use double opt-in, where the visitor confirms by email first. The list and your campaigns are managed in Brevo.
