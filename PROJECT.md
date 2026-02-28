# civi.me — Civic Engagement Platform for Hawaii

**Domain:** https://civi.me
**Type:** Free community resource (independent project)
**Status:** Concept / Design Phase
**Started:** 2026-02-27
**Founder:** Patrick Gartside (Honolulu, HI)

---

## Vision

civi.me makes Hawaii's government **functionally accessible** — not just technically public. Government information is scattered across disconnected databases, buried in PDFs, and presented in ways that shut out everyday residents. civi.me aggregates, simplifies, and activates that information so people can actually participate.

As Kenneth Peck wrote in Civil Beat (Jan 2026): *"Influence becomes difficult to trace not because documents are hidden, but because they are inaccessible in practice."*

That's the problem civi.me solves — with technology tools built for the people of Hawaii.

---

## Scope & Phasing

### Phase 1: Hawaii (State + Counties)
- All four counties: Honolulu, Maui, Hawai'i, Kaua'i
- State government — boards, commissions, legislature
- Focus on entities subject to **Sunshine Law** (HRS Chapter 92)
- Engage youth and diverse communities
- Support all **15 languages recognized by the Office of Language Access**

### Future Phases
- Federal representation for Hawaii
- Expansion to other states
- Cross-state civic tools

---

## The Problem (from Civil Beat, Jan 2026)

Hawaii's transparency crisis isn't about secrecy — it's about **functional inaccessibility**:

1. **Fragmented data** — Lobbying records, campaign finance, bills, and meeting agendas live on separate, disconnected government websites. Answering "Who is lobbying on housing bills?" requires navigating three platforms and manually cross-referencing PDFs.

2. **No proactive communication** — The state posts agendas but has no notification system. No alerts for bills, hearings, or budget decisions. If you don't check the right website on the right day, you miss your window.

3. **Youth disengagement** — Young residents don't engage because the systems weren't built for them. No mobile-first experiences, no plain-language explanations, no cultural relevance.

4. **Growing complexity** — Mainland lobbying firms with sophisticated advocacy methods are entering Hawaii politics, widening the gap between professional influence and public understanding.

5. **Static data formats** — Information published as unsearchable PDFs and spreadsheets instead of queryable, linkable data.

---

## Relationship to Access100

civi.me is **independent** from Access100. Different missions, shared infrastructure.

| | civi.me | Access100 |
|--|---------|-----------|
| **Mission** | Civic engagement for all residents | Disability accessibility services |
| **Focus** | Information access, notifications, civic tools | Hosting/moderating meetings, tech support, accessibility audits |
| **Audience** | General public, youth, diverse communities | Government agencies, organizations needing a11y |
| **Revenue** | Free resource (grant-funded) | Service contracts (QCRP, consulting) |
| **Role** | Public-facing consumer site | Data API provider + specialized services |

**Access100.app becomes an API.** The meetings database, council directory, agenda scraping, and AI summaries live at Access100 as a REST API. civi.me consumes that API to power its meetings calendar and notification features. This means:

- Access100 owns the data pipeline (scraping, storage, summarization)
- civi.me owns the user experience (display, notifications, engagement)
- Other civic projects could also consume the Access100 API in the future
- Access100 can still serve its own disability-focused views of the same data

---

## Architecture

### Two-System Design

```
┌──────────────────────────────────────────────────────────┐
│  civi.me (Docker WordPress)                              │
│  ┌────────────┐ ┌────────────┐ ┌──────────────────────┐  │
│  │  WP Theme  │ │  Plugins   │ │  Content / Pages     │  │
│  │  (custom)  │ │  (civime-*)│ │  Mission, guides,    │  │
│  │            │ │            │ │  events, resources   │  │
│  └────────────┘ └─────┬──────┘ └──────────────────────┘  │
│                       │                                   │
│          REST API calls (meetings, councils, agendas)     │
│                       │                                   │
└───────────────────────┼──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│  Access100.app (API Backend)                             │
│  ┌────────────┐ ┌────────────┐ ┌──────────────────────┐  │
│  │  Scraper   │ │  REST API  │ │  AI Summary          │  │
│  │  eHawaii,  │ │  /meetings │ │  Pipeline            │  │
│  │  counties  │ │  /councils │ │  (agenda → summary)  │  │
│  └────────────┘ │  /agendas  │ └──────────────────────┘  │
│                 └────────────┘                            │
│  Database: councils, meetings, attachments, summaries    │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│  Civic Phone System (Twilio)                             │
│  ┌────────────┐ ┌────────────┐ ┌──────────────────────┐  │
│  │  Inbound   │ │  Outbound  │ │  SMS                 │  │
│  │  Voice     │ │  Calls     │ │  Alerts              │  │
│  │  Line      │ │  Reminders │ │  Opt-in only         │  │
│  └────────────┘ └────────────┘ └──────────────────────┘  │
│  Cloned from SILC survey system, adapted for civic use   │
└──────────────────────────────────────────────────────────┘
```

