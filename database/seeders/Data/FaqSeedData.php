<?php

namespace Database\Seeders\Data;

/**
 * Placeholder questions for the client's review (outside production only), seeded into the `faqs` table:
 * the FAQ page's questions (`ITEMS`: category, question, answer), the questions that belong to one
 * builder page (`PAGE_ITEMS`), and the questions of single areas and project segments (attached through
 * `faqables`). The client edits them in the admin.
 */
final class FaqSeedData
{
    /**
     * [category, question, answer] — every row has `faq` in show_on.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const array ITEMS = [
        ['buying', 'How do I book an apartment?', '<p>Choose the project and unit with our sales team, sign the booking form and pay the booking money. You receive a money receipt the same day and the allotment letter within two weeks.</p>'],
        ['buying', 'Can I visit a project before I book?', '<p>Yes. <a href="/projects">Pick a project</a> and use <strong>Book a site visit</strong>, or call the hotline. Visits run every day, including Fridays, with an engineer or sales advisor on site.</p>'],
        ['buying', 'What is included in the apartment price?', '<p>The price covers the apartment, its share of the land and the common areas, and the finishes listed in the specification. Car parking, the utility connection fees and registration costs are quoted separately.</p>'],
        ['buying', 'How is the size of an apartment measured?', '<p>Sizes are stated in square feet (sft) and include a proportional share of the common areas such as stairs, lift lobbies and corridors. The usable floor area is shown on each floor plan.</p>'],
        ['payment', 'Which payment plans do you offer?', '<p>Most projects offer:</p><ul><li>a one-time payment with a discount,</li><li>instalments linked to construction stages,</li><li>monthly instalments over the construction period.</li></ul><p>The plan for each project is in its brochure.</p>'],
        ['payment', 'Can I take a home loan?', '<p>Yes. Several banks and financial institutions offer home loans for our projects. Our team can introduce you to them and provide the documents the bank asks for.</p>'],
        ['payment', 'What happens if I miss an instalment?', '<p>Talk to us first: we can usually reschedule one instalment. Repeated late payments carry a delay charge, as set out in the agreement.</p>'],
        ['payment', 'Do you accept payments in foreign currency?', '<p>Buyers abroad can pay by bank transfer to our account in Bangladesh. The amount is credited in taka at the bank’s rate on the day it arrives.</p>'],
        ['legal', 'How do I know the land title is clear?', '<p>Before we start any project our legal team checks the deeds, mutation, tax records and court records for the land. Copies of the title documents are available to buyers before booking.</p>'],
        ['legal', 'Is the project approved by RAJUK?', '<p>Every project is built to an approved plan. The approval number and date are shown on the project page and in the brochure, and you can check them with the authority.</p>'],
        ['legal', 'When is the apartment registered in my name?', '<p>Registration follows handover and the final payment. Our legal team prepares the deed and books the appointment at the sub-registry office; registration fees and taxes are paid by the buyer.</p>'],
        ['nrb', 'Can I buy if I live outside Bangladesh?', '<p>Yes. Many of our buyers live abroad. You can choose a unit by video call, sign the booking documents through a power of attorney and pay by bank transfer. See <a href="/nrb">buying from abroad</a>.</p>'],
        ['nrb', 'Do I need to come to Bangladesh to sign?', '<p>No. A family member or another person you trust can sign for you with a power of attorney attested by the Bangladesh mission in your country.</p>'],
        ['nrb', 'How will I follow construction progress?', '<p>Project pages show monthly progress photos and the current construction stage. Your sales advisor can also arrange a video walk-through of the site.</p>'],
        ['landowner', 'What size of plot do you develop?', '<p>Plots of five katha and above are usually suitable, depending on the road width and the approved floor-area ratio. <a href="/landowner">Share your plot details</a> and our land team will advise you.</p>'],
        ['landowner', 'What share of the building does the landowner receive?', '<p>The share depends on the plot, its location and the approved design. We propose a figure after the site and title review and write it into the joint-venture agreement.</p>'],
        ['after_sales', 'What happens at handover?', '<p>You inspect the apartment with our engineer, note anything to fix and receive the keys, the user manual and the warranty documents once the list is closed.</p>'],
        ['after_sales', 'Is there a warranty?', '<p>Structural work is covered for ten years and fittings for one year from handover. The warranty terms are part of your handover documents.</p>'],
        ['after_sales', 'Who manages the building after handover?', '<p>Our facility team runs the building for the first year while the owners’ association is formed, then hands over the accounts and contracts to it.</p>'],
        ['general', 'Where are your offices?', '<p>Our head office is in Gulshan 2 and the sales lounge is in Bashundhara R/A. Addresses, hours and maps are on the <a href="/contact">contact page</a>.</p>'],
        ['general', 'How can I reach you outside office hours?', '<p>Send a message on WhatsApp or use <strong>Request a call back</strong>, and we will call you the next working day at the time you choose.</p>'],
    ];

    /**
     * Questions that belong to one builder page (a `faq` block's `category`, 16.1), not to PG-18:
     * category → [question, answer]. They keep the wording the page had in Part A.
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const array PAGE_ITEMS = [
        'page_services' => [
            ['Do you only sell new apartments?',
                '<p>No. We also sell commercial floors and plots, and a few ready apartments from completed projects are usually available. Browse <a href="/projects/status/ready">ready projects</a> to see what is open today.</p>'],
            ['Can you help me arrange a home loan?',
                '<p>Our sales advisors work with several partner banks and can explain the documents each one asks for. The loan itself is agreed between you and the bank.</p>'],
            ['What does after-sales care include?',
                '<p>A snagging walk-through, utility and meter transfers, and warranty cover for structure, waterproofing and fittings through the first year.</p>'],
            ['Do you work with landowners outside Dhaka?',
                '<p>We consider plots in Dhaka and in Chattogram. Share your plot details through the <a href="/landowner">landowner page</a> and the land team will tell you whether it fits.</p>'],
        ],
        'page_landowner' => [
            ['How big does my plot need to be?',
                '<p>Plots of five katha and above are usually suitable, depending on the road width and the approved floor-area ratio. Smaller plots can sometimes be combined with neighbouring ones.</p>'],
            ['What share of the building do I receive?',
                '<p>The share depends on the plot, its location and the approved design. We propose a figure after the site and title review, and you can negotiate it with your lawyer.</p>'],
            ['How long does a project take?',
                '<p>Approvals usually take three to four months and construction fourteen to eighteen months for a mid-rise building. The schedule is written into the agreement.</p>'],
            ['Can I see projects built on landowners’ plots?',
                '<p>Yes. Our sales team can arrange visits to handed-over projects and introductions to landowners who have agreed to speak with you.</p>'],
            ['What happens if the project is delayed?',
                '<p>The agreement sets out the compensation for each month of delay within our control, so the risk does not sit with you.</p>'],
        ],
        'page_nrb' => [
            ['Can I buy without visiting Bangladesh?',
                '<p>Yes. Many of our NRB buyers complete the whole process remotely, using video visits, a power of attorney and bank transfers. You are welcome to visit whenever it suits you.</p>'],
            ['How do I pay from abroad?',
                '<p>Through authorised banking channels. The NRB desk will share the account details and the receipt process, and explain how your bank should describe the transfer.</p>'],
            ['Who can sign on my behalf?',
                '<p>A relative or lawyer you name in a power of attorney attested by the Bangladesh embassy or consulate in your country.</p>'],
            ['Can someone else collect the keys?',
                '<p>Yes, an authorised family member can take the keys at handover, or our after-sales team can look after the unit until you arrive.</p>'],
        ],
        'page_buyer_guide' => [
            ['Is the booking amount refundable?',
                '<p>It depends on the terms in your booking letter. Ask for the cooling-off period and refund conditions in writing before you pay.</p>'],
            ['How do I check an approval number?',
                '<p>Ask the developer for the approval letter and compare its number with the one on the project page and the RAJUK records.</p>'],
        ],
    ];

    /**
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const array AREAS = [
        'bashundhara-ra' => [
            ['Is Bashundhara R/A good for families?', '<p>Yes. The area has several universities and international schools, Evercare Hospital and large parks, with less through traffic than the older neighbourhoods.</p>'],
            ['How far is Bashundhara from Gulshan?', '<p>About 20 minutes by car outside peak hours, via Progati Sarani or the airport road.</p>'],
            ['Can I buy a plot instead of an apartment?', '<p>Yes. Our land projects in the newer blocks offer 3–5 katha plots with clear titles and roads already laid.</p>'],
        ],
        'gulshan' => [
            ['Why are Gulshan apartments more expensive?', '<p>Land in Gulshan is scarce and buildings are low-density, so each apartment carries a larger share of land. Lake views and the business address add to the value.</p>'],
            ['Are there commercial spaces for sale in Gulshan?', '<p>Yes. Gemcon Gulshan Avenue offers office floors from 900 sft, with full generator back-up and basement parking.</p>'],
        ],
        'banani' => [
            ['Can I buy a single office suite in Banani?', '<p>Yes. Suites start at about 850 sft, and adjoining suites can be combined into a full floor.</p>'],
            ['Is parking included with office space?', '<p>Each suite comes with an allocated basement car park; larger units have two or more.</p>'],
        ],
        'dhanmondi' => [
            ['Are there ready apartments in Dhanmondi?', '<p>Yes. Gemcon Cedar Court is ready to move in, with a few units still available.</p>'],
            ['What is the parking situation in Dhanmondi?', '<p>Every apartment in our Dhanmondi buildings has at least one basement or ground-floor car park.</p>'],
        ],
        'uttara' => [
            ['How is the commute from Uttara to Motijheel?', '<p>The metro runs from Uttara North to Motijheel in about 40 minutes, avoiding road traffic entirely.</p>'],
            ['Which sectors are your Uttara projects in?', '<p>Sectors 7, 11 and 13 for apartments, a gated block for plots, and a site next to Uttara North station for retail and offices.</p>'],
            ['Are prices lower than in Gulshan?', '<p>Yes. A similar-sized apartment in Uttara usually costs less than half the Gulshan price, with more green space per home.</p>'],
        ],
        'khulshi' => [
            ['Do your Khulshi apartments have views?', '<p>Most of them do. The buildings are placed on the slopes so that upper floors look over the city or the hills.</p>'],
        ],
    ];

    /**
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const array SEGMENTS = [
        'land' => [
            ['Are the plot titles clear?', '<p>Yes. Every plot has a registered deed and completed mutation, and we hand over the full chain of title documents for your lawyer to check before booking.</p>'],
            ['Can I combine two plots?', '<p>Adjoining plots can be bought together and registered as one, subject to RAJUK rules for the combined size.</p>'],
            ['When are the utilities connected?', '<p>Roads, drainage and water lines are complete before handover. Electricity and gas connections are applied for in the owner\'s name with our help.</p>'],
        ],
        'commercial' => [
            ['Can I buy a whole floor?', '<p>Yes. Floors can be bought whole or as suites, and adjoining suites can be joined later without structural work.</p>'],
            ['Is there parking for visitors?', '<p>Each unit comes with allocated basement parking, and part of the ground-floor parking is kept for visitors.</p>'],
            ['When can I start the fit-out?', '<p>Fit-out can start once the floor is handed over; our team shares the services drawings and fit-out guidelines in advance.</p>'],
        ],
        'residential' => [
            ['What is the payment schedule?', '<p>A booking money payment is followed by a down payment and instalments linked to construction progress. The sales team shares the full schedule for each unit type.</p>'],
            ['Is the project approved by RAJUK?', '<p>Yes. The approval number is listed under At a glance, and copies of the approval are available at our sales office.</p>'],
            ['Can I buy from abroad?', '<p>Yes. Many of our buyers are non-resident Bangladeshis. We arrange video site visits, send documents by email and accept payments through bank channels.</p>'],
        ],
        'mixed_use' => [
            ['What is the payment schedule?', '<p>A booking money payment is followed by a down payment and instalments linked to construction progress. The sales team shares the full schedule for each unit type.</p>'],
            ['Is the project approved by RAJUK?', '<p>Yes. The approval number is listed under At a glance, and copies of the approval are available at our sales office.</p>'],
            ['Can I buy from abroad?', '<p>Yes. Many of our buyers are non-resident Bangladeshis. We arrange video site visits, send documents by email and accept payments through bank channels.</p>'],
            ['Are the shops sold separately?', '<p>Yes. Shops on the ground and first floors are sold separately from the apartments and have their own entrance.</p>'],
        ],
    ];
}
