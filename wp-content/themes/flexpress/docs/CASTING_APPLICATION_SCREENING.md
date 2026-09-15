# Casting Applications: Portfolio, AI Screening, and Notifications

## Purpose

FlexPress uses Contact Form 7 for casting applications. The form collects useful portfolio details, sends the application to the configured casting workflow, and uses Grok to triage likely spam without blocking legitimate applicants by default.

## Casting Form Fields

Required fields are name, email, gender identity, preferred stage age, location, about-you text, and the over-18 agreement.

Applicants must provide at least one social or portfolio reference. The accepted references are:

- Instagram handle
- X/Twitter handle
- Primary Portfolio / Sample Link
- OnlyFans Free Trial Link
- Fansly Profile

The portfolio, OnlyFans trial, and Fansly fields are individually optional public URLs. FlexPress rejects malformed, placeholder, local, IP-address, and non-public URLs. The form copy makes clear that complete applications receive priority, while still welcoming newcomers who have limited portfolio material.

The casting page also introduces the application with the "Make Your Application Count" message. Do not replace that copy with a promise of acceptance or a guarantee of work.

## Grok Spam Triage

Configure Grok in **FlexPress → AI**:

1. Save the xAI/Grok API key.
2. Keep or change the configured structured-output-capable model (the default is `grok-4.3`).
3. Enable casting spam screening.
4. Use **Test Grok connection** after saving the key.

For every successful casting submission, FlexPress sends the current form fields to xAI with a structured-output prompt. It returns one of:

| Classification | Potential spam | Meaning |
| --- | --- | --- |
| `legit` | No | Plausible lead for normal staff review. |
| `suspicious` | Yes | Needs manual review but may be genuine. |
| `spam_troll` | Yes | Strong likely fake, troll, scam, or irrelevant application. |
| `unreviewed` | No | Grok is disabled, unconfigured, timed out, or returned an invalid result. |

The prompt is intentionally cautious: new performers, poor grammar, adult content, private applicants, overseas applicants, gender identity, one stage-age band of difference, and a missing portfolio are not spam on their own. Portfolio links are supportive evidence only; Grok must not browse them or claim they were checked. FlexPress does not retain application history or compare a new applicant with prior applicants.

The internal historical review at `../../../../docs/casting-spam-review-2026-09-15.md` is source material for the policy. It contains personal data and must not be copied into prompts, Discord, or public-facing documentation.

## Email and Discord Handling

Screening happens through Contact Form 7's pre-mail flow, so email handling can reflect the result while the applicant sees the normal successful-submission message.

| Result | Staff email | Applicant autoresponder | Discord |
| --- | --- | --- | --- |
| `legit` | Normal | Normal | Normal notification |
| `suspicious` | Sent with `[Potential spam]` subject prefix | Normal | Visible, push notification suppressed |
| `spam_troll` | Skipped | Skipped | Visible, push notification suppressed |
| `unreviewed` | Normal | Normal | Normal notification |

Silent Discord delivery uses Discord's `SUPPRESS_NOTIFICATIONS` message flag (`4096`). The message stays in the casting channel and may still create a notification badge depending on each member's Discord settings.

## Discord Setup

In **FlexPress → Discord**, configure **Casting Applications Webhook** for a dedicated casting destination. Its fallback order is:

1. Casting Applications Webhook
2. Contact Forms Webhook
3. Default Discord Webhook

Casting embeds include applicant details, all supplied portfolio fields, `AI Screening`, and `AI Assessment`. Potential-spam labels are `Potential spam — suspicious` and `Potential spam — spam/troll`; normal screened applications show `Likely genuine`.

Keep webhook URLs private. Anyone holding a webhook URL can post to its Discord channel.

## Operational Checks

- Submit a normal, clearly labelled test application and confirm a normal email plus a `Likely genuine` Discord embed.
- Submit a clearly labelled troll/spam test and confirm no staff/autoresponder email, plus a silent `Potential spam — spam/troll` Discord embed.
- Confirm an OnlyFans Free Trial URL or Fansly URL alone satisfies the social/portfolio reference requirement.
- If the AI test fails, submissions must continue as `AI unavailable / unreviewed`; this fail-open behavior prevents lost legitimate leads.
