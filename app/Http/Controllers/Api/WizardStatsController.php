<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\MigrationRequest;
use App\Http\Resources\MigrationResource;
use GuzzleHttp\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
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
        $sql = $this->_getMigrationsQuery();
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
            $sql = $this->_getMigrationsQuery(false, $start, $limit, true);

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

    private function _getMigrationsQuery($count = false, $start = null, $limit = null, $allData = false): Builder
    {
        $request = new MigrationRequest;

        $forceLimit = true;

        if ($start === null) {
            $start = $request->get('start', 0);
        }

        if ($limit === null) {
            $forceLimit = false;
            $limit = $request->get('limit', 15);
        }

        $filterData = $this->_getFilterData(new FilterRequest);

        $useIds = !$count;
        foreach ($filterData as $filter) {
            if ($filter['field'] == 'migrationId') {
                $useIds = false;
                break;
            }
        }

        if (!$allData && $useIds) {
            $migrationsSelect = DB::table('migrations')
                ->select('id')
                ->orderByDesc('id')
                ->limit($limit, $start);

            $useIds = $migrationsSelect->pluck('id')->toArray();
        }

        $filter = new \Cart2cart\Extjs4\Filter([
            'migrationId' => 'm.id',
            'price' => 'm.price_in_dollars_with_discount',
            'estimatorPrice' => 'm.price_in_dollars',
            'wizardCreated' => 'mshc.date',
            'demoCompleted' => 'mshd.date',
            'fullCompleted' => 'mshf.date',
            'sourceId' => 'mss.cart_id',
            'targetId' => 'mst.cart_id',
            'lastLoginDate' => 'a.last_visit',
            'demoRate' => 'mr.rate',
            'demoResultsChecked' => 'mdl.value',
            'qualityProcessTime' => 'mdqp.value',
        ], $request);

        $where = $filter->getWhere() ?? null;

        if ($where === null) {
            $where = '1';
        }
        $select = DB::table('accounts AS a')
            ->select([
                'a.email',
                'a.last_visit AS lastLoginDate',
                'm.id AS migrationId',
                'm.price_in_dollars_with_discount AS price',
                'm.price_in_dollars AS estimatorPrice',
            ])
            ->leftJoin('_migrations AS m', 'm.account_id', '=', 'a.id')
            ->leftJoin('migrations_stores AS mss', 'm.source_store_id', '=', 'mss.id')
            ->leftJoin('migrations_stores AS mst', 'm.target_store_id', '=', 'mst.id')
            ->leftJoin('migrations_status_history AS mshc', function ($join) {
                $join->on('m.id', '=', 'mshc.migration_id')
                    ->where('mshc.status', '=', 'created');
            })
            ->where($where)
            ->where('m.deleted', 0)
            ->orderBy(DB::raw($filter->getOrder($request)));

        if ($forceLimit) {
            $select->limit($limit);
        } else {
            $select->skip($start)->limit($limit);
        }

        if (!$count && is_array($useIds)) {
            $select->whereIn('m.id', $useIds);
        }

        if ($filterData || !$count) {
            $select->leftJoin('migrations_status_history AS mshd', function ($join) {
                $join->on('m.id', '=', 'mshd.migration_id')
                    ->where('mshd.status', '=', 'demo_completed');
            })
                ->selectRaw('mshd.date AS demoCompleted')
                ->leftJoin('migrations_status_history AS mshf', function ($join) {
                    $join->on('m.id', '=', 'mshf.migration_id')
                        ->where('mshf.status', '=', 'completed');
                })
                ->selectRaw('mshf.date AS fullCompleted')
                ->leftJoin('migrations_rates AS mr', 'm.id', '=', 'mr.migration_id')
                ->selectRaw('IFNULL(mr.rate, 0) AS demoRate')
                ->leftJoin('migrations_data AS mdl', function ($join) {
                    $join->on('m.id', '=', 'mdl.migration_id')
                        ->where('mdl.key', '=', 'demo_result_links_checked');
                })
                ->selectRaw('IFNULL(mdl.value, 0) AS demoResultsChecked')
                ->leftJoin('migrations_data AS mdsp', function ($join) {
                    $join->on('m.id', '=', 'mdsp.migration_id')
                        ->where('mdsp.key', '=', \Cart2cart\Migration\Data::PLUGIN_USED_FOR_SOURCE);
                })
                ->selectRaw('IFNULL(mdsp.value, 0) AS sourceUsedPlugin')
                ->leftJoin('migrations_data AS mdtp', function ($join) {
                    $join->on('m.id', '=', 'mdtp.migration_id')
                        ->where('mdtp.key', '=', \Cart2cart\Migration\Data::PLUGIN_USED_FOR_TARGET);
                })
                ->selectRaw('IFNULL(mdtp.value, 0) AS targetUsedPlugin')
                ->leftJoin('migrations_data AS mdqp', function ($join) {
                    $join->on('m.id', '=', 'mdqp.migration_id')
                        ->where('mdqp.key', '=', \Cart2cart\Migration\Data::QUALITY_PROCESS_TIME);
                })
                ->selectRaw('IFNULL(mdqp.value, 0) AS qualityProcessTime')
                ->leftJoin('migrations_data AS mdwt', function ($join) {
                    $join->on('m.id', '=', 'mdwt.migration_id')
                        ->where('mdwt.key', '=', 'wizard_stores_setup_wait_time');
                })
                ->selectRaw('IFNULL(mdwt.value, 0) AS storesSetupTime')
                ->groupBy('migrationId');
        }

        return $select;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function _getFilterData(FilterRequest $request): array
    {
        $filters = json_decode($request->get('filter', ''), true);

        return $filters ?? [];
    }
}
