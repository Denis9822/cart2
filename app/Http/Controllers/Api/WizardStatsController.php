<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MigrationResource;
use App\Services\WizardStats;
use GuzzleHttp\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WizardStatsController extends Controller
{
    public function __construct()
    {
        if (request()->isJson()) {
            response()->json()->setStatusCode(204, 'No Content');
        }
    }

    public function migration(): JsonResource
    {
        $sql = WizardStats::_getMigrationsQuery();
        $res = $sql->get();

        return MigrationResource::collection($res);
    }

    /**
     * @throws JsonEncodingException
     * @throws InvalidArgumentException
     */
    public function save(): BinaryFileResponse
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $start = 0;
        $limit = 15;

        $handle = fopen('php://memory', 'rw+');
        fputcsv($handle, [
            'migrationId',
            'wizardCreated',
            'demoCompleted',
            'fullCompleted',
            'sourceId',
            'sourceUsedPlugin',
            'targetId',
            'targetUsedPlugin',
            'price',
            'estimatorPrice',
            'lastLoginDate',
            'demoRate',
            'demoResultsChecked',
            'storesSetupTime',
            'qualityProcessTime',
        ]);

        while (true) {
            $sql = WizardStats::_getMigrationsQuery(false, $start, $limit, true);

            $res = $sql->get();

            if (!$res) {
                break;
            }
            (new MigrationResource($res))->resolve();
            foreach ($res as $row) {
                fputcsv($handle, (new MigrationResource($res))->resolve());
            }

            $start += $limit;
        }

        rewind($handle);

        return response()->download(
            resource_path('migrations.csv'),
            'migrations.csv',
            [
                'Pragma' => 'public',
                'Expires' => 0,
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0, private',
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=migrations.csv;',
            ]
        );

    }
}
