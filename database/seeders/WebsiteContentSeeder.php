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
            $content = $row['content'] ?? [];
            WebsiteContent::updateOrCreate(
                ['page' => $page],
                array_merge([
                    'is_active' => true,
                    'title_en' => $row['title'] ?? null,
                    'title_bn' => $row['title'] ?? null,
                    'meta_description_en' => $row['meta_description'] ?? null,
                    'meta_description_bn' => $row['meta_description'] ?? null,
                    'content_en' => $content,
                    'content_bn' => $content,
                    'cms_input_mode' => WebsiteContent::INPUT_MODE_JSON,
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
                    'partners' => [
                        ['name' => 'Ministry of Education', 'url' => 'https://moedu.gov.bd', 'color' => 'blue', 'icon' => 'book'],
                        ['name' => 'Directorate of Primary Education', 'url' => 'https://moedu.gov.bd', 'color' => 'emerald', 'icon' => 'school'],
                        ['name' => 'Secondary & Higher Secondary Board', 'url' => 'https://ducationboard.gov.bd', 'color' => 'amber', 'icon' => 'award'],
                        ['name' => 'National Board of Examination', 'url' => 'http://www.biseb.gov.bd', 'color' => 'purple', 'icon' => 'clipboard'],
                        ['name' => 'NAEM', 'url' => 'https://www.teachers.gov.bd', 'color' => 'rose', 'icon' => 'users'],
                    ],
                ],
                'content_bn' => [
                    'hero' => [
                        'headline' => 'আমাদের স্কুল কমিউনিটিতে স্বাগতম',
                        'motto' => 'যেখানে কৌতূহল ও উৎকৃষ্টতা মিলিত হয় এবং প্রতিটি শিক্ষার্থীকে গুরুত্ব দেওয়া হয়।',
                    ],
                    'principal' => [
                        'name' => 'অধ্যাপক',
                        'message' => "আমাদের স্কুলের ওয়েবসাইটে আপনাকে স্বাগতম। আমাদের অনুগ্রহশীল শিক্ষকমণ্ডলী, সমৃদ্ধ পাঠ্যক্রম এবং সহায়ক পরিবেশ শিক্ষার্থীদের শৈক্ষিক ও দায়িত্বশীল নাগরিক হিসেবে গড়ে তুলতে সাহায্য করে।\n\nআমরা আপনাকে ভর্তি প্রক্রিয়া অনুসন্ধান, আমাদের অনুষদের সাথে পরিচিত হতে এবং অভিভাবক পোর্টালের মাধ্যমে সংযুক্ত থাকতে আমন্ত্রণ জানাই।",
                    ],
                    'testimonials' => [
                        ['quote' => 'শিক্ষকরা সত্যিই যত্নশীল — আমার মেয়ে এখানে ফুলে ফুলে উঠেছে।', 'name' => 'আ. রহমান', 'role' => 'অভিভাবক'],
                        ['quote' => 'শক্তিশালী শৈক্ষিক কার্যক্রম ও ক্লাব — আমি বিজ্ঞানের প্রতি আমার আবেগ খুঁজে পেয়েছি।', 'name' => 'এস. খান', 'role' => 'প্রাক্তন ছাত্র'],
                    ],
                    'highlights' => [
                        'STEM ও শিল্পকলা সহ সমন্বিত পাঠ্যক্রম',
                        'প্রতিযোগিতামূলক খেলাধুলা ও সাংস্কৃতিক অনুষ্ঠান',
                        'কাউন্সেলিং ও বিশ্ববিদ্যালয় নির্দেশনা',
                    ],
                    'partners' => [
                        ['name' => 'শিক্ষা মন্ত্রণালয়', 'url' => 'https://moedu.gov.bd', 'color' => 'blue', 'icon' => 'book'],
                        ['name' => 'প্রাথমিক ও মাধ্যমিক ও গণশিক্ষা মন্ত্রণালয়', 'url' => 'https://moedu.gov.bd', 'color' => 'emerald', 'icon' => 'school'],
                        ['name' => 'মাধ্যমিক ও উচ্চ মাধ্যমিক শিক্ষা বোর্ড', 'url' => 'https://ducationboard.gov.bd', 'color' => 'amber', 'icon' => 'award'],
                        ['name' => 'জাতীয় পরীক্ষা বোর্ড', 'url' => 'http://www.biseb.gov.bd', 'color' => 'purple', 'icon' => 'clipboard'],
                        ['name' => 'জাতীয় শিক্ষক প্রশিক্ষণ ইনস্টিটিউট', 'url' => 'https://www.teachers.gov.bd', 'color' => 'rose', 'icon' => 'users'],
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
            'terms' => [
                'title' => 'Terms & conditions',
                'title_bn' => 'শর্তাবলী',
                'meta_description' => 'Terms of use for this website and related school services.',
                'meta_description_bn' => 'এই ওয়েবসাইট ও সম্পর্কিত স্কুল সেবা ব্যবহারের শর্তাবলী।',
                'content' => [
                    'intro' => 'By using this website and our services, you agree to the following terms. Please read them carefully; the school may update this page from time to time.',
                    'sections' => [
                        $section('Use of the website', [
                            'This site is provided for information about the school, admissions, and community updates. You agree not to misuse the site, attempt unauthorised access, or distribute harmful content.',
                        ]),
                        $section('Accounts and portal', [
                            'Portal accounts are for authorised parents, students, and staff. You are responsible for keeping login details confidential and for activity under your account.',
                        ]),
                        $section('Intellectual property', [
                            'Text, images, logos, and materials on this site are owned by the school or used with permission. Reuse requires prior written consent unless otherwise stated.',
                        ]),
                        $section('Limitation of liability', [
                            'Information on this site is provided in good faith. The school is not liable for indirect loss arising from use of the site or reliance on its content, to the extent permitted by law.',
                        ]),
                        $section('Contact', [
                            'Questions about these terms can be directed to the school office using the contact details published on this website.',
                        ]),
                    ],
                ],
                'content_bn' => [
                    'intro' => 'এই ওয়েবসাইট ও আমাদের সেবা ব্যবহার করে আপনি নিম্নলিখিত শর্তাবলীতে সম্মত হচ্ছেন। অনুগ্রহ করে এগুলো মনোযোগ দিয়ে পড়ুন; স্কুল সময়ে সময়ে এই পৃষ্ঠা হালনাগাদ করতে পারে।',
                    'sections' => [
                        $section('ওয়েবসাইট ব্যবহার', [
                            'এই সাইট স্কুল, ভর্তি ও কমিউনিটি আপডেট সম্পর্কে তথ্য প্রদানের জন্য প্রদান করা হয়েছে। আপনি সাইটের অপব্যবহার, অননুমোদিত প্রবেশ প্রচেষ্টা বা ক্ষতিকর বিষয়বস্তু বিতরণ না করতে সম্মত হচ্ছেন।',
                        ]),
                        $section('অ্যাকাউন্ট ও পোর্টাল', [
                            'পোর্টাল অ্যাকাউন্ট অনুমোদিত অভিভাবক, ছাত্র ও কর্মচারীদের জন্য। আপনি লগইন তথ্য গোপনীয় রাখার এবং আপনার অ্যাকাউন্টের কার্যকলাপের দায়িত্ব বহন করবেন।',
                        ]),
                        $section('বৌদ্ধিক সম্পত্তি', [
                            'এই সাইটের পাঠ্য, ছবি, লোগো ও উপাদান স্কুলের সম্পত্তি বা অনুমতিক্রমে ব্যবহৃত। অন্যথা উল্লেখ না থাকলে পুনঃব্যবহারের জন্য পূর্বের লিখিত সম্মতি প্রয়োজন।',
                        ]),
                        $section('দায়মুক্তির সীমাবদ্ধতা', [
                            'এই সাইটের তথ্য সৎ উদ্দেশ্যে প্রদান করা হয়েছে। আইনের অনুমত সীমার মধ্যে, সাইট ব্যবহার বা এর বিষয়বস্তুর উপর নির্ভরতাজনিত পরোক্ষ ক্ষতির জন্য স্কুল দায়ী নয়।',
                        ]),
                        $section('যোগাযোগ', [
                            'এই শর্তাবলী সম্পর্কে প্রশ্ন এই ওয়েবসাইটে প্রকাশিত যোগাযোগের তথ্য ব্যবহার করে স্কুল অফিসে জানানো যেতে পারে।',
                        ]),
                    ],
                ],
            ],
            'privacy' => [
                'title' => 'Privacy policy',
                'title_bn' => 'গোপনীয়তা নীতি',
                'meta_description' => 'How we collect, use, and protect personal information.',
                'meta_description_bn' => 'আমরা কীভাবে আপনার তথ্য সংগ্রহ, ব্যবহার ও সুরক্ষা করি।',
                'content' => [
                    'intro' => 'We respect your privacy. This policy explains what information we may collect, how we use it, and your choices. It applies to this website and related processes such as admissions and the parent portal.',
                    'sections' => [
                        $section('Information we collect', [
                            'We may collect information you provide (for example on contact or application forms), technical data such as IP address and browser type, and records needed to run the school and portal.',
                        ]),
                        $section('How we use information', [
                            'We use data to respond to enquiries, process admissions, operate teaching and administration, comply with law, and improve our services and website security.',
                        ]),
                        $section('Sharing and retention', [
                            'We do not sell personal data. We may share information with service providers who assist our operations (under contract), or when required by law. We retain data only as long as needed for these purposes.',
                        ]),
                        $section('Cookies and analytics', [
                            'We may use cookies or similar technologies where needed for site function or to understand aggregate usage. You can control cookies through your browser settings.',
                        ]),
                        $section('Your rights', [
                            'Depending on applicable law, you may have rights to access, correct, or delete certain personal data. Contact the school office to exercise these rights or ask questions.',
                        ]),
                    ],
                ],
                'content_bn' => [
                    'intro' => 'আমরা আপনার গোপনীয়তাকে সম্মান করি। এই নীতিতে ব্যাখ্যা করা হয়েছে যে আমরা কী ধরনের তথ্য সংগ্রহ করতে পারি, কীভাবে আমরা এটি ব্যবহার করি এবং আপনার পছন্দসমূহ। এটি এই ওয়েবসাইট এবং ভর্তি ও অভিভাবক পোর্টালসহ সম্পর্কিত প্রক্রিয়াগুলোর ক্ষেত্রে প্রযোজ্য।',
                    'sections' => [
                        $section('আমরা যে তথ্য সংগ্রহ করি', [
                            'আমরা আপনার প্রদত্ত তথ্য (যেমন যোগাযোগ বা আবেদন ফর্মে), আইপি ঠিকানা ও ব্রাউজার টাইপের মতো প্রযুক্তিগত তথ্য, এবং স্কুল ও পোর্টাল পরিচালনার জন্য প্রয়োজনীয় রেকর্ড সংগ্রহ করতে পারি।',
                        ]),
                        $section('আমরা কীভাবে তথ্য ব্যবহার করি', [
                            'আমরা প্রশ্নের উত্তর দিতে, ভর্তি প্রক্রিয়া করতে, শিক্ষা ও প্রশাসন পরিচালনা করতে, আইন মেনে চলতে এবং আমাদের সেবা ও ওয়েবসাইট নিরাপত্তা উন্নত করতে তথ্য ব্যবহার করি।',
                        ]),
                        $section('তথ্য শেয়ার ও সংরক্ষণ', [
                            'আমরা ব্যক্তিগত তথ্য বিক্রি করি না। আমরা চুক্তির ভিত্তিতে আমাদের পরিচালনায় সহায়তা কারী সেবা প্রদানকারীদের সাথে তথ্য শেয়ার করতে পারি, অথবা আইন অনুযায়ী প্রয়োজন হলে। আমরা এই উদ্দেশ্য পূরণের জন্য প্রয়োজনীয় সময়ের জন্যই তথ্য সংরক্ষণ করি।',
                        ]),
                        $section('কুকি ও অ্যানালিটিক্স', [
                            'ওয়েবসাইটের কার্যক্ষমতা বা সামগ্রিক ব্যবহার বোঝার জন্য প্রয়োজন হলে আমরা কুকি বা অনুরূপ প্রযুক্তি ব্যবহার করতে পারি। আপনি আপনার ব্রাউজার সেটিংসের মাধ্যমে কুকি নিয়ন্ত্রণ করতে পারেন।',
                        ]),
                        $section('আপনার অধিকার', [
                            'প্রযোজ্য আইনের উপর নির্ভর করে, আপনার কিছু ব্যক্তিগত তথ্য অ্যাক্সেস, সংশোধন বা মুছে ফেলার অধিকার থাকতে পারে। এই অধিকার প্রয়োগ করতে বা প্রশ্ন করতে স্কুল অফিসে যোগাযোগ করুন।',
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
