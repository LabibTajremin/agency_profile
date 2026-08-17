<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * The copy a brand-new install renders before anybody has written anything.
 *
 * Nothing here is written to the database. These are the values every getter falls back to, so
 * a site with an empty options table and an empty posts table still draws a complete home page.
 * The moment an owner edits a field, their value is stored and only that field stops using this
 * array — which is why the merge underneath is recursive and why lists replace wholesale.
 *
 * The tone is deliberate: this is not lorem ipsum and it is not "Your headline here". A theme
 * demo that reads as a placeholder is a theme the buyer has to rewrite before they can show it
 * to anyone. Every string below is written as if it belonged to a working consultancy, with the
 * numbers rounded the way a real one would round them.
 *
 * Images are the generated vector artworks bundled with the boutique demo pack — provenance in
 * `demos/LICENSES.md`. No third-party photography, no real university marks.
 */
final class DemoContent
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            'hero' => self::hero(),
            'statistics' => self::stats(),
            'services' => self::services(),
            'video-rail' => self::videoRail(),
            'destinations' => self::destinations(),
            'universities' => self::universities(),
            'highlights' => self::highlights(),
            'process' => self::process(),
            'intakes' => self::intakes(),
            'scholarships' => self::scholarships(),
            'testimonials' => self::testimonials(),
            'team' => self::team(),
            'events' => self::events(),
            'blog' => self::blog(),
            'faq' => self::faq(),
            'cta' => self::cta(),
            'footer' => self::footer(),
        ];
    }

    /**
     * Copy for pages rather than home-page sections.
     *
     * Kept out of `all()` because everything in there is keyed by a `SectionId` and a test holds
     * it to that. A page is not a section: it has no toggle, no position in the home-page order,
     * and no entry in the sections list.
     *
     * @return array<string, mixed>
     */
    public static function pages(): array
    {
        return ['founders' => self::founders()];
    }

    /**
     * @return array<string, mixed>
     */
    private static function founders(): array
    {
        return [
            'title' => 'The two people who started this',
            'lead' => 'Between them, nineteen years of getting Bangladeshi students into '
                . 'universities that were not expecting them.',
            'visionTitle' => 'What we are trying to build',
            'vision' => 'Most students who leave this country for a degree do it on borrowed '
                . 'confidence — a cousin who went somewhere, an agent who promised something. We '
                . 'started this because the advice available here was either free and worthless '
                . 'or expensive and no better.'
                . "\n\n"
                . 'The plan is unglamorous: know the requirements properly, say no when the '
                . 'answer is no, and be reachable after the student has landed and stopped '
                . 'paying us. That is the whole strategy, and it is harder to copy than it '
                . 'sounds.',
            'goalsTitle' => 'Where we intend to be',
            'goalsYear' => 'by 2030',
            'goals' => [
                ['value' => 10000, 'label' => 'students placed'],
                ['value' => 600, 'label' => 'partner universities'],
                ['value' => 25, 'label' => 'countries'],
                ['value' => 6, 'label' => 'offices'],
            ],
            'milestonesTitle' => 'How we got here',
            'milestones' => [
                ['year' => '2014', 'event' => 'Two desks in Dhanmondi and eleven students'],
                ['year' => '2017', 'event' => 'First agreements with UK and Australian partners'],
                ['year' => '2019', 'event' => 'IELTS centre opens; 400 students that year'],
                ['year' => '2021', 'event' => 'Chattogram branch'],
                ['year' => '2023', 'event' => 'Two thousandth placement'],
                ['year' => '2026', 'event' => 'Fourteen destination countries'],
            ],
            'ctaTitle' => 'Come and talk to one of us',
            'ctaBlurb' => 'The first meeting is with a counsellor, not a salesperson, and it '
                . 'costs nothing.',
            'people' => [
                [
                    'name' => 'Nusrat Jahan',
                    'designation' => 'Co-founder and Head of Admissions',
                    'tagline' => 'Reads a transcript the way an admissions officer does.',
                    'photo' => 'portrait-nusrat-jahan.svg',
                    'bio' => 'Spent four years assessing applications inside a UK partner office '
                        . 'before starting this, which is where the habit of telling students '
                        . 'the truth about their chances came from. Handles the UK and Ireland, '
                        . 'and every statement of purpose that leaves this office.',
                    'languages' => 'Bangla, English',
                    'expertise' => 'UK admissions, statements of purpose, scholarship applications',
                    'education' => [
                        [
                            'degree' => 'MSc International Education Policy',
                            'institution' => 'University of Glasgow',
                            'year' => '2013',
                            'country' => 'United Kingdom',
                        ],
                        [
                            'degree' => 'BA English',
                            'institution' => 'University of Dhaka',
                            'year' => '2010',
                            'country' => 'Bangladesh',
                        ],
                    ],
                    'experience' => [
                        ['role' => 'Co-founder', 'org' => 'This consultancy', 'years' => '2014 to now'],
                        ['role' => 'Admissions assessor', 'org' => 'UK partner office, Dhaka', 'years' => '2010–2014'],
                    ],
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:hello@example.com'],
                ],
                [
                    'name' => 'Tanvir Ahmed',
                    'designation' => 'Co-founder and Head of Compliance',
                    'tagline' => 'Has never had a visa file returned for documentation.',
                    'photo' => 'portrait-tanvir-ahmed.svg',
                    'bio' => 'Came to this from immigration paperwork rather than education, '
                        . 'which is why the visa files leaving here are assembled in the order '
                        . 'the officer reads them. Runs Canada and Australia, and rehearses '
                        . 'every interview personally.',
                    'languages' => 'Bangla, English, Hindi',
                    'expertise' => 'Study permits, financial documentation, interview preparation',
                    'education' => [
                        [
                            'degree' => 'LLM Immigration Law',
                            'institution' => 'University of Toronto',
                            'year' => '2012',
                            'country' => 'Canada',
                        ],
                        [
                            'degree' => 'LLB',
                            'institution' => 'University of Chittagong',
                            'year' => '2008',
                            'country' => 'Bangladesh',
                        ],
                    ],
                    'experience' => [
                        ['role' => 'Co-founder', 'org' => 'This consultancy', 'years' => '2014 to now'],
                        ['role' => 'Caseworker', 'org' => 'Immigration practice, Toronto', 'years' => '2012–2014'],
                    ],
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:hello@example.com'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function hero(): array
    {
        return [
            'headline' => 'Study abroad without guessing what happens next',
            'subheadline' => 'Course shortlists, honest eligibility answers and a visa file that '
                . 'holds up — from the first conversation to the day you fly.',
            'searchPlaceholder' => 'Search a course, a university or a country',
            'primaryCta' => ['label' => 'Check your eligibility', 'url' => '#eligibility'],
            'secondaryCta' => ['label' => 'Talk to a counsellor', 'url' => '/contact/'],
            'image' => 'hero-campus.svg',
            'badges' => [
                ['label' => 'British Council certified counsellors'],
                ['label' => 'ICEF trained team'],
                ['label' => 'No fee until you have an offer'],
            ],
            'chips' => [
                ['value' => '96%', 'label' => 'visa success, last intake'],
                ['value' => '11 days', 'label' => 'average offer turnaround'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function stats(): array
    {
        return [
            'title' => 'Where our students are now',
            'items' => [
                ['value' => 2400, 'suffix' => '+', 'label' => 'students placed', 'icon' => 'students'],
                ['value' => 310, 'suffix' => '', 'label' => 'partner universities', 'icon' => 'university'],
                ['value' => 14, 'suffix' => '', 'label' => 'countries', 'icon' => 'globe'],
                ['value' => 12, 'suffix' => '', 'label' => 'years advising', 'icon' => 'calendar'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function services(): array
    {
        return [
            'title' => 'What we actually do for you',
            'intro' => 'Six pieces of work, each with a named person responsible for it.',
            'items' => [
                [
                    'icon' => 'compass',
                    'title' => 'Course and country shortlisting',
                    'blurb' => 'We match your grades, budget and career plan against live entry '
                        . 'requirements, then hand you a ranked shortlist you can defend.',
                    'url' => '/services/shortlisting/',
                ],
                [
                    'icon' => 'document',
                    'title' => 'Application and SOP review',
                    'blurb' => 'Every statement is read line by line by a counsellor who has seen '
                        . 'what that admissions office rejects.',
                    'url' => '/services/applications/',
                ],
                [
                    'icon' => 'passport',
                    'title' => 'Visa file preparation',
                    'blurb' => 'Financial documents, sponsor letters and the interview rehearsal, '
                        . 'assembled in the order the embassy reads them.',
                    'url' => '/services/visa/',
                ],
                [
                    'icon' => 'book',
                    'title' => 'IELTS and PTE coaching',
                    'blurb' => 'Small classes, weekly mock tests and a score guarantee you can '
                        . 'hold us to before you pay for the real sitting.',
                    'url' => '/services/test-prep/',
                ],
                [
                    'icon' => 'coins',
                    'title' => 'Scholarship and funding search',
                    'blurb' => 'We track the awards that actually pay out, with their deadlines, '
                        . 'and we file the paperwork with you.',
                    'url' => '/services/scholarships/',
                ],
                [
                    'icon' => 'plane',
                    'title' => 'Pre-departure and arrival',
                    'blurb' => 'Accommodation, airport pickup, bank account and SIM — arranged '
                        . 'before you land, not after.',
                    'url' => '/services/pre-departure/',
                ],
            ],
        ];
    }

    /**
     * The promo video row, seeded so the section is visible before anybody has pasted a URL.
     *
     * These are the Blender Foundation's open movies, which are **CC BY 3.0** — genuinely
     * licensed for redistribution, credited in the titles below and in `demos/LICENSES.md`.
     * That is the whole reason they are here rather than four links to somebody's real
     * marketing videos: a demo that ships another company's content is a demo that cannot be
     * sold, and there is no such thing as a Facebook, Instagram or TikTok URL that belongs to
     * nobody.
     *
     * So the seed is one YouTube embed and three self-hosted files. The other three hosts are
     * documented in the admin help with the URL shapes they accept, which is the useful half of
     * a demo link anyway — an owner replaces every one of these on day one.
     *
     * @return array<string, mixed>
     */
    private static function videoRail(): array
    {
        $bucket = 'https://storage.googleapis.com/gtv-videos-bucket/sample/';

        return [
            'enabled' => true,
            'title' => 'Watch our student stories',
            'subtitle' => 'Two minutes each. Replace these with your own — Facebook, Instagram, '
                . 'TikTok, YouTube or a file you upload.',
            'aspect' => '16:9',
            'autoplay' => true,
            'consent' => false,
            'items' => [
                [
                    'source' => 'youtube',
                    'url' => 'https://www.youtube.com/watch?v=YE7VzlLtp-4',
                    'title' => 'Sample: Big Buck Bunny (Blender Foundation, CC BY)',
                    'poster_id' => 0,
                    'duration' => '9:56',
                ],
                [
                    'source' => 'mp4',
                    'url' => $bucket . 'ForBiggerJoyrides.mp4',
                    'title' => 'Sample clip — replace with your own',
                    'poster_id' => 0,
                    'duration' => '0:15',
                ],
                [
                    'source' => 'mp4',
                    'url' => $bucket . 'ForBiggerMeltdowns.mp4',
                    'title' => 'Sample clip — replace with your own',
                    'poster_id' => 0,
                    'duration' => '0:15',
                ],
                [
                    'source' => 'mp4',
                    'url' => $bucket . 'ForBiggerEscapes.mp4',
                    'title' => 'Sample clip — replace with your own',
                    'poster_id' => 0,
                    'duration' => '0:15',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function destinations(): array
    {
        return [
            'title' => 'Where our students go',
            'intro' => 'Eight countries we know well enough to tell you when not to apply.',
            'items' => [
                [
                    'name' => 'United Kingdom',
                    'universities' => 84,
                    'tuition' => '£14,000–£26,000 a year',
                    'hook' => 'One-year masters and a two-year graduate route after it.',
                    'image' => 'destination-united-kingdom.svg',
                ],
                [
                    'name' => 'Canada',
                    'universities' => 61,
                    'tuition' => 'C$18,000–C$32,000 a year',
                    'hook' => 'A post-study permit that scales with the length of your course.',
                    'image' => 'destination-canada.svg',
                ],
                [
                    'name' => 'Australia',
                    'universities' => 42,
                    'tuition' => 'A$25,000–A$45,000 a year',
                    'hook' => 'Work rights during term, and regional bonuses if you go inland.',
                    'image' => 'destination-australia.svg',
                ],
                [
                    'name' => 'United States',
                    'universities' => 58,
                    'tuition' => 'US$22,000–US$52,000 a year',
                    'hook' => 'Assistantships that cut the real cost far below the sticker price.',
                    'image' => 'destination-united-states.svg',
                ],
                [
                    'name' => 'Germany',
                    'universities' => 27,
                    'tuition' => '€0–€3,500 a year',
                    'hook' => 'Public universities charge almost nothing; the blocked account is '
                        . 'the real hurdle.',
                    'image' => 'destination-germany.svg',
                ],
                [
                    'name' => 'Republic of Ireland',
                    'universities' => 18,
                    'tuition' => '€12,000–€24,000 a year',
                    'hook' => 'English-taught, EU-based, and two years to stay on after a masters.',
                    'image' => 'destination-republic-of-ireland.svg',
                ],
                [
                    'name' => 'Malaysia',
                    'universities' => 22,
                    'tuition' => 'RM 25,000–RM 48,000 a year',
                    'hook' => 'British and Australian branch campuses at a third of the cost.',
                    'image' => 'destination-malaysia.svg',
                ],
                [
                    'name' => 'Japan',
                    'universities' => 16,
                    'tuition' => '¥550,000–¥1,200,000 a year',
                    'hook' => 'MEXT funding and English-taught engineering, if you start early.',
                    'image' => 'destination-japan.svg',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function universities(): array
    {
        return [
            'title' => 'Universities we place students into',
            'intro' => 'Twelve of the three hundred and ten institutions we hold an agreement with.',
            'items' => [
                [
                    'name' => 'University of Glasgow',
                    'country' => 'United Kingdom',
                    'ranking' => 'QS 78',
                    'crest' => 'crest-university-of-glasgow.svg',
                ],
                [
                    'name' => 'University of Leeds',
                    'country' => 'United Kingdom',
                    'ranking' => 'QS 82',
                    'crest' => 'crest-university-of-leeds.svg',
                ],
                [
                    'name' => 'University of Birmingham',
                    'country' => 'United Kingdom',
                    'ranking' => 'QS 84',
                    'crest' => 'crest-university-of-birmingham.svg',
                ],
                [
                    'name' => 'University of Toronto',
                    'country' => 'Canada',
                    'ranking' => 'QS 25',
                    'crest' => 'crest-university-of-toronto.svg',
                ],
                [
                    'name' => 'University of Waterloo',
                    'country' => 'Canada',
                    'ranking' => 'QS 112',
                    'crest' => 'crest-university-of-waterloo.svg',
                ],
                [
                    'name' => 'Dalhousie University',
                    'country' => 'Canada',
                    'ranking' => 'QS 308',
                    'crest' => 'crest-dalhousie-university.svg',
                ],
                [
                    'name' => 'University of Melbourne',
                    'country' => 'Australia',
                    'ranking' => 'QS 13',
                    'crest' => 'crest-university-of-melbourne.svg',
                ],
                [
                    'name' => 'Monash University',
                    'country' => 'Australia',
                    'ranking' => 'QS 37',
                    'crest' => 'crest-monash-university.svg',
                ],
                [
                    'name' => 'Deakin University',
                    'country' => 'Australia',
                    'ranking' => 'QS 233',
                    'crest' => 'crest-deakin-university.svg',
                ],
                [
                    'name' => 'Northeastern University',
                    'country' => 'United States',
                    'ranking' => 'QS 431',
                    'crest' => 'crest-northeastern-university.svg',
                ],
                [
                    'name' => 'Technical University of Munich',
                    'country' => 'Germany',
                    'ranking' => 'QS 28',
                    'crest' => 'crest-technical-university-of-munich.svg',
                ],
                [
                    'name' => 'Trinity College Dublin',
                    'country' => 'Republic of Ireland',
                    'ranking' => 'QS 87',
                    'crest' => 'crest-trinity-college-dublin.svg',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function highlights(): array
    {
        return [
            'title' => 'Why families send us their second child too',
            'image' => 'scene-counselling-session-dhanmondi-office.svg',
            'callout' => ['value' => '96%', 'label' => 'of our files cleared the visa interview last intake'],
            'items' => [
                [
                    'title' => 'You are told no when the answer is no',
                    'blurb' => 'If your profile will not clear a university, we say so in the first '
                        . 'meeting rather than banking your application fee.',
                ],
                [
                    'title' => 'One counsellor, start to finish',
                    'blurb' => 'The person who reads your transcript is the person who rehearses '
                        . 'your visa interview. Nothing is handed to a desk you have never met.',
                ],
                [
                    'title' => 'Fees you can see before you commit',
                    'blurb' => 'A written schedule at the first meeting, and nothing payable until '
                        . 'an offer letter is in your hand.',
                ],
                [
                    'title' => 'We are still there after you land',
                    'blurb' => 'Accommodation problems, course changes, visa extensions — our '
                        . 'alumni group answers on the same day, years later.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function process(): array
    {
        return [
            'title' => 'How an application actually runs',
            'intro' => 'Six steps, roughly seven months from first meeting to departure.',
            'items' => [
                [
                    'icon' => 'chat',
                    'title' => 'Profile review',
                    'blurb' => 'Transcripts, budget and career plan, read properly before advice.',
                ],
                [
                    'icon' => 'compass',
                    'title' => 'Shortlist agreed',
                    'blurb' => 'Six to eight courses ranked by offer likelihood and cost.',
                ],
                [
                    'icon' => 'document',
                    'title' => 'Applications filed',
                    'blurb' => 'Statements drafted with you, references chased, portals submitted.',
                ],
                [
                    'icon' => 'mail',
                    'title' => 'Offers compared',
                    'blurb' => 'Conditions, deposits and deadlines laid side by side.',
                ],
                [
                    'icon' => 'passport',
                    'title' => 'Visa file built',
                    'blurb' => 'Funds, sponsor letters and a rehearsed interview.',
                ],
                [
                    'icon' => 'plane',
                    'title' => 'Departure',
                    'blurb' => 'Housing, insurance, forex and an arrival contact.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function intakes(): array
    {
        return [
            'title' => 'Intakes still open',
            'intro' => 'Apply against the deadline, not against the intake date.',
            'items' => [
                [
                    'month' => 'January',
                    'year' => '2027',
                    'countries' => 'United Kingdom, Canada, Australia, Malaysia',
                    'deadline' => 'Applications close 15 October 2026',
                    'cta' => ['label' => 'Start a January file', 'url' => '/contact/'],
                ],
                [
                    'month' => 'May',
                    'year' => '2027',
                    'countries' => 'Canada, Malaysia, Japan',
                    'deadline' => 'Applications close 20 January 2027',
                    'cta' => ['label' => 'Ask about May', 'url' => '/contact/'],
                ],
                [
                    'month' => 'September',
                    'year' => '2027',
                    'countries' => 'All fourteen destinations',
                    'deadline' => 'Early offers from 1 November 2026',
                    'cta' => ['label' => 'Plan for September', 'url' => '/contact/'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function scholarships(): array
    {
        return [
            'title' => 'Funding worth applying for',
            'intro' => 'Awards our students have actually been paid in the last two intakes.',
            'items' => [
                [
                    'name' => 'Chevening Scholarship',
                    'amount' => 'Full tuition + stipend',
                    'eligibility' => 'Two years work experience and a leadership record',
                    'country' => 'United Kingdom',
                    'deadline' => 'Early November',
                ],
                [
                    'name' => 'Vice-Chancellor’s International Award',
                    'amount' => '£3,000–£8,000',
                    'eligibility' => 'A first-class undergraduate result',
                    'country' => 'United Kingdom',
                    'deadline' => 'Rolling, by intake',
                ],
                [
                    'name' => 'Ontario Graduate Merit Award',
                    'amount' => 'C$10,000–C$15,000',
                    'eligibility' => 'CGPA 3.5 and a research proposal',
                    'country' => 'Canada',
                    'deadline' => 'Mid January',
                ],
                [
                    'name' => 'Destination Australia Grant',
                    'amount' => 'A$15,000 a year',
                    'eligibility' => 'Enrolment at a regional campus',
                    'country' => 'Australia',
                    'deadline' => 'Two months before intake',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function testimonials(): array
    {
        return [
            'title' => 'In their words',
            'items' => [
                [
                    'name' => 'Nusrat Jahan',
                    'university' => 'University of Glasgow',
                    'country' => 'United Kingdom',
                    'avatar' => 'portrait-nusrat-jahan.svg',
                    'quote' => 'They told me my first shortlist was too ambitious and rebuilt it '
                        . 'with me the same afternoon. Two of the four replacements made offers '
                        . 'within a month, and the visa file needed no corrections at all.',
                ],
                [
                    'name' => 'Tanvir Ahmed',
                    'university' => 'University of Waterloo',
                    'country' => 'Canada',
                    'avatar' => 'portrait-tanvir-ahmed.svg',
                    'quote' => 'The financial documents were the part I was dreading. We went '
                        . 'through every page twice, in the order the officer would read them, '
                        . 'and the interview lasted four minutes.',
                ],
                [
                    'name' => 'Sadia Islam',
                    'university' => 'Monash University',
                    'country' => 'Australia',
                    'avatar' => 'portrait-sadia-islam.svg',
                    'quote' => 'I came in with a 6.0 and needed 6.5 with no band under 6. The mock '
                        . 'tests were harsher than the real one, which is exactly what I needed. '
                        . 'I sat it once and passed.',
                ],
                [
                    'name' => 'Imran Chowdhury',
                    'university' => 'Technical University of Munich',
                    'country' => 'Germany',
                    'avatar' => 'portrait-imran-chowdhury.svg',
                    'quote' => 'Nobody else explained the blocked account properly. Here it was '
                        . 'drawn out on paper in the first meeting, with the real timeline, and '
                        . 'I opened mine three months before I needed it.',
                ],
                [
                    'name' => 'Farhana Rahman',
                    'university' => 'Trinity College Dublin',
                    'country' => 'Republic of Ireland',
                    'avatar' => 'portrait-farhana-rahman.svg',
                    'quote' => 'My counsellor answered a message about accommodation at ten at '
                        . 'night, three weeks after I had already landed and stopped being a '
                        . 'paying client. That is the part I tell people about.',
                ],
                [
                    'name' => 'Rezaul Karim',
                    'university' => 'University of Toronto',
                    'country' => 'Canada',
                    'avatar' => 'portrait-rezaul-karim.svg',
                    'quote' => 'I had been rejected once already on my own. They read the old '
                        . 'statement, showed me the two paragraphs that sank it, and the '
                        . 'rewritten version cleared the same university a year later.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function team(): array
    {
        return [
            'title' => 'The counsellors you will actually meet',
            'items' => [
                [
                    'name' => 'Nusrat Jahan',
                    'role' => 'Head of UK admissions',
                    'languages' => 'Bangla, English',
                    'photo' => 'portrait-nusrat-jahan.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:uk@example.com'],
                ],
                [
                    'name' => 'Tanvir Ahmed',
                    'role' => 'Canada and visa compliance',
                    'languages' => 'Bangla, English, Hindi',
                    'photo' => 'portrait-tanvir-ahmed.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:canada@example.com'],
                ],
                [
                    'name' => 'Sadia Islam',
                    'role' => 'IELTS and PTE lead trainer',
                    'languages' => 'Bangla, English',
                    'photo' => 'portrait-sadia-islam.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:tests@example.com'],
                ],
                [
                    'name' => 'Imran Chowdhury',
                    'role' => 'Europe and scholarships',
                    'languages' => 'Bangla, English, German',
                    'photo' => 'portrait-imran-chowdhury.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:europe@example.com'],
                ],
                [
                    'name' => 'Farhana Rahman',
                    'role' => 'Australia and New Zealand',
                    'languages' => 'Bangla, English',
                    'photo' => 'portrait-farhana-rahman.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:anz@example.com'],
                ],
                [
                    'name' => 'Rezaul Karim',
                    'role' => 'Pre-departure and alumni',
                    'languages' => 'Bangla, English',
                    'photo' => 'portrait-rezaul-karim.svg',
                    'socials' => ['linkedin' => '#', 'email' => 'mailto:alumni@example.com'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function events(): array
    {
        return [
            'title' => 'Come and talk to us in person',
            'items' => [
                [
                    'day' => '18',
                    'month' => 'Sep',
                    'title' => 'UK admissions fair',
                    'venue' => 'Dhanmondi office, Road 27, Dhaka',
                    'cta' => ['label' => 'Reserve a slot', 'url' => '/events/'],
                    'image' => 'scene-uk-admissions-fair-dhanmondi.svg',
                ],
                [
                    'day' => '02',
                    'month' => 'Oct',
                    'title' => 'Canada study permit workshop',
                    'venue' => 'Online, 7pm Dhaka time',
                    'cta' => ['label' => 'Join the workshop', 'url' => '/events/'],
                    'image' => 'scene-students-at-the-canada-study-permit-workshop.svg',
                ],
                [
                    'day' => '15',
                    'month' => 'Oct',
                    'title' => 'Scholarship clinic',
                    'venue' => 'Chattogram branch, Agrabad',
                    'cta' => ['label' => 'Book a review', 'url' => '/events/'],
                    'image' => 'scene-scholarship-clinic-online-cohort.svg',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function blog(): array
    {
        return [
            'title' => 'Worth reading before you apply',
            'items' => [
                [
                    'category' => 'Visas',
                    'title' => 'What the UK financial requirement really asks for',
                    'excerpt' => 'Twenty-eight days, the right closing balance and a sponsor letter '
                        . 'that names you — where files usually fail.',
                    'date' => '4 August 2026',
                    'readTime' => '6 min read',
                    'image' => 'scene-visa-documentation-review.svg',
                ],
                [
                    'category' => 'Tests',
                    'title' => 'IELTS band 6.5 with no section under 6',
                    'excerpt' => 'The overall score is the easy part. Here is how to lift the one '
                        . 'section that keeps failing you.',
                    'date' => '22 July 2026',
                    'readTime' => '8 min read',
                    'image' => 'scene-ielts-mock-test-in-progress.svg',
                ],
                [
                    'category' => 'Costs',
                    'title' => 'What a year in Canada actually costs a Dhaka family',
                    'excerpt' => 'Tuition is the number everyone quotes. Rent, insurance and the '
                        . 'first month are the ones that surprise people.',
                    'date' => '9 July 2026',
                    'readTime' => '7 min read',
                    'image' => 'scene-pre-departure-briefing-september-intake.svg',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function faq(): array
    {
        return [
            'title' => 'Questions we are asked every week',
            'items' => [
                [
                    'question' => 'What does your service cost?',
                    'answer' => 'Counselling, shortlisting and application filing are free. A '
                        . 'single service fee is payable only once you accept an offer, and the '
                        . 'amount is written down at your first meeting.',
                ],
                [
                    'question' => 'Can I apply without IELTS?',
                    'answer' => 'Often, yes. Several universities accept a medium-of-instruction '
                        . 'letter or their own internal test. We will tell you which of your '
                        . 'shortlist does before you book a sitting.',
                ],
                [
                    'question' => 'How long does the whole process take?',
                    'answer' => 'Plan on seven months from first meeting to departure. Five is '
                        . 'possible for a January intake if your documents are already in order.',
                ],
                [
                    'question' => 'What CGPA do I need?',
                    'answer' => 'For a taught masters, 2.8 out of 4 opens most of our UK and '
                        . 'Australian partners. Below that we look at work experience and at '
                        . 'pathway programmes rather than pretending it makes no difference.',
                ],
                [
                    'question' => 'Do you guarantee a visa?',
                    'answer' => 'No, and neither should anyone else. We prepare the file to the '
                        . 'standard the embassy asks for and we tell you honestly where yours is '
                        . 'weak. Last intake, 96% of our files cleared.',
                ],
                [
                    'question' => 'Can my spouse come with me?',
                    'answer' => 'It depends on the country and the level of the course. Canada and '
                        . 'Australia allow dependants on most masters programmes; the UK now '
                        . 'restricts it to research degrees.',
                ],
                [
                    'question' => 'How much money do I need to show?',
                    'answer' => 'Tuition for the first year plus living costs for nine to twelve '
                        . 'months, held for the required period. The exact figure changes by '
                        . 'country and we work it out with you in the first meeting.',
                ],
                [
                    'question' => 'Can I work while studying?',
                    'answer' => 'Twenty hours a week during term is the common allowance across '
                        . 'the UK, Canada and Australia, with full hours in vacations. Budget as '
                        . 'though it will cover food, not tuition.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cta(): array
    {
        return [
            'title' => 'Find out where you stand, in one conversation',
            'blurb' => 'Tell us your grades, your budget and where you want to be in three years. '
                . 'You will leave the first meeting with a shortlist and an honest answer.',
            'image' => 'scene-offer-letter-day.svg',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function footer(): array
    {
        return [
            'about' => 'An education consultancy for students leaving Bangladesh — course '
                . 'shortlisting, applications, funding and visa files, handled by the same '
                . 'counsellor from the first meeting to the day you land.',
            'hours' => 'Sunday to Thursday, 10am–7pm. Saturday by appointment.',
            'phone' => '+880 1700 000000',
            'email' => 'hello@example.com',
            'branches' => [
                [
                    'name' => 'Dhaka',
                    'address' => 'House 42, Road 27, Dhanmondi, Dhaka 1209',
                ],
                [
                    'name' => 'Chattogram',
                    'address' => 'Level 5, Agrabad Commercial Area, Chattogram 4100',
                ],
            ],
            'links' => [
                ['label' => 'Destinations', 'url' => '/destinations/'],
                ['label' => 'Courses', 'url' => '/courses/'],
                ['label' => 'Scholarships', 'url' => '/scholarships/'],
                ['label' => 'Events', 'url' => '/events/'],
                ['label' => 'Contact', 'url' => '/contact/'],
            ],
            'socials' => [
                ['network' => 'facebook', 'url' => '#'],
                ['network' => 'instagram', 'url' => '#'],
                ['network' => 'linkedin', 'url' => '#'],
                ['network' => 'youtube', 'url' => '#'],
            ],
            'newsletter' => [
                'title' => 'Intake reminders, nothing else',
                'blurb' => 'One email a month: deadlines that are close, and funding that opened.',
                'buttonLabel' => 'Subscribe',
            ],
        ];
    }
}
