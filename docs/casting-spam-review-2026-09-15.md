# Casting applications — spam / troll review

**INTERNAL.** Applicant emails, names, and socials. Do not post this file to Discord or the site.

| | |
|--|--|
| Pulled | 2026-09-15 (batches 1–3) |
| Source | `#dolls-support-bot` (`1422458258886627369`) · DollsBot webhook posts from the FlexPress casting form on dollsdownunder.com/casting |
| Set | **40** applications in three batches of 10 / 10 / 20 (newest first). No applications after 2026-09-05. Oldest in this file: 2026-06-06. |
| Classifier | Local **Dolphin3** via `dolphin3-local` MCP (`local_ai_chat`, temperature `0.2`, max_tokens `400`) |
| Discord MCP note | `read_messages` only returns the title `New casting application received!`. Embed fields were fetched from the DollsBot channel webhook. |
| Age policy | Small stage-age mismatches are **allowed**. A 35-year-old can pass as 28; one band (~5–8 years) is normal. Only extreme gaps (about 15+ years / two or more bands) count as a troll signal. |

## Summary — batch 1 (most recent 10)

Prompt A (stricter on age). See [Classification prompt — batch 1](#classification-prompt--batch-1).

| # | Submitted (AEST) | Name | Verdict (Dolphin3) | Conf. | Agent call | Why |
|---|------------------|------|--------------------|-------|------------|-----|
| 1 | 2026-09-06 04:28 | Tim | suspicious | 0.80 | **spam_troll** | Repeat of #10. Virgin one-liner. Junk Instagram `?deoia=1` homepage. |
| 2 | 2026-09-05 00:42 | Johnny Varveris | legit | 0.85 | **legit** | Coherent bio, real-looking IG handle. Age 28 vs 31–35 is allowed. |
| 3 | 2026-09-04 02:24 | Malcolm Henry | suspicious | 0.85 | **suspicious** | Near-duplicate of #4 (~56 min later, typo fixed). Could still be a real newbie. |
| 4 | 2026-09-04 01:28 | Malcolm Henry | suspicious | 0.80 | **suspicious** | First of the pair. Same email / name / handle. |
| 5 | 2026-09-03 09:54 | Casey Cini | suspicious | 0.70 | **suspicious** | Thin bio + work link is bare `pornhub.com`. Age 40 vs 26–30 is ~10–14 years — **do not hide on age alone**; homepage tube link is the real flag. |
| 6 | 2026-09-02 21:37 | Samuel Henwood | suspicious | 0.70 | **spam_troll** | “20 year old boy” vs stage age 50+ (extreme) and Non-binary. No agreement. Crude one-liner. Email does not match the name. |
| 7 | 2026-08-31 20:19 | Idaline Bloomingdale | legit | 0.85 | **legit** | Named Scarlet Blue escort profile. 22 vs 18–21 is allowed. |
| 8 | 2026-08-30 23:02 | Lukas | suspicious | 0.60 | **suspicious** (lean legit) | Specific: French, Brisbane, working rights. Instagram field is a sentence, not a handle. |
| 9 | 2026-08-29 16:05 | Marcus | suspicious | 0.70 | **suspicious** | Virgin one-liner. 18 vs 22–25 is **one band — allowed**. Different email from Tim. Staff flagged the virgin story, not the age. |
| 10 | 2026-08-27 20:53 | Tim | suspicious | 0.80 | **spam_troll** | Origin of the repeating virgin troll. Same junk IG URL. Staff: “put a checker in place to hide this stuff.” |

**Batch 1 Dolphin3:** legit 2 · suspicious 8 · spam_troll 0.

## Summary — batch 2 (previous 10)

Prompt B (age mismatches allowed unless extreme). See [Classification prompt — batch 2](#classification-prompt--batch-2).

| # | Submitted (AEST) | Name | Verdict (Dolphin3) | Conf. | Agent call | Why |
|---|------------------|------|--------------------|-------|------------|-----|
| 11 | 2026-08-26 12:55 | Kieran Whelan | suspicious | 0.70 | **legit** | 6 months OF with ex. IG `@Kieranwhelan2` matches name. Twitter `@None` is a junk fill, not enough to hide. |
| 12 | 2026-08-25 15:32 | Brad McNulty | legit | 0.85 | **legit** | 50 vs 46–50 match. Business email `streamline1.com.au`. Messy English, specific lifestyle bio. IG field is a name with a space. |
| 13 | 2026-08-24 23:04 | Kaoshal kaoshik Deo | legit | 0.90 | **suspicious** (lean real) | Real-looking IG + numeric OnlyFans URL. Bio is three hobby fragments — low effort, not the troll templates. |
| 14 | 2026-08-24 04:51 | Louis Vega | spam_troll | 0.90 | **spam_troll** | Staff laughed in-channel. 48 vs 22–25 (extreme). “17000 Pornhub videos” + “40 awards”. Work link is his email. |
| 15 | 2026-08-17 01:52 | Jake Williams | suspicious | 0.85 | **suspicious** | Resubmit of #16 ~4 min later. Private `t.me/c/` “work” link. Age 32 vs 31–35 match. Treat as one lead. |
| 16 | 2026-08-17 01:48 | Jake Williams | suspicious | 0.60 | **suspicious** | First of the pair. Amateur-sounding, 32, IG `@Dontdowhatjakedoes`. |
| 17 | 2026-08-14 00:51 | Kieran cameron | legit | 0.95 | **legit** | 36 vs 36–40 match. Job (manhole builder), AU mobile in bio, IG handle. |
| 18 | 2026-08-11 14:59 | Joel | suspicious | 0.60 | **legit** | Model Mayhem profile URL + nude/erotic stills, no film yet. Dolphin3 wrongly used the previous female app as a reason. |
| 19 | 2026-08-11 12:43 | Hina himawari | suspicious | 0.70 | **suspicious** (lean real) | Female Indonesian cosplayer. Zak: “Was not expecting that.” Logistics (work rights / travel) ≠ spam. IG `@hinahimawari2020`. |
| 20 | 2026-08-08 21:54 | Rudolf / Rodolfo Socha | legit | 0.95 | **legit** | Long specific bio: Chile, 8 years in AU, carpenter, BJJ, surfing, tattoos. 31 vs 31–35 match. IG `@Elcaniofficial`. |

**Batch 2 Dolphin3:** legit 4 · suspicious 5 · spam_troll 1.

## Summary — batch 3 (previous 20)

Prompt B (same as batch 2). Applications **#21–#40**, immediately older than Rudolf (#20). Window: 2026-08-07 back to 2026-06-06.

| # | Submitted (AEST) | Name | Verdict (Dolphin3) | Conf. | Agent call | Why |
|---|------------------|------|--------------------|-------|------------|-----|
| 21 | 2026-08-08 04:00 | Kavith | suspicious | 0.60 | **suspicious** | One-line “5'8 Sri Lankan Male.” Named IG `@_kavith__`. Thin, not the troll templates. |
| 22 | 2026-08-06 15:03 | Tim Renkema | legit | 0.85 | **legit** | Stand-up / actor, specific look (redhead, glasses, moustache, dadbod). `timrenkema@me.com`. Not the Tim Golding troll. |
| 23 | 2026-08-04 22:09 | Violet | suspicious | 0.60 | **suspicious** (lean real) | Female, measurements, coherent bio. Messy form: IG note about hacked account, Twitter sentence, work URL is “I don't have any experience.” **Not** the Aug 4 repeat guy. |
| 24 | 2026-08-04 14:09 | Ethan Sawyers | suspicious | 0.70 | **suspicious** | Resubmit of #26. Staff: “second time this guy has applied / I cannot stop him from resending.” LinkedIn. Throttle, don’t hide as troll. |
| 25 | 2026-07-31 18:51 | Claudio | suspicious | 0.60 | **suspicious** (lean legit) | 35 Italian, sport/travel/sex, IG `@cl4udioconti`. 35 vs 31–35 allowed. |
| 26 | 2026-07-30 11:23 | Ethan Sawyers | suspicious | 0.60 | **suspicious** | First of the Ethan pair. Gaffer / extra / modelling. Same LinkedIn. |
| 27 | 2026-07-27 20:05 | Josh / Josue Maechling | legit | 0.90 | **legit** | French in AU, life modelling Sydney/Hobart, Drive portfolio, found DDU via an actress. |
| 28 | 2026-07-24 17:31 | Jaiden henness | suspicious | 0.60 | **suspicious** | “I am new to the porn industry.” IG is a name with a space. Twitter `@Hennessjai27577`. |
| 29 | 2026-07-16 14:16 | Astrid Singleton | suspicious | 0.70 | **legit** | **Staff ground truth:** “this girl is keen, she's even contacted me. She wants to fly up to you for a shoot.” Dolphin3 invented that the Discord message id was spam. |
| 30 | 2026-07-14 20:33 | Celia | legit | 0.90 | **legit** | **Staff:** “We got a female!” Named OF `genevievecc`, 1 year filmed, IG `@astart_ish`. |
| 31 | 2026-07-14 02:13 | Divanshu Malhotra | suspicious | 0.60 | **suspicious** | Stats one-liner (8", 5'11). Named IG. |
| 32 | 2026-07-13 23:14 | Francisco Martins | suspicious | 0.60 | **suspicious** | Long chef/slaughterman CV, no adult context. Named IG `@Franc_866`. |
| 33 | 2026-07-11 16:18 | Guru | suspicious | 0.80 | **spam_troll** | “I need Job plz help.” Email `gssaini123456789@gmail.com`. Facebook share as work. Job-seeker spam. |
| 34 | 2026-07-05 14:43 | Matthew Walker | suspicious | 0.60 | **suspicious** (lean legit) | 6'2, full-limb tattoos, 41–45, named IG. Newcomer. |
| 35 | 2026-06-28 15:24 | Hunter Winterburn | suspicious | 0.80 | **suspicious** | Method-actor “I'm yours.” Work link `summerfreeze101.com`. No IG. |
| 36 | 2026-06-19 00:43 | James Lochhead | suspicious | 0.60 | **legit** | Escort/stripper past, counsellor now, rugby, clothing brand. Dolphin3 flagged discretion — that is a real reason, not a troll signal. |
| 37 | 2026-06-17 13:50 | Tim (Golding) | spam_troll | 0.95 | **spam_troll** | Same email as #1/#10. Virgin one-liner. IG `@Adamhill` does not match Tim. Troll starts at least here, not August. |
| 38 | 2026-06-15 05:03 | Jordan campbell | suspicious | 0.60 | **suspicious** | Short “gentle giant” bio. Email `jnewton272788` vs name Jordan. Named IG. |
| 39 | 2026-06-08 01:35 | Daniel | suspicious | 0.70 | **suspicious** | “Want to make money and have confidence.” Work URL `http://dan.battle`. IG `@Dan.battle`. |
| 40 | 2026-06-06 23:30 | Taylor Thurtell / Ryde | legit | 0.95 | **legit** | Cosplayer, 22 vs 18–21 **allowed**. Named OF + Pornhub `/model/taylor-ryde` (not a tube homepage). |

**Batch 3 Dolphin3:** legit 4 · suspicious 15 · spam_troll 1.

The **Agent call** column is the gold label for a hide-filter. Dolphin3 invents fields (live IG checks, “Discord message id is spam”) and under-calls `spam_troll` except when staff quotes or a known-troll email are in the prompt. Do not train a production classifier on Dolphin3 verdicts alone.

---

## Classification prompt — batch 1

Used on applications **#1–#10**. Stricter on age (superseded by batch 2). Only the user-message `APPLICATION` block and `KNOWN CONTEXT` line changed.

### System prompt

```
You classify adult-studio casting applications for Dolls Down Under, an Australian professional adult production company. Flag spam, trolling, joke submissions, and low-effort fakes. Sexual content itself is not spam. Newcomers and inexperienced applicants can be legitimate.

Return ONLY valid JSON with keys:
- verdict: "legit" | "suspicious" | "spam_troll"
- confidence: number 0.0-1.0
- reasons: array of short strings
- red_flags: array of short strings (empty if none)
- notes: one sentence for staff

Raise spam/troll risk for: one-liner virgin / "never done this" bios with no other substance (this studio has a repeating troll using that story); duplicate email or near-duplicate bio of another recent application; placeholder/junk socials (bare instagram.com, ?deoia= URLs, "I don't have one" stuffed into Instagram, generic pornhub.com with no profile); internal contradictions (stated age vs preferred stage age vs bio); gender-identity vs bio mismatch that looks like form-mashing; missing agreement tick; no usable social/work link AND empty or joke bio.

Support legit for: coherent bio with location, work rights, experience, or a real-looking professional link; matching age band and bio; real Instagram/Twitter/escort-directory/portfolio URL; specific non-copy-paste writing.

Do not invent facts. If unsure, use suspicious, not spam_troll.
```

### User prompt template

```
Classify this casting application.

KNOWN CONTEXT FROM THIS BATCH:
{duplicate emails, staff Discord comments, missing fields — facts only}

APPLICATION:
Submitted: {form submitted timestamp, AEST}
Discord message id: {id}
Name: {name}
Email: {email}
Gender Identity: {gender}
Preferred Stage Age: {band}
Instagram: {instagram or (none)}
Twitter: {twitter or (none)}
Link to Work: {url or (none)}
About You: {bio}
Agreement: {Confirmed or (missing)}
```

Settings: `temperature=0.2`, `max_tokens=400`.

---

## 1. Tim — 2026-09-06

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1545863318928101419
- Message id: `1545863318928101419`
- UTC: 2026-09-05T18:28:55Z

| Field | Value |
|-------|-------|
| Name | Tim |
| Email | timothygolding123@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | https://www.instagram.com/?deoia=1 |
| Twitter | — |
| Link to Work | — |
| About You | Im a virgin so im new to this lifestyle. |
| Agreement | Confirmed |

**Known context sent:** same email applied 2026-08-27 (#10) with “Im a virgin so I have never done this before” and the same junk Instagram URL.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.8,
  "reasons": [
    "Duplicate email from a previous application with a one-liner bio.",
    "Junk Instagram URL with ?deoia=1, not a valid profile link."
  ],
  "red_flags": [
    "One-liner bio with no other substance.",
    "Missing valid social or work link."
  ],
  "notes": "Previous application with similar email and one-liner bio was flagged as suspicious."
}
```

**Agent call:** `spam_troll`. Repeat submitter, virgin template, Instagram cookie/homepage URL not a profile. Hide.

---

## 2. Johnny Varveris — 2026-09-05

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1545444042131771545
- Message id: `1545444042131771545`
- UTC: 2026-09-04T14:42:52Z

| Field | Value |
|-------|-------|
| Name | Johnny Varveris |
| Email | johnnyvarveris@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@j.gvan_](https://instagram.com/j.gvan_) |
| Twitter | — |
| Link to Work | — |
| About You | I'm 28, fit and healthy and got a mind that is open to many things for the camera. I have my own business, am very career minded but also down to get any task done. |
| Agreement | Confirmed |

**Known context sent:** no duplicate of this email in the other nine.

**Dolphin3**

```json
{
  "verdict": "legit",
  "confidence": 0.85,
  "reasons": [
    "Coherent bio with location and career details.",
    "Matching age band and bio.",
    "Real-looking professional Instagram link."
  ],
  "red_flags": [],
  "notes": "Staff notes: Applicant has a solid, consistent bio and a professional-looking Instagram profile."
}
```

**Agent call:** `legit`. Worth a look. Dolphin3 invented “location” (none in the bio) and called 28 vs 31–35 a match — it is a slight mismatch, not a troll signal. IG handle was not live-checked here.

---

## 3. Malcolm Henry — 2026-09-04 (second)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1545107129223094335
- Message id: `1545107129223094335`
- UTC: 2026-09-03T16:24:06Z

| Field | Value |
|-------|-------|
| Name | Malcolm Henry |
| Email | blueseeker69@outlook.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@blueseeker69](https://instagram.com/blueseeker69) |
| Twitter | — |
| Link to Work | — |
| About You | I'm a shy and slim guy that loves sex who wants to try out the adult industry. I want to meet new people and try out new experiences and have fun along the way. I'm very fresh to this industry so I have no professional references currently and have no social links depicting my naughty side as I am a very private person who's wanting to start getting out there. I have decent experience with sex and been complimented twice about my stamina so I thought I'd try and give the adult industry a go. If I sound like the right fit for you and you're willing to give this newbie a go, I'll be ready for your call. |
| Agreement | Confirmed |

**Known context sent:** same email ~56 minutes earlier (#4); this copy fixed “profeshional” → “professional”.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.85,
  "reasons": [
    "Duplicate email from earlier application with minor correction in bio.",
    "Same Instagram handle as previous application.",
    "Lack of substantial professional or social links."
  ],
  "red_flags": [
    "Near-duplicate application",
    "Placeholder socials"
  ],
  "notes": "Staff notes: Consider verifying the applicant's identity and intentions due to the suspiciously close timing and lack of unique identifiers."
}
```

**Agent call:** `suspicious`. Double-submit after a typo fix is common for real people. Treat as one lead, not two. Handle is porny; not enough to call troll.

---

## 4. Malcolm Henry — 2026-09-04 (first)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1545093062781435995
- Message id: `1545093062781435995`
- UTC: 2026-09-03T15:28:12Z

| Field | Value |
|-------|-------|
| Name | Malcolm Henry |
| Email | blueseeker69@outlook.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | https://www.instagram.com/blueseeker69/ |
| Twitter | — |
| Link to Work | — |
| About You | I'm a shy and slim guy that loves sex who wants to try out the adult industry. I want to meet new people and try out new experiences and have fun along the way. I'm very fresh to this industry so I have no profeshional references currently and have no social links depicting my naughty side as I am a very private person who's wanting to start getting out there. I have decent experience with sex and been complimented twice about my stamina so I thought I'd try and give the adult industry a go. If I sound like the right fit for you and you're willing to give this newbie a go, I'll be ready for your call. |
| Agreement | Confirmed |

**Known context sent:** almost identical resubmit ~56 minutes later (#3).

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.8,
  "reasons": [
    "Duplicate email submission ~56 minutes later with minor typo correction.",
    "Placeholder social media links with no content or recent activity.",
    "Lack of specific experience and references in the adult industry.",
    "Mismatch between 'shy and slim' bio and 'sex-loving' casting application."
  ],
  "red_flags": [
    "Email duplication",
    "Lack of social media content",
    "Lack of industry experience"
  ],
  "notes": "Staff notes: Reviewer should contact the applicant for further clarification on the bio mismatch and email duplication."
}
```

**Agent call:** `suspicious` (same lead as #3). Dolphin3 claimed it checked IG “content or recent activity” — it did not; that is invented. Newcomer + no adult socials is allowed by the prompt.

---

## 5. Casey Cini — 2026-09-03

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1544857992392876074
- Message id: `1544857992392876074`
- UTC: 2026-09-02T23:54:07Z

| Field | Value |
|-------|-------|
| Name | Casey Cini |
| Email | dcruzer79@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | — |
| Twitter | — |
| Link to Work | http://pornhub.com |
| About You | Im male and 40 into anything. Interested in a porn shoot if any positions are available. |
| Agreement | Confirmed |

**Known context sent:** no duplicate of this email in the other nine.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.7,
  "reasons": [
    "Generic pornhub.com link with no profile",
    "No usable social/work link",
    "Age discrepancy: 40 vs preferred stage age 26-30"
  ],
  "red_flags": [
    "Generic pornhub.com with no profile"
  ],
  "notes": "Casey Cini's bio seems mostly coherent, but the age discrepancy and generic pornhub link raise suspicions."
}
```

**Agent call:** `suspicious`. Age 40 vs 26–30 plus homepage Pornhub is enough to deprioritise. Not the virgin-troll template.

---

## 6. Samuel Henwood — 2026-09-02

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1544672524279742517
- Message id: `1544672524279742517`
- UTC: 2026-09-02T11:37:08Z

| Field | Value |
|-------|-------|
| Name | Samuel Henwood |
| Email | billg6582@gmail.com |
| Gender Identity | Non-binary |
| Preferred Stage Age | 50+ |
| Instagram | [@henwoodjac](https://instagram.com/henwoodjac) |
| Twitter | [@henwoodjac](https://x.com/henwoodjac) |
| Link to Work | — |
| About You | im a 20 year old boy with a big cock and gay |
| Agreement | *(missing — field not on the embed)* |

**Known context sent:** no duplicate email; agreement field missing.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.7,
  "reasons": [
    "Missing agreement tick",
    "Age mismatch: 20-year-old bio with 50+ preferred stage age",
    "Generic pornhub.com link with no profile"
  ],
  "red_flags": [
    "Placeholder socials",
    "Internal age contradiction"
  ],
  "notes": "Staff notes: Check for real social links and age consistency."
}
```

**Agent call:** `spam_troll`. Form-mashing: 20 vs 50+, “boy” vs Non-binary, email `billg6582` vs name Samuel Henwood, crude one-liner, no agreement. **Dolphin3 invented a Pornhub link that is not on this application.**

---

## 7. Idaline Bloomingdale — 2026-08-31

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1543928222385963009
- Message id: `1543928222385963009`
- UTC: 2026-08-31T10:19:33Z

| Field | Value |
|-------|-------|
| Name | Idaline Bloomingdale |
| Email | idaart369@gmail.com |
| Gender Identity | Female |
| Preferred Stage Age | 18-21 |
| Instagram | — |
| Twitter | — |
| Link to Work | https://scarletblue.com.au/escort/idaline-bloomingdale |
| About You | None! This is new to me. But I want to try. I am 22, but pass as younger. I am submissive and love BDSM. I can send photos as well. |
| Agreement | Confirmed |

**Known context sent:** no duplicate email; work link is a named scarletblue.com.au escort profile.

**Dolphin3**

```json
{
  "verdict": "legit",
  "confidence": 0.85,
  "reasons": [
    "Unique email in recent applications",
    "Named escort-directory profile URL on scarletblue.com.au",
    "Age band and bio match",
    "Agreement confirmed"
  ],
  "red_flags": [],
  "notes": "New application with named work link and confirmed agreement."
}
```

**Agent call:** `legit`. Strongest real-looking lead in this batch. Profile URL was not opened here. She states 22 vs stage band 18–21 and “pass as younger” — confirm age on ID if you proceed; that is a production check, not a troll flag.

---

## 8. Lukas — 2026-08-30

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1543606807308533861
- Message id: `1543606807308533861`
- UTC: 2026-08-30T13:02:21Z

| Field | Value |
|-------|-------|
| Name | Lukas |
| Email | lk2226667@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 18-21 |
| Instagram | @I don't currently have one for modelling |
| Twitter | — |
| Link to Work | — |
| About You | Hi I'm a French guy with full working rights in Australia currently living in Brisbane. I am seeking to have connections with passionate people from this industry and this is the reason why I apply to your team. I have never done anything from this industry before but I'm really happy to learn from you. I'm looking forward to give you further information. Have a good day |
| Agreement | Confirmed |

**Known context sent:** no duplicate of this email in the other nine.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.6,
  "reasons": [
    "Newcomer with no other substance in bio",
    "Generic 'I'm new but eager to learn' bio",
    "Missing usable social/work link"
  ],
  "red_flags": [
    "Placeholder socials",
    "No specific writing"
  ],
  "notes": "Needs to provide more specific details about his experience or skills to be considered further."
}
```

**Agent call:** `suspicious`, lean legit. Dolphin3 undersold this: French + Brisbane + working rights is specific. Instagram field is a sentence, not a handle — treat as “no IG,” not junk-URL troll. Fine to reply and ask for photos / ID.

---

## 9. Marcus — 2026-08-29

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1543139440422158410
- Message id: `1543139440422158410`
- UTC: 2026-08-29T06:05:12Z

| Field | Value |
|-------|-------|
| Name | Marcus |
| Email | marcusownie@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | [@marcusownie](https://instagram.com/marcusownie) |
| Twitter | — |
| Link to Work | — |
| About You | Hi, I am 18 years old and I was wondering if I could join you guys, btw, I have no experince and I am virgin |
| Agreement | Confirmed |

**Known context sent:** staff had already joked about a virgin applying; Tim troll is a different email.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.7,
  "reasons": [
    "Applicant's bio states 'I am 18 years old and I have no experience and I am a virgin,' which is a common phrase used by the repeating troll.",
    "Email marcusownie@gmail.com is different from the known troll's email but the similarity in the virgin one-liner raises suspicion."
  ],
  "red_flags": [
    "Applicant's bio is very similar to the known troll's story, indicating potential form-mashing or copying.",
    "No usable social/work link provided."
  ],
  "notes": "Staff to review the authenticity of the Instagram profile @marcusownie and cross-check with previous applications for consistency."
}
```

**Agent call:** `suspicious`. Same *story* as Tim, different identity. 18 vs stage 22–25 is a contradiction. Could be copycat troll or a naive 18-year-old. Check `@marcusownie` before writing it off. In-channel this is the one gingerninja flagged.

---

## 10. Tim — 2026-08-27

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1542487094335774781
- Message id: `1542487094335774781`
- UTC: 2026-08-27T10:53:01Z

| Field | Value |
|-------|-------|
| Name | Tim |
| Email | timothygolding123@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | https://www.instagram.com/?deoia=1 |
| Twitter | — |
| Link to Work | — |
| About You | Im a virgin so I have never done this before |
| Agreement | Confirmed |

**Known context sent:** same email reapplied 2026-09-06 (#1); staff said they would put a checker in to hide this.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.8,
  "reasons": [
    "Duplicate email from a recent application.",
    "One-liner virgin bio with no other substance.",
    "Placeholder/junk Instagram URL."
  ],
  "red_flags": [
    "Duplicate email from a recent application.",
    "One-liner virgin bio with no other substance.",
    "Placeholder/junk Instagram URL."
  ],
  "notes": "Applicant has applied again with the same email and bio as a recent application, raising suspicions of a duplicate submission or a repeating troll story."
}
```

**Agent call:** `spam_troll`. This is the seed of the repeating Tim / virgin / `?deoia=1` pattern. Hide both #10 and #1.

---

# Batch 2 — previous 10 (2026-08-26 back to 2026-08-08)

Immediately older than #10 (Tim, 27 Aug). Staff chatter in this window:

- After #14 Louis Vega (23 Aug, staff replied next morning 24 Aug): gingerninja “fuck this one made me laugh at work today”; zak “how does he know how many hea watched”; gingerninja “and what did he win awards for if he's never been the talent?” / “I've watched a LOT of F1 races... does that mean Verstappen should get out so I can have a drive next weekend?”
- After #19 Hina (11 Aug): zak “Was not expecting that” (female applicant). Joel (#18) arrived ~2 hours later and is unrelated.
- 4 Aug (just older than this batch, not included): gingerninja “second time this guy has applied. can you make it so they can't apply more than once?”; zak “i cannot stop him from resending”. Repeat-submit is a known real-world behaviour, not automatically troll.

## Classification prompt — batch 2

Exact prompt sent to Dolphin3 for **#11–#20**. Age policy updated per staff: a 35-year-old can pass as 28.

### System prompt

```
You classify adult-studio casting applications for Dolls Down Under, an Australian professional adult production company. Flag spam, trolling, joke submissions, and low-effort fakes. Sexual content itself is not spam. Newcomers and inexperienced applicants can be legitimate.

Return ONLY valid JSON with keys:
- verdict: "legit" | "suspicious" | "spam_troll"
- confidence: number 0.0-1.0
- reasons: array of short strings
- red_flags: array of short strings (empty if none)
- notes: one sentence for staff

AGE: Small stage-age mismatches are ALLOWED and are not a red flag. A 35-year-old can pass as 28; a 22-year-old picking 18-21 is fine; one stage-age band (~5-8 years) is normal. Only treat age as a problem when it is extreme (about 15+ years, or two or more stage-age bands), e.g. 48 claiming 22-25, or 20 claiming 50+.

Raise spam/troll risk for: one-liner virgin / "never done this" bios with no other substance (this studio has a repeating troll using that story); duplicate email or near-duplicate bio of another recent application; placeholder/junk socials (bare instagram.com, ?deoia= URLs, "@None", Instagram field that is a sentence or a person's full name with spaces, generic pornhub.com with no profile, work link that is an email address); gender-identity vs bio mismatch that looks like form-mashing; missing agreement tick; no usable social/work link AND empty or joke bio; absurd claims (thousands of tube videos watched as "experience", fake awards).

Support legit for: coherent bio with location, work rights, job, sport, or a real-looking professional link (OnlyFans profile, Model Mayhem, escort directory, named Instagram handle); specific non-copy-paste writing; phone number or business email; experience described in plain language even if amateur.

Do not invent facts. If unsure, use suspicious, not spam_troll. Do not mention age mismatch unless it is extreme as defined above.
```

### User prompt template

Same shape as batch 1. `KNOWN CONTEXT` included duplicate emails, staff Discord quotes, and whether an age gap was a match / allowed / extreme — facts only.

Settings: `temperature=0.2`, `max_tokens=400`.

---

## 11. Kieran Whelan — 2026-08-26

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1542004572648906833
- Message id: `1542004572648906833`
- UTC: 2026-08-26T02:55:39Z

| Field | Value |
|-------|-------|
| Name | Kieran Whelan |
| Email | kieranwhelan411@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | [@Kieranwhelan2](https://instagram.com/Kieranwhelan2) |
| Twitter | [@None](https://x.com/None) |
| Link to Work | — |
| About You | I have had about 6 months experience doing OF with my ex girlfriend and loved it made a bit of side cash doing it with her was something I'd definetly do again |
| Agreement | Confirmed |

**Known context sent:** no duplicate email; Twitter field is the literal handle `@None`.

**Dolphin3**

```json
{
  "verdict": "suspicious",
  "confidence": 0.7,
  "reasons": [
    "Generic social media handles without substantial content",
    "No professional link provided",
    "Mention of doing OnlyFans with an ex-girlfriend without clear evidence of professional experience"
  ],
  "red_flags": [
    "Lack of specific details about experience",
    "No other professional or personal information provided"
  ],
  "notes": "Staff notes that the application lacks detailed information about the applicant's experience and professional background."
}
```

**Agent call:** `legit`. Amateur OF collab + name-matching IG is a normal DDU male lead. `@None` is a form-fill for “I don’t have Twitter,” not a troll URL. Dolphin3 claimed it checked IG “content” — it did not.

**Tool features:** `twitter == "@None"` or `twitter.lower() in {none, n/a, na}` → treat as empty, not junk-URL. Name-token overlap with IG handle is a legit signal.

---

## 12. Brad McNulty — 2026-08-25

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1541681698704592970
- Message id: `1541681698704592970`
- UTC: 2026-08-25T05:32:40Z

| Field | Value |
|-------|-------|
| Name | Brad McNulty |
| Email | brad@streamline1.com.au |
| Gender Identity | Male |
| Preferred Stage Age | 46-50 |
| Instagram | [@Brad McNulty](https://instagram.com/Brad McNulty) |
| Twitter | — |
| Link to Work | — |
| About You | 50 year-old man very fit plenty of cinema done threesomes foursomes . Of fuck women in an open parties 30 people watch me at a time no stage fright for me like to try out filming. |
| Agreement | Confirmed |

**Known context sent:** business-looking `.com.au` email; IG is a full name with a space; 50 vs 46–50 is a match.

**Dolphin3**

```json
{
  "verdict": "legit",
  "confidence": 0.85,
  "reasons": [
    "Stated age matches preferred stage age.",
    "Business domain email.",
    "Specific writing in the bio.",
    "Phone number or business email.",
    "Experience described in plain language."
  ],
  "red_flags": [],
  "notes": "Staff notes: Valid application with a coherent bio and a business email."
}
```

**Agent call:** `legit`. Messy English ≠ troll. Lifestyle/swinger bio is specific. IG-with-spaces is a bad handle (won’t resolve) but the email is the contact. Dolphin3 invented a phone number.

**Tool features:** `email` on a real org domain (`.com.au` company, not gmail) is a strong legit signal. `instagram` containing whitespace → empty/invalid handle, not automatic spam. Age 50 in the **matching** band is not a mismatch.

---

## 13. Kaoshal kaoshik Deo — 2026-08-24

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1541432944525180970
- Message id: `1541432944525180970`
- UTC: 2026-08-24T13:04:12Z

| Field | Value |
|-------|-------|
| Name | Kaoshal kaoshik Deo |
| Email | kaoshaldeo@hotmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [kaoshaldeo](https://www.instagram.com/kaoshaldeo?igsi=MXhidTFxbnRzemZ2OA==) |
| Twitter | — |
| Link to Work | https://onlyfans.com/u266623033 |
| About You | X carpenter, X soccer player  X video game player |
| Agreement | Confirmed |

**Known context sent:** IG has a tracking `?igsi=` query; OF is a numeric `/u…` URL; bio is three fragments.

**Dolphin3**

```json
{
  "verdict": "legit",
  "confidence": 0.9,
  "reasons": [
    "Unique email in the batch.",
    "Tracking query in Instagram URL is normal.",
    "Numeric OnlyFans URL is valid.",
    "Three short fragments about past hobbies/jobs in bio.",
    "Confirmed agreement tick."
  ],
  "red_flags": [],
  "notes": "Staff notes: Legitimate application with a tracking query in the Instagram URL and numeric OnlyFans URL. No duplicates in the batch."
}
```

**Agent call:** `suspicious` (lean real). Links look real; bio is too thin to be a strong lead. Not the virgin / awards / `?deoia=` templates. Strip `?igsi=` (and `?deoia=`) when parsing IG — `?deoia=` on the **homepage** is junk, `?igsi=` on a named profile is a copied share URL.

**Tool features:** `onlyfans.com/u` + digits is a valid OF profile, not a missing handle. Short resume-fragment bios are low-effort, not spam by themselves.

---

## 14. Louis Vega — 2026-08-24

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1541157912217395331
- Message id: `1541157912217395331`
- UTC: 2026-08-23T18:51:19Z

| Field | Value |
|-------|-------|
| Name | Louis Vega |
| Email | casique9146@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | — |
| Twitter | — |
| Link to Work | http://casique9146@gmail.com |
| About You | Hi there I'm Louis from Melbourne Australia I'm 48 years old I'm fairly fit I have a south American background I've been into sex watching for over ten years I've watched over 17000 videos on pornhub I've won over 40 awards I love it so much I've waited to act for a long time and I finally got the courage up to apply please give me a go |
| Agreement | Confirmed |

**Known context sent:** staff laughed the next morning (quotes above); work link is his email; 48 vs 22–25 is extreme.

**Dolphin3**

```json
{
  "verdict": "spam_troll",
  "confidence": 0.9,
  "reasons": [
    "Laughed at by studio staff.",
    "Extremely mismatched age.",
    "Absurd claims of pornhub videos watched and awards won."
  ],
  "red_flags": [
    "Stated age 48 vs preferred stage age 22-25 is extreme.",
    "Work link is an email address, not a URL.",
    "Generic pornhub.com with no profile."
  ],
  "notes": "Staff laughed at the application due to absurd claims and extreme age mismatch."
}
```

**Agent call:** `spam_troll`. Best labelled troll in this file after Tim. **Staff ground truth.** Watching 17k videos is not experience; “40 awards” with no talent credits. Work field is `http://email` (form accepted an email as a URL). Dolphin3 invented a Pornhub **link** — he only mentioned the site in prose.

**Tool features:**
- Parse a number from bio vs midpoint of stage-age band. Hide if `abs(age - band_mid) >= 15` **and** there is another troll signal (here: fake awards / video-count-as-experience / email-as-URL). Age 48 vs 22–25 would already be extreme enough to hide even without the awards, because it is **three bands**, not one.
- `work_url` matching an email / `http://user@domain` → invalid work link.
- Claims like `\d{3,} videos` or `\d+ awards` with no named show/studio → absurd-claim flag.
- This is **not** the 35-passing-as-28 case.

---

## 15. Jake Williams — 2026-08-17 (second)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1538576080250601633
- Message id: `1538576080250601633`
- UTC: 2026-08-16T15:52:02Z

| Field | Value |
|-------|-------|
| Name | Jake Williams |
| Email | jakealanwilliams@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@Don'tdowhatjakedoes](https://instagram.com/Don'tdowhatjakedoes) |
| Twitter | — |
| Link to Work | https://t.me/c/3618374127/93 |
| About You | I have only just realised I have a decent size cock and that I can use it. Been in 2 relationships for total 15 years, I am Looking to be challenged and learn sexually. I treat women with respect and want to please. I have some videos I can share video email. |
| Agreement | Confirmed |

**Known context sent:** same email ~4 minutes earlier (#16); IG apostrophe added; Telegram `t.me/c/` is a private chat, not a public username.

**Dolphin3:** `suspicious` 0.85 — duplicate + private Telegram as work.

**Agent call:** `suspicious`. One lead with #16. Private Telegram is unusable as a public work link (staff cannot open `t.me/c/` without being in the chat). Not a troll template.

**Tool features:** `t.me/c/` → not a public portfolio. Duplicate email within minutes + small IG edit = real person fixing a submit, same as Malcolm.

---

## 16. Jake Williams — 2026-08-17 (first)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1538575176331960381
- Message id: `1538575176331960381`
- UTC: 2026-08-16T15:48:27Z

| Field | Value |
|-------|-------|
| Name | Jake Williams |
| Email | jakealanwilliams@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@Dontdowhatjakedoes](https://instagram.com/Dontdowhatjakedoes) |
| Twitter | — |
| Link to Work | — |
| About You | I have only recently realised I have a fair sized cock, and can be quite good with it. I've been in 2 relationships for total of 15 years and I am 32. I respect women and just want to make them feel safe, and satisfied. I am hoping to try experiencing so fun with people who can challenge me or teach me sexually. |
| Agreement | Confirmed |

**Known context sent:** resubmitted 4 minutes later; 32 vs 31–35 match.

**Dolphin3:** `suspicious` 0.60 — resubmit + personal relationship details.

**Agent call:** `suspicious`. Amateur, 32, named-ish IG. Crude but not the virgin troll. Merge with #15.

---

## 17. Kieran cameron — 2026-08-14

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1537473725035708527
- Message id: `1537473725035708527`
- UTC: 2026-08-13T14:51:40Z

| Field | Value |
|-------|-------|
| Name | Kieran cameron |
| Email | Kieran.cameron.kc@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 36-40 |
| Instagram | [@Fluffyk90](https://instagram.com/Fluffyk90) |
| Twitter | — |
| Link to Work | — |
| About You | Im out going guy the is bi and love all forms and styles. I have done any modeling work but whilling to give it a shot. Im a fit 36yr old male thats works as a manhole builder during the day Have any further questions dont hesitate to give me a call on 0439365875 |
| Agreement | Confirmed |

**Known context sent:** no duplicate; AU mobile `0439365875`; 36 vs 36–40 match.

**Dolphin3**

```json
{
  "verdict": "legit",
  "confidence": 0.95,
  "reasons": [
    "Stated age 36 matches preferred stage age 36-40.",
    "Bio includes an Australian mobile number.",
    "Specific non-copy-paste writing.",
    "Phone number provided."
  ],
  "red_flags": [],
  "notes": "Valid application with a coherent bio and provided contact information."
}
```

**Agent call:** `legit`. Job + age match + AU `04xx` mobile + IG. ESL / typos are normal. Phone in the bio is extra contact, not a spam pattern here.

**Tool features:** AU mobile `\b04\d{8}\b` in bio is a legit-contact signal. “I have done any modeling” = “I have not done any” (ESL) — do not keyword-match “done any modeling” as experience.

---

## 18. Joel — 2026-08-11

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1536600018423906356
- Message id: `1536600018423906356`
- UTC: 2026-08-11T04:59:53Z

| Field | Value |
|-------|-------|
| Name | Joel |
| Email | jbtelfer1@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | — |
| Twitter | — |
| Link to Work | https://www.modelmayhem.com/4880315 |
| About You | I have never done any filmed work before but I have modelled nude and done erotic photoshoots before |
| Agreement | Confirmed |

**Known context sent:** Model Mayhem profile; staff “Was not expecting that” was about the **previous** female app, not Joel.

**Dolphin3:** `suspicious` 0.60 — wrongly used the Hina timing as a reason; also “no Instagram.”

**Agent call:** `legit`. Named Model Mayhem ID is a real-looking work link. Newcomer to film + stills experience is a standard DDU male. Ignore “posted after a female” as a feature — that is channel coincidence.

**Tool features:** `modelmayhem.com/` + numeric id = valid portfolio host (alongside scarletblue, onlyfans named or `/u`, linktree). Missing IG is fine if work link exists.

---

## 19. Hina himawari — 2026-08-11

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1536565630353018881
- Message id: `1536565630353018881`
- UTC: 2026-08-11T02:43:14Z

| Field | Value |
|-------|-------|
| Name | Hina himawari |
| Email | hinahimawari0@gmail.com |
| Gender Identity | Female |
| Preferred Stage Age | 18-21 |
| Instagram | [@hinahimawari2020](https://instagram.com/hinahimawari2020) |
| Twitter | — |
| Link to Work | — |
| About You | Hii, i m a cosplayer from indonesia.. i love sex and being in adult movie is my dream.. i only like to play with males.. |
| Agreement | Confirmed |

**Known context sent:** no duplicate; zak ~2h later “Was not expecting that”; Indonesia, no work-rights stated; female apps are uncommon in this channel.

**Dolphin3:** `suspicious` 0.70 — no AU work rights stated; “no evidence of cosplay work.”

**Agent call:** `suspicious` (lean real). Staff reaction is surprise at a female, not a troll call. Overseas + no work-rights is a **casting logistics** flag (visa / fly-in), not spam. IG handle matches the name. Do not auto-hide on “Indonesia” or “female.”

**Tool features:** `gender == Female` is rare in this stream and is a **priority** signal for staff, not a spam signal. Country outside AU without “working rights” / “visa” / “Brisbane|Sydney|Melbourne|Gold Coast” → `logistics_review`, separate from `spam_troll`.

---

## 20. Rudolf / Rodolfo Socha — 2026-08-08

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1535617130089816118
- Message id: `1535617130089816118`
- UTC: 2026-08-08T11:54:14Z

| Field | Value |
|-------|-------|
| Name | Rudolf Socha |
| Email | rudolfsocha12@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@Elcaniofficial](https://instagram.com/Elcaniofficial) |
| Twitter | — |
| Link to Work | — |
| About You | Hi, I'm Rodolfo Socha. I'm 31 years old, originally from Chile, and I've been living in Australia for the past eight years. I work as a carpenter, and I'm a very active and energetic person. I'm really into Brazilian Jiu-Jitsu and surfing, so fitness and staying in good shape are a big part of my lifestyle. I have an athletic build and quite a few tattoos, which are definitely part of my look and personality. I'd describe myself as confident, outgoing and very social. I really enjoy meeting new people, connecting with different personalities and having a good time. I love the beach, training, being outdoors and, of course, going out and enjoying a good party from time to time. I'm comfortable around people and in front of the camera, and I'm excited to bring my energy, personality and life experience into acting and new creative opportunities. |
| Agreement | Confirmed |

**Known context sent:** form name Rudolf vs bio Rodolfo; 31 vs 31–35 match; long specific bio.

**Dolphin3:** `legit` 0.95.

**Agent call:** `legit`. Strongest male bio in the 20. Rudolf/Rodolfo is a normal Spanish/German name pair, not a form mash. Years-in-Australia + job + sports + tattoos is the shape of a real lead.

**Tool features:** bio length, AU-resident phrasing (“living in Australia for the past N years”), named job, named sports. First-name variant Rudolf vs Rodolfo ≠ name/email mismatch (email still contains `socha`).

---

# Batch 3 — previous 20 (2026-08-07 back to 2026-06-06)

Immediately older than #20 (Rudolf, 8 Aug). Prompt B. Staff chatter in this window:

- **4 Aug (Ethan #24, after #26):** gingerninja “second time this guy has applied. can you make it so they can't apply more than once?”; zak “i cannot stop him from resending / if you reply to him and tell him we will contact him if we need someone then maybe he will be chill about it.” That is **Ethan**, not Violet (#23) who applied later the same day.
- **17 Jul (Astrid #29):** zak “@everyone this girl is keen, she's even contacted me. She wants to fly up to you for a shoot.”
- **14 Jul (Celia #30):** zak “We got a female!”
- **20 May** is *after* this batch (“man, these dudes are relentless”) — not included.

Tim Golding’s virgin-troll email is in this batch (#37, 17 Jun) — earlier than the August copies.

---

## 21. Kavith — 2026-08-08

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1535346841300766762
- UTC: 2026-08-07T18:00:12Z

| Field | Value |
|-------|-------|
| Name | Kavith |
| Email | pererak778@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | [@_kavith__](https://instagram.com/_kavith__) |
| Twitter | — |
| Link to Work | — |
| About You | 5'8 Sri Lankan Male. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60 (invented “single-line bio on Instagram” — the one-liner is the About You field).

**Agent call:** `suspicious`. Thin physical line + named IG. Not hide.

---

## 22. Tim Renkema — 2026-08-06

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1534789003281170565
- UTC: 2026-08-06T05:03:33Z

| Field | Value |
|-------|-------|
| Name | Tim Renkema |
| Email | timrenkema@me.com |
| Gender Identity | Male |
| Preferred Stage Age | 36-40 |
| Instagram | [@renkemuffin](https://instagram.com/renkemuffin) |
| Twitter | — |
| Link to Work | — |
| About You | Hello! I'm Tim. I'm a stand-up comedian and actor interested in filming adult films. I'm a real Everyman with a unique look. Redhair, glasses, moustache, tattoos, dadbod, 6ft tall. Yet me know if anything might be possible! Thanks so much, cheers xoxo. |
| Agreement | Confirmed |

**Dolphin3:** `legit` 0.85 (claimed it checked IG content — it did not).

**Agent call:** `legit`. Different Tim from Golding (`me.com`, full name, specific look). Do not keyword-match first name “Tim” to the troll.

**Tool features:** email local-part matching full name; “dadbod / redhead / stand-up” specificity.

---

## 23. Violet — 2026-08-04

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1534171492642979871
- UTC: 2026-08-04T12:09:47Z

| Field | Value |
|-------|-------|
| Name | Violet |
| Email | thesimsviolet@gmail.com |
| Gender Identity | Female |
| Preferred Stage Age | 18-21 |
| Instagram | @violetanahera__ (Old one is violetanahera, it got hacked :() |
| Twitter | @i don't have one!! (sorry) |
| Link to Work | http://I don't have any experience. |
| About You | While I do not have any on-camera experience, I do have sex experience. I love sex and have a high sex drive, am a pretty open-minded person when it comes to sex, and I feel confident in my ability to translate that into this context. I am confident, friendly, and have lots of non-sexual work experience. I am 173cm and 60-64 kg (depending on the week), have B-C cups, and appear generally lean with a bit of curve. I have blonde hair that I dye (the same colour) regularly and brown eyes. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60.

**Agent call:** `suspicious` (lean real). Female + measurements is a real lead shape. Form fields are messy (parentheticals, sentence as Twitter, prose as work URL) — parse the handle `violetanahera__` out of the IG field; treat Twitter/work as empty.

**Tool features:** strip ` (…)` from IG. `http://` + spaces/prose → invalid work link, not a troll by itself if the bio is coherent. Female = priority, not spam. Staff “this guy resending” that day is Ethan.

---

## 24. Ethan Sawyers — 2026-08-04 (second)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1534050566341136416
- UTC: 2026-08-04T04:09:16Z

| Field | Value |
|-------|-------|
| Name | Ethan Sawyers |
| Email | ethansawyers2000@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@ethan_sawyers26](https://instagram.com/ethan_sawyers26) |
| Twitter | — |
| Link to Work | https://www.linkedin.com/in/ethan-sawyers-9105bb243/ |
| About You | Dear Sex-Dolls-Downunder Warm greetings, My name is Ethan Sawyers. I am currently looking for work opportunities immediately. Since being in the Film business since 2020. I found the adult films side very interesting over the years & looking to get on screen now as a professional just to support myself living & daily life. I am always on the lookout for my next wild ride. Look forward to creating with you soon. Kind Regards E. Sawyers |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.70.

**Agent call:** `suspicious`. **Staff ground truth: throttle repeats, don’t treat as troll.** LinkedIn + name-matching IG. Cover-letter / “need work immediately” is needy, not Louis-style fake awards.

**Tool features:** `linkedin.com/in/` is a valid work host. Repeat email of a *real* lead → collapse thread, optional staff ping, not `hide`. Distinct from Tim Golding repeats.

---

## 25. Claudio — 2026-07-31

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1532672164057059539
- UTC: 2026-07-31T08:51:59Z

| Field | Value |
|-------|-------|
| Name | Claudio |
| Email | claudioconti13@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | [@cl4udioconti](https://instagram.com/cl4udioconti) |
| Twitter | — |
| Link to Work | — |
| About You | I am a 35yrs old Italian man who loves sport, travel and sex. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60 (invented missing social — IG is present).

**Agent call:** `suspicious` (lean legit). Short but specific. 35 vs 31–35 allowed. Email matches Conti.

---

## 26. Ethan Sawyers — 2026-07-30 (first)

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1532196790789734453
- UTC: 2026-07-30T01:23:01Z

| Field | Value |
|-------|-------|
| Name | Ethan Sawyers |
| Email | ethansawyers2000@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@ethan_sawyers26](https://instagram.com/ethan_sawyers26) |
| Twitter | — |
| Link to Work | https://www.linkedin.com/in/ethan-sawyers-9105bb243/ |
| About You | A young man seeking extra work, I have been involved with the entertainment Industry (Film & Tv) as a Lighting gaffer, then early this year A company reached me & I have been doing modelling for their company & background extra work on numerous productions. But now- I though, why not give the other crating side of film a crack & see where it leads me. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60.

**Agent call:** `suspicious`. Same lead as #24. Gaffer/extra is plausible AU film-casual work. Merge with #24.

---

## 27. Josh / Josue Maechling — 2026-07-27

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1531241110880391209
- UTC: 2026-07-27T10:05:29Z

| Field | Value |
|-------|-------|
| Name | Josh |
| Email | josue.maechling@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | @instagram.com/josue_maechling |
| Twitter | — |
| Link to Work | https://drive.google.com/drive/folders/12VC9WZbSrJYyKWprbs1eQlw8SC8kke |
| About You | Hi! I'm Josh, a 27yo french man in Australia. I'm new to this kind of art but I've been exposed to nudity in public through my experience in life modelling, mainly in Sydney and Hobart and some webcam modelling while I was travelling through South America. I have some experience in swingers clubs but was always curious about creating quality pornography. I am a photographer/videographer part time and appreciate good content both behind and in front of the camera. You will find my public Instagram for face pictures and my portfolio in life modelling for my body. I haven't opened an onlyfans account yet but am willing to if it helps. I found out about you through one of your actresses and would like to help make interesting content. Although I'm not Aussie I believe I can represent some of the diverse population here! |
| Agreement | Confirmed |

**Dolphin3:** `legit` 0.90.

**Agent call:** `legit`. Strongest male in this batch with Josh-level specificity (cities, life model, referral).

**Tool features:** `drive.google.com/drive/folders/` = valid portfolio. IG field `instagram.com/handle` → parse handle `josue_maechling`. 27 vs 26–30 match. “Found you through an actress” is a legit referral signal.

---

## 28. Jaiden henness — 2026-07-24

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1530115207274172488
- UTC: 2026-07-24T07:31:33Z

| Field | Value |
|-------|-------|
| Name | Jaiden henness |
| Email | jhfs20035@outlook.com |
| Gender Identity | Male |
| Preferred Stage Age | 18-21 |
| Instagram | @Jaiden Henness |
| Twitter | [@Hennessjai27577](https://x.com/Hennessjai27577) |
| Link to Work | — |
| About You | I am new to the porn industry |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60.

**Agent call:** `suspicious`. Newcomer one-liner. IG whitespace. Twitter looks auto-generated (`…27577`). Email local-part `jhfs20035` is opaque. Review, not hide.

---

## 29. Astrid Singleton — 2026-07-16

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1527167005747183647
- UTC: 2026-07-16T04:16:27Z

| Field | Value |
|-------|-------|
| Name | Astrid Singleton |
| Email | sastrid.singer08@outlook.com |
| Gender Identity | Female |
| Preferred Stage Age | 18-21 |
| Instagram | [@asteroid_sin](https://instagram.com/asteroid_sin) |
| Twitter | — |
| Link to Work | — |
| About You | Hi! My name is Astrid, my middle name is Liza which is what I would like my stage name to be. I am very new and inexperienced in this industry but I'm looking to start up. Im a natural redhead with a curvy body type, I'm shy at first but it doesn't take long for my confident bubbly and open personality to come out. I am very open to most things only having a couple boundaries and am desperately excited to start with this industry! |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.70 — **invented** “Discord message ID suggests spamming.” Ignore.

**Agent call:** `legit`. **Staff ground truth:** “this girl is keen, she's even contacted me. She wants to fly up to you for a shoot.” Newcomer female with named IG and a stage-name request is a show, not a hide.

---

## 30. Celia — 2026-07-14

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1526537155315368050
- UTC: 2026-07-14T10:33:39Z

| Field | Value |
|-------|-------|
| Name | Celia |
| Email | celiakhin@gmail.com |
| Gender Identity | Female |
| Preferred Stage Age | 22-25 |
| Instagram | [@astart_ish](https://instagram.com/astart_ish) |
| Twitter | — |
| Link to Work | https://onlyfans.com/genevievecc |
| About You | I have my own Onlyfans and I have about 1 year of experience in being filmed. |
| Agreement | Confirmed |

**Dolphin3:** `legit` 0.90.

**Agent call:** `legit`. **Staff:** “We got a female!” Named OF (handle ≠ form name — common; stage vs legal). Short bio is fine when the work link is a named OF.

**Tool features:** OF handle mismatch with form name ≠ form-mash if IG/email are consistent internally.

---

## 31. Divanshu Malhotra — 2026-07-14

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1526260183649485032
- UTC: 2026-07-13T16:13:04Z

| Field | Value |
|-------|-------|
| Name | Divanshu Malhotra |
| Email | malhotra159divanshu@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@dv.malhotra](https://instagram.com/dv.malhotra) |
| Twitter | — |
| Link to Work | — |
| About You | I'm an asian fit handsome muscular with a high stamina and 8" and 5'11 |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60 (called `@dv.malhotra` a placeholder — it matches the surname).

**Agent call:** `suspicious`. Stats one-liner + named IG. Same bucket as Kavith.

---

## 32. Francisco Sebastiao Xavier Martins — 2026-07-13

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1526215334275715073
- UTC: 2026-07-13T13:14:51Z

| Field | Value |
|-------|-------|
| Name | Francisco Sebastiao Xavier Martins |
| Email | francisco.s.x.martins@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 22-25 |
| Instagram | [@Franc_866](https://instagram.com/Franc_866) |
| Twitter | — |
| Link to Work | — |
| About You | My name is Francisco S X Martins. I am a dedicated and hardworking professional with experience as a chef and slaughterman. I take pride in my ability to work with care, precision, and attention to detail in everything I do. My passion for food, preparation, and quality workmanship has helped me develop strong skills in the kitchen and the meat industry. Outside of work, I enjoy staying active and improving myself through regular workouts at the gym. Fitness helps me maintain discipline, strength, and a positive mindset. I believe in working hard, continuing to learn, and always striving to be the best version of myself. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60.

**Agent call:** `suspicious`. Long general CV, zero adult context. Could be a real person who pasted a resume into the wrong form, or a genuine gym/chef guy. Review. Email matches the long name.

**Tool features:** long bio that never mentions sex/film/modelling/camera → `resume_paste` review flag, not auto-hide.

---

## 33. Guru — 2026-07-11

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1525385904712056893
- UTC: 2026-07-11T06:18:59Z

| Field | Value |
|-------|-------|
| Name | Guru |
| Email | gssaini123456789@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | — |
| Twitter | — |
| Link to Work | https://www.facebook.com/share/1CzWTCoh5a/?mibextid=wwXIfr |
| About You | I need Job plz help |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.80.

**Agent call:** `spam_troll` (job-seeker spam). Sequential digits in email, generic name, Facebook share, “need job please help” with no adult context.

**Tool features:** `I need (a )?job` + no adult keywords; email local-part with long digit run `123456789`; `facebook.com/share/` is not a portfolio.

---

## 34. Matthew Walker — 2026-07-05

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1523187616705544253
- UTC: 2026-07-05T04:43:47Z

| Field | Value |
|-------|-------|
| Name | Matthew Walker |
| Email | mattwalker0083@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 41-45 |
| Instagram | [@matthewwalker7811](https://instagram.com/matthewwalker7811) |
| Twitter | — |
| Link to Work | — |
| About You | Im 6'2, both arms and legs fully tattooed. I have no experience but Im a quick learner and excited to finally pursue a career in Adult Films. |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60 (called IG a placeholder — it contains the name).

**Agent call:** `suspicious` (lean legit). Physical description + 41–45 band + named IG. Newcomer is allowed.

---

## 35. Hunter Winterburn — 2026-06-28

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1520661081528467569
- UTC: 2026-06-28T05:24:14Z

| Field | Value |
|-------|-------|
| Name | Hunter Winterburn |
| Email | hunterlouiew@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | — |
| Twitter | — |
| Link to Work | http://summerfreeze101.com |
| About You | I'm a method actor, will commit to the job for days if you want. I don't stop acting until I'm told otherwise. Give me a job and I'm yours |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.80.

**Agent call:** `suspicious`. Unknown domain as “work,” no IG, needy method-actor line. Review. Not the virgin template.

**Tool features:** work host not in allow-list (OF, PH `/model/`, Model Mayhem, Scarlet Blue, Drive, LinkedIn, IG, X) → weak/unknown.

---

## 36. James Lochhead — 2026-06-19

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1517177976532373730
- UTC: 2026-06-18T14:43:37Z

| Field | Value |
|-------|-------|
| Name | James Lochhead |
| Email | jameslochhead@hotmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 31-35 |
| Instagram | @filo_ozstriker or my clothing business the_right_fit_co |
| Twitter | — |
| Link to Work | — |
| About You | My name is James, previously I went by Jason when i was in my early twenties i escorted for 2 years, did stripping and topless bartending for a year as well. Due to my profession currently as a counsellor I would like to keep some high level of discretion and privacy but would love to still be apart and filming content. There is plenty of photos of me on my clothing business website, The Right Fit Co. I also have a quite a lot of photos naturally from the photoshoots if required. I am a country guy at heart but grown in the city and played rugby league for 16years of which 6 was international. Would love to have a chat and see what we could collab together, I have always had a high interest in POV content! |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60 — wrongly treated discretion + career change as red flags.

**Agent call:** `legit`. Adult-work history + current civilian job + sport + brand is a strong real lead. Parse **two** IG handles from one field: `filo_ozstriker`, `the_right_fit_co`.

**Tool features:** “discretion” / civilian job (counsellor, teacher, etc.) is a **legit** privacy request. Split IG on ` or `.

---

## 37. Tim (Golding) — 2026-06-17

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1516651239595704330
- UTC: 2026-06-17T03:50:33Z

| Field | Value |
|-------|-------|
| Name | Tim |
| Email | timothygolding123@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@Adamhill](https://instagram.com/Adamhill) |
| Twitter | — |
| Link to Work | — |
| About You | Im a virgin and new to this |
| Agreement | Confirmed |

**Dolphin3:** `spam_troll` 0.95 (known-troll email was in the prompt).

**Agent call:** `spam_troll`. Earliest copy in this 40. Later #10/#1 switch IG to `instagram.com/?deoia=1`. Handle `@Adamhill` ≠ Tim.

**Tool features:** email allow/blocklist once labelled. Virgin one-liner. IG handle token-overlap with name fails (`adamhill` vs `tim`).

---

## 38. Jordan campbell — 2026-06-15

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1515793865041248266
- UTC: 2026-06-14T19:03:39Z

| Field | Value |
|-------|-------|
| Name | Jordan campbell |
| Email | jnewton272788@gmail.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@Imtherealjordanc](https://instagram.com/Imtherealjordanc) |
| Twitter | — |
| Link to Work | — |
| About You | People call me the gentle giant i can be quite shy and i want to be able to change for once and i feel like the porn industry will be a place where i will be able to explore |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.60.

**Agent call:** `suspicious`. Email `jnewton` vs name Jordan Campbell is a mild mismatch (could be a partner’s/old email). IG contains Jordan. Short personal bio, not virgin-troll.

---

## 39. Daniel — 2026-06-08

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1513204643281240194
- UTC: 2026-06-07T15:35:00Z

| Field | Value |
|-------|-------|
| Name | Daniel |
| Email | dan.battle@icloud.com |
| Gender Identity | Male |
| Preferred Stage Age | 26-30 |
| Instagram | [@Dan.battle](https://instagram.com/Dan.battle) |
| Twitter | — |
| Link to Work | http://dan.battle |
| About You | Want to make money and have confidence |
| Agreement | Confirmed |

**Dolphin3:** `suspicious` 0.70.

**Agent call:** `suspicious`. Thin bio. `http://dan.battle` is a handle stuffed into the URL field (same class as Violet’s prose-URL). Email + IG are consistent with the name.

**Tool features:** work URL with no valid TLD / single-label host → invalid.

---

## 40. Taylor Thurtell / Taylor Ryde — 2026-06-06

- Discord: https://discord.com/channels/1272365913261211718/1422458258886627369/1512810983087669288
- UTC: 2026-06-06T13:30:45Z

| Field | Value |
|-------|-------|
| Name | Taylor Thurtell |
| Email | neoqueentitania.cos@gmail.com |
| Gender Identity | Female |
| Preferred Stage Age | 18-21 |
| Instagram | [@neoqueentitania](https://instagram.com/neoqueentitania) |
| Twitter | [@neoqueentitania](https://x.com/neoqueentitania) |
| Link to Work | https://onlyfans.com/neoqueentitania, https://www.pornhub.com/model/taylor-ryde |
| About You | Hi! I'm Taylor Thurtell (Stage name Taylor Ryde). I'm 22 years old, though I'm constantly told I look younger and tend to cosplay characters 18-20 range. I'm a cosplayer, but I also make out-of-cosplay content focused on POVs and role-plays. I'm really looking to extend my knowledge and experience in the industry and feel ready and confident for professional studio work. I think this is the next step for my career and I really hope I can work with you. |
| Agreement | Confirmed |

**Dolphin3:** `legit` 0.95.

**Agent call:** `legit`. 22 vs 18–21 **allowed** (same pattern as Idaline). Work links include **Pornhub `/model/`** — that is a profile, **not** the Casey homepage `pornhub.com`.

**Tool features:** allow `pornhub.com/model/` and `pornhub.com/pornstar/`; hide only tube **homepages**. Split work field on commas. Email/IG/Twitter token overlap (`neoqueentitania`) is a strong legit cluster.

---

# Gold labels for a classifier (all 40)

Use **Agent call** as the training target. `hide` = spam_troll. `review` = suspicious. `show` = legit.

Use **Agent call** as the training target. `hide` = spam_troll. `review` = suspicious. `show` = legit.

| # | Gold | Staff in-channel | Strong features |
|---|------|------------------|-----------------|
| 1 Tim | hide | later “same story” / trolled | repeat email of #10, virgin one-liner, IG `?deoia=` homepage |
| 2 Johnny Varveris | show | — | coherent bio, named IG, 28 vs 31–35 **allowed** |
| 3–4 Malcolm Henry | review (one lead) | — | duplicate 56 min, typo fix, named IG, newbie bio |
| 5 Casey Cini | review | — | `pornhub.com` homepage as work, thin bio. 40 vs 26–30 **do not hide on age alone** |
| 6 Samuel Henwood | hide | — | 20 vs 50+ **extreme**, gender vs “boy”, no agreement, crude one-liner, email≠name |
| 7 Idaline Bloomingdale | show | — | named scarletblue profile, 22 vs 18–21 **allowed** |
| 8 Lukas | review / show | — | Brisbane + French + working rights; IG field is a sentence |
| 9 Marcus | review | “wtf a virgin applied” | virgin one-liner, named IG. 18 vs 22–25 **allowed** |
| 10 Tim | hide | “put a checker in place to hide this stuff” | virgin one-liner, IG `?deoia=` |
| 11 Kieran Whelan | show | — | OF-with-ex, IG matches name, Twitter `@None` = empty |
| 12 Brad McNulty | show | — | `.com.au` business email, 50 vs 46–50 match, messy specific bio |
| 13 Kaoshal | review | — | named IG + numeric OF, three-fragment bio |
| 14 Louis Vega | hide | laughed; F1/Verstappen joke; “40 awards if never talent” | 48 vs 22–25 **extreme**, 17000 videos, 40 awards, email-as-URL |
| 15–16 Jake Williams | review (one lead) | — | 4 min resubmit, 32 vs 31–35 match, `t.me/c/` private work link |
| 17 Kieran cameron | show | — | 36 vs 36–40, manhole builder, AU mobile, IG |
| 18 Joel | show | — | modelmayhem.com/id, nude stills, no film yet |
| 19 Hina himawari | review | “Was not expecting that” (female, not troll) | named IG, Indonesia → logistics not spam |
| 20 Rudolf Socha | show | — | long AU-resident bio, job, sports, 31 vs 31–35, IG |
| 21 Kavith | review | — | one-line height/ethnicity, named IG |
| 22 Tim Renkema | show | — | stand-up/actor, specific look. **Not** Golding |
| 23 Violet | review / show | — | female + measurements; messy IG/Twitter/work fields |
| 24–26 Ethan Sawyers | review (one lead) | “second time / cannot stop resending” | LinkedIn, name-matching IG. **Throttle, don’t hide** |
| 25 Claudio | review / show | — | 35 Italian, named IG, 35 vs 31–35 allowed |
| 27 Josh Maechling | show | — | French in AU, Drive portfolio, cities, actress referral |
| 28 Jaiden henness | review | — | newcomer one-liner, IG whitespace, twitter `…27577` |
| 29 Astrid Singleton | show | “this girl is keen… fly up for a shoot” | female newcomer, named IG, stage name Liza |
| 30 Celia | show | “We got a female!” | named OF, 1 year filmed |
| 31 Divanshu Malhotra | review | — | stats one-liner, named IG |
| 32 Francisco Martins | review | — | chef/slaughterman resume paste, named IG |
| 33 Guru | hide | — | “I need Job plz help”, `123456789` email, FB share |
| 34 Matthew Walker | review / show | — | 6'2 tattoos, 41–45, named IG |
| 35 Hunter Winterburn | review | — | method actor, unknown domain, no IG |
| 36 James Lochhead | show | — | escort/stripper past, counsellor, rugby, two IG handles |
| 37 Tim Golding | hide | (later Aug hide-this-stuff) | virgin one-liner, `@Adamhill` ≠ Tim, same email as #1/#10 |
| 38 Jordan campbell | review | — | short bio, email `jnewton` vs Jordan, IG has Jordan |
| 39 Daniel | review | — | money/confidence one-liner, `http://dan.battle` |
| 40 Taylor Thurtell / Ryde | show | — | 22 vs 18–21 allowed, OF + `pornhub.com/model/` |

**Gold counts (unique people, not rows):** hide ~5 (Tim Golding, Samuel, Louis, Guru) · review ~15 · show ~15. Ethan is review/throttle. Tim Golding is **three rows** (#1, #10, #37).

---

# Feature notes for the hide tool

## Age

- Form band is “preferred **stage** age,” not a legal-age field. Talent routinely pick a younger band they can pass.
- **Allowed:** one band, roughly ≤8 years (35 as 28, 22 as 18–21, 28 as 31–35, 18 as 22–25, 40 as 26–30 is the grey edge — do not hide on that alone).
- **Extreme / usable as a troll feature:** ≥15 years **or** two+ bands (48 vs 22–25, 20 vs 50+). Combine with another signal when possible.
- Parse `I'm 48` / `36yr old` / `I am 32` from About You. Compare to band midpoint. If no numeric age in the bio, **do not infer** one from the band.

## Hide with high precision (catch Tim, Samuel, Louis, Guru; keep Johnny, Idaline, Brad, Kierans, Joel, Rudolf, Astrid, Celia, Taylor, Josh, James, Tim Renkema)

1. Repeat **email** within N days → collapse to one thread. Then classify the *person*: Tim Golding = hide; Ethan / Malcolm / Jake = keep one lead.
2. Instagram host is homepage or `?deoia=` without a path handle → hide (Tim later copies).
3. Virgin / “never done this” one-liner (short bio, contains `virgin`) **and** (no real social **or** IG handle does not overlap the name) → hide (Tim; `@Adamhill` vs Tim). Marcus has `@marcusownie` → review.
4. Extreme age gap **and** (absurd claim **or** gender/bio mash **or** email-as-URL) → hide (Louis, Samuel).
5. Work link is a tube **homepage** (`pornhub.com` with no path) → review/hide (Casey). Allow `pornhub.com/model/` and `/pornstar/` (Taylor).
6. Work link is an email / `http://user@domain` / single-label host (`http://dan.battle`) / prose (`http://I don't have…`) → invalid URL, not automatic hide.
7. Agreement missing **and** another mash signal → hide (Samuel).
8. `\d{3,} videos` / `\d+ awards` as “experience” with no named production → hide (Louis).
9. Bio is only a job-beg (`I need Job plz help`) with no adult context, especially with a long digit-run in the email → hide (Guru).

## Do not hide on

- Newcomer / no film experience (Joel, Lukas, Jake, Idaline, Astrid, Matthew).
- ESL / typos / crude-but-specific bios (Brad, Kieran cameron).
- Female, overseas, or cosplay (Hina, Violet, Astrid, Celia, Taylor) — female is **priority**, not spam. Overseas without work-rights → `logistics_review`.
- Twitter/IG equal to `None`, `n/a`, a full name with spaces, a sentence, or two handles joined by `or` — parse or treat as empty.
- `?igsi=` on a named IG path; `instagram.com/handle` nested in the IG field (Josh).
- Numeric OnlyFans `/u123`, Model Mayhem `/id`, Google Drive folder, LinkedIn `/in/` (Ethan).
- Private `t.me/c/` — unusable portfolio, not a troll by itself.
- One-band age mismatch (Taylor 22 vs 18–21, Claudio 35 vs 31–35).
- Repeat submit from a real lead (Ethan). Staff want a **once-only form**, not a spam label.
- Civilian-job discretion (James / counsellor).
- First name “Tim” — only Golding’s **email** is the troll (Tim Renkema is legit).

## Dolphin3 as a second pass

- Emits `spam_troll` when the prompt includes a known-troll email (Tim #37) or staff-laugh + extreme age (Louis). Otherwise stays on `suspicious`.
- Invents fields (live IG checks, “Discord message id is spam” on Astrid, Pornhub **link** on Samuel/Louis).
- For production: **rules first**, model second, pin to JSON, “only use fields present in APPLICATION.”

