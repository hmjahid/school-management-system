<?php

namespace Database\Seeders;

use App\Models\WebsiteContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('website_contents')) {
            return;
        }

        if (! Schema::hasColumn('website_contents', 'page')) {
            $this->command?->warn('website_contents.page column missing — run migrations.');

            return;
        }

        $pages = $this->pages();

        foreach ($pages as $page => $row) {
            WebsiteContent::updateOrCreate(
                ['page' => $page],
                array_merge([
                    'is_active' => true,
                ], $row)
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function pages(): array
    {
        $section = fn (string $heading, array $paragraphs = [], array $bullets = [], array $extra = []) => array_merge([
            'heading' => $heading,
            'paragraphs' => $paragraphs,
            'bullets' => $bullets,
        ], $extra);

        return [
            'home' => [
                'title' => 'Home',
                'meta_description' => 'Official school website — admissions, academics, news, and parent portal.',
                'content' => [
                    'hero' => [
                        'headline' => 'Welcome to our school community',
                        'motto' => 'Where curiosity meets excellence and every learner matters.',
                    ],
                    'principal' => [
                        'name' => 'Principal',
                        'message' => "We are delighted to welcome you to our school's website. Our dedicated faculty, rich programmes, and supportive environment help students grow academically and as responsible citizens.\n\nWe invite you to explore admissions, meet our faculty, and stay connected through the parent portal.",
                    ],
                    'testimonials' => [
                        ['quote' => 'The teachers genuinely care — my daughter has flourished here.', 'name' => 'A. Rahman', 'role' => 'Parent'],
                        ['quote' => 'Strong academics and clubs — I found my passion for science.', 'name' => 'S. Khan', 'role' => 'Alumni'],
                    ],
                    'highlights' => [
                        'Holistic curriculum with STEM and arts',
                        'Competitive sports and cultural events',
                        'Counselling and university guidance',
                    ],
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'meta_description' => 'History, mission, vision, leadership, facilities, and achievements.',
                'content' => [
                    'intro' => 'Learn who we are, what we stand for, and how we support every learner.',
                    'sections' => [
                        $section('School history & mission', [
                            'Founded to serve the local community with affordable, quality education, we have grown into a full-spectrum institution from primary through secondary levels.',
                            'Our mission is to nurture critical thinking, integrity, and service-minded graduates.',
                        ]),
                        $section('Vision & core values', [
                            'We aspire to be a model school where innovation and inclusion drive measurable student success.',
                        ], ['Excellence', 'Integrity', 'Respect', 'Collaboration', 'Resilience']),
                        $section("Principal's message", [
                            'Education is a partnership between school, students, and families. Together we build habits of discipline, empathy, and lifelong learning.',
                        ]),
                        $section('School administration', [
                            'Led by the principal and heads of sections, supported by admissions, finance, and student services teams.',
                        ]),
                        $section('Infrastructure & facilities', [], [
                            'Science and computer labs', 'Library and reading rooms', 'Sports grounds', 'Auditorium', 'Safe transport (where applicable)',
                        ]),
                        $section('Achievements & accreditations', [
                            'Board examination results, national competition placements, and community service awards are updated each term on the news page.',
                        ]),
                        $section('School anthem & emblem', [
                            'Lyrics and emblem usage guidelines are available from the school office and can be published here via the CMS.',
                        ]),
                    ],
                ],
            ],
            'academics' => [
                'title' => 'Academics',
                'meta_description' => 'Curriculum, calendar, departments, co-curricular activities, exams, and results.',
                'content' => [
                    'intro' => 'Our academic programme balances rigorous outcomes with wellbeing and creativity.',
                    'sections' => [
                        $section('Curriculum overview', [
                            'National curriculum expectations are met and extended through project work, labs, and digital resources.',
                        ]),
                        $section('Academic calendar', [
                            'Term dates, holidays, and assessment windows are published at the start of each session.',
                        ]),
                        $section('Departments', [], [], [
                            'cards' => [
                                ['title' => 'Science', 'body' => 'Physics, chemistry, biology, and integrated STEM projects.'],
                                ['title' => 'Arts', 'body' => 'Literature, visual arts, music, and drama.'],
                                ['title' => 'Commerce', 'body' => 'Accounting, business studies, and economics foundations.'],
                                ['title' => 'Computer studies', 'body' => 'Programming, digital literacy, and responsible technology use.'],
                            ],
                        ]),
                        $section('Co-curricular activities', [
                            'Clubs, debates, robotics, and community service complement classroom learning.',
                        ]),
                        $section('Examination system', [
                            'Continuous assessment plus term examinations; integrity policies apply to all tests.',
                        ]),
                        $section('Academic results', [
                            'Published results for students and parents are available in the portal when released by the examination office.',
                        ]),
                    ],
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'meta_description' => 'How to apply, requirements, fees, dates, tests, scholarships, and FAQs.',
                'content' => [
                    'intro' => 'We welcome families who share our values and commitment to learning.',
                    'apply_intro' => 'Complete every section accurately. You will receive an application number after submission.',
                    'sections' => [
                        $section('Admission process', [], [
                            'Submit the online application with documents',
                            'Admissions review and entrance test (if applicable)',
                            'Offer and fee payment',
                            'Enrollment and class placement',
                        ]),
                        $section('Requirements', [], [
                            'Birth certificate', 'Previous school records', 'Passport-size photo', 'Parent/guardian ID (where required)',
                        ]),
                        $section('Fee structure', [
                            'See the payments portal for current fee heads. Installment options may be available for eligible families.',
                        ]),
                        $section('Important dates', [
                            'Application window, test dates, and orientation are announced each cycle on this page and via SMS/email.',
                        ]),
                        $section('Admission test syllabus', [
                            'Age-appropriate literacy, numeracy, and reasoning items align with the entry grade.',
                        ]),
                        $section('FAQ', [], [], [
                            'faq' => [
                                ['q' => 'Can I save a draft online?', 'a' => 'Public applications submit in one step; contact admissions if you need assistance.'],
                                ['q' => 'How do I pay fees?', 'a' => 'Use the fee payment portal or approved bank channels listed there.'],
                            ],
                        ]),
                    ],
                ],
            ],
            'students' => [
                'title' => 'Students',
                'meta_description' => 'Student life — council, clubs, calendar, exams, uniform, conduct, and resources.',
                'content' => [
                    'sections' => [
                        $section('Student council', [
                            'Elected representatives voice student ideas and lead service initiatives.',
                        ]),
                        $section('Clubs & societies', [], ['Debate', 'Science club', 'Sports teams', 'Community service', 'Arts guild']),
                        $section('Academic calendar', ['Key dates mirror the academics page and are shared in homeroom.']),
                        $section('Exam schedule', ['Detailed schedules are posted in the portal before each term.']),
                        $section('Results', ['Published results appear in the student/parent portal.']),
                        $section('School uniform', ['Dress code supports safety and equality; specifics are in the student handbook.']),
                        $section('Code of conduct', ['Respect, honesty, and digital citizenship expectations apply on campus and online.']),
                        $section('Student resources', ['Library hours, learning support, and counselling booking via the office.']),
                    ],
                ],
            ],
            'faculty' => [
                'title' => 'Faculty',
                'meta_description' => 'Teaching and administrative staff, achievements, development, and resources.',
                'content' => [
                    'sections' => [
                        $section('Teaching staff directory', [
                            'Profiles below are synced from the school information system; publish bios through the CMS if needed.',
                        ]),
                        $section('Administrative staff', [
                            'Admissions, finance, IT, and operations teams support daily school life.',
                        ]),
                        $section('Faculty achievements', [
                            'Workshops, certifications, and research contributions are highlighted in newsletters.',
                        ]),
                        $section('Professional development', [
                            'Ongoing training in pedagogy, safeguarding, and subject depth.',
                        ]),
                        $section('Teacher of the month', [
                            'Recognising innovation and student impact — see news for current honourees.',
                        ]),
                        $section('Faculty resources', [
                            'Internal LMS and shared drives are available to staff accounts.',
                        ]),
                    ],
                ],
            ],
            'news' => [
                'title' => 'News & Events',
                'meta_description' => 'Latest news, upcoming and past events, gallery links, magazine, and press.',
                'content' => [
                    'intro' => 'Stay informed about campus life and community milestones.',
                    'sections' => [
                        $section('School magazine & newsletter', [
                            'PDF archives can be linked from structured CMS sections when files are uploaded to storage.',
                        ]),
                        $section('Press releases', [
                            'Official statements are published here and may be distributed to local media.',
                        ]),
                    ],
                ],
            ],
            'gallery' => [
                'title' => 'Gallery',
                'meta_description' => 'Photos and videos from academic, cultural, sports, and annual events.',
                'content' => [
                    'intro' => 'Moments from life on campus — categories are filled from the gallery module.',
                    'sections' => [
                        $section('Photo gallery categories', [], [
                            'Academic activities', 'Cultural events', 'Sports', 'Annual functions', 'Field trips',
                        ]),
                    ],
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'meta_description' => 'Address, forms, map, hours, emergency contacts, and social media.',
                'content' => [
                    'intro' => 'We are here to help prospective families, students, and partners.',
                    'emergency_contacts' => [
                        ['label' => 'Main security desk', 'phone' => '+1 (555) 000-0110'],
                        ['label' => 'Medical room', 'phone' => '+1 (555) 000-0111'],
                    ],
                    'sections' => [
                        $section('School hours', [
                            'Office hours typically follow the timetable in website settings; adjust in admin settings for accuracy.',
                        ]),
                    ],
                ],
            ],
            'payments' => [
                'title' => 'Fee payments',
                'meta_description' => 'Fee structure summary, online gateways, history, receipts, and plans.',
                'content' => [
                    'intro' => 'Transparent fees and secure payment options for families.',
                    'sections' => [
                        $section('Payment plans', [
                            'Installments may be arranged through the finance office subject to policy.',
                        ]),
                        $section('Scholarship application', [
                            'Use the scholarship form on the admissions page or contact the bursar.',
                        ]),
                        $section('Receipts', [
                            'Successful online transactions generate references; printed receipts are available from finance on request.',
                        ]),
                    ],
                ],
            ],
        ];
    }
}
