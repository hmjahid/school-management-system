<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CareerController extends Controller
{
    /**
     * Get all job listings with optional filters
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobs(Request $request)
    {
        $filters = $request->only(['status', 'department', 'type']);
        
        // This is sample data - in a real app, this would come from a database
        $jobs = $this->getSampleJobs();
        
        // Apply filters if provided
        if (!empty($filters['status'])) {
            $jobs = array_filter($jobs, function($job) use ($filters) {
                return $job['status'] === $filters['status'];
            });
        }
        
        if (!empty($filters['department'])) {
            $jobs = array_filter($jobs, function($job) use ($filters) {
                return $job['department'] === $filters['department'];
            });
        }
        
        if (!empty($filters['type'])) {
            $jobs = array_filter($jobs, function($job) use ($filters) {
                return $job['type'] === $filters['type'];
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => array_values($jobs), // Reindex array after filtering
            'message' => 'Job listings retrieved successfully.'
        ]);
    }
    
    /**
     * Get details of a specific job
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobDetails($id)
    {
        $jobs = $this->getSampleJobs();
        $job = collect($jobs)->firstWhere('id', $id);
        
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => 'Job details retrieved successfully.'
        ]);
    }
    
    /**
     * Submit a job application
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'cover_letter' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // In a real application, you would:
        // 1. Store the uploaded resume file
        // 2. Save the application to the database
        // 3. Send notification emails
        
        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'data' => [
                'application_id' => uniqid('APP-'),
                'applied_at' => now()->toDateTimeString(),
            ]
        ]);
    }
    
    /**
     * Get job categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = [
            ['id' => 'teaching', 'name' => 'Teaching'],
            ['id' => 'administration', 'name' => 'Administration'],
            ['id' => 'support', 'name' => 'Support Staff'],
            ['id' => 'other', 'name' => 'Other'],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Job categories retrieved successfully.'
        ]);
    }
    
    /**
     * Get sample job listings
     * In a real application, this would come from a database
     */
    private function getSampleJobs()
    {
        return [
            [
                'id' => 1,
                'title' => 'Mathematics Teacher',
                'type' => 'Full-time',
                'department' => 'High School',
                'location' => 'Dhaka, Bangladesh',
                'description' => 'We are looking for an experienced Mathematics teacher to join our high school department.',
                'requirements' => [
                    'Master\'s degree in Mathematics or related field',
                    'B.Ed or equivalent teaching certification',
                    'Minimum 3 years of teaching experience',
                    'Strong knowledge of curriculum standards'
                ],
                'responsibilities' => [
                    'Develop and deliver engaging math lessons',
                    'Assess and evaluate student progress',
                    'Participate in school events and meetings',
                    'Collaborate with other faculty members'
                ],
                'posted_date' => '2023-11-01',
                'deadline' => '2023-12-15',
                'status' => 'active',
                'salary' => 'Competitive',
                'experience' => '3+ years',
                'education' => 'Master\'s degree required',
                'created_at' => '2023-11-01 10:00:00',
                'updated_at' => '2023-11-01 10:00:00',
            ],
            [
                'id' => 2,
                'title' => 'Science Laboratory Assistant',
                'type' => 'Part-time',
                'department' => 'Science Department',
                'location' => 'Dhaka, Bangladesh',
                'description' => 'Assist in the preparation and maintenance of science laboratories.',
                'requirements' => [
                    'Bachelor\'s degree in a science-related field',
                    'Previous lab experience preferred',
                    'Knowledge of lab safety procedures',
                    'Attention to detail'
                ],
                'responsibilities' => [
                    'Prepare laboratory equipment and materials',
                    'Maintain inventory of supplies',
                    'Ensure lab safety standards are met',
                    'Assist teachers during lab sessions'
                ],
                'posted_date' => '2023-11-05',
                'deadline' => '2023-12-10',
                'status' => 'active',
                'salary' => 'As per policy',
                'experience' => '1+ years',
                'education' => 'Bachelor\'s degree required',
                'created_at' => '2023-11-05 09:30:00',
                'updated_at' => '2023-11-05 09:30:00',
            ],
            // Add more sample jobs as needed
        ];
    }
}
