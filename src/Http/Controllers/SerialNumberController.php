<?php

namespace AzahariZaman\ControlledNumber\Http\Controllers;

use AzahariZaman\ControlledNumber\Http\Resources\SerialLogResource;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SerialNumberController extends Controller
{
    public function __construct(
        protected SerialManager $serialManager
    ) {}

    /**
     * Generate a new serial number.
     *
     * POST /api/v1/serial-numbers/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|integer',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            // Resolve model if provided
            $model = null;
            if (!empty($validated['model_type']) && !empty($validated['model_id'])) {
                $modelClass = $validated['model_type'];
                if (class_exists($modelClass)) {
                    $model = $modelClass::findOrFail($validated['model_id']);
                }
            }

            $serial = $this->serialManager->generate(
                $validated['type'],
                $model,
                $validated['context'] ?? []
            );

            $log = SerialLog::where('serial', $serial)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'serial' => $serial,
                    'log' => $log ? new SerialLogResource($log) : null,
                ],
                'message' => 'Serial number generated successfully',
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Model not found',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Preview the next serial number without generating it.
     *
     * GET /api/v1/serial-numbers/{type}/peek
     */
    public function peek(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|integer',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            // Resolve model if provided
            $model = null;
            if (!empty($validated['model_type']) && !empty($validated['model_id'])) {
                $modelClass = $validated['model_type'];
                if (class_exists($modelClass)) {
                    $model = $modelClass::findOrFail($validated['model_id']);
                }
            }

            $preview = $this->serialManager->preview(
                $type,
                $model,
                $validated['context'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'preview' => $preview,
                    'type' => $type,
                ],
                'message' => 'Serial number preview generated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset a sequence counter.
     *
     * POST /api/v1/serial-numbers/{type}/reset
     */
    public function reset(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_value' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            $result = $this->serialManager->resetSequence(
                $type,
                $validated['start_value'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sequence not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sequence reset successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Void a serial number.
     *
     * POST /api/v1/serial-numbers/{serial}/void
     */
    public function void(Request $request, string $serial): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            $result = $this->serialManager->void(
                $serial,
                $validated['reason'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number not found',
                ], 404);
            }

            $log = SerialLog::where('serial', $serial)->first();

            return response()->json([
                'success' => true,
                'data' => new SerialLogResource($log),
                'message' => 'Serial number voided successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Query serial logs with filters.
     *
     * GET /api/v1/serial-numbers/logs
     */
    public function logs(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pattern_name' => 'nullable|string',
            'is_void' => 'nullable|boolean',
            'user_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            $query = SerialLog::query();

            // Apply filters
            if (!empty($validated['pattern_name'])) {
                $query->where('pattern_name', $validated['pattern_name']);
            }

            if (isset($validated['is_void'])) {
                $query->where('is_void', $validated['is_void']);
            }

            if (!empty($validated['user_id'])) {
                $query->where('user_id', $validated['user_id']);
            }

            if (!empty($validated['from_date'])) {
                $query->where('generated_at', '>=', $validated['from_date']);
            }

            if (!empty($validated['to_date'])) {
                $query->where('generated_at', '<=', $validated['to_date']);
            }

            $perPage = $validated['per_page'] ?? 15;
            $logs = $query->orderBy('generated_at', 'desc')->paginate($perPage);

            return SerialLogResource::collection($logs);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
