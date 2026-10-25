# Careers (REQUIREMENT §7.7, DM-JOB, DM-APP)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.7 Careers (`FR-CAR`)

| ID | Requirement |
|---|---|
| FR-CAR-001 | Job posts: title, slug, department, location (office), employment type (`full_time`, `part_time`, `contract`, `internship`), experience (text), salary (optional text; shown only if set), description, responsibilities, requirements, benefits (rich text), deadline (date), status (`draft`, `open`, `closed`), SEO. |
| FR-CAR-002 | Apply form: name*, phone*, email*, current position, years of experience (0–50), expected salary, LinkedIn URL, CV* (PDF/DOC/DOCX ≤ 5 MB, private disk), cover letter (≤ 3000), consent*. Same spam rules as FR-LEAD-003. |
| FR-CAR-003 | Closing: job auto-closes after its deadline (scheduler, 00:05 Asia/Dhaka); closed jobs show "Applications closed" for 30 days, then return 410 Gone. |
| FR-CAR-004 | General CV drop on PG-15 when no roles are open (job_post_id null). |
| FR-CAR-005 | Applications admin: table, status (`new`, `shortlisted`, `interview`, `rejected`, `hired`), notes, CV download (authorised), export. |

- **AC-CAR-01** Given a CV of 6 MB, When submitted, Then the form shows "CV must be 5 MB or smaller" next to the file field and nothing is stored.

**Permissions:** `careers.*` (Admin, HR via custom role created by Super Admin).

## Data model (REQUIREMENT §11.2)

**DM-JOB `job_posts`** (SD): title, slug unique, department varchar(60), office_id FK null, employment_type varchar(12), experience varchar(80) null, salary_text varchar(80) null, salary_min_bdt, salary_max_bdt bigint null, description, responsibilities, requirements, benefits (longtext), deadline date, status varchar(8), closed_at timestamp null, published_at.

**DM-APP `job_applications`** (SD): job_post_id FK null, name, phone, email, current_position null, years_experience tinyint null, expected_salary varchar(40) null, linkedin_url null, cv_path varchar(255) (private disk), cover_letter text null, status varchar(12), notes text null, consent_text text, ip_hash, created_at.
