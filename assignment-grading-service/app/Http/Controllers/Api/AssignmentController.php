<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // For inter-service communication
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /**
     * Create a new assignment. [cite: 66]
     */
    public function store(Request $request)
    {
        $request->validate([
            'classId' => ['required', 'uuid'], [cite: 66]
            'teacherId' => ['required', 'uuid'], [cite: 66]
            'title' => ['required', 'string', 'max:255'], [cite: 66]
            'description' => ['nullable', 'string'], [cite: 66]
            'dueDate' => ['required', 'date_format:Y-m-d\TH:i:s\Z'], [cite: 66] // ISO 8601 format
            'maxScore' => ['required', 'integer', 'min:0'], [cite: 66]
            'assignmentType' => ['required', 'string', Rule::in(['HOMEWORK', 'QUIZ', 'PROJECT'])], [cite: 66]
        ]);

        // Validate classId and teacherId by calling respective services (as per Phase 2, Section 5)
        try {
            // This is a placeholder for actual service calls.
            // In a real scenario, you would use Consul to find service addresses.
            // For now, we use a mock or hardcoded URL for demonstration.
            $userServiceBaseUrl = env('USER_SERVICE_BASE_URL', 'http://user-management-service:80'); // Internal Docker network name
            $classServiceBaseUrl = env('CLASS_SERVICE_BASE_URL', 'http://class-management-service:80'); // Internal Docker network name (assuming it exists)

            // Validate teacherId with User Management Service 
            $teacherResponse = Http::timeout(5)->get("{$userServiceBaseUrl}/api/users/{$request->teacherId}");

            if ($teacherResponse->status() !== 200 || $teacherResponse->json('roleName') !== 'TEACHER') { [cite: 78, 79, 80]
                return response()->json(['message' => 'Invalid or non-teacher teacherId provided.'], 400); [cite: 81]
            }

            // Validate classId with Class Management Service (Placeholder - assuming it exists)
            // $classResponse = Http::timeout(5)->get("{$classServiceBaseUrl}/api/classes/{$request->classId}");
            // if ($classResponse->status() !== 200) {
            //     return response()->json(['message' => 'Invalid classId provided.'], 400);
            // }

        } catch (\Exception $e) {
            Log::error("Inter-service communication failed for assignment creation: " . $e->getMessage());
            return response()->json(['message' => 'Failed to validate teacher or class ID.'], 500);
        }


        try {
            $assignment = Assignment::create([
                'class_id' => $request->classId,
                'teacher_id' => $request->teacherId,
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => \Carbon\Carbon::parse($request->dueDate),
                'max_score' => $request->maxScore,
                'assignment_type' => $request->assignmentType,
                'status' => 'ACTIVE',
            ]);

            return response()->json([
                'assignmentId' => $assignment->id,
                'classId' => $assignment->class_id,
                'teacherId' => $assignment->teacher_id,
                'title' => $assignment->title,
                'dueDate' => $assignment->due_date->toIso8601String(),
                'status' => $assignment->status,
            ], 201); // 201 Created [cite: 67]

        } catch (\Exception $e) {
            Log::error("Failed to create assignment: " . $e->getMessage());
            return response()->json(['message' => 'Could not create assignment.'], 500);
        }
    }

    /**
     * Retrieve a single assignment's details. [cite: 67]
     */
    public function show($assignmentId)
    {
        $assignment = Assignment::where('id', $assignmentId)->first();

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404); [cite: 69]
        }

        return response()->json([
            'assignmentId' => $assignment->id,
            'classId' => $assignment->class_id,
            'teacherId' => $assignment->teacher_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'dueDate' => $assignment->due_date->toIso8601String(),
            'maxScore' => $assignment->max_score,
            'assignmentType' => $assignment->assignment_type,
            'status' => $assignment->status,
        ], 200); // 200 OK [cite: 68]
    }

    /**
     * Retrieve a list of assignments. [cite: 69]
     */
    public function index(Request $request)
    {
        $query = Assignment::query();

        if ($request->has('classId')) {
            $query->where('class_id', $request->input('classId')); [cite: 70]
        }

        if ($request->has('teacherId')) {
            $query->where('teacher_id', $request->input('teacherId')); [cite: 70]
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status')); [cite: 70]
        }

        $assignments = $query->paginate($request->input('size', 15), ['*'], 'page', $request->input('page', 1)); [cite: 70]

        if ($assignments->isEmpty()) {
            return response()->json([], 204); // 204 No Content [cite: 71]
        }

        return response()->json($assignments->items(), 200); // Array of assignment objects [cite: 71]
    }
}