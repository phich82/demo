<?php

namespace App\Http\Controllers\Admin;

use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MileRepository;
use App\Repositories\PromoRepository;
use App\Http\Requests\Mile\StoreUpdatePromotion;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Api\Contracts\ActivityApiContract;

class PromotionController extends Controller
{
    /**
     * @var mileType
     */
    private $_mileType = null;
    

    /**
     * @var PromoRepository
     */
    protected $promotionRepository;

    /**
     * @var mileRepository
     */
    protected $mileRepository;

    /**
     * @var ActivityApiContract
     */
    protected $activityApi;


    /**
     * PromotionController constructor.
     * @param PromoRepository $promotionRepository
     * @param MileRepository $mileRepository
     * @param ActivityApiContract $activityApi
     */
    public function __construct(PromoRepository $promotionRepository, MileRepository $mileRepository, ActivityApiContract $activityApi)
    {
        $this->promotionRepository = $promotionRepository;
        $this->mileRepository = $mileRepository;
        $this->activityApi = $activityApi;

        $currentRouteName = \Route::current()->getName();
        if (stripos($currentRouteName, 'mile') !== false && stripos($currentRouteName, 'mile.redemption') !== false) {
            $this->_mileType = \Constant::MILE_REDEMPTION;
        } elseif (stripos($currentRouteName, 'mile') !== false && stripos($currentRouteName, 'mile.redemption') === false) {
            $this->_mileType = \Constant::MILE_ACCUMULATION;
        }
    }

    /**
     * Show the form for editing the specified resource (accumulation).
     *
     * @param integer $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // invalid id
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            abort(404);
        }

        // get current basic setting
        $currentSetting = $this->mileRepository->getCurrentSetting($this->_mileType);

        // get promotion with area
        $promotion = $this->promotionRepository->getPromotionWithArea($id, $this->_mileType);

        // get activity title from api
        if (!empty($promotion) && $promotion->activity_id) {
            $response = $this->activityApi->find($promotion->activity_id);
            if (isset($response->id) && isset($response->title)) {
                // update activity_title
                $promotion->activity_title = $response->title;
            }
        }

        // not found
        if (empty($promotion)) {
            abort(404);
        }

        return view('admin.mile.'.($this->_mileType == \Constant::MILE_REDEMPTION ? 'redemption' : 'accumulation').'.edit-promotion', compact('promotion', 'currentSetting'));
    }

    /**
     * Update the specified resource in storage.
     * @param UpdatePromotion $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreUpdatePromotion $request)
    {
        $params = $request->all();
        $params['updated_user'] = auth()->user()->email;

        try {
            $result = $this->promotionRepository->updatePromotion($request->id, $params);
            return response()->json(['type' => $result ? 'success' : 'fail']);
        } catch (\Exception $exception) {
            return response()->json(['type' => 'fail']);
        }
    }

    /**
     * Show the form for creating a new resource (accumulation).
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // get current basic setting
        $currentSetting = $this->mileRepository->getCurrentSetting($this->_mileType);

        return view('admin.mile.'.($this->_mileType == \Constant::MILE_REDEMPTION ? 'redemption' : 'accumulation').'.create-promotion', compact('currentSetting'));
    }

    /**
     * Store a newly created resource in storage (accumulation).
     *
     * @param StorePromotion $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUpdatePromotion $request)
    {
        $data = $request->all();
        $data['created_user'] = auth()->user()->email;

        try {
            $result = $this->promotionRepository->createPromotion($data);
            return response()->json(['type' => $result ? 'success' : 'fail']);
        } catch (\Exception $exception) {
            return response()->json(['type' => 'fail']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $result = $this->promotionRepository->deletePromotion($id);
        return response()->json(['type' => $result ? 'success' : 'fail']);
    }

    /**
     * get activity by ActivityID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityById(Request $request)
    {
        try {
            $response = $this->activityApi->find($request->input('activity_id'));
        } catch (HttpException $e) {
            return response()->json(['statusCode' => $e->getStatusCode()]);
        }
        
        if ($request->ajax()) {
            $result = ['statusCode' => 404];
            if (isset($response->id) && isset($response->title)) {
                $result['statusCode'] = 200;
                $data = [
                    (object) ['id' => $response->id, 'title' => $response->title]
                ];
                $result['total'] = 1;
                $result['data']  = view('admin.mile._list_activity', compact('data'))->render();
            }
            return response()->json($result);
        }
        return response()->json($response);
    }

    /**
     * get activities by title
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivitiesByTitle(Request $request)
    {
        try {
            $response = $this->activityApi->getByTitle(['q' => $request->input('title')]);
        } catch (HttpException $e) {
            return response()->json(['statusCode' => $e->getStatusCode()]);
        }

        if ($request->ajax()) {
            $result = ['statusCode' => 404];
            if (isset($response->activities)) {
                $result['statusCode'] = 200;
                $data = $response->activities;
                $result['total'] = count($data);
                $result['data']  = view('admin.mile._list_activity', compact('data'))->render();
            }
            return response()->json($result);
        }

        return response()->json($response);
    }
}
