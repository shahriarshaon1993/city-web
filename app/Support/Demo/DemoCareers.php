<?php

namespace App\Support\Demo;

use App\Support\Seo\Breadcrumbs;
use Carbon\CarbonImmutable;

/**
 * Part A careers (FR-CAR-001…004, DM-JOB): job posts shaped like the later
 * JobPostResource (resources/js/types/careers.ts), the PG-15 intro blocks
 * and the page copy. Deadlines are relative to today so the demo never
 * expires: one role closed a few days ago ("Applications closed"), one more
 * than 30 days ago (410, FR-CAR-003) and one draft (404). 17.1 swaps this
 * for the JobPost model; deleted with DemoContent in 16.4.
 *
 * @phpstan-type Job array{slug: string, title: string, department: string, office: int|null, type: string, experience: string|null, salary: string|null, deadline_days: int, status: string, summary: string, description: string, responsibilities: list<string>, requirements: list<string>, benefits: list<string>}
 */
final class DemoCareers
{
    public const string TIMEZONE = 'Asia/Dhaka';

    /** FR-CAR-003: a closed job stays reachable this many days, then 410. */
    public const int GONE_AFTER_DAYS = 30;

    /** DM-JOB `employment_type` → label; the URL value uses hyphens. */
    public const array TYPE_LABELS = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship'];

    /** DM-JOB `department` (free text in 17.1); slug → label. */
    public const array DEPARTMENTS = [
        'sales' => 'Sales',
        'engineering' => 'Engineering',
        'architecture-design' => 'Architecture & design',
        'finance' => 'Finance',
        'marketing' => 'Marketing',
        'human-resources' => 'Human resources',
    ];

    /** Jobs without an office (office_id null) are based on the project sites. */
    public const string SITES_SLUG = 'project-sites';

    private const string SITES_LABEL = 'Project sites';

    private const array OFFICE_SLUGS = [1 => 'head-office', 2 => 'sales-lounge'];

