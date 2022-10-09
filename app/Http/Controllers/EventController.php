<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventDeleteRequest;
use App\Http\Requests\EventDetailUpdateRequest;
use App\Http\Requests\EventListRequest;
use App\Http\Requests\EventStoreRequest;
use App\Models\Event;
use App\Models\EventDetail;
use App\Models\Posts;
use DateTime;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(EventListRequest $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $sellerId = $request->get('seller_id');

        $events = EventDetail::where(function ($query) use ($start, $end) {
            return $query->whereBetween('start', [$start, $end])
                ->orWhereBetween('end', [$start, $end])
                ->orWhere(function ($query) use ($start, $end) {
                    return $query->where('start', '<=', $start)
                        ->where('end', '>=', $end);
                });
        })->when($request->has('seller_id'), function ($query) use ($sellerId) {
            return $query->where('seller_id', $sellerId);
        })->get();

        return response()->json([
            'message' => 'Events fetched successfully',
            'events' => $events,
        ], 200);
    }

    public function show($eventId)
    {
        $eventDetails = EventDetail::find($eventId);
        $event = Event::find($eventDetails->event_id);

        return response()->json([
            'message' => 'Event fetched successfully',
            'eventDetails' => $eventDetails,
            'event' => $event,
        ], 200);
    }

    public function store(EventStoreRequest $request)
    {
        if ($request->seller_id) {
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
        }

        if ($request->repeat && (!$request->repeat_count && !$request->repeat_until)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'repeat_count' => ['The repeat count or repeat until field is required when repeat is true.'],
                    'repeat_until' => ['The repeat count or repeat until field is required when repeat is true.'],
                ],
            ], 422);
        }

        DB::transaction(function () use ($request) {
            $event = Event::create([
                'title' => $request->title,
                'notes' => $request->notes,
                'all_day' => $request->all_day,
                'start' => $request->start,
                'end' => $request->end,
                'seller_id' => $request->seller_id,
                'repeat' => $request->repeat,
                'repeat_type' => $request->repeat_type,
                'repeat_interval' => $request->repeat_interval,
                'repeat_count' => $request->repeat_count,
                'repeat_until' => $request->repeat_until,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->createEventDetails($event);

            return response()->json($event, 201);
        });
    }

    private function createEventDetails(Event $event)
    {
        EventDetail::where('event_id', $event->id)->delete();

        $startDatetime = new DateTime($event->start);
        $endDatetime = new DateTime($event->end);
        $eventsCreated = 0;

        do {
            EventDetail::create([
                'event_id' => $event->id,
                'title' => $event->title,
                'notes' => $event->notes,
                'all_day' => $event->all_day,
                'start' => $startDatetime->format('Y-m-d H:i:s'),
                'end' => $endDatetime->format('Y-m-d H:i:s'),
                'seller_id' => $event->seller_id,
                'updated_by' => $event->updated_by,
                'all_day'
            ]);

            if ($event->repeat_count || $event->repeat_until) {
                $startDatetime->modify('+' . $event->repeat_interval . ' ' . $event->repeat_type);
                $endDatetime->modify('+' . $event->repeat_interval . ' ' . $event->repeat_type);

                $eventsCreated++;
            }

            if ($event->repeat_count) {
                $hasEventToCreate = $eventsCreated < $event->repeat_count;
            } else if ($event->repeat_until) {
                $hasEventToCreate = $startDatetime->format('Y-m-d H:i:s') < $event->repeat_until;
            } else {
                $hasEventToCreate = false;
            }
        } while ($hasEventToCreate);
    }

    public function update(EventDetailUpdateRequest $request, $id)
    {
        $eventDetail = EventDetail::find($id);

        if ($request->seller_id) {
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
        }

        if ($request->repeat && (!$request->repeat_count && !$request->repeat_until)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'repeat_count' => ['The repeat count or repeat until field is required when repeat is true.'],
                    'repeat_until' => ['The repeat count or repeat until field is required when repeat is true.'],
                ],
            ], 422);
        }

        if ($request->update_type == 'all') {
            $eventDetailStartDatetime = new DateTime($eventDetail->start);
            $requestStartDatetime = new DateTime($request->start);

            $eventDate = $eventDetailStartDatetime->format('Y-m-d');
            $requestDate = $requestStartDatetime->format('Y-m-d');

            if ($eventDate != $requestDate) {
                return response()->json([
                    'message' => 'A data de início deve ser a mesma quando atualizando todos os eventos',
                ], 422);
            }
        }

        switch ($request->update_type) {
            case 'all':
                return $this->updateAll($request, $eventDetail);
                break;
            case 'one':
                return $this->updateOne($request, $eventDetail);
                break;
            case 'after':
                return $this->updateAfter($request, $eventDetail);
                break;
        }
    }

    private function updateAll(EventDetailUpdateRequest $request, EventDetail $eventDetail)
    {
        $event = Event::find($eventDetail->event_id);

        $eventStartDatetime = new DateTime($event->start);
        $requestStartDatetime = new DateTime($request->start);
        $requestEndDatetime = new DateTime($request->end);

        $eventDuration = $requestStartDatetime->diff($requestEndDatetime);
        $newStart = $eventStartDatetime->format('Y-m-d') . ' ' . $requestStartDatetime->format('H:i:s');
        $newEnd = $eventStartDatetime->add($eventDuration)->format('Y-m-d H:i:s');

        $event->update([
            'all_day' => $request->all_day,
            'title' => $request->title,
            'notes' => $request->notes,
            'start' => $newStart,
            'end' => $newEnd,
            'seller_id' => $request->seller_id,
            'repeat' => $request->repeat,
            'repeat_type' => $request->repeat_type,
            'repeat_interval' => $request->repeat_interval,
            'repeat_count' => $request->repeat_count,
            'repeat_until' => $request->repeat_until,
            'updated_by' => $request->user()->id,
        ]);

        $this->createEventDetails($event);

        return response()->json($event, 200);
    }

    private function updateOne(EventDetailUpdateRequest $request, EventDetail $eventDetail)
    {
        $eventDetail->update([
            'all_day' => $request->all_day,
            'title' => $request->title,
            'notes' => $request->notes,
            'start' => $request->start,
            'end' => $request->end,
            'seller_id' => $request->seller_id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($eventDetail, 200);
    }

    private function updateAfter(EventDetailUpdateRequest $request, EventDetail $eventDetail)
    {
        DB::transaction(function () use ($request, $eventDetail) {
            $this->deleteAfter($eventDetail);

            $event = Event::create([
                'title' => $request->title,
                'notes' => $request->notes,
                'all_day' => $request->all_day,
                'start' => $request->start,
                'end' => $request->end,
                'seller_id' => $request->seller_id,
                'repeat' => $request->repeat,
                'repeat_type' => $request->repeat_type,
                'repeat_interval' => $request->repeat_interval,
                'repeat_count' => $request->repeat_count,
                'repeat_until' => $request->repeat_until,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->createEventDetails($event);

            return response()->json($event, 201);
        });
    }

    public function delete(EventDeleteRequest $request, $id)
    {
        $eventDetail = EventDetail::find($id);

        switch ($request->delete_type) {
            case 'all':
                return $this->deleteAll($eventDetail);
                break;
            case 'one':
                return $this->deleteOne($eventDetail);
                break;
            case 'after':
                return $this->deleteAfter($eventDetail);
                break;
        }
    }

    private function deleteAll(EventDetail $eventDetail)
    {
        $event = Event::find($eventDetail->event_id);

        EventDetail::where('event_id', $event->id)->delete();

        $event->delete();

        return response()->json(null, 204);
    }

    private function deleteOne(EventDetail $eventDetail)
    {
        $eventDetail->delete();

        return response()->json(null, 204);
    }

    private function deleteAfter(EventDetail $eventDetail)
    {
        $event = Event::find($eventDetail->event_id);

        $event->repeat_count = null;
        $event->repeat_until = $eventDetail->start;

        $event->save();

        EventDetail::where('event_id', $event->id)
            ->where('start', '>=', $eventDetail->start)
            ->delete();

        return response()->json(null, 204);
    }
}
