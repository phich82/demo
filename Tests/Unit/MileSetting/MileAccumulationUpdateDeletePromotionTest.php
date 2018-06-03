<?php

namespace Tests\Unit\MileSetting;

use Mockery;
use Tests\TestCase;
use App\Models\Area;
use Tests\StubAccount;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use App\Repositories\PromoRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MileAccumulationUpdateDeletePromotionTest extends TestCase
{
    use DatabaseTransactions;
    use StubAccount;

    /**
     * @var PromoRepository
     */
    private $promotionRepo;

    /**
     * @var mileType
     */
    private static $mileType;
    
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        self::$mileType = \Constant::MILE_ACCUMULATION;
        $this->promotionRepo = new PromoRepository();
    }

    /**
     * clean up
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->app) {
            foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
                call_user_func($callback);
            }
            $this->app->flush();
            $this->app = null;
        }

        if (class_exists('Mockery')) {
            Mockery::close();
        }
    }
    
    /**
     * [TestCase-4.1] Test redirect to login page when login failed
     *
     * Condition:
     * - Authenticate a user with the wrong information of login
     *
     * Expectation:
     * - Redirect to the login page (/management/login)
     */
    public function test41RedirectToLoginPageWhenLoginFailed()
    {
        $this->get($this->getUrlById())
             ->assertRedirect(route('admin.login'));
    }

    /**
     * [TestCase-4.2] Test access to mile accumulation update/delete promotion page when login successfully
     *
     * Condition:
     * - Authenticate a user as admin
     * - Url of Mile Accumulation Update/Delete Promotion Page (/management/mileage/accumulation/promotions/create)
     *
     * Expectation:
     * - See text: 'マイル積算 - プロモーション'
     */
    public function test42AccessToMileAccumulationNewPromotionPageWhenLoggedIn()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.3] Test redirect to mile accumulation page when click on the link '<< マイル積算トップへ戻る'
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation Basic Setting Page (/management/mileage/accumulation/basic-setting/create)
     * - Click on the link '<< マイル積算トップへ戻る' on screen
     *
     * Expectation:
     * - Redirect to Mile Accumulation List Page (/management/mileage/accumulation)
     * - See text: 'マイル積算'
     * - Do not see: text 'マイル積算 - プロモーション'
     */
    public function test43RedirectToMileAccumulationListPageWhenClickOnLinkAccumulation()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.4.1] Test redirect to the mile accumulation list page when invalid id
     *
     * Condition:
     * - Authenticate a user as admin
     * - Given id is '1xx' (invalid)
     * - Access to Mile Accumulation Update/Delete Promotion Page (/management/mileage/accumulation/promotions/1xxx)
     *
     * Expectation:
     * - Redirect to Mile Accumulation List Page
     */
    public function test441RedirectToMileAccumulationListPageWhenInvalidId()
    {
        $this->checkLogin();

        $this->createPromotions(10);

        $idCheck = '1xxx';

        $this->get($this->getUrlById($idCheck))
             ->assertRedirect(route('admin.mile.index'))
             ->assertDontSee('マイル積算 - プロモーション');
    }

    /**
     * [TestCase-4.4.2] Test redirect to the mile accumulation list page if not found when valid id
     *
     * Condition:
     * - Authenticate a user as admin
     * - Given id
     * - Access to Mile Accumulation Update/Delete Promotion Page (/management/mileage/accumulation/promotions/{id})
     *
     * Expectation:
     * - Redirect to Mile Accumulation List Page
     */
    public function test442RedirectToMileAccumulationListIfNotFoundWhenValidId()
    {
        $this->checkLogin();

        $total = 10;
        $this->createPromotions($total);

        $idCheck = $total + 1;

        $this->get($this->getUrlById($idCheck))
             ->assertRedirect(route('admin.mile.index'))
             ->assertDontSee('マイル積算 - プロモーション');
    }

    /**
     * [TestCase-4.5] Test display the promotion information by the given valid id
     *
     * Condition:
     * - Authenticate a user as admin
     * - Given id
     * - Access to Mile Accumulation New Promotion Page (/management/mileage/accumulation/promotions/{id})
     *
     * Expectation:
     * - See the link 'このプロモーションを削除する' on screen
     * - See the promotion information on screen
     */
    public function test45DisplayPromotionInformationWhenValidId()
    {
        $this->markTestIncomplete('Mark skip test to find solution');

        // $this->checkLogin();

        // Area::query()->delete();
        // Promotion::query()->delete();
        
        // factory(Area::class)->create([
        //     'area_path'    => 'euro/uk/london',
        //     'area_path_jp' => 'test',
        //     'created_user' => 'admin@test.com',
        // ]);

        // $promotion = factory(Promotion::class)->create([
        //     'activity_id'         => 'VELTRA-107551',
        //     'activity_title'      => 'test',
        //     'area_path'           => 'euro/uk/london',
        //     'unit'                => \Constant::UNIT_AREA,
        //     'amount'              => 150,
        //     'activity_start_date' => date('Y-m-d'),
        //     'purchase_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + 2*24*60*60),
        //     'mile_type'           => self::$mileType,
        //     'rate_type'           => \Constant::ACCUMULATION_RATE_TYPE_FIXED,
        //     'created_user'        => 'admin@gmail.com',
        // ]);

        // $this->get($this->getUrlById($promotion->promotion_id))
        //      ->assertSee((string)$promotion->unit)
        //      ->assertSee($promotion->activity_start_date)
        //      ->assertSee($promotion->purchase_start_date)
        //      ->assertSee((string)$promotion->amount);
    }

    /**
     * [TestCase-4.6] Test display a popup for confirming to delete promotion
     *
     * Condition:
     * - Authenticate a user as admin
     * - Given id
     * - Click on the link (このプロモーションを削除する)
     *
     * Expectation:
     * - See a popup with a header text 'このマイル償還プロモーション設定を削除しますか？'
     */
    public function test46DisplayAPopupForConfirmingToDeletePromotion()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.7] Test delete promotion successfully
     *
     * Condition:
     * - Authenticate a user as admin
     * - Given id
     * - Click on the link (このプロモーションを削除する), display a popup
     * - Click on button 'はい'
     *
     * Expectation:
     * - See column 'deleteFlag' with the value changed from 0 to 1
     */
    public function test47DeletePromotionSuccessfully()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.8] Test display error when unit not 1 or 2
     *
     * Condition:
     * - Authenticate a user as admin
     * - Unit is an integer (3) but not 1 or 2
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択された単位は、有効ではありません。'
     */
    public function test48DisplayErrorWhenUnitNot1Or2()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.9] Test display the error message when entering acitivity start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is wrong date format (2018-01-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '期間開始の形式は、'Y-m-d'と合いません。'
     */
    public function test49DisplayErrorWhenEnteringActivityStartDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.10] Test display the error message when entering acitivity end date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity end date is wrong date format (2018-05-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '期間終了の形式は、'Y-m-d'と合いません。'
     */
    public function test410DisplayErrorWhenEnteringActivityEndDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.11] Test display the error message when entering acitivity end date less than activity start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is 2018-05-05
     * - Activity end date is 2018-05-02
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '期間終了には、期間開始以降の日付を指定してください。'
     */
    public function test411DisplayErrorWhenEnteringActivityEndDateLessThanActivityStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.12] Test display the error message when entering purchase start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is wrong date format (2018-02-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日の形式は、'Y-m-d'と合いません。'
     */
    public function test412DisplayErrorWhenEnteringPurchaseStartDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.13] Test display the error message when entering purchase end date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase end date is wrong date format (2018-05-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日終了の形式は、'Y-m-d'と合いません。'
     */
    public function test413DisplayErrorWhenEnteringPurchaseEndDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.14] Test display the error message when entering purchase end date less than purchase start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is 2018-05-05
     * - Purchase end date is 2018-05-01
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日終了には、申し込み日以降の日付を指定してください。'
     */
    public function test414DisplayErrorWhenEnteringPurchaseEndDateLessThanPurchaseStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.15] Test display the error message when entering purchase start date less than activity start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is 2018-05-01
     * - Activity start date is 2018-05-05
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日には、期間開始以降の日付を指定してください。'
     */
    public function test415DisplayErrorWhenEnteringPurchaseStartDateLessThanActivityStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.16] Test display the error message when has any errors from inputs
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is wrong date format (2018-01-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error messages in the error list on screen
     */
    public function test416DisplayErrorWhenAnyErrorsFromInputs()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.17] Test save completed when save successfully
     *
     * Condition:
     * - Authenticate a user as admin
     * - All inputs are valid
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See a popup with the header text '保存完了'
     * - See the returned result as a json {type: success'}
     */
    public function test417SaveCompletedWhenSaveSuccessfully()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.18] Test change rate type when click on radio button '変動額' (change rate type)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on radio button (変動額)
     *
     * Expectation:
     * - See a text '1マイル' & an input & a text '円'
     */
    public function test418ChangeRateTypeWhenClickOnChangeRadioButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.19] Test change rate type when click on radio button '固定額 (fixed rate type)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on radio button (固定額)
     *
     * Expectation:
     * - See a text '一律' & an input & text 'マイル割引'
     */
    public function test419ChangeRateTypeWhenClickOnFixedRadioButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.20] Test display the error message when amount non numeric
     *
     * Condition:
     * - Authenticate a user as admin
     * - Amount is '150xxx'
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '積算値には、数字を指定してください。'
     */
    public function test420DisplayErrorMessageWhenAmountNonNumeric()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.21] Test display the error message when rate type not 1 or 2
     *
     * Condition:
     * - Authenticate a user as admin
     * - Rate type is not 1 or 2 (3)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択された積算設定は、有効ではありません。'
     */
    public function test421DisplayErrorMessageWhenRateTypeNot1Or2()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.22] Test display the error message when mile type not 1 (redemption)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Mile type is not 1 (3)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択されたマイルタイプは、有効ではありません。'
     */
    public function test422DisplayErrorMessageWhenMileTypeNotRedemption()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.23] Test display the error message when activity start date duplicated
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is same in database
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '指定の期間開始は既に使用されています。'
     */
    public function test423DisplayErrorMessageWhenActivityStartDateDuplicated()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.24] Test display the error message when purchase start date duplicated
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is same in database
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '指定の申し込み日は既に使用されています。'
     */
    public function test424DisplayErrorMessageWhenPurchaseStartDateDuplicated()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-4.25] Test save failed when server died
     *
     * Condition:
     * - Authenticate a user as admin
     * - Connection failed
     * - Mile type is 1
     * - Click on Save button
     *
     * Expectation:
     * - See the error message 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test425SaveFailedWhenServerDied()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * create the area list for test
     *
     * @param integer $totalExpected
     * @param string|null $areaPath
     * @param string|null $areaPathJP
     * @return void
     */
    private function createAreas($totalExpected = 1, $areaPath = null, $areaPathJP = null)
    {
        Area::query()->delete();

        $areaPaths = [
            'europe/spain/cordoba/',
            'europe/spain/granada/',
            'europe/spain/malaga/',
            'europe/spain/northern_spain/',
            'europe/spain/seville/',
            'europe/uk/london/'
        ];

        $areaPathsJP = [
            'europe/spain/cordoba/',
            'europe/spain/granada/',
            'europe/spain/malaga/',
            'europe/spain/northern_spain/',
            'europe/spain/seville/',
            'europe/uk/london/'
        ];

        if (!is_int($totalExpected) || $totalExpected <= 0) {
            $totalExpected = 1;
        }

        for ($i = 0; $i < $totalExpected; $i++) {
            factory(Area::class)->create([
                'area_path'    => ($i === 0 && !empty($areaPath) && !empty($areaPathJP) ? $areaPath   : $areaPaths[array_rand($areaPaths)]),
                'area_path_jp' => ($i === 0 && !empty($areaPath) && !empty($areaPathJP) ? $areaPathJP : $areaPathsJP[array_rand($areaPathsJP)]),
                'created_user' => 'admin@test.com',
            ]);
        }
    }

    /**
     * create promotions from params in database
     *
     * @param array $activityIDs
     * @param integer $totalRecords
     * @return void
     */
    private function createPromotions($activityIDs = [], $totalRecords = 3)
    {
        Promotion::query()->delete();

        $count = 1;
        $y = 2018;
        $m = 5;
        $total = is_int($activityIDs) ? $activityIDs : $totalRecords;
        $ids = ['VELTRA-10474', 'VELTRA-10484', 'VELTRA-154165'];
        $defData = [
            'VELTRA-10474'  => [
                'title'     => 'バトー・ロンドン(Bateaux London)☆お得に一流サービスを！テムズ川ランチクルーズ',
                'mile_type' => \Constant::MILE_ACCUMULATION,
                'area_path' => 'europe/spain/cordoba/',
            ],
            'VELTRA-10484'  => [
                'title' => 'ロンドン自転車ツアー　3つのコースから選べる！＜英語＞ ',
                'mile_type' => \Constant::MILE_ACCUMULATION,
                'area_path' => 'europe/spain/cordoba/',
            ],
            'VELTRA-154165' => [
                'title' => 'ロンドン・エクスプローラーパス（London Explorer Pass®）人気ツアー＆観光スポット入場カード',
                'mile_type' => \Constant::MILE_ACCUMULATION,
                'area_path' => 'europe/uk/london/',
            ],
        ];
        $defUnit = [\Constant::UNIT_ACTIVITY, \Constant::UNIT_AREA];
        $defRateType = [\Constant::ACCUMULATION_RATE_TYPE_VARIABLE, \Constant::ACCUMULATION_RATE_TYPE_FIXED];

        $data = is_array($activityIDs) && !empty($activityIDs) ? $activityIDs : $defData;
        
        $idsRandom = ['VELTRA-10474', 'VELTRA-10484', 'VELTRA-154165'];
        if ($total == 1) {
            $ids = ['VELTRA-10474'];
        } elseif ($total == 2) {
            $ids = ['VELTRA-10474', 'VELTRA-10484'];
        } elseif ($total > 3) {
            $len = $total - 3;
            for ($i = 0; $i < $len; $i++) {
                $ids[] = $idsRandom[array_rand($idsRandom)];
            }
        }

        foreach ($ids as $k => $id) {
            if ($count > 28) {
                $count = 1;
                if ($m >= 12) {
                    $m = 0;
                    $y++;
                    $m++;
                }
            }

            $dASD = $count < 10 && $count <= 30 ? '0'.$count       : $count;
            $dPSD = $count < 8  && $count <= 25 ? '0'.($count + 2) : ($count + 2);
            
            $asd = $y.'-'.($m < 10 ? '0'.$m : ($m > 12 ? 12 : $m)).'-'.$dASD;
            $psd = $y.'-'.($m < 10 ? '0'.$m : ($m > 12 ? 12 : $m)).'-'.$dPSD;

            factory(Promotion::class)->create([
                'activity_id'         => $id,
                'activity_title'      => $data[$id]['title'],
                'area_path'           => $data[$id]['area_path'],
                'unit'                => $defUnit[array_rand($defUnit)],
                'amount'              => 150 + ($k + 1),
                'activity_start_date' => isset($data[$id]['asd']) ? $data[$id]['asd']: $asd,
                'activity_end_date'   => isset($data[$id]['aed']) ? $data[$id]['aed']: null,
                'purchase_start_date' => isset($data[$id]['psd']) ? $data[$id]['psd']: $psd,
                'purchase_end_date'   => isset($data[$id]['ped']) ? $data[$id]['ped']: null,
                'mile_type'           => $data[$id]['mile_type'],
                'rate_type'           => $defRateType[array_rand($defRateType)],
                'created_user'        => 'admin@gmail.com',
            ]);
            $count++;
        }
    }

    /**
     * Create new promotion
     *
     * @param array $params
     * @param boolean $return
     * @return mixed
     */
    private function createPromotion($params = [], $return = false)
    {
        Promotion::query()->delete();

        $promotion = factory(Promotion::class)->create([
            'activity_id'         => $params['activity_id'] ?? null,
            'activity_title'      => $params['activity_title'] ?? null,
            'area_path'           => $params['area_path'] ?? null,
            'unit'                => $params['unit'] ?? \Constant::UNIT_ACTIVITY,
            'amount'              => $params['activity_end_date'] ?? 100,
            'activity_start_date' => $params['activity_start_date'],
            'activity_end_date'   => $params['activity_end_date'] ?? null,
            'purchase_start_date' => $params['purchase_start_date'],
            'purchase_end_date'   => $params['purchase_end_date'] ?? null,
            'mile_type'           => $params['mile_type'] ?? \Constant::MILE_ACCUMULATION,
        ]);

        if ($return === true) {
            return $promotion;
        }
    }

    /**
     * authenticate a given user as the current user
     *
     * default: login as admin
     *
     * @param object $user
     * @return void
     */
    private function checkLogin($isAdmin = true)
    {
        $this->actingAs($isAdmin ? $this->createAdmin() : $this->createOperator());
    }

    private function login($role = 'operator')
    {
        $this->actingAs($role == 'admin' ? $this->createAdmin() : $this->createOperator());
    }

    /**
     * get url by id
     *
     * @param integer|null $id
     * @return string
     */
    private function getUrlById($id = null)
    {
        $id = !empty($id) ? $id : 1;
        return route('admin.mile.promotion.edit', [$id]);
    }
}