    /**
     * @var list<Job>
     */
    private const array JOBS = [
        [
            'slug' => 'senior-sales-executive',
            'title' => 'Senior sales executive',
            'department' => 'sales',
            'office' => 2,
            'type' => 'full_time',
            'experience' => '3–5 years in property or banking sales',
            'salary' => '৳ 60,000–80,000 a month plus commission',
            'deadline_days' => 18,
            'status' => 'open',
            'summary' => 'Guide families and investors from their first visit to the signed agreement for our residential projects in Dhaka.',
            'description' => '<p>You will be the first person many buyers meet at Gemcon City. You show apartments, explain payment plans and approvals in plain words, and stay in touch until the agreement is signed.</p><p>You work from the Bashundhara sales lounge with a team of eight, with regular visits to our project sites.</p>',
            'responsibilities' => ['Meet walk-in and referred buyers, and show them apartments and site progress.', 'Explain prices, payment plans, approvals and handover dates accurately.', 'Follow up every enquiry assigned to you within one working day.', 'Prepare booking documents with the legal and accounts teams.', 'Keep the lead records up to date, so nobody is called twice.'],
            'requirements' => ['Three to five years of sales experience in property, banking or insurance.', 'Clear spoken and written Bangla and English.', 'Comfortable with numbers: instalments, areas and price per sft.', 'A bachelor’s degree in any subject.'],
            'benefits' => ['Monthly commission on every signed agreement.', 'Two festival bonuses a year.', 'Health cover for you and your family.', 'Mobile phone and transport allowance.'],
        ],
        [
            'slug' => 'site-engineer-civil',
            'title' => 'Site engineer (civil)',
            'department' => 'engineering',
            'office' => null,
            'type' => 'full_time',
            'experience' => '2+ years on high-rise sites',
            'salary' => null,
            'deadline_days' => 25,
            'status' => 'open',
            'summary' => 'Run day-to-day construction on one of our residential sites, from foundation work to the finishing stage.',
            'description' => '<p>You will supervise contractors on one of our ongoing projects, check work against the approved drawings and keep the weekly progress report that buyers see on the website.</p>',
            'responsibilities' => ['Supervise structural and finishing work against the drawings and specifications.', 'Check materials on delivery and keep the test records.', 'Report weekly progress with photos for the project page.', 'Keep the site safe: toolbox talks, PPE checks and incident reports.'],
            'requirements' => ['B.Sc. in civil engineering, IEB membership preferred.', 'At least two years on residential or commercial high-rise sites.', 'Working knowledge of BNBC and RAJUK requirements.', 'Willing to work on sites across Dhaka.'],
            'benefits' => ['Site allowance and lunch on site.', 'Two festival bonuses a year.', 'Health cover for you and your family.', 'Training budget for professional courses.'],
        ],
        [
            'slug' => 'architect',
            'title' => 'Architect',
            'department' => 'architecture-design',
            'office' => 1,
            'type' => 'full_time',
            'experience' => '5+ years, residential projects',
            'salary' => null,
            'deadline_days' => 10,
            'status' => 'open',
            'summary' => 'Design apartment buildings that make the most of each plot while keeping light, air and access for the neighbours.',
            'description' => '<p>You will lead the design of two or three projects at a time, from the feasibility study to the RAJUK submission and the construction drawings, working closely with landowners and our engineers.</p>',
            'responsibilities' => ['Prepare feasibility studies and massing options for new plots.', 'Develop the design through RAJUK approval and construction drawings.', 'Coordinate structural, electrical and plumbing consultants.', 'Visit sites to answer questions and check the work.'],
            'requirements' => ['B.Arch., registered with the IAB.', 'Five or more years of residential design experience.', 'Fluent in AutoCAD and one 3D tool (SketchUp, Revit or similar).', 'A portfolio of built work.'],
            'benefits' => ['Flexible start and finish times.', 'Two festival bonuses a year.', 'Health cover for you and your family.', 'Conference and study-tour budget.'],
        ],
        [
            'slug' => 'accounts-officer',
            'title' => 'Accounts officer',
            'department' => 'finance',
            'office' => 1,
            'type' => 'full_time',
            'experience' => '2–4 years',
            'salary' => '৳ 40,000–50,000 a month',
            'deadline_days' => 30,
            'status' => 'open',
            'summary' => 'Keep buyer instalments, receipts and supplier payments accurate and on time.',
            'description' => '<p>You will join the finance team at the head office and look after the instalment ledger of our residential projects, from the booking money to the final payment.</p>',
            'responsibilities' => ['Record buyer instalments and issue money receipts.', 'Reconcile bank statements every week.', 'Prepare supplier payments for approval.', 'Help with the monthly and yearly closing.'],
            'requirements' => ['Bachelor’s degree in accounting or finance; a CA or CMA course is a plus.', 'Two to four years of accounts experience.', 'Confident with Excel and an accounting package.'],
            'benefits' => ['Two festival bonuses a year.', 'Health cover for you and your family.', 'Provident fund after confirmation.'],
        ],
        [
            'slug' => 'digital-marketing-executive',
            'title' => 'Digital marketing executive',
            'department' => 'marketing',
            'office' => 1,
            'type' => 'contract',
            'experience' => '2+ years in digital campaigns',
            'salary' => null,
            'deadline_days' => 6,
            'status' => 'open',
            'summary' => 'Plan and run the search and social campaigns that bring buyers to our project pages, on a 12-month contract.',
            'description' => '<p>A 12-month contract, with the option to extend. You will run our paid search and social campaigns, report the leads they bring and work with the sales team on what happens next.</p>',
            'responsibilities' => ['Plan and run campaigns on search and social platforms.', 'Report leads, cost per lead and site visits every week.', 'Brief designers and copywriters for campaign material.', 'Keep project pages and listings up to date with the web team.'],
            'requirements' => ['Two or more years of hands-on campaign work.', 'Comfortable with analytics and spreadsheets.', 'Good written English and Bangla.'],
            'benefits' => ['Contract bonus on completion.', 'Health cover during the contract.', 'Laptop provided.'],
        ],
        [
            'slug' => 'hr-intern',
            'title' => 'Human resources intern',
            'department' => 'human-resources',
            'office' => 1,
            'type' => 'internship',
            'experience' => 'No experience needed',
            'salary' => '৳ 15,000 a month stipend',
            'deadline_days' => 14,
            'status' => 'open',
            'summary' => 'A six-month internship in recruitment, onboarding and staff records for a final-year or recent graduate.',
            'description' => '<p>Six months in our HR team at the head office. You will help with recruitment, onboarding and staff records, with a mentor who reviews your work every week.</p>',
            'responsibilities' => ['Screen CVs and schedule interviews.', 'Prepare onboarding packs for new colleagues.', 'Keep staff records and leave registers up to date.'],
            'requirements' => ['Final-year student or recent graduate in business or HR.', 'Organised, discreet and friendly.', 'Available for six months, full days.'],
            'benefits' => ['Monthly stipend.', 'Certificate and reference letter.', 'A chance of a permanent role.'],
        ],
        [
            'slug' => 'quantity-surveyor',
            'title' => 'Quantity surveyor',
            'department' => 'engineering',
            'office' => 1,
            'type' => 'full_time',
            'experience' => '3+ years',
            'salary' => null,
            'deadline_days' => -5,
            'status' => 'open',
            'summary' => 'Prepare cost estimates and bills of quantities for new projects, and check contractor bills.',
            'description' => '<p>You will prepare estimates and bills of quantities for new projects, and check contractor bills against the work done on site.</p>',
            'responsibilities' => ['Prepare estimates and bills of quantities.', 'Measure work on site and check contractor bills.', 'Track material use against the budget.'],
            'requirements' => ['B.Sc. or diploma in civil engineering.', 'Three or more years of quantity surveying.'],
            'benefits' => ['Two festival bonuses a year.', 'Health cover for you and your family.'],
        ],
        [
            'slug' => 'customer-relations-manager',
            'title' => 'Customer relations manager',
            'department' => 'sales',
            'office' => 1,
            'type' => 'full_time',
            'experience' => '5+ years',
            'salary' => null,
            'deadline_days' => -45,
            'status' => 'open',
            'summary' => 'Lead the after-sales team that looks after buyers from booking to handover.',
            'description' => '<p>You will lead the after-sales team.</p>',
            'responsibilities' => ['Lead the after-sales team.'],
            'requirements' => ['Five or more years in customer service.'],
            'benefits' => ['Health cover for you and your family.'],
        ],
        [
            'slug' => 'project-manager',
            'title' => 'Project manager',
            'department' => 'engineering',
            'office' => 1,
            'type' => 'full_time',
            'experience' => '8+ years',
            'salary' => null,
            'deadline_days' => 40,
            'status' => 'draft',
            'summary' => 'Draft post: never public.',
            'description' => '<p>Draft.</p>',
            'responsibilities' => [],
            'requirements' => [],
            'benefits' => [],
        ],
    ];

