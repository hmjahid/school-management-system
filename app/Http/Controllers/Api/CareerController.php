<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CareerController extends Controller
{
    /**
     * Get all career opportunities
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Career::where('is_published', true)
                ->orderBy('created_at', 'desc');
                
            $careers = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $careers,
                'message' => 'Career opportunities retrieved successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching careers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve career opportunities.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get a specific career opportunity
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $career = Career::where('is_published', true)
                ->findOrFail($id);
                
            return response()->json([
                'success' => true,
                'data' => $career,
                'message' => 'Career opportunity retrieved successfully.'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Career opportunity not found.'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error fetching career: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve career opportunity.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Submit a job application
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(Request $request)
    {
        try {
            $validated = $request->validate([
                'career_id' => 'required|exists:careers,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
                'cover_letter' => 'nullable|string',
            ]);
            
            // Handle file upload
            if ($request->hasFile('resume')) {
                $path = $request->file('resume')->store('resumes', 'public');
                $validated['resume_path'] = $path;
            }
            
            // In a real app, you would save this to the database
            // For now, we'll just return a success response
            
            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'data' => $validated
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error submitting job application: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
