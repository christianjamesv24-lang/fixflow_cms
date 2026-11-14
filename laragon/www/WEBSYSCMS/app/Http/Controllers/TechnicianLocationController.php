<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicianLocationController extends Controller
{
    private const ACTIVE_STATUSES = ['assigned', 'accepted', 'in_progress'];

    public function show(int $id): JsonResponse
    {
        $user = $this->authUser();
        $serviceRequest = $this->findRequestForParticipant($id, $user);
        $customerLocation = null;
        $technicianLocation = null;
        $databaseNow = strtotime((string) db_value('SELECT CURRENT_TIMESTAMP'));

        if (in_array($serviceRequest['status'], self::ACTIVE_STATUSES, true)) {
            if ($serviceRequest['latitude'] !== null && $serviceRequest['longitude'] !== null) {
                $customerLocation = [
                    'latitude' => (float) $serviceRequest['latitude'],
                    'longitude' => (float) $serviceRequest['longitude'],
                    'accuracy' => $serviceRequest['location_accuracy'] !== null
                        ? (float) $serviceRequest['location_accuracy']
                        : null,
                ];
            }

            if ($serviceRequest['technician_id'] !== null) {
                $location = db_one(
                    'SELECT latitude, longitude, accuracy, updated_at
                     FROM technician_live_locations
                     WHERE request_id = ? AND technician_id = ?',
                    [$id, $serviceRequest['technician_id']]
                );

                if ($location) {
                    $lastUpdatedAt = strtotime((string) $location['updated_at']);
                    $technicianLocation = [
                        'latitude' => (float) $location['latitude'],
                        'longitude' => (float) $location['longitude'],
                        'accuracy' => $location['accuracy'] !== null ? (float) $location['accuracy'] : null,
                        'updated_at' => $location['updated_at'],
                        'is_live' => $databaseNow !== false
                            && $lastUpdatedAt !== false
                            && $lastUpdatedAt >= $databaseNow - 30,
                    ];
                }
            }
        }

        return response()->json([
            'status' => $serviceRequest['status'],
            'customer_location' => $customerLocation,
            'technician_location' => $technicianLocation,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->authUser();
        abort_unless($user->role === 'technician', 404);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        DB::transaction(function () use ($id, $user, $data): void {
            $serviceRequest = DB::table('service_requests')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $serviceRequest
                    && (int) $serviceRequest->technician_id === (int) $user->id
                    && in_array($serviceRequest->status, self::ACTIVE_STATUSES, true),
                404
            );

            DB::table('technician_live_locations')->updateOrInsert(
                ['request_id' => $id],
                [
                    'technician_id' => $user->id,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
                ]
            );
        });

        $timestamp = db_value(
            'SELECT updated_at FROM technician_live_locations WHERE request_id = ?',
            [$id]
        );

        return response()->json(['updated_at' => $timestamp]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->authUser();
        abort_unless($user->role === 'technician', 404);

        $serviceRequest = db_one(
            'SELECT id FROM service_requests WHERE id = ? AND technician_id = ?',
            [$id, $user->id]
        );
        abort_if(! $serviceRequest, 404);

        DB::table('technician_live_locations')
            ->where('request_id', $id)
            ->where('technician_id', $user->id)
            ->delete();

        return response()->json(['stopped' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function findRequestForParticipant(int $id, User $user): array
    {
        $serviceRequest = db_one(
            'SELECT id, customer_id, technician_id, status, latitude, longitude, location_accuracy
             FROM service_requests WHERE id = ?',
            [$id]
        );

        abort_if(
            ! $serviceRequest
                || ! (
                    ($user->role === 'customer' && (int) $user->id === (int) $serviceRequest['customer_id'])
                    || ($user->role === 'technician' && (int) $user->id === (int) $serviceRequest['technician_id'])
                ),
            404
        );

        return $serviceRequest;
    }
}