    /**
     * Every public job with its computed state: open until the deadline
     * has passed, then closed from 00:05 Dhaka time the next day (the
     * scheduler of FR-CAR-003); drafts are left out. Every demo post is
     * `open` or `draft`; a post closed by hand arrives with 17.1.
     *
     * @return list<array<string, mixed>>
     */
    public static function jobs(): array
    {
        $today = self::today();
        $jobs = [];

        foreach (self::JOBS as $index => $job) {
            if ($job['status'] === 'draft') {
                continue;
            }

            $deadline = $today->addDays($job['deadline_days']);
            $closedAt = $deadline->addDay()->setTime(0, 5);
            $open = CarbonImmutable::now(self::TIMEZONE)->lessThan($closedAt);

            $jobs[] = [...$job, 'id' => $index + 1, 'deadline' => $deadline, 'closed_at' => $open ? null : $closedAt];
        }

        return $jobs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::jobs(), fn (array $job): bool => $job['closed_at'] === null));
    }

    /**
     * A public job by slug; null for an unknown slug or a draft.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::jobs() as $job) {
            if ($job['slug'] === $slug) {
                return $job;
            }
        }

        return null;
    }

    /**
     * FR-CAR-003: closed more than 30 days ago → 410 Gone.
     *
     * @param  array<string, mixed>  $job
     */
    public static function isGone(array $job): bool
    {
        return $job['closed_at'] !== null
            && CarbonImmutable::now(self::TIMEZONE)->greaterThan($job['closed_at']->addDays(self::GONE_AFTER_DAYS));
    }

    /**
     * Slug of the job's location facet (an office, or the project sites).
     *
     * @param  array<string, mixed>  $job
     */
    public static function locationSlug(array $job): string
    {
        return $job['office'] === null ? self::SITES_SLUG : self::OFFICE_SLUGS[$job['office']];
    }

    /**
     * Location facet options: the offices, then the project sites.
     *
     * @return array<string, string>
     */
    public static function locations(): array
    {
        $options = [];

        foreach (DemoContent::offices() as $office) {
            $id = (int) $office['id'];

            if (isset(self::OFFICE_SLUGS[$id])) {
                $options[self::OFFICE_SLUGS[$id]] = (string) $office['name'];
            }
        }

        return [...$options, self::SITES_SLUG => self::SITES_LABEL];
    }

    /**
     * JobRowResource (types/careers.ts): one PG-15 list row.
     *
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function row(array $job): array
    {
        return [
            'id' => $job['id'],
            'slug' => $job['slug'],
            'url' => '/careers/'.$job['slug'],
            'title' => $job['title'],
            'department' => self::DEPARTMENTS[$job['department']],
            'location' => self::locations()[self::locationSlug($job)],
            'employment_type' => $job['type'],
            'employment_type_label' => self::TYPE_LABELS[$job['type']],
            'deadline' => $job['deadline']->toDateString(),
            'closes_label' => self::closesLabel($job),
        ];
    }

    /**
     * JobPostResource (types/careers.ts): PG-16. Salary only when set (FR-CAR-001).
     *
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function detail(array $job): array
    {
        $list = fn (array $items): string => $items === [] ? '' : '<ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $items)).'</ul>';

        return [
            ...self::row($job),
            'summary' => $job['summary'],
            'experience' => $job['experience'],
            'salary_text' => $job['salary'],
            'is_open' => $job['closed_at'] === null,
            'closed_at' => $job['closed_at']?->toDateString(),
            'sections' => array_values(array_filter([
                ['id' => 'about', 'heading' => self::copy()['about_heading'], 'body' => $job['description']],
                ['id' => 'responsibilities', 'heading' => self::copy()['responsibilities_heading'], 'body' => $list($job['responsibilities'])],
                ['id' => 'requirements', 'heading' => self::copy()['requirements_heading'], 'body' => $list($job['requirements'])],
                ['id' => 'benefits', 'heading' => self::copy()['benefits_heading'], 'body' => $list($job['benefits'])],
            ], fn (array $section): bool => $section['body'] !== '')),
            'breadcrumbs' => Breadcrumbs::to($job['title'], [[self::copy()['title'], '/careers']]),
        ];
    }

    /**
     * LAY-CAREER-DETAIL "deadline countdown text", worked out on the server
     * so SSR and the browser agree.
     *
     * @param  array<string, mixed>  $job
     */
    public static function closesLabel(array $job): ?string
    {
        if ($job['closed_at'] !== null) {
            return null;
        }

        $days = (int) self::today()->diffInDays($job['deadline']);

        return match (true) {
            $days === 0 => 'Closes today',
            $days === 1 => 'Closes tomorrow',
            default => "Closes in {$days} days",
        };
    }

    /**
     * PG-15 builder intro (LAY-CAREERS): page_header → image_text (culture)
     * → feature_cards (benefits). The open-roles list follows from the template.
     *
     * @return list<array<string, mixed>>
     */
    public static function introBlocks(): array
    {
        return [
            DemoBlocks::block(1, 'page_header', [
                'title' => self::copy()['title'],
                'intro' => 'Engineers, architects, sales advisors and finance people who like to finish what they start. Join the team that builds homes Dhaka families keep for generations.',
                'image' => null,
                'breadcrumbs' => Breadcrumbs::to(self::copy()['title']),
            ], spacing: 'none'),
            DemoBlocks::block(2, 'image_text', [
                'heading' => 'Work you can point at',
                'body' => '<p>Every project we take on ends with a building that families move into. Our teams see their work go from drawings to keys, often within two years, and stay with it after handover.</p><p>We keep teams small, give people real responsibility early and promote from within: most of our project managers started as site engineers.</p>',
                'image' => DemoMedia::image('progress/progress-02'),
                'image_side' => 'right',
                'cta' => null,
                'reveal' => 'mask',
            ], spacing: 'lg'),
            DemoBlocks::block(3, 'feature_cards', [
                'heading' => 'What we offer',
                'columns' => 3,
                'tilt' => false,
                'items' => [
                    ['icon' => 'banknote', 'image' => null, 'title' => 'Fair, on-time pay', 'text' => 'Salaries paid on the first working day, two festival bonuses and a provident fund after confirmation.', 'link' => null],
                    ['icon' => 'shield-check', 'image' => null, 'title' => 'Health cover', 'text' => 'Medical cover for you, your spouse and your children from your first month.', 'link' => null],
                    ['icon' => 'layers', 'image' => null, 'title' => 'Room to grow', 'text' => 'A training budget for every team, and promotions from within before we hire from outside.', 'link' => null],
                ],
            ], background: 'muted', spacing: 'lg'),
        ];
    }

    /**
     * Labels and copy of PG-15/16 (CMS-000a: copy comes from props).
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'title' => 'Careers at Gemcon City',
            'description' => 'Open roles at Gemcon City Limited in sales, engineering, architecture, finance and marketing. Apply online with your CV.',
            'roles_heading' => 'Open roles',
            'roles_count' => '{n} open roles',
            'roles_count_one' => '1 open role',
            'all_label' => 'All',
            'department_label' => 'Department',
            'location_label' => 'Location',
            'type_label' => 'Type',
            'apply_by_label' => 'Apply by',
            'no_match_title' => 'No roles match these filters',
            'no_match_body' => 'Try another department or location, or see every open role.',
            'no_match_action' => 'Show all roles',
            'no_openings_title' => 'No openings right now',
            'no_openings_body' => 'We have no open roles at the moment, but we are always glad to meet good people. Send us your CV and we will contact you when a role fits.',
            'cv_heading' => 'Send us your CV',
            'cv_intro' => 'Tell us a little about yourself and attach your CV. The HR team reads every application and keeps it on file for twelve months.',
            'about_heading' => 'About the role',
            'responsibilities_heading' => 'What you will do',
            'requirements_heading' => 'What you bring',
            'benefits_heading' => 'What we offer',
            'summary_heading' => 'Role at a glance',
            'department_term' => 'Department',
            'location_term' => 'Location',
            'type_term' => 'Employment type',
            'experience_term' => 'Experience',
            'salary_term' => 'Salary',
            'deadline_term' => 'Deadline',
            'apply_now' => 'Apply now',
            'apply_heading' => 'Apply for this role',
            'apply_intro' => 'Fill in your details and attach your CV. You will hear from the HR team within ten working days if your application is shortlisted.',
            'apply_side' => '<p>Only the HR team and the hiring manager see your application. Questions about a role? Email <a href="mailto:careers@gemconcity.com">careers@gemconcity.com</a>.</p>',
            'closed_title' => 'Applications closed',
            'closed_text' => 'This role stopped taking applications on {date}.',
            'closed_action' => 'See open roles',
            'other_roles_heading' => 'Other open roles',
            'success_title' => 'Thank you, we have your application',
            'success_text' => 'The HR team reads every application. If yours is shortlisted, we will contact you within ten working days.',
            'success_toast' => 'Your application has been sent.',
            'cv_success_title' => 'Thank you, we have your CV',
            'cv_success_text' => 'We keep it on file for twelve months and will contact you when a role fits.',
        ];
    }

    private static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }
}
