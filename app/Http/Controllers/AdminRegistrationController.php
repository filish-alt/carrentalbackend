<?php

namespace App\Http\Controllers;

use App\Http\Request\Admin\AdminRegistrationRequest;
use App\Services\AdminRegistrationService;
use Illuminate\Http\JsonResponse;

class AdminRegistrationController extends Controller
{
    protected $adminRegistrationService;

    public function __construct(AdminRegistrationService $adminRegistrationService)
    {
        $this->adminRegistrationService = $adminRegistrationService;
    }
     /**
     * Register a new admin user
     *
     * @param AdminRegistrationRequest $request
     * 
     */
     /**
     * @OA\Tag(
     *     name="Admin",
     *     description="Operations related to Admin users"
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/admin/register",
     *     summary="Register a new admin user",
     *     tags={"Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Admin registered successfully."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Registration failed"
     *     )
     * )
     */
    public function register(AdminRegistrationRequest $request): JsonResponse
    {
        try {
            $result = $this->adminRegistrationService->adminRegister($request->validated());
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

      /**
     * @OA\Get(
     *     path="/api/admin/list",
     *     summary="Get all registered admin users",
     *     tags={"Admin"},
     *     @OA\Response(
     *         response=200,
     *         description="List of admin users fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All admin fetched successfully."),
     *             @OA\Property(property="users", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getAllAdmin()
    {
        $users = $this->adminRegistrationService->getAllAdmins();
        return response()->json([
            'message' => 'All admin fetched successfully.',
            'users' => $users,
        ]);
    }
}