### civi.me — Docker WordPress

The main public site. Features delivered as **WordPress plugins**.

- Docker Compose: WordPress + MySQL + Nginx
- Custom theme designed with UI UX Pro Max
- Plugin-per-feature architecture
- Open source on GitHub from day one

### Access100.app — API Backend

The data engine. Access100 already scrapes and stores government meeting data. We formalize this as a **REST API** that civi.me (and potentially other projects) can consume.

**API endpoints to build:**
- `GET /api/v1/meetings` — List meetings (filter by date, council, keyword)
- `GET /api/v1/meetings/{id}` — Meeting detail with agenda, attachments, summary
- `GET /api/v1/councils` — List all councils/boards/commissions
- `GET /api/v1/councils/{id}/meetings` — Meetings for a specific council
- `GET /api/v1/meetings/{id}/summary` — AI-generated plain-language summary

**What needs to be fixed/built at Access100 first:**
- AI summary pipeline (currently broken — `summary_text` never populates)
- Notification delivery system (subscriptions save but emails never send)
- Proper API layer on top of existing MySQL tables
- API key auth for external consumers
- County meeting data (currently state-only)

### Civic Phone System

Cloned from the SILC phone survey system (`~/dev/GartsideOS/scripts/survey-server.py`), adapted for civic engagement.

- Dedicated civi.me phone number (Twilio)
- **Inbound:** Community voice line for opinions, feedback, community pulse
- **Outbound:** Meeting reminders, hearing alerts, deadline notifications
- **SMS:** Opt-in text alerts for subscribed councils/topics
- **Surveys:** Community pulse surveys on civic issues
- All contacts strictly **opt-in only**
- Privacy-by-design (inherited from SILC system)
- Multilingual (5 languages ready, expandable to all 15 OLA languages)

### Design System

