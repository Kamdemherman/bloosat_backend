<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SystemLog::with('user')->latest('created_at');

        if ($request->filled('user_id'))    $query->where('user_id', $request->user_id);
        if ($request->filled('action'))     $query->where('action', $request->action);
        if ($request->filled('model_type')) $query->where('model_type', 'like', "%{$request->model_type}%");
        if ($request->filled('date'))       $query->whereDate('created_at', $request->date);
        if ($request->filled('search'))     $query->where('action', 'like', "%{$request->search}%");

        return response()->json($query->paginate(50));
    }
}
