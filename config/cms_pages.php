<?php

/**
 * CMS page schema registry.
 *
 * Each key is a page slug. The value declares:
 *   - label: human-friendly name shown in the editor and sidebar.
 *   - group: sidebar grouping ('content' for body pages, 'global' for system-level).
 *   - description: short blurb shown at the top of the edit form.
 *   - sections: ordered list of typed sections the editor renders. Each
 *     section has a 'type' (text, textarea, image, repeater, list, kv, hero,
 *     group, contact_cards) and a 'key' that maps to the JSON tree in
 *     WebsiteContent::content_en / content_bn.
 *
 * Every page — including the global labels page — is edited through a
 * friendly form, never raw JSON. The editor and controller both read this
 * registry to build inputs and persist the values back.
 *
 * To add a new field: declare it here. No code change required.
 */

return [
    'home' => [
        'label' => 'Home',
        'group' => 'content',
        'description' => 'Hero, principal message, highlights, testimonials shown on the homepage.',
        'sections' => [
            [
                'key' => 'hero',
                'label' => 'Hero banner',
                'help' => 'The large banner at the very top of the homepage.',
                'type' => 'kv',
                'fields' => [
                    ['key' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true],
                    ['key' => 'motto', 'label' => 'Subtitle / motto', 'type' => 'text'],
                    ['key' => 'cta_primary', 'label' => 'Primary button text', 'type' => 'text'],
                    ['key' => 'cta_secondary', 'label' => 'Secondary button text', 'type' => 'text'],
                    ['key' => 'background_image', 'label' => 'Background image', 'type' => 'image', 'shared' => true],
                ],
            ],
            [
                'key' => 'notices',
                'label' => 'Notices panel',
                'help' => 'Title and link for the scrolling notices inside the hero.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Panel title', 'type' => 'text'],
                    ['key' => 'view_all', 'label' => 'View all link text', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'features_heading',
                'label' => 'Features heading',
                'help' => 'Title and intro for the "Why Choose Us" feature cards.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'intro', 'label' => 'Section intro', 'type' => 'textarea', 'rows' => 2],
                ],
            ],
            [
                'key' => 'features',
                'label' => 'Feature cards',
                'help' => 'The 4 feature cards shown on the homepage. Leave blank to use the defaults.',
                'type' => 'repeater',
                'item_label' => 'Feature',
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2],
                ],
            ],
            [
                'key' => 'stats',
                'label' => 'Stats counters',
                'help' => 'Labels shown below each counter number.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'students', 'label' => 'Students', 'type' => 'text'],
                    ['key' => 'faculty', 'label' => 'Faculty', 'type' => 'text'],
                    ['key' => 'years', 'label' => 'Years', 'type' => 'text'],
                    ['key' => 'awards', 'label' => 'Awards', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'principal',
                'label' => "Principal's message",
                'help' => 'Shown as a short block in the middle of the page.',
                'type' => 'kv',
                'fields' => [
                    ['key' => 'section_title', 'label' => 'Section heading', 'type' => 'text'],
                    ['key' => 'photo', 'label' => 'Principal photo', 'type' => 'image'],
                    ['key' => 'name', 'label' => 'Principal name', 'type' => 'text'],
                    ['key' => 'designation', 'label' => 'Designation', 'type' => 'text'],
                    ['key' => 'message', 'label' => 'Message', 'type' => 'textarea', 'rows' => 5],
                ],
            ],
            [
                'key' => 'teachers',
                'label' => 'Teachers section',
                'help' => 'Title, intro, and link for the teachers slider.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'intro', 'label' => 'Section intro', 'type' => 'textarea', 'rows' => 2],
                    ['key' => 'view_all', 'label' => 'View all link text', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'testimonials_heading',
                'label' => 'Testimonials heading',
                'help' => 'Title for the testimonials section.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'testimonials',
                'label' => 'Testimonials',
                'help' => 'Quotes from parents, students, or alumni.',
                'type' => 'repeater',
                'item_label' => 'Testimonial',
                'fields' => [
                    ['key' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['key' => 'role', 'label' => 'Role / class', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'remarkable_students',
                'label' => 'Remarkable students',
                'help' => 'Title and intro for the outstanding students grid.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'intro', 'label' => 'Section intro', 'type' => 'textarea', 'rows' => 2],
                ],
            ],
            [
                'key' => 'events',
                'label' => 'Events section',
                'help' => 'Title and link for the upcoming events block.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'view_all', 'label' => 'View all link text', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'news',
                'label' => 'News section',
                'help' => 'Title and link for the latest news grid.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'view_all', 'label' => 'View all link text', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'highlights_heading',
                'label' => 'Highlights heading',
                'help' => 'Title for the highlights section.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'highlights',
                'label' => 'Highlights',
                'help' => 'Bullet points that appear in the highlights block.',
                'type' => 'list',
                'item_label' => 'Highlight',
            ],
            [
                'key' => 'partners_heading',
                'label' => 'Partners heading',
                'help' => 'Title for the partners strip.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'partners',
                'label' => 'Partners',
                'help' => 'Logos shown in the partner strip. Color options: blue, emerald, amber, purple, rose. Icon options: book, school, award, clipboard, users.',
                'type' => 'repeater',
                'item_label' => 'Partner',
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['key' => 'url', 'label' => 'URL', 'type' => 'text'],
                    ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
                    ['key' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                ],
            ],
        ],
    ],

    'about' => [
        'label' => 'About',
        'group' => 'content',
        'description' => 'School history, vision, leadership, facilities, and achievements.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'help' => 'Each section is a heading followed by paragraphs. Press Enter twice for a new paragraph.', 'type' => 'repeater_sections'],
        ],
    ],

    'academics' => [
        'label' => 'Academics',
        'group' => 'content',
        'description' => 'Curriculum, subjects, assessment, and academic calendar.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'admissions' => [
        'label' => 'Admissions',
        'group' => 'content',
        'description' => 'Admissions process, requirements, fees, and FAQs.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'apply_intro', 'label' => 'Online application intro', 'help' => 'Shown on the admissions form page.', 'type' => 'textarea', 'rows' => 3],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'students' => [
        'label' => 'Student life',
        'group' => 'content',
        'description' => 'Student council, clubs, calendar, uniform, and code of conduct.',
        'sections' => [
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'faculty' => [
        'label' => 'Faculty & staff',
        'group' => 'content',
        'description' => 'Teaching and administrative staff, achievements, and resources.',
        'sections' => [
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'news' => [
        'label' => 'News',
        'group' => 'content',
        'description' => 'Newsletter, press releases, and announcements intro.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'gallery' => [
        'label' => 'Gallery',
        'group' => 'content',
        'description' => 'Photo gallery intro and category labels.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'contact' => [
        'label' => 'Contact',
        'group' => 'content',
        'description' => 'Address, opening hours, and emergency contacts.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            [
                'key' => 'emergency_contacts',
                'label' => 'Emergency contacts',
                'help' => 'Phone numbers shown in the sidebar of the contact page.',
                'type' => 'contact_cards',
            ],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'payments' => [
        'label' => 'Payments',
        'group' => 'content',
        'description' => 'Fee structure and payment guidance for parents.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'terms' => [
        'label' => 'Terms of use',
        'group' => 'content',
        'description' => 'Website and service terms.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'privacy' => [
        'label' => 'Privacy policy',
        'group' => 'content',
        'description' => 'How we collect and protect user data.',
        'sections' => [
            ['key' => 'intro', 'label' => 'Page introduction', 'type' => 'textarea', 'rows' => 4],
            ['key' => 'sections', 'label' => 'Content sections', 'type' => 'repeater_sections'],
        ],
    ],

    'site-ui' => [
        'label' => 'Global labels',
        'group' => 'global',
        'description' => 'Nav, footer, and home-page UI text shown across the public site. Leave any field blank to use the default text from the language file.',
        'sections' => [
            [
                'key' => 'nav',
                'label' => 'Top navigation',
                'help' => 'Main menu links shown in the site header.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'home', 'label' => 'Home', 'type' => 'text'],
                    ['key' => 'about', 'label' => 'About Us', 'type' => 'text'],
                    ['key' => 'academics', 'label' => 'Academics', 'type' => 'text'],
                    ['key' => 'admissions', 'label' => 'Admissions', 'type' => 'text'],
                    ['key' => 'students', 'label' => 'Students', 'type' => 'text'],
                    ['key' => 'faculty', 'label' => 'Faculty', 'type' => 'text'],
                    ['key' => 'news', 'label' => 'News & Events', 'type' => 'text'],
                    ['key' => 'gallery', 'label' => 'Gallery', 'type' => 'text'],
                    ['key' => 'contact', 'label' => 'Contact', 'type' => 'text'],
                    ['key' => 'payments', 'label' => 'Payments', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'footer',
                'label' => 'Footer',
                'help' => 'Column headings and short text shown in the site footer.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'about_title', 'label' => 'About column title', 'type' => 'text'],
                    ['key' => 'about_fallback', 'label' => 'About blurb', 'type' => 'textarea', 'rows' => 2],
                    ['key' => 'quick_links_title', 'label' => 'Quick links title', 'type' => 'text'],
                    ['key' => 'legal_title', 'label' => 'Legal column title', 'type' => 'text'],
                    ['key' => 'contact_title', 'label' => 'Contact column title', 'type' => 'text'],
                    ['key' => 'newsletter_title', 'label' => 'Newsletter title', 'type' => 'text'],
                    ['key' => 'newsletter_intro', 'label' => 'Newsletter intro', 'type' => 'textarea', 'rows' => 2],
                    ['key' => 'newsletter_placeholder', 'label' => 'Newsletter placeholder', 'type' => 'text'],
                    ['key' => 'newsletter_button', 'label' => 'Newsletter button', 'type' => 'text'],
                    ['key' => 'copyright_suffix', 'label' => 'Copyright suffix', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'home',
                'label' => 'Home page',
                'help' => 'CTA banner and common labels shared across homepage sections. Section-specific titles are edited in the Home page CMS.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'cta_banner_title', 'label' => 'CTA banner title', 'type' => 'text'],
                    ['key' => 'cta_banner_intro', 'label' => 'CTA banner intro', 'type' => 'textarea', 'rows' => 2],
                    ['key' => 'cta_apply', 'label' => 'CTA — Apply', 'type' => 'text'],
                    ['key' => 'cta_contact', 'label' => 'CTA — Contact', 'type' => 'text'],
                    ['key' => 'read_more', 'label' => 'Read more', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'auth',
                'label' => 'Login page',
                'help' => 'Headings and labels on the login form.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'login_title', 'label' => 'Login title', 'type' => 'text'],
                    ['key' => 'login_intro', 'label' => 'Login intro', 'type' => 'textarea', 'rows' => 2],
                    ['key' => 'email', 'label' => 'Email field', 'type' => 'text'],
                    ['key' => 'password', 'label' => 'Password field', 'type' => 'text'],
                    ['key' => 'remember', 'label' => 'Remember me', 'type' => 'text'],
                    ['key' => 'sign_in', 'label' => 'Sign in button', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'admissions_bar',
                'label' => 'Admissions bar',
                'help' => 'The top bar shown when admissions are open. Use :year and :next placeholders for the current and next year (e.g. "Admissions Open :year-:next").',
                'type' => 'group',
                'fields' => [
                    ['key' => 'title', 'label' => 'Bar title', 'type' => 'text'],
                    ['key' => 'cta', 'label' => 'CTA button text', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'admissions_landing',
                'label' => 'Admissions page',
                'help' => 'Text on the admissions landing page hero badge. Use :year and :next placeholders.',
                'type' => 'group',
                'fields' => [
                    ['key' => 'badge', 'label' => 'Hero badge text', 'type' => 'text'],
                ],
            ],
        ],
    ],
];
