<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MigrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'migrationId' => $this->migrationId,
            'wizardCreated' => $this->wizardCreated,
            'demoCompleted' => $this->demoCompleted,
            'fullCompleted' => $this->fullCompleted,
            'sourceId' => $this->sourceId,
            'sourceUsedPlugin' => Carbon::createFromTimestamp($row->sourceUsedPlugin)->format('i:s') ?? 0,
            'targetId' => $this->targetId,
            'targetUsedPlugin' => Carbon::createFromTimestamp($row->targetUsedPlugin)->format('i:s') ?? 0,
            'price' => $this->price,
            'estimatorPrice' => $this->estimatorPrice,
            'lastLoginDate' => $this->lastLoginDate,
            'demoRate' => $this->demoRate,
            'demoResultsChecked' => $this->demoResultsChecked,
            'storesSetupTime' => (int) ($this->storesSetupTime ? $this->storesSetupTime + 2 : 0 / 60).'m. '.($this->storesSetupTime ? $this->storesSetupTime + 2 : 0 % 60).'s',
            'qualityProcessTime' => Carbon::createFromTimestamp($row->qualityProcessTime)->format('i:s') ?? 0,
        ];
    }
}
