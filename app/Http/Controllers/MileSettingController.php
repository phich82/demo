<?php

namespace App\Http\Controllers\Admin;

use App\Commons\CSV;
use App\Api\Contracts\ActivityApiContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\AreaRepository;
use App\Repositories\MileRepository;
use App\Http\Requests\Mile\StoreMile;
use App\Repositories\PromoRepository;
use Illuminate\Support\Facades\Validator;

class MileSettingController extends Controller
{
    /**
     * @var mileType
     */
    private $_mileType = null;

    /**
     * @var ActivityApiContract
     */
    protected $activityApi;

    /**
     * @var CSV
     */
    protected $csv;

    /**
     * @var mileRepository
     */
    protected $mileRepository;

    /**
     * @var promotionRepository
     */
    protected $promotionRepository;

    /**
     * @var areaRepository
     */
    protected $areaRepository;

    /**
     * constructor
     *
     * @param MileRepository $mileRepository
     * @param PromoRepository $promotionRepository
     * @param AreaRepository $areaRepository
     * @param ActivityApiContract $activityApi
     * @param CSV $csv
     * @return void
     */
    public function __construct(MileRepository $mileRepository, PromoRepository $promotionRepository, AreaRepository $areaRepository, ActivityApiContract $activityApi, CSV $csv)
    {
        $this->mileRepository = $mileRepository;
        $this->promotionRepository = $promotionRepository;
        $this->areaRepository = $areaRepository;
        $this->activityApi = $activityApi;
        $this->csv = $csv;

        $currentRouteName = \Route::current()->getName();
        if (stripos($currentRouteName, 'mile') !== false && stripos($currentRouteName, 'mile.redemption') !== false) {
            $this->_mileType = \Constant::MILE_REDEMPTION;
        } elseif (stripos($currentRouteName, 'mile') !== false && stripos($currentRouteName, 'mile.redemption') === false) {
            $this->_mileType = \Constant::MILE_ACCUMULATION;
        }
    }

    /**
     * MileSetting - List Screen
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $mileType = $this->_mileType;

        // get current basic setting
        $currentSetting = $this->mileRepository->getCurrentSetting($this->_mileType);
        
        // get scheduled basic setting
        $scheduleSetting = $this->mileRepository->getScheduleSetting($this->_mileType);

        if ($request->ajax()) {
            $validation = Validator::make(
                [
                    'page'         => $request->get('page', 1),
                    'perPage'      => $request->get('limit', 20),
                    'unit'         => $request->get('unit', null),
                    'sortAP'       => $request->get('sortActivityPurchase'),
                    'activityDate' => $request->get('activityDate', null),
                    'purchaseDate' => $request->get('purchaseDate', null)
                ],
                [
                    'page'         => 'nullable|integer',
                    'perPage'      => 'nullable|integer',
                    'unit'         => 'nullable|integer',
                    'sortAP'       => 'nullable|integer',
                    'activityDate' => 'nullable|date_format:"Y-m-d"',
                    'purchaseDate' => 'nullable|date_format:"Y-m-d"',
                ]
            );
            
            $result = null;
            if ($validation->fails()) {
                $result = [
                    'data' => [
                        'promotions' => [],
                        'perPage' => $request->get('limit', 20)
                    ]
                ];
            } else {
                // get params
                $offset = (int)$request->get('page', 1);
                $limit  = (int)$request->get('limit', 20);
                $params = [
                    'unit'         => $request->get('unit', null),
                    'sortAP'       => $request->get('sortActivityPurchase'),
                    'activityArea' => $request->get('activityArea', null),
                    'activityDate' => $request->get('activityDate', null),
                    'purchaseDate' => $request->get('purchaseDate', null)
                ];
                // filter promotions by params
                $result = $this->getPromotionsByFilters($offset, $limit, $params, $this->_mileType);
            }

            return view('admin.mile._list', compact('result', 'mileType'))->render();
        }

        return view('admin.mile.index', compact('currentSetting', 'scheduleSetting', 'mileType'));
    }

    /**
     * Display the current basic setting and a form for add new records.
     * @return \Illuminate\Http\Response
     */
    public function createBasicSetting()
    {
        $mileType = $this->_mileType;

        // get the current setting
        $currentSetting = $this->mileRepository->getCurrentSetting($this->_mileType);

        // get the scheduled basic setting
        $scheduledSetting = $this->mileRepository->getAllScheduleSettings($this->_mileType);

        return view('admin.mile.basic', compact('currentSetting', 'scheduledSetting', 'mileType'));
    }

