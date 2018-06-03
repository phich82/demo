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

class MileAccumulationNewPromotionTest extends TestCase
{
    use DatabaseTransactions;
    use StubAccount;

    /**
     * @var PromotionRepository
     */
    private $promotionRepo;

    /**
     * @var mileType
     */
    private static $mileType;

    /**
     * @var $url
     */
    private static $url;
    
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        self::$mileType = \Constant::MILE_ACCUMULATION;
        self::$url = route('admin.mile.promotion.create');
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
     * [TestCase-3.1] Test redirect to login page when login failed
     *
     * Condition:
     * - Authenticate a user with the wrong information of login
     *
     * Expectation:
     * - Redirect to the login page (/management/login)
     */
    public function test31RedirectToLoginPageWhenLoginFailed()
    {
        $this->get(self::$url)
             ->assertRedirect(route('admin.login'));
    }

    /**
     * [TestCase-3.2] Test access to mile accumulation new promotion page when login successfully
     *
     * Condition:
     * - Authenticate a user as admin
     * - Url of Mile Accumulation New Promotion Page (/management/mileage/accumulation/promotions/create)
     *
     * Expectation:
     * - See text: 'マイル積算 - プロモーション'
     */
    public function test32AccessToMileAccumulationNewPromotionPageWhenLoggedIn()
    {
        $this->login('admin');

        $this->get(self::$url)
             ->assertSee('マイル積算 - プロモーション');
    }

