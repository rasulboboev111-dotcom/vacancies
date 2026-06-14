<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIntakeRequest;
use App\Services\ApplicationIntakeService;
use Illuminate\Http\JsonResponse;

class ApplicationIntakeController extends Controller
{
    public function store(StoreIntakeRequest $request, ApplicationIntakeService $service): JsonResponse
    {
        $application = $service->upsert($request->validated(), $request->file('resume'));

        return response()->json(['id' => $application->id], 200);
    }
}