    /**
     * store basic setting(s) to database
     * @param StoreMile $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBasicSetting(StoreMile $request)
    {
        $data = $request->validated();

        try {
            $result = $this->mileRepository->processMile($data['mile'], auth()->user()->email, $this->_mileType);
            // get the error message
            if (array_key_exists('errors', $result)) {
                if (array_key_exists('lower_current_date', $result['errors'])) { // lower than current date
                    $result['errors']['lower_current_date'] = [
                        'date'    => $result['errors']['lower_current_date'],
                        'message' => str_replace(':date', "'".date('Y-m-d')."'", __('validation.custom.lower_current_date.after'))
                    ];
                } else { // duplicated plan start date
                    $result['errors']['duplicated'] = [
                        'date'    => $result['errors']['duplicated'],
                        'message' => __('validation.custom.plan_start_date.unique')
                    ];
                }
            }
            return response()->json($result);
        } catch (\Exception $exception) {
            return response()->json(['type' => 'fail']);
        }
    }

    /**
     * download csv file
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function downloadCSV(Request $request)
    {
        // get params
        $params = [
            'unit'         => $request->get('unit', null),
            'sortAP'       => $request->get('sortActivityPurchase'),
            'activityArea' => $request->get('activityArea', null),
            'activityDate' => $request->get('activityDate', null),
            'purchaseDate' => $request->get('purchaseDate', null)
        ];

        // get promotions
        $promotions = $this->getAllPromotions($params, $this->_mileType);

        // error restful api
        if (empty($promotions)) {
            return false;
        }

        // replace the default values in promotions
        $replaces = [
            'rate_type' => [
                \Constant::UNIT_AREA => '変動額',     // 1: area
                \Constant::UNIT_ACTIVITY => '固定額', // 2: activity
            ]
        ];

        // set the required fields & titles in csv file
        $titles = [
            'unit_name'           => '単位',
            'promotion_name'      => 'プロモーション名',
            'activity_start_date' => '期間開始',
            'activity_end_date'   => '期間終了',
            'purchase_start_date' => '申し込み日開始',
            'purchase_end_date'   => '申し込み日終了',
            'rate_type'           => '積算設定',
            'amount'              => '積算値',
        ];

        // csv filename
        $filename = 'promotionlist_'.date('Ymdis');

        return $this->csv
                  ->filename($filename)
                  ->replaces($replaces)
                  ->titles($titles)
                  ->contents($promotions->toArray())
                  ->sendStream(true);
    }

    /**
     * get promotions
     *
     * @param integer       $offset
     * @param integer       $limit
     * @param array         $params
     * @param integer|null  $mileType
     * @return array
     */
    private function getPromotionsByFilters($offset, $limit, $params, $mileType = null)
    {
        // get promotions & pagination
        $promotions = $this->promotionRepository->getSqlBuilderObj($params, $mileType)
                           ->skip($offset)
                           ->take($limit)
                           ->paginate($limit);
        
        // filter only ActivityIDs from promotion list
        $activityIDs = [];
        $activities  = [];
        foreach ($promotions as $row) {
            if ($row->unit == \Constant::UNIT_ACTIVITY) {
                $activityIDs[] = $row->activity_id;
            }
        }

        // get activity titles by ActivityIDs through restful api
        if (!empty($activityIDs) && empty($params['activityArea'])) {
            $data = $this->activityApi->getByIds($activityIDs);
            if (empty($data)) {
                return ['success' => false, 'status' => 404];
            }
            $activities = collect($data)->pluck('title', 'id')->all();
        }

        return [
            'success' => true,
            'data'    => [
                'promotions' => $promotions,
                'activities' => $activities
            ]
        ];
    }

    /**
     * get all promotions for csv download
     *
     * @param array $params
     * @param integer|null $mileType
     * @return array
     */
    private function getAllPromotions($params, $mileType = null)
    {
        // get promotions
        $promotions = $this->promotionRepository->getSqlBuilderObj($params, $mileType)->get();

        // filter only activityIDs, add 'unitName' & 'promotionName' keys to promotions (for csv)
        $activityIDs = $activities = [];
        if (!empty($promotions)) {
            foreach ($promotions as $k => $row) {
                $promotions[$k]->promotion_name = '';
                if ($row['unit'] == \Constant::UNIT_AREA) {
                    $promotions[$k]->unit_name = 'エリア';
                    $promotions[$k]->promotion_name = $promotions[$k]['area_path_jp'];
                } elseif ($row['unit'] == \Constant::UNIT_ACTIVITY) {
                    $promotions[$k]->unit_name = '商品';
                    if (trim($row['activity_id'])) {
                        $activityIDs[] = $row['activity_id'];
                    }
                }
            }
        }

        // get titles of activities by ActivityIDs through api
        if (!empty($activityIDs)) {
            $data = $this->activityApi->getByIds($activityIDs);
            if (empty($data)) {
                return null;
            }
            $activities = collect($data)->pluck('title', 'id')->all();
        }

        // update promotion name
        foreach ($promotions as $k => $row) {
            if ($row['unit'] == \Constant::UNIT_ACTIVITY) {
                $promotions[$k]->promotion_name = $activities[$row['activity_id']];
            }
        }

        return $promotions;
    }

    /**
     * filter areas by the partial matching AreaPathJP keyword
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAreasByPathJP(Request $request)
    {
        $data = $this->areaRepository->getAreasByPathJP($request->input('area_pathJP'));

        $statusCode = !empty($data) ? 200 : 404;
        if ($request->ajax()) {
            $result = ['statusCode' => $statusCode];
            $result['total'] = count($data);
            $result['data']  = view('admin.mile._list_areapath', compact('data'))->render();
            return response()->json($result);
        }
        return response()->json(['statusCode' => $statusCode, 'data' => $data]);
    }
}
