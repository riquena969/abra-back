<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkTimeStoreRequest;
use App\Models\Wordpress\Posts;
use App\Models\WorkTime;
use Illuminate\Support\Facades\DB;

class WorkTimesController extends Controller
{
    public function index($sellerId)
    {
        return response()->json(
            WorkTime::where('seller_id', $sellerId)->select(
                'day_of_week',
                'start_time',
                'end_time'
            )->get()
        );
    }

    public function store(WorkTimeStoreRequest $request)
    {
        $seller = Posts::sellers()->find($request->seller_id);

        if (!$seller) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'seller_id' => [
                        'Vendedor não encontrado'
                    ]
                ],
            ], 422);
        }

        DB::transaction(function () use ($request) {
            WorkTime::where('seller_id', $request->seller_id)->delete();

            foreach ($request->work_times as $workTime) {
                WorkTime::create([
                    'seller_id' => $request->seller_id,
                    'day_of_week' => $workTime['day_of_week'],
                    'start_time' => $workTime['start_time'],
                    'end_time' => $workTime['end_time'],
                ]);
            }
        });

        return $this->index($request->seller_id);
    }
}