    /**
     * [TestCase-3.3] Test redirect to mile accumulation page when click on the link '<< マイル積算トップへ戻る'
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Redemption New Promotion Page (/management/mileage/redemption/promotions/create)
     * - Click on the link '<< マイル積算トップへ戻る' on screen
     *
     * Expectation:
     * - Redirect to Mile Redemption List Page (/management/mileage/redemption)
     * - See text: 'マイル積算'
     * - Do not see: text 'マイル積算 - 基本設定変更'
     */
    public function test33RedirectToMileRedemptionListPageWhenClickOnLinkRedemption()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.4] Test hightlight tab Activity when screen loaded
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation New Promotion Page (/management/mileage/accumulation/promotions/create)
     *
     * Expectation:
     * - See tab Activity (商品) highlighted
     */
    public function test34HighlightTabActivityWhenPageLoaded()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.5] Test hightlight tab Area when clicked
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation New Promotion Page (/management/mileage/accumulation/promotions/create)
     * - Click on tab Area (エリア)
     *
     * Expectation:
     * - See tab Activity (エリア) highlighted
     */
    public function test35HighlightTabAreaWhenClicked()
    {
        $this->markTestIncomplete('Mark skip test to find solution [click event]');
    }

    /**
     * [TestCase-3.6] Test display error when unit not 1 or 2
     *
     * Condition:
     * - Authenticate a user as admin
     * - Unit is an integer but not 1 or 2
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択された単位は、有効ではありません。'
     */
    public function test36DisplayErrorMessageWhenUnitNot1Or2()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.7] Test display the error message when entering acitivity start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is wrong date format (2018-01-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '期間開始の形式は、'Y-m-d'と合いません。'
     */
    public function test37DisplayErrorMessageWhenEnteringActivityStartDateWithWrongFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.8] Test display the error message when entering acitivity end date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity end date is wrong date format (2018-05-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '期間終了の形式は、'Y-m-d'と合いません。'
     */
    public function test38DisplayErrorMessageWhenEnteringActivityEndDateWithWrongFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.9] Test display the error message when entering acitivity end date less than activity start date
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
    public function test39DisplayErrorMessageWhenEnteringActivityEndDateLessThanActivityStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.10] Test display the error message when entering purchase start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is wrong date format (2018-02-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日の形式は、'Y-m-d'と合いません。'
     */
    public function test310DisplayErrorMessageWhenEnteringPurchaseStartDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.11] Test display the error message when entering purchase end date with the wrong date format
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase end date is wrong date format (2018-05-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '申し込み日終了の形式は、'Y-m-d'と合いません。'
     */
    public function test311DisplayErrorMessageWhenEnteringPurchaseEndDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.12] Test display the error message when entering purchase end date less than purchase start date
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
    public function test312DisplayErrorMessageWhenEnteringPurchaseEndDateLessThanPurchaseStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.13] Test display the error message when entering purchase start date less than activity start date
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
    public function test313DisplayErrorMessageWhenEnteringPurchaseStartDateLessThanActivityStartDate()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.14] Test display the error message when has any errors from inputs
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is wrong date format (2018-01-01xxx)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error messages in the error list on screen
     */
    public function test314DisplayErrorMessageWhenAnyErrorsFromInputs()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.15] Test save completed when save successfully
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
    public function test315SaveCompletedWhenSaveSuccessfully()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.16] Test Display A Popup For Search By Activity When Click On Link Select Activity (商品を選ぶ)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on tab '商品'
     * - Click on the link '商品を選ぶ'
     *
     * Expectation:
     * - See a popup with the header text '商品を検索し、選択してください。'
     */
    public function test316DisplayPopupForSearchByActivityWhenClickOnLinkSelectActivity()
    {
        $this->markTestIncomplete('Mark skip test to find solution [click event]');
    }

    /**
     * [TestCase-3.17] Test no display the activity list if not found anything the matching when search by acitivity id
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on tab '商品'
     * - Click on the link '商品を選ぶ', display a popup
     * - Enter a invalid value at ActivityID field (blablabla)
     *
     * Expectation:
     * - Do not see any rows (records) in the activity list
     * - See the result text '0 件'
     */
    public function test317NoDisplayActivityListIfNotFoundAnythingMatchingWhenSearchByAcivityId()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.18] Test no display the activity list if not found anything the matching when search by acitivity title
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on tab '商品'
     * - Click on the link '商品を選ぶ', display a popup
     * - Enter a invalid value at ActivityTitle field (blablabla)
     *
     * Expectation:
     * - Do see any rows (records) in the activity list
     * - See the result text '0 件'
     */
    public function test318NoDisplayActivityListIfNotFoundAnythingMatchingWhenSearchByAcivityTitle()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.19] Test display the activity list if found the matching when search by acitivity id
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on tab '商品'
     * - Click on the link '商品を選ぶ', display a popup
     * - Enter a valid value at ActivityID field (VELTRA-10484)
     *
     * Expectation:
     * - See a row (record) in the activity list
     * - See ActivityID for search in row
     * - See the result text '1 件'
     */
    public function test319DisplayActivityListIfFoundWhenSearchByAcivityId()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.20] Test display the activity list if found the partial matching when search by acitivity title
     *
     * Condition:
     * - Authenticate a user as admin
     * - All inputs are valid
     * - Click on tab '商品'
     * - Click on the link '商品を選ぶ', display a popup
     * - Enter a valid value at ActivityTitle field ('ロンドン')
     *
     * Expectation:
     * - See the rows (records) in the activity list
     * - Each row in list contains Search keyword ActivityTitle
     * - See the result text '{number} 件' (number > 0)
     */
    public function test320DisplayActivityListIfFoundWhenSearchByAcivityTitle()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.21] Test Display A Popup For Search By Area When Click On Link Select Area ('エリアを選ぶ')
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on tab 'エリア'
     * - Click on the link 'エリアを選ぶ'
     *
     * Expectation:
     * - See a popup with the header text 'エリアを検索し、選択してください。'
     */
    public function test321DisplayAPopupForSearchByAreaWhenClickOnLinkSelectArea()
    {
        $this->markTestIncomplete('Mark skip test to find solution [click event]');
    }

    /**
     * [TestCase-3.22.1] Test no display the area list when database empty
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on Button 'エリア'
     * - Click on the link 'エリアを選ぶ''
     * - Enter a invalid value in input ('blablabla')
     *
     * Expectation:
     * - Do not see any rows (records) in the area list
     * - See a result text '0 件'
     */
    public function test3221NoDisplayAreaListWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.22.2] Test no display the area list if not found the matching when search by area
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on Button 'エリア'
     * - Click on the link 'エリアを選ぶ''
     * - Enter a invalid value in input ('blablabla')
     *
     * Expectation:
     * - Do not see any rows (records) in the area list
     * - See a result text '0 件'
     */
    public function test3222NoDisplayAreaListIfNotFoundWhenSearchByArea()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.23] Test display the area list if found the matching when search by area
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on Button 'エリア'
     * - Click on the link 'エリアを選ぶ''
     * - Enter a value in input ('london')
     *
     * Expectation:
     * - See the rows (records) in the area list
     * - Each row (record) contains search keyword for AreaPath
     * - See a result text '{number} 件' (number > 0)
     */
    public function test323DisplayAreaListIfFoundWhenSearchByArea()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.24] Test change rate type when click on radio button '変動額' (change rate type)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on radio button (変動額)
     *
     * Expectation:
     * - See a text '1マイル' & an input & a text '円'
     */
    public function test324ChangeRateTypeWhenClickOnChangeRadioButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.25] Test change rate type when click on radio button '固定額 (fixed rate type)
     *
     * Condition:
     * - Authenticate a user as admin
     * - Click on radio button (固定額)
     *
     * Expectation:
     * - See a text '一律' & an input & text 'マイル割引'
     */
    public function test325ChangeRateTypeWhenClickOnFixedRadioButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.26] Test display the error message when amount non numeric
     *
     * Condition:
     * - Authenticate a user as admin
     * - Amount is '150xxx'
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '積算値には、数字を指定してください。'
     */
    public function test326DisplayErrorMessageWhenAmountNonNumeric()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.27] Test display the error message when rate type not 1 or 2
     *
     * Condition:
     * - Authenticate a user as admin
     * - Rate type is not 1 or 2 (3)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択された積算設定は、有効ではありません。'
     */
    public function test327DisplayErrorMessageWhenRateTypeNot1Or2()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.28] Test display the error message when mile type not 2
     *
     * Condition:
     * - Authenticate a user as admin
     * - Mile type is not 2 (3)
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '選択されたマイルタイプは、有効ではありません。'
     */
    public function test328DisplayErrorMessageWhenMileTypeNotAccumulation()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.29] Test display the error message when activity start date duplicated
     *
     * Condition:
     * - Authenticate a user as admin
     * - Activity start date is same in database
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '指定の期間開始は既に使用されています。'
     */
    public function test329DisplayErrorMessageWhenActivityStartDateDuplicated()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.30] Test display the error message when purchase start date duplicated
     *
     * Condition:
     * - Authenticate a user as admin
     * - Purchase start date is same in database
     * - Click on Save button (保存する)
     *
     * Expectation:
     * - See the error message '指定の申し込み日は既に使用されています。'
     */
    public function test330DisplayErrorMessageWhenPurchaseStartDateDuplicated()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.15] Test save failed when server died
     *
     * Condition:
     * - Authenticate a user as admin
     * - Connection failed
     * - Mile type is 2
     * - Some new rows inserted (2)
     * - Click on Save button
     *
     * Expectation:
     * - See the error message 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test331SaveFailedWhenServerDied()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-3.32] Test display error message for restful api when the server failed
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Redemption New Promotion Page (/management/mileage/redemption/promotions/create)
     * - Connection failed
     *
     * Expectation:
     * - See the error message: 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test332DisplayErrorApiWhenServerFailed()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * mock restful api
     *
     * @param array $params
     * @return void
     */
    private function mockApi($params = [])
    {
        $activity_ids = ['VELTRA-10474', 'VELTRA-10484', 'VELTRA-154165'];
        $activity_titles = [
            'VELTRA-10474'  => 'バトー・ロンドン(Bateaux London)☆お得に一流サービスを！テムズ川ランチクルーズ',
            'VELTRA-10484'  => 'ロンドン自転車ツアー　3つのコースから選べる！＜英語＞ ',
            'VELTRA-154165' => 'ロンドン・エクスプローラーパス（London Explorer Pass®）人気ツアー＆観光スポット入場カード',
        ];

        $m = Mockery::mock('App\Api\ActivityApi');

        if (array_key_exists('id', $params)) { // fake api for search by ActivityID
            if (array_search($params['id'], $activity_ids) === false) { // not found
                $returned = (object)[
                    'common' => (object)['status_code' => 404]
                ];
                // return the api error
                $m->shouldReceive('find')->andReturn($returned);
            } else {
                $data = [
                    (object) ['id' => $params['id'], 'title' => $activity_titles[$params['id']]]
                ];
                $returned = (object)[
                    'common' => (object)['status_code' => 200],
                    'id'     => $params['id'],
                    'title'  => $activity_titles[$params['id']]
                ];
                $m->shouldReceive('find')->andReturn($returned);
            }
        }
        
        if (array_key_exists('title', $params)) { // fake api for search by ActivityTitle
            $out = [];
            foreach ($activity_titles as $id => $title) {
                if (stripos($title, $params['title']) !== false) {
                    $out[] = (object)['id' => $id, 'title' => $title];
                }
            }
            $returned = (object)[
                'common'     => (object)['status_code' => 200],
                'activities' => !empty($out) ? $out : [],
                'total'      => count($out),
            ];
            $m->shouldReceive('getByTitle')->andReturn($returned);
        }
        $this->app->instance('App\Api\ActivityApi', $m);
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
            'europe/spain/cordoba-',
            'europe/spain/granada-',
            'europe/spain/malaga-',
            'europe/spain/northern_spain-',
            'europe/spain/seville-',
            'europe/uk/london-'
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
                'area_path'     => ($i === 0 && !empty($areaPath) && !empty($areaPathJP) ? $areaPath.$i.'-/' : $areaPaths[array_rand($areaPaths)].$i.'/'),
                'area_path_jp'  => ($i === 0 && !empty($areaPath) && !empty($areaPathJP) ? $areaPathJP      : $areaPathsJP[array_rand($areaPathsJP)]),
                'created_user'  => 'admin@test.com',
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
                'activity_id' => $id,
                'activity_title' => $data[$id]['title'],
                'area_path' => $data[$id]['area_path'],
                'unit' => $defUnit[array_rand($defUnit)],
                'amount' => 150 + ($k + 1),
                'activity_start_date' => isset($data[$id]['asd']) ? $data[$id]['asd'] : $asd,
                'activity_end_date' => isset($data[$id]['aed']) ? $data[$id]['aed'] : null,
                'purchase_start_date' => isset($data[$id]['psd']) ? $data[$id]['psd'] : $psd,
                'purchase_end_date' => isset($data[$id]['ped']) ? $data[$id]['ped'] : null,
                'mile_type' => $data[$id]['mile_type'],
                'rate_type' => $defRateType[array_rand($defRateType)],
                'created_user' => 'admin@gmail.com',
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
}
