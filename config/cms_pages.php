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
        'description' => 'Hero, slider, principal message, highlights, testimonials shown on the homepage.',
        'sections' => [
            [
                'key' => 'hero_design',
                'label' => 'Hero design',
                'help' => 'Choose one of the 6 hero layouts shown at the top of the homepage.',
                'type' => 'select',
                'options' => [
                    'design-1' => 'Design 1 — Dark split with notices panel',
                    'design-2' => 'Design 2 — Centered banner',
                    'design-3' => 'Design 3 — Light split with photo',
                    'design-4' => 'Design 4 — Minimal gradient',
                    'design-5' => 'Design 5 — Full-width image with school name',
                    'design-6' => 'Design 6 — School name with hero slider',
                ],
            ],
            [
                'key' => 'hero',
                'label' => 'Hero content',
                'help' => 'Headline, buttons and background image used by the selected hero design.',
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
                'help' => 'Title and link for the scrolling notices inside hero design 1.',
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
                'key' => 'slider',
                'label' => 'Photo slider',
                'help' => 'Photo carousel showing recent events/activities. Leave empty to auto-show recent events with photos.',
                'type' => 'slider',
                'item_label' => 'Slide',
                'fields' => [
                    ['key' => 'image', 'label' => 'Slide image', 'type' => 'image', 'shared' => true],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'caption', 'label' => 'Caption', 'type' => 'text'],
                    ['key' => 'link', 'label' => 'Link URL', 'type' => 'text'],
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

];
