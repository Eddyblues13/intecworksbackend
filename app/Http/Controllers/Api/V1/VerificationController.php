<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VerificationDocument;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinary,
    ) {}

    // ───────────────────────── Cloudinary Config ─────────────────────────

    /**
     * Return the cloud name + unsigned upload preset so the Flutter app
     * can upload files directly to Cloudinary (client-side upload).
     */
    public function cloudinaryConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->cloudinary->getConfig(),
        ]);
    }

    // ───────────────────────── Submit Documents ─────────────────────────

    /**
     * Accept Cloudinary secure_url values that the Flutter app already
     * uploaded directly.  No multipart file handling on the server.
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'idDocumentUrl'      => 'required|url',
            'idDocumentPublicId' => 'required|string',
            'certificationUrl'      => 'nullable|url',
            'certificationPublicId' => 'nullable|string',
            'portfolioUrl'          => 'nullable|url',
            'portfolioPublicId'     => 'nullable|string',
        ]);

        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json([
                'message' => 'Only providers can submit verification documents.',
            ], 403);
        }

        $docData = [
            'user_id'               => $user->id,
            'id_document_url'       => $request->input('idDocumentUrl'),
            'id_document_public_id' => $request->input('idDocumentPublicId'),
            'status'                => 'pending',
        ];

        if ($request->filled('certificationUrl')) {
            $docData['certification_url']       = $request->input('certificationUrl');
            $docData['certification_public_id'] = $request->input('certificationPublicId');
        }

        if ($request->filled('portfolioUrl')) {
            $docData['portfolio_url']       = $request->input('portfolioUrl');
            $docData['portfolio_public_id'] = $request->input('portfolioPublicId');
        }

        $doc = VerificationDocument::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending'],
            $docData,
        );

        $user->update(['account_status' => 'verification_under_review']);

        return response()->json([
            'message' => 'Documents submitted for review.',
            'status'  => 'verification_under_review',
        ]);
    }

    // ───────────────────────── Check Status ─────────────────────────

    public function checkStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $doc = VerificationDocument::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'status'          => $user->flutter_account_status,
            'rejectionReason' => $doc?->rejection_reason,
            'reviewedAt'      => $doc?->reviewed_at?->toIso8601String(),
        ]);
    }

    // ───────────────────────── Resubmit After Rejection ─────────────────

    public function resubmit(Request $request): JsonResponse
    {
        $request->validate([
            'idDocumentUrl'      => 'required|url',
            'idDocumentPublicId' => 'required|string',
            'certificationUrl'      => 'nullable|url',
            'certificationPublicId' => 'nullable|string',
        ]);

        $user = $request->user();

        if ($user->account_status !== 'rejected') {
            return response()->json([
                'message' => 'Resubmission only allowed for rejected accounts.',
            ], 403);
        }

        $docData = [
            'user_id'               => $user->id,
            'id_document_url'       => $request->input('idDocumentUrl'),
            'id_document_public_id' => $request->input('idDocumentPublicId'),
            'status'                => 'pending',
            'rejection_reason'      => null,
            'reviewed_at'           => null,
        ];

        if ($request->filled('certificationUrl')) {
            $docData['certification_url']       = $request->input('certificationUrl');
            $docData['certification_public_id'] = $request->input('certificationPublicId');
        }

        VerificationDocument::create($docData);

        $user->update([
            'account_status'   => 'verification_under_review',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Documents resubmitted for review.',
            'status'  => 'verification_under_review',
        ]);
    }
}
