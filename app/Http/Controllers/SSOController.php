<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SSOService;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="SSO Authentication",
 *     description="Single Sign-On authentication endpoints (Google OAuth)"
 * )
 */
class SSOController extends Controller
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google",
     *     summary="Redirect to Google OAuth",
     *     tags={"SSO Authentication"},
     *     description="Initiate Google OAuth authentication flow",
     *     @OA\Parameter(
     *         name="platform",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"web", "mobile"}),
     *         description="Platform type for appropriate callback",
     *         example="web"
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google OAuth page"
     *     )
     * )
     */
    public function redirectToGoogle(Request $request)
    {
        $platform = $request->query('platform');
        $redirectUrl = $this->ssoService->redirectToGoogle($platform);
        return redirect($redirectUrl);
    }

    public function handleGoogleCallback()
    {
        try {
            $result = $this->ssoService->handleGoogleCallback();
            return redirect($result['redirect_url']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exchangeCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $result = $this->ssoService->exchangeCode($request->code);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}

