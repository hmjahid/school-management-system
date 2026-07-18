<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegalController extends Controller
{
    /**
     * Get the Terms of Service content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTerms()
    {
        $content = [
            'pageTitle' => 'Terms of Service',
            'heroTitle' => 'Terms of Service',
            'lastUpdated' => now()->format('F j, Y'),
            'sections' => [
                [
                    'title' => '1. Introduction',
                    'content' => 'Welcome to our school website. These terms of service outline the rules and regulations for the use of our website.'
                ],
                [
                    'title' => '2. Intellectual Property Rights',
                    'content' => 'All content published and made available on our website is the property of the school and its content creators.'
                ],
                [
                    'title' => '3. User Responsibilities',
                    'content' => 'As a user of our website, you agree to use our website legally, not to use our website for illegal purposes, and not to harass or cause distress to other users.'
                ],
                [
                    'title' => '4. Limitation of Liability',
                    'content' => 'The school will not be liable for any actions, claims, losses, damages, or expenses arising from your use of the website.'
                ],
                [
                    'title' => '5. Changes to Terms',
                    'content' => 'We reserve the right to modify these terms at any time. Your continued use of the website after any changes constitutes your acceptance of the new terms.'
                ]
            ]
        ];

        return response()->json($content);
    }

    /**
     * Get the Privacy Policy content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrivacy()
    {
        $content = [
            'pageTitle' => 'Privacy Policy',
            'heroTitle' => 'Privacy Policy',
            'lastUpdated' => now()->format('F j, Y'),
            'introduction' => 'We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.',
            'sections' => [
                [
                    'title' => '1. Information We Collect',
                    'content' => 'We may collect personal information such as your name, email address, phone number, and other information you provide when you register, subscribe to our newsletter, or fill out a form on our website.'
                ],
                [
                    'title' => '2. How We Use Your Information',
                    'content' => 'We may use the information we collect to provide and maintain our services, notify you about changes, allow you to participate in interactive features, provide customer support, and gather analysis to improve our website.'
                ],
                [
                    'title' => '3. Information Sharing and Disclosure',
                    'content' => 'We do not sell, trade, or rent your personal information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners and advertisers.'
                ],
                [
                    'title' => '4. Data Security',
                    'content' => 'We implement appropriate data collection, storage, and processing practices and security measures to protect against unauthorized access, alteration, disclosure, or destruction of your personal information.'
                ],
                [
                    'title' => '5. Your Rights',
                    'content' => 'You have the right to access, correct, or delete your personal information. You may also have the right to object to or restrict certain processing of your data.'
                ],
                [
                    'title' => '6. Changes to This Privacy Policy',
                    'content' => 'We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.'
                ]
            ],
            'contact' => [
                'email' => 'privacy@school.edu',
                'phone' => '(123) 456-7890',
                'address' => '123 School Street, City, Country'
            ]
        ];

        return response()->json($content);
    }

    /**
     * Get the Sitemap content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSitemap()
    {
        $content = [
            'pageTitle' => 'Sitemap',
            'heroTitle' => 'Website Sitemap',
            'lastUpdated' => now()->format('F j, Y'),
            'description' => 'Browse our complete website structure to find what you\'re looking for.',
            'sections' => [
                [
                    'title' => 'Main Pages',
                    'links' => [
                        [
                            'text' => 'Home',
                            'url' => '/',
                            'description' => 'Welcome to our school'
                        ],
                        [
                            'text' => 'About Us',
                            'url' => '/about',
                            'description' => 'Learn about our history, mission, and values'
                        ],
                        [
                            'text' => 'Academics',
                            'url' => '/academics',
                            'description' => 'Explore our academic programs and curriculum'
                        ],
                        [
                            'text' => 'Admissions',
                            'url' => '/admissions',
                            'description' => 'Information about enrollment and requirements'
                        ],
                        [
                            'text' => 'News & Events',
                            'url' => '/news',
                            'description' => 'Latest updates and school events'
                        ],
                        [
                            'text' => 'Gallery',
                            'url' => '/gallery',
                            'description' => 'Photos and videos of school life'
                        ],
                        [
                            'text' => 'Contact Us',
                            'url' => '/contact',
                            'description' => 'Get in touch with our team'
                        ]
                    ]
                ],
                [
                    'title' => 'Legal',
                    'links' => [
                        [
                            'text' => 'Terms of Service',
                            'url' => '/terms',
                            'description' => 'Website terms and conditions'
                        ],
                        [
                            'text' => 'Privacy Policy',
                            'url' => '/privacy',
                            'description' => 'How we handle your information'
                        ],
                        [
                            'text' => 'Sitemap',
                            'url' => '/sitemap',
                            'description' => 'Complete website structure'
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($content);
    }

    /**
     * Get the Home page content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHome()
    {
        $content = [
            'hero' => [
                'title' => 'Welcome to Our School',
                'subtitle' => 'Nurturing young minds for a brighter future',
                'ctaText' => 'Learn More',
                'ctaLink' => '/about',
                'backgroundImage' => '/images/hero-bg.jpg'
            ],
            'features' => [
                [
                    'title' => 'Experienced Faculty',
                    'description' => 'Our dedicated team of educators brings years of experience and expertise to the classroom.',
                    'icon' => 'FaGraduationCap'
                ],
                [
                    'title' => 'Modern Facilities',
                    'description' => 'State-of-the-art classrooms and laboratories to support innovative learning.',
                    'icon' => 'FaChalkboardTeacher'
                ],
                [
                    'title' => 'Comprehensive Curriculum',
                    'description' => 'A well-rounded curriculum that prepares students for future challenges.',
                    'icon' => 'FaBook'
                ],
                [
                    'title' => 'Inclusive Community',
                    'description' => 'A welcoming environment that celebrates diversity and fosters inclusion.',
                    'icon' => 'FaUsers'
                ]
            ],
            'testimonials' => [
                [
                    'quote' => 'This school has provided an excellent learning environment for my child. The teachers are dedicated and caring.',
                    'author' => 'Sarah Johnson',
                    'role' => 'Parent',
                    'rating' => 5
                ],
                [
                    'quote' => 'The facilities and resources available to students are outstanding. Highly recommended!',
                    'author' => 'Michael Chen',
                    'role' => 'Alumni',
                    'rating' => 5
                ]
            ],
            'stats' => [
                ['value' => '98%', 'label' => 'Graduation Rate'],
                ['value' => '25+', 'label' => 'Academic Programs'],
                ['value' => '50+', 'label' => 'Extracurricular Activities'],
                ['value' => '1:12', 'label' => 'Student-Teacher Ratio']
            ]
        ];

        return response()->json($content);
    }
}
