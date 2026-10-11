<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemMatch;
use Illuminate\Support\Str;

class ItemMatchingService
{
    private const MATCH_THRESHOLD = 60;

    /**
     * Find and save possible matches for a newly created item.
     */
    public function findMatches(Item $item): void
    {
        // Only submitted reports should be matched.
        if ($item->status !== 'submitted') {
            return;
        }

        // A lost item searches found reports, and vice versa.
        $oppositeType = $item->type === 'lost'
            ? 'found'
            : 'lost';

        $candidates = Item::query()
            ->where('type', $oppositeType)
            ->where('status', 'submitted')
            ->where('user_id', '!=', $item->user_id)
            ->get();

        foreach ($candidates as $candidate) {
            $score = $this->calculateScore($item, $candidate);

            if ($score < self::MATCH_THRESHOLD) {
                continue;
            }

            // Always store the lost item first and found item second.
            $lostItem = $item->type === 'lost' ? $item : $candidate;
            $foundItem = $item->type === 'found' ? $item : $candidate;

            // Do not create the same pair twice.
            $alreadyExists = ItemMatch::query()
                ->where('lost_item_id', $lostItem->id)
                ->where('found_item_id', $foundItem->id)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            ItemMatch::create([
                'lost_item_id' => $lostItem->id,
                'found_item_id' => $foundItem->id,
                'match_score' => $score,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Calculate a simple similarity score from 0 to 100.
     */
    private function calculateScore(Item $a, Item $b): float
    {
        $score = 0;

        // Same category: 20 points
        if (
            $a->category_id !== null &&
            $a->category_id === $b->category_id
        ) {
            $score += 20;
        }

        // Similar item name: up to 25 points
        if ($this->hasValue($a->name) && $this->hasValue($b->name)) {
            similar_text(
                Str::lower($a->name),
                Str::lower($b->name),
                $similarity
            );

            $score += ($similarity / 100) * 25;
        }

        // Same brand: 15 points
        if (
            $this->hasValue($a->brand) &&
            $this->hasValue($b->brand) &&
            Str::lower($a->brand) === Str::lower($b->brand)
        ) {
            $score += 15;
        }

        // Same color: 15 points
        if (
            $this->hasValue($a->color) &&
            $this->hasValue($b->color) &&
            Str::lower($a->color) === Str::lower($b->color)
        ) {
            $score += 15;
        }

        // Similar location: up to 15 points
        if (
            $this->hasValue($a->location) &&
            $this->hasValue($b->location)
        ) {
            similar_text(
                Str::lower($a->location),
                Str::lower($b->location),
                $similarity
            );

            $score += ($similarity / 100) * 15;
        }

        // Dates close together: up to 10 points
        if ($a->incident_date && $b->incident_date) {
            $daysApart = abs(
                $a->incident_date->diffInDays($b->incident_date)
            );

            if ($daysApart <= 3) {
                $score += 10;
            } elseif ($daysApart <= 7) {
                $score += 5;
            }
        }

        return round($score, 2);
    }

    private function hasValue(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
