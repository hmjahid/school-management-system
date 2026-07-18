<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;

class AcademicController extends ApiController
{
    /**
     * Get curriculum data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurriculum()
    {
        $data = [
            'pageTitle' => 'Our Curriculum',
            'heroTitle' => 'Comprehensive Learning Experience',
            'heroSubtitle' => 'A well-rounded curriculum designed to foster academic excellence and personal growth',
            'overview' => 'Our curriculum is carefully designed to provide students with a balanced education that combines academic rigor with practical skills and character development.',
            'programs' => [
                [
                    'id' => 1,
                    'title' => 'Core Subjects',
                    'description' => 'Strong foundation in Mathematics, Science, Languages, and Social Studies',
                    'icon' => 'FaBookOpen',
                    'features' => [
                        'Mathematics & Statistics',
                        'Sciences (Physics, Chemistry, Biology)',
                        'Languages & Literature',
                        'Social Studies & Humanities'
                    ]
                ]
                // Additional programs can be added here
            ]
        ];

        return $this->successResponse($data);
    }

    /**
     * Get academic programs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrograms()
    {
        $data = [
            'pageTitle' => 'Academic Programs',
            'heroTitle' => 'Diverse Learning Opportunities',
            'heroSubtitle' => 'Explore our range of academic programs designed to meet every student\'s needs',
            'programs' => [
                [
                    'id' => 1,
                    'name' => 'Elementary School',
                    'description' => 'Foundational learning for young minds (Grades 1-5)',
                    'features' => [
                        'Literacy & Numeracy Focus',
                        'Exploratory Learning',
                        'Social-Emotional Development',
                        'Creative Expression'
                    ]
                ]
                // Additional programs can be added here
            ]
        ];

        return $this->successResponse($data);
    }

    /**
     * Get faculty information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFaculty()
    {
        $data = [
            'pageTitle' => 'Our Faculty',
            'heroTitle' => 'Dedicated Educators',
            'heroSubtitle' => 'Meet our team of experienced and passionate educators',
            'faculty' => [
                [
                    'id' => 1,
                    'name' => 'Dr. Sarah Johnson',
                    'position' => 'Head of Science Department',
                    'bio' => 'PhD in Physics with 15 years of teaching experience',
                    'image' => '/images/faculty/sarah-johnson.jpg'
                ]
                // Additional faculty members can be added here
            ]
        ];

        return $this->successResponse($data);
    }
}