Use **UI UX Pro Max** (`~/dev/ui-ux-pro-max`) for design decisions:
- Style: Accessible & Ethical (#8) or Inclusive Design (#17) as base
- Culturally appropriate for Hawaii — not a mainland template
- WCAG 2.1 AA minimum
- Mobile-first, clean, easy to navigate
- Plain typography, clear hierarchy, no clutter

---

## Launch Plan

### Step 1: Build the Website
Stand up civi.me as a WordPress site with compelling content about the project's mission, the problem it solves, and how people can get involved. This is the public face before any tools go live.

**Landing page content:**
- What civi.me is and why it exists
- The problem (functional inaccessibility of government info in Hawaii)
- What's coming (preview of tools)
- How to get involved (volunteer, ambassador program, partnerships)
- About / team

### Step 1.5: Fix Access100 API
Before civi.me can consume meeting data, fix the broken pieces at Access100:
- Build a REST API layer on top of the existing meetings database
- Fix the AI summary pipeline so `summary_text` actually populates
- Fix notification delivery (email infrastructure)
- Add county meeting data sources

### Step 2: Meetings Calendar (First Feature)
Build `civime-meetings` WordPress plugin that consumes the Access100 API. This is the first real tool people can use.

**What the plugin does:**
- Fetches meetings from Access100 API (not direct DB access)
- Calendar display with filters by council, date, keyword, county/state
- Meeting detail pages with AI summaries, agendas, attachments
- Subscribe to councils for email + SMS notifications (opt-in)
- ICS calendar feed export
- All Sunshine Law entities (not just disability-related)
- WCAG 2.1 AA compliant

### Step 3: Civic Communications System
Clone and adapt the SILC phone survey system for civic engagement. Explore uses:
- **Outbound calls** — Notify people about upcoming meetings, hearings, deadlines
- **SMS alerts** — Text-based meeting reminders and bill updates
- **Community voice line** — Let people call in to share opinions on public issues
- **Survey deployment** — Run community needs surveys on civic topics
- Dedicated civi.me phone number (separate from SILC)

### Step 4: Community Engagement Programs
- **Letter Writing Parties** — Host events where people come together to write testimony, letters to representatives, or public comments. civi.me provides the tools (templates, contact info, submission guides) and the community brings the energy.
- **Youth Ambassadors** — Volunteer program, explore partnerships with UH clubs, high schools, and service-learning programs
- **University presence** — Set up at campuses, partner with student government, civic-minded clubs

---

## Feature Roadmap (Full)

### Tier 1: Launch Features
- Project website with mission, content, calls to action
- Meetings calendar with working notifications (email + SMS)
- AI-generated meeting summaries

### Tier 2: Civic Tools
- **"Who Represents Me?"** — Enter your address, see your reps, senator, council member, neighborhood board, and relevant boards/commissions. **Built client-side for privacy** — no addresses sent to a server.
- **Civic Phone System** — Outbound calls, SMS alerts, community voice line (cloned from SILC survey system)
- **Issue Explorer** — Pick a topic (housing, environment, education, transit). See active bills, upcoming hearings, relevant boards, recent decisions — all in one view.

### Tier 3: Information Simplification
- **Plain-Language Bill Summaries** — AI-generated summaries of legislation in accessible language.
- **"How Do I...?" Guides** — Step-by-step: How do I testify? Find my neighborhood board? Request a public record?
- **Government Glossary** — Searchable dictionary of terms, acronyms, and processes.
- **Lobbying + Legislation Linker** — Connect lobbying filings to the bills they target (the reform Peck advocates, citing Montana and Colorado as models).

### Tier 4: Legislative Session Tools (2027)
- **Bill Tracker** — Follow specific bills or topics. Alerts on hearings, amendments, votes.
- **Testimony Helper** — Templates and submission tools for public testimony.
- **Legislative Dashboard** — Session overview: what's moving, what's dying, who's voting how.

### Tier 5: Youth & Community
- **Youth Ambassador Program** — Volunteer civic engagement champions
- **Civics for Hawaii** — Culturally grounded civic education content
- **Letter Writing Events** — Tools and templates for community letter-writing parties
- **Multilingual Access** — Full OLA language support (see Language section below).

---

## Youth & Community Engagement Strategy

### Youth Ambassadors (Volunteer)
- Recruit high school and college students as civi.me ambassadors
- They promote the platform, provide feedback, and help shape features for their generation
- Explore service-learning credit partnerships with schools
- Start with UH Manoa, community colleges, then expand

### University Partnerships
- Partner with student government associations
- Set up at civic-minded university clubs
- Guest lectures / workshops on civic tech
- Potential capstone project partnerships (CS, poli-sci students build features)

### Letter Writing Parties
- **Ambassador-led** — Youth ambassadors and community volunteers host the events; civi.me provides the toolkit and support
- **civi.me-hosted events** — For high-profile issues: upcoming council decisions, public comment periods, etc. Potentially evolving into **community town halls**
- **Party Kit includes:** Letter templates, representative contact info, submission guides, talking points (neutral/factual), event hosting checklist
- **"Fund the pizza"** — Small event grants or sponsorships to cover food/venue for ambassadors hosting parties
- Partner with libraries, community centers, churches, university common spaces
- Virtual option for neighbor islands

### Reaching Diverse Communities
- Engage people in their own languages (OLA languages)
- Partner with cultural organizations (Filipino Community Center, Japanese Cultural Center, Marshallese community groups, etc.)
- Community-based outreach, not just digital
- Phone system ensures access for people without smartphones or internet

---

## Language Access

### Office of Language Access Recognized Languages (15)

civi.me will support all languages recognized by Hawaii's OLA:

| Language | Script | Community |
|----------|--------|-----------|
| English | Latin | Default |
| Hawaiian (ʻŌlelo Hawaiʻi) | Latin | Native Hawaiian |
| Cantonese (廣東話) | Traditional Chinese | Chinese community |
| Mandarin (国语/普通话) | Simplified Chinese | Chinese community |
| Chuukese | Latin | Micronesian community |
| Ilocano | Latin | Filipino community |
| Japanese (日本語) | Japanese | Japanese community |
| Korean (한국어) | Korean | Korean community |
| Marshallese (Kajin Majôl) | Latin | Micronesian community |
| Samoan (Gagana Samoa) | Latin | Pacific Islander community |
| Spanish (Español) | Latin | Hispanic/Latino community |
| Tagalog | Latin | Filipino community |
| Thai (ภาษาไทย) | Thai | Thai community |
| Tongan | Latin | Pacific Islander community |
| Vietnamese (Tiếng Việt) | Latin | Vietnamese community |
| Visayan/Cebuano | Latin | Filipino community |

**Implementation approach:** Start with English, then add languages by community size and volunteer availability. The phone survey system already supports English, Spanish, Mandarin, Japanese, and Korean.

---

## Principles

### Political Neutrality
civi.me is **not partisan, not advocacy, not activist.** It is infrastructure for participation.

- The platform does not endorse candidates, parties, or positions on issues
- Content presents facts: what a bill does, who supports/opposes it, what the arguments are — without taking a side
- Letter writing parties provide templates and contact info, not talking points that push a position
- The goal is to bring people **together across the political and issue-opinion spectrum** by giving everyone equal access to information and tools
- Sponsorships and partnerships only from nonpartisan organizations
- civi.me empowers people to form their own opinions and take their own action

### Cultural Sensitivity
- **Not everyone recognizes the State of Hawaii** — Native Hawaiian sovereignty is a living issue. civi.me is mindful of language around "government" and does not assume all residents identify with or feel represented by the state structure.
- **Inclusive framing** — Use language like "public decisions that affect your community" rather than "your government." Frame civic engagement as community participation, not just government interaction.
- **Reflect Hawaii values** — Community (ohana), stewardship (malama), inclusion, respect. Design and tone should feel local, not corporate or mainland-imported.

### Design Identity
- Top-notch, professional design — clean, plain, easy to navigate
- Not cluttered, not over-designed
- Respectful of the place and people
- Mobile-first — many users will access on phones
- Accessible — WCAG 2.1 AA is the floor, not the ceiling

### Open Source
- Codebase public on GitHub from day one
- Open to community contributions
- Transparent development process
- License: TBD (likely MIT or GPL)

---

## Civic Tech Landscape & Partnerships

### Existing tools to learn from or partner with:

| Tool | What It Does | civi.me Opportunity |
|------|-------------|---------------------|
| **OpenStates** | National bill tracking | Go deeper on Hawaii; use their API |
| **GovTrack** | Federal bill tracking | Cover federal Hawaii delegation |
| **Code for America** | Civic tech movement | Join the brigade network |
| **Resistbot** | Text-based civic action | Integrate or complement |
| **Civil Beat** | Hawaii journalism + Digital Democracy | Data partnership potential |
| **Common Cause Hawaii** | Government accountability | Content + advocacy partnership |
| **League of Women Voters HI** | Voter education | Co-promote, share resources |

### Differentiation

Most civic tech tools are **national and generic**. civi.me is:
- **Hawaii-specific** — Built for Hawaii's unique political landscape, culture, and communities
- **Multilingual by design** — 15 OLA languages, not English-only
- **Culturally grounded** — Designed by and for Hawaii residents
- **Action-oriented** — Not just information, but tools to participate
- **Phone-accessible** — Voice-based civic engagement (no smartphone required)

---

## Sustainability Model

- **Free to all users** — No paywalls, no premium tiers, ever
- **Grant funding** — Knight Foundation, Mozilla, Hawaii Community Foundation, government modernization grants
- **Fiscal sponsorship** — Could operate under an existing 501(c)(3) initially
- **Volunteer development** — Open-source, community-contributed
- **Sponsorships** — From aligned nonpartisan organizations (not political entities)
- **Government partnerships** — Potential contracts to improve government communication tools

---

## Technical Architecture

See **[ARCHITECTURE.md](ARCHITECTURE.md)** for the full technical breakdown including:
- Two-system diagram (civi.me WordPress + Access100 API)
- Notification subscription flow (step-by-step with wireframe)
- All API endpoints (meetings, councils, subscriptions)
- Database schema changes needed at Access100
- AI summary pipeline
- Change detection + notification delivery pipeline
- Authentication & security model
- What gets built tonight (Part 1 + Part 2 checklist)

### High-Level Data Flow
```
Government Sites                Access100 API              civi.me
─────────────────               ─────────────              ───────
calendar.ehawaii.gov  ──scrape──▶ meetings DB ──REST API──▶ WP plugin display
county council sites  ──scrape──▶ councils DB              user subscription UI
                                  AI summaries  ──notify──▶ email/SMS delivery
                                  subscriptions            manage preferences UI
```

### GitHub (Open Source)
```
github.com/civime/
├── civime-site/        # WordPress theme + plugins
├── civime-phone/       # Civic phone system (forked from SILC survey)
└── civime-docs/        # Documentation, contribution guides
```

---

## Existing Code Inventory

### Access100 Meetings System (to become API backend)
**Location:** `~/dev/Access100/app website/public_html/meetings/`

| File | Purpose | Disposition |
|------|---------|-------------|
| `index.php` | Calendar listing — filters, search, grouped by date | Keep for Access100 UI; API layer wraps same queries |
| `detail.php` | Meeting detail — agenda, attachments, AI summary | Summary broken; fix AI pipeline first |
| `subscribe.php` | Email subscription signup — council selection | Delivery broken; rebuild in notifications system |
| `ics.php` | iCalendar export for individual meetings | Port logic to API endpoint |
| `style.css` | WCAG AA stylesheet, dark/light mode | Reference for civi.me theme, don't copy directly |
| `key_councils.php` | Disability-keyword council highlighting | Access100-specific; civi.me won't use this |

**Database tables:** `councils`, `meetings`, `users`, `subscriptions`, `attachments`

**Work needed to become API:**
1. Build REST API layer (PHP or lightweight framework) on top of existing MySQL
2. Fix AI summary pipeline (`summary_text` never populates — need scraper → LLM → DB pipeline)
3. Build email/SMS delivery system for subscription notifications
4. Move hardcoded DB credentials to environment config
5. Add API key authentication for external consumers
6. Add county meeting data sources (currently state-only from eHawaii)

### SILC Phone Survey System (to clone for civic use)
**Location:** `~/dev/GartsideOS/scripts/survey-server.py` (1,235 lines)

| Component | What Exists | What to Adapt |
|-----------|-------------|---------------|
| IVR engine | Full state machine, DTMF + speech | Reuse as-is |
| Survey flow | SILC community needs questions | Replace with civic engagement flows |
| Languages | en, es, zh, ja, ko | Expand to all 15 OLA languages |
| Privacy | Anonymous, no phone storage | Keep — add opt-in contact list for outbound |
| Accessibility | TTY, speech timeout, cognitive pacing | Keep all |
| Direction | Inbound only | Add outbound calls + SMS |
| Hosting | Port 8091 on GartsideOS server | Separate deployment for civi.me |

---

## Decisions Made

| Decision | Answer |
|----------|--------|
| Geographic scope | Hawaii first — state + all 4 counties |
| Primary audience | Everyday residents, with youth focus |
| Organizational independence | civi.me is independent from Access100 |
| Architecture | Docker WordPress (civi.me) + Access100 REST API backend |
| Access100 role | Data pipeline + API + notification delivery engine |
| Hosting | Home server (Docker), will migrate as needed |
| Email delivery | SendGrid — `alert@civi.me` and `alert@access100.app` |
| Political stance | Strictly neutral — not partisan, not advocacy |
| Contact list | Opt-in only, always (double opt-in confirmation) |
| Open source | Yes, public on GitHub from day one |
| Youth ambassadors | Volunteer program |
| Letter writing parties | Ambassador-led with civi.me toolkits + civi.me-hosted events |
| First feature | Project website, then meetings calendar with notifications |
| Legislative tools | Not this year — target 2027 session |

## Open Questions

1. **Phone number** — Get a dedicated civi.me Twilio number? Vanity number?
2. **First partnerships** — Code for America brigade? Civil Beat data partnership? UH student orgs?
3. **Organizational structure** — Fiscally sponsored under an existing 501(c)(3)? New nonprofit? Under Gartside LLC to start?
4. **GitHub org name** — `civime`? `civi-me`? `civime-hawaii`?
5. **Town halls** — When does civi.me start hosting its own events vs. just enabling ambassadors?
6. **Funding** — First grant target? Draft concept paper for Knight Foundation or Hawaii Community Foundation?
7. **Domain DNS** — Where is civi.me registered? Point A record to home server IP.

---

## References

- [Civil Beat: Hawai'i's Transparency Problem Isn't Just About Secrecy](https://www.civilbeat.org/2026/01/hawai%CA%BBis-transparency-problem-isnt-just-about-secrecy/) (Jan 2026)
- [Access100 Meetings Calendar](https://access100.app/meetings/)
- [Hawaii Office of Language Access](https://health.hawaii.gov/ola/)
- [Hawaii Sunshine Law (HRS Chapter 92)](https://www.capitol.hawaii.gov/hrscurrent/Vol02_Ch0046-0115/HRS0092/)
- Access100 meetings codebase: `~/dev/Access100/app website/public_html/meetings/`
- SILC phone survey system: `~/dev/GartsideOS/scripts/survey-server.py`
- UI UX Pro Max design system: `~/dev/ui-ux-pro-max/`
