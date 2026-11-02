<?php

namespace App\Support\Demo;

/**
 * Part A team members (FR-SOC-004, TeamMemberResource shape of
 * `TeamMember` in resources/js/types/blocks.ts) for the `team_grid` block of
 * 6.2 and the leadership page of 6.3. Fictional people and placeholder
 * portraits (six photos, reused across groups). Deleted with DemoContent in 16.4.
 */
final class DemoTeam
{
    /**
     * name, role, bio, portrait, LinkedIn.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string|null, 3: int, 4: bool}>>
     */
    private const array MEMBERS = [
        'leadership' => [
            ['Rashed Karim', 'Chairman', 'Founded Gemcon City after two decades in construction finance. He still reviews every land purchase himself and signs each handover certificate.', 1, true],
            ['Farhana Rahman', 'Managing Director', 'Leads planning, sales and after-sales. Trained as an architect, she insists that every plan is walked on site before it is launched.', 2, true],
            ['Tanvir Hossain', 'Director, Projects', 'Oversees engineering and construction across all active sites, with a focus on tested materials and published inspection stages.', 3, true],
        ],
        'management' => [
            ['Nasrin Akter', 'Head of Legal', null, 4, true],
            ['Imran Chowdhury', 'Chief Engineer', null, 5, true],
            ['Sadia Islam', 'Head of Sales', null, 6, false],
            ['Mahmud Alam', 'Head of Finance', null, 1, true],
        ],
        'team' => [
            ['Rubina Sultana', 'Customer Care Lead', null, 2, false],
            ['Arif Hasan', 'Site Engineer', null, 3, false],
            ['Mim Khatun', 'Sales Advisor', null, 4, true],
            ['Shakil Ahmed', 'Architect', null, 5, true],
            ['Lamia Noor', 'Interior Designer', null, 6, false],
            ['Jahid Mia', 'Land Acquisition Officer', null, 1, false],
        ],
    ];

    /**
     * @return list<array{id: int, name: string, role: string, photo: array<string, mixed>|null, bio: string|null, linkedin_url: string|null, email: string|null}>
     */
    public static function members(string $group): array
    {
        $offset = array_search($group, array_keys(self::MEMBERS), true) * 10;

        return array_map(
            fn (array $member, int $index): array => [
                'id' => $offset + $index + 1,
                'name' => $member[0],
                'role' => $member[1],
                'photo' => DemoMedia::image(sprintf('team/team-%02d', $member[3])),
                'bio' => $member[2],
                'linkedin_url' => $member[4] ? 'https://www.linkedin.com/' : null,
                'email' => null,
            ],
            self::MEMBERS[$group],
            array_keys(self::MEMBERS[$group]),
        );
    }
}
