<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TopDonorsService
{
    private const CACHE_KEY = 'top_donors_list';

    private const CACHE_TTL = 300;

    /**
     * Returns a ranked list of top donors.
     *
     * Named donors (is_anonymous=false, is_guest=false) are shown individually by name.
     * All anonymous/guest payments are aggregated into a single "فاعل خير" entry,
     * ranked by total amount and inserted wherever it falls in the list.
     *
     * @return list<array{rank: int, name: string, total: float, is_anonymous: bool}>
     */
    public function getTopDonors(int $limit = 100): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($limit) {
            return $this->buildRankedList($limit);
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildRankedList(int $limit): array
    {
        // Named registered donors: group by sponsor, sum non-anonymous succeeded payments
        $namedRows = Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->where('is_guest', false)
            ->where('is_anonymous', false)
            ->whereNotNull('sponsor_id')
            ->selectRaw('sponsor_id, SUM(amount) as total_amount')
            ->groupBy('sponsor_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        $userIds = $namedRows->pluck('sponsor_id');
        $users = User::whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        $entries = $namedRows->map(fn ($row) => [
            'name' => $users->get($row->sponsor_id)?->name ?? __('top_donors.anonymous_label'),
            'total' => (float) $row->total_amount,
            'is_anonymous' => false,
        ])->all();

        // Anonymous pool: all succeeded payments that are anonymous or guest
        $anonTotal = (float) Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->where(fn ($q) => $q->where('is_anonymous', true)->orWhere('is_guest', true))
            ->sum('amount');

        if ($anonTotal > 0) {
            $entries[] = [
                'name' => __('top_donors.anonymous_label'),
                'total' => $anonTotal,
                'is_anonymous' => true,
            ];
        }

        // Sort by total desc, slice to limit, assign ranks
        usort($entries, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_values(array_map(
            fn ($entry, $idx) => array_merge($entry, ['rank' => $idx + 1]),
            array_slice($entries, 0, $limit),
            array_keys(array_slice($entries, 0, $limit))
        ));
    }
}
