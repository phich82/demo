<?php
/**
 * Screen: ANA_MileSetting_Accumulation_ListScreen
 * @author Phat Huynh Nguyen <huynh.phat@mulodo.com>
 */
namespace Tests\Unit\MileSetting;

use Tests\TestCase;
use App\Models\Mile;
use Tests\StubAccount;
use App\Models\Promotion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\MileRepository;
use App\Repositories\PromoRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MileAccumulationListTest extends TestCase
{
    use DatabaseTransactions;
    use StubAccount;

    /**
     * @var MileRepository
     */
    private $mileRepo;

    /**
     * @var PromoRepository
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
        self::$url = route('admin.mile.index');
        $this->mileRepo = new MileRepository();
        $this->promotionRepo = new PromoRepository();
        if ($this->bookingDetail === null) {
            $this->mockApi();
            Mile::query()->delete();
            Promotion::query()->delete();
        }
        $this->prepareData();
        $this->startSession();
    }

    /**
     * [TestCase-1.1] Test redirect to login page when login failed
     *
     * Condition:
     * - Authenticate a user with the wrong information of login
     *
     * Expectation:
     * - Redirect to the login page (/management/login)
     */
    public function test11RedirectToLoginPageWhenLoginFailed()
    {
        $this->get(self::$url)
             ->assertRedirect('/management/login');
    }

    /**
     * [TestCase-1.2] Test access to mile accumulation page when login successfully
     *
     * Condition:
     * - Authenticate a user
     * - Url of Mile Accumulation List Page (/management/mileage/accumulation)
     *
     * Expectation:
     * - See text: 'マイル積算'
     */
    public function test12AccessToMileAccumulationListPageWhenLoggedIn()
    {
        $this->checkLogin();

        $this->get(self::$url)
             ->assertSee('マイル積算');
    }

    /**
     * [TestCase-1.3] Test redirect to mile redemption page when click on the link 'マイル償還'
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Click on the link 'マイル償還' on screen
     *
     * Expectation:
     * - Redirect to Mile Accumulation List Page (/management/mileage/accumulation)
     * - See text: 'マイル償還-プロモーション'
     */
    public function test13RedirectToMileRedemptionListPageWhenClickOnLinkRedemption()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.4.1] Test no display mile current basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Database empty
     *
     * Expectation:
     * - Do not see: '円=1マイル'
     */
    public function test141NoDisplayMileCurrentBasicSettingWhenDatabaseEmpty()
    {
        $this->checkLogin();

        Mile::query()->delete();

        $this->get(self::$url)
             ->assertViewHas('currentSetting', null);
    }

    /**
     * [TestCase-1.4.2] Test no display mile current basic setting information when returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see: '円=1マイル'
     */
    public function test142NoDisplayMileCurrentBasicSettingWhenReturnedDataEmpty()
    {
        $this->login();

        // create a mile current basic setting with type as redemption
        $this->createBasicSetting(['plan_start_date' => date('Y-m-d'), 'mile_type' => \Constant::MILE_REDEMPTION]);

        $this->get(self::$url)
             ->assertViewHas('currentSetting', null);
    }

    /**
     * [TestCase-1.5] Test display mile current basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Plan Start Date is '2018-01-01' & amount is '100'
     * - Mile type is 1
     *
     * Expectation:
     * - Display mile current basic setting information: '100円=1マイル' & '(2018-01-01以降)'
     */
    public function test15DisplayMileCurrentBasicSetting()
    {
        $this->checkLogin();

        $this->createBasicSettings([
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) - 2*24*60*60), 'amount' => 50],
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + 2*24*60*60), 'amount' => 150],
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + 4*24*60*60), 'amount' => 200],
        ]);

        $currentSetting = $this->mileRepo->getCurrentSetting(self::$mileType);

        $this->assertEquals(1, count($currentSetting));

        $this->get(self::$url)
             ->assertSee((string)$currentSetting->amount)
             ->assertSee($currentSetting->plan_start_date);
    }

    /**
     * [TestCase-1.6.1] Test no display mile schedule basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Database empty
     *
     * Expectation:
     * - Do not see any the mile scheduled basic settings on screen
     */
    public function test161NoDisplayMileScheduleBasicSettingWhenDatabaseEmpty()
    {
        $this->login();

        Mile::query()->delete();

        $this->get(self::$url)
             ->assertViewHas('scheduledSetting', null);
    }

    /**
     * [TestCase-1.6.2] Test no display mile schedule basic setting information when the returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see: 'より'
     */
    public function test162NoDisplayMileScheduleBasicSettingWhenReturnedDataEmpty()
    {
        $this->checkLogin();

        Mile::query()->delete();

        $this->createBasicSetting(['plan_start_date' => date('Y-m-d'), 'amount' => 150]);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);
        
        $this->assertEquals(null, $scheduleSetting);
        $this->get(self::$url)
             ->assertViewHas('scheduleSetting', null);
    }

    /**
     * [TestCase-1.7] Test display mile schedule basic setting information when returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Returned data not empty
     * - Plan Start Date is '2019-01-01' & amount is '150'
     * - Mile type is 1
     *
     * Expectation:
     * - Display mile schedule basic setting information: '2019-01-01より' & '150円=1マイル'
     */
    public function test17DisplayMileScheduleBasicSetting()
    {
        $this->login();

        $this->createBasicSettings([
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 3*24*60*60), 'amount' => 150],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 5*24*60*60), 'amount' => 200],
        ]);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);
        
        $this->assertEquals(1, count($scheduleSetting));
        $this->get(self::$url)
             ->assertSee((string)$scheduleSetting->amount)
             ->assertSee($scheduleSetting->plan_start_date);
    }

    /**
     * [TestCase-1.8] Test no display the link Edit Mile Schedule Setting when login as operator
     *
     * Condition:
     * - Authenticate a user as operator
     * - Database not empty
     *
     * Expectation:
     * - Do not see the link '編集' at area Schedule Basic Setting
     */
    public function test18NoDisplayLinkEditScheduleSettingWhenLoginAsOperator()
    {
        // login as operator
        $this->login('operator');

        $this->createBasicSettings([
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 3*24*60*60), 'amount' => 150],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 5*24*60*60), 'amount' => 200],
        ]);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);

        $this->assertEquals(1, count($scheduleSetting));
        $this->get(self::$url)
             ->assertDontSee('<a href="'.route('admin.mile.basic'));
    }

    /**
     * [TestCase-1.9] Test display the link Edit Mile Schedule Setting when login as admin
     *
     * Condition:
     * - Authenticate a user as admin
     * - Database not empty
     *
     * Expectation:
     * - See the link: '編集'
     */
    public function test19DisplayLinkEditScheduleSettingWhenLoginAsAdmin()
    {
        $this->login('admin');

        // create 5 records randomly
        $this->createBasicSettings([
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 3*24*60*60), 'amount' => 150],
            ['plan_start_date' => date('Y-m-d', \strtotime(date('Y-m-d')) + 5*24*60*60), 'amount' => 200],
        ]);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);

        $this->assertEquals(1, count($scheduleSetting));
        $this->get(self::$url)
             ->assertSee('編集')
             ->assertSee(route('admin.mile.basic', $scheduleSetting->id));
    }

    /**
     * [TestCase-1.10] Test display 0 results of promotions when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Database empty
     * - Total of records per page (perPage) is 20
     *
     * Expectation:
     * - See a result text: '0 件中 1-20 を表示'
     */
    public function test110DisplayZeroResultsOfPromotionsWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.11] Test display total of the returned promotions results
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Total of records per page (perPage) is 30
     *
     * Expectation:
     * - See a result text: '30 件中 1-2 を表示'
     * - (Do not see a result text '0 件中 1-1 を表示')
     */
    public function test111DisplayTotalOfReturnedPromotionsResults()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.12] Test Enabled CSV Button when database not empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Total of records per page (perPage) is 25
     *
     * Expectation:
     * - See a text: '<span id="totalPromotions" data-total="3"'
     * - See a result text: '25 件中 1-3 を表示'
     * - Do not see text ('<span id="totalPromotions" data-total="0"')
     */
    public function test112EnabledCsvButtonWhenDatabaseNotEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.13] Test Enabled CSV Button when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Database empty
     * - Total of records per page (perPage) is 25
     *
     * Expectation:
     * - See a text: '<span id="totalPromotions" data-total="0"'
     * - See a result text: '0 件中 1-1 を表示'
     */
    public function test113DisabledCsvButtonWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.14] Test download CSV file when click on the button 'CSVダウンロード'
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Redemption List Page (/management/mileage/redemption)
     * - Database not empty
     * - Click on the button 'CSVダウンロード' on screen
     *
     * Expectation:
     * - See a csv file downloaded:
     *   + Code status = 200
     *   + Headers contains 'Content-Disposition: attachment; filename="promotionlist_[0-9]{12}.csv"'
     */
    public function test114DownloadCsvFileWhenClickOnCsvDownloadButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.15] Test display a link create new promotion when login as admin
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Redemption List Page (/management/mileage/redemption)
     *
     * Expectation:
     * - See text: '新規プロモーションを作成'
     */
    public function test115DisplayLinkCreateNewPromotionWhenLoginAsAdmin()
    {
        $this->login('admin');

        $this->get(self::$url)
             ->assertSee('新規プロモーションを作成');
    }

    /**
     * [TestCase-1.16] Test no display a link create new promotion when login as operator
     *
     * Condition:
     * - Authenticate a user as operator
     * - Access to Mile Redemption List Page (/management/mileage/redemption)
     *
     * Expectation:
     * - Do not see text: '新規プロモーションを作成'
     */
    public function test116NoDisplayLinkCreateNewPromotionWhenLoginAsOperator()
    {
        $this->login('operator');

        $this->get(self::$url)
             ->assertDontSee('新規プロモーションを作成');
    }

    /**
     * [TestCase-1.17] Test redirect to Accumulation New Promotion Page when click on button 'New Promotion'
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Click on button 'New Promotion' (新規プロモーションを作成)
     *
     * Expectation:
     * - See text: 'マイル積算 - プロモーション'
     */
    public function test117RedirectToNewPromotionPageWhenClickOnNewPromotionButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.18] Test redirect to Accumulation Edit Basic Setting Page when click on button 'Edit Schedule Basic Setting'
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Click on button 'Edit Schedule Basic Setting' (編集)
     *
     * Expectation:
     * - See text: 'マイル積算 - 基本設定変更'
     */
    public function test118RedirectToEditBasicSettingPageWhenClickOnEditButton()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.19] Test display the promotion list
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     *
     * Expectation:
     * - See the promotion list
     */
    public function test119DisplayPromotionList()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.20] Test no display the promotion list
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Redemption List Page (/management/mileage/redemption)
     * - Database empty
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test120NoDisplayPromotionList()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.21.1] Test display the promotion list from filtering by sorting ASC activity start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a sort type as ASC Activity Start Date (1)
     *
     * Expectation:
     * - See: the promotion list filtered by the selected sort type (1)
     */
    public function test1211DisplayPromotionsListByFilteringActivityStartDateAscSort()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.21.2] Test display the promotion list from filtering by sorting DESC activity start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a sort type as DESC Activity Start Date (2)
     *
     * Expectation:
     * - See: the promotion list filtered by the selected sort type (2)
     */
    public function test1212DisplayPromotionsListByFilteringActivityStartDateDescSort()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.21.3] Test display the promotion list from filtering by sorting ASC purchase start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a sort type as ASC Purchase Start Date (3)
     *
     * Expectation:
     * - See: the promotion list filtered by the selected sort type (3)
     */
    public function test1213DisplayPromotionsListByFilteringPurchaseStartDateAscSort()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.21.4] Test display the promotion list from filtering by sorting DESC purchase start date
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a sort type as ASC Purchase Start Date (4)
     *
     * Expectation:
     * - See: the promotion list filtered by the selected sort type (4)
     */
    public function test1214DisplayPromotionsListByFilteringPurchaseStartDateDescSort()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.22] Test display the promotion list from filtering by total of records per page
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select the total of records will shown on a page (50)
     *
     * Expectation:
     * - See: the promotion list contains 50 rows
     */
    public function test122DisplayPromotionsListByFilteringTotalRecordsPerPage()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.23.1] Test display the promotion list from filtering by unit
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a unit (null: All (すべて))
     *
     * Expectation:
     * - See: the promotion list that each row contains either 'すべて' or 'エリア'
     */
    public function test1231DisplayPromotionsListActivityAreaByFilteringUnitAll()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.23.2] Test display the promotion list with only activity from filtering by unit
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a unit (1: Activity (商品))
     *
     * Expectation:
     * - See: the promotion list that each row contains only 'すべて'
     */
    public function test1232DisplayPromotionsListOnlyActivityByFilteringUnitActivity()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.23.3] Test display the promotion list with only area from filtering by unit
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Select a unit (2: Area (エリア))
     *
     * Expectation:
     * - See: the promotion list that each row contains only 'エリア'
     */
    public function test1233DisplayPromotionsListOnlyAreaByFilteringUnitArea()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.24.1] Test display the promotion list from filtering by activity/area when found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (london) at item '商品・エリア'
     *
     * Expectation:
     * - See: the promotion list that each row contains text 'london'
     */
    public function test1241DisplayPromotionsListByFilteringActivityAreaKeywordWhenFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.24.2] Test no display the promotion list from filtering by activity/area when not found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (blablabla) at item '商品・エリア'
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test1242NoDisplayPromotionsListByFilteringActivityAreaKeywordWhenNotFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.25.1] Test display the promotion list from filtering by period of activity dates when found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (2018-06-01) at item '期間'
     *
     * Expectation:
     * - See: the promotion list that each row contains:
     *   + ActivityStartDate < 2018-06-01 if ActivityEndDate null
     *   + ActivityStartDate < 2018-06-01 < ActivityEndDate if ActivityEndDate not null
     */
    public function test1251DisplayPromotionsListByFilteringActivityDatePeriodWhenFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.25.2] Test no display the promotion list from filtering by period of activity dates when not found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (2018-01-01) at item '期間'
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test1252NoDisplayPromotionsListByFilteringActivityDatePeriodWhenNotFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.25.3] Test no display the promotion list from filtering by period of activity dates when the invalid date period
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (blablabla) at item '期間'
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test1253NoDisplayPromotionsListByFilteringActivityDatePeriodWhenInvalidPeriod()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.26.1] Test display the promotion list from filtering by period of purchase dates when found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (2018-06-01) at item '申し込み日'
     *
     * Expectation:
     * - See: the promotion list that each row contains:
     *   + PurchaseStartDate < 2018-06-01 if PurchaseEndDate null
     *   + PurchaseStartDate < 2018-06-01 < PurchaseEndDate if PurchaseEndDate not null
     */
    public function test1261DisplayPromotionsListByFilteringPurchaseDatePeriodWhenFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.26.2] Test no display the promotion list from filtering by period of purchase dates when not found
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (2018-01-01) at item '申し込み日'
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test1262NoDisplayPromotionsListByFilteringPurchaseDatePeriodWhenNotFound()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.26.3] Test no display the promotion list from filtering by period of purchase dates when the invalid date period
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Enter the input field a value (blablabla) at item '申し込み日'
     *
     * Expectation:
     * - Do not see: the promotion list
     */
    public function test1263NoDisplayPromotionsListByFilteringPurchaseDatePeriodWhenInvalidPeriod()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.27] Test no display the link edit promotion on each row of the promotion list
     *
     * Condition:
     * - Authenticate a user as operator
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     *
     * Expectation:
     * - Do not see: the link edit promotion '編集' on each row of the promotion list
     */
    public function test127NoDisplayLinkEditPromotionWhenLoginAsOperator()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.28] Test display the link edit promotion on each row of the promotion list
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     *
     * Expectation:
     * - See: the link edit promotion '編集' on each row of the promotion list
     */
    public function test128DisplayLinkEditPromotionWhenLoginAsAdmin()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.29] Test redirect to Update/Delete promotion page when click on the link edit promotion '編集'
     *
     * Condition:
     * - Authenticate a user as admin
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty
     * - Click on the link edit promotion '編集'
     *
     * Expectation:
     * - Redirect to Update/Delete Promotion page:
     *   + See a header text 'マイル積算 - プロモーション'
     *   + See a link 'このプロモーションを削除する'
     */
    public function test129RedirectToEitPromotionPageWhenClickOnLinkEditPromotion()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.30] Test no display the links of pagination when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database empty
     *
     * Expectation:
     * - Do not see: the links of pagination
     */
    public function test130NoDisplayLinksPaginationWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.31] Test display the links of pagination when database not empty
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty (81 records)
     *
     * Expectation:
     * - See: the links of pagination [<] [1] [2] [3] [4] [5] [>]
     */
    public function test131DisplayLinksPaginationWhenDatabaseNotEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.32] Test display promotion list when click on each link of pagination
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty (81 records)
     * - Click on each link of pagination (2)
     *
     * Expectation:
     * - See: a new promotion list will be shown
     */
    public function test132DisplayPromotionListWhenClickOnLinkPagination()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.33] Test display the summary result at bottom-right screen
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty (81 records)
     * - Total of records per page (20)
     * - Total of records (50)
     *
     * Expectation:
     * - See: '50 件中 1-20 を表示'
     * - (Do not see '0 件中 1-1 を表示')
     */
    public function test133DisplaySummaryResultWhenLoadScreen()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.34] Test display the summary result at bottom-right screen when changing the filter action
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Database not empty (81 records)
     * - Total of records per page (20)
     * - Total of records (50)
     * - Change unit (1)
     *
     * Expectation:
     * - See: '50 件中 1-20 を表示'
     */
    public function test134DisplaySummaryResultWhenChangeFilterAction()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.35] Test display error message when the server failed
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Connection failed
     *
     * Expectation:
     * - See the error message: 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test135DisplayErrorWhenServerFailed()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.36] Test display error message for restful api when the server failed
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation List Page (/management/mileage/accumulation)
     * - Connection failed
     *
     * Expectation:
     * - See the error message: 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test136DisplayErrorApiWhenServerFailed()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * authenticate a given user as the current user
     *
     * default: login as operator
     *
     * @param string $role
     * @return void
     */
    private function login($role = 'operator')
    {
        $this->actingAs($role == 'admin' ? $this->createAdmin() : $this->createOperator());
    }

    /**
     * create the mile basic settings
     *
     * @param array $data
     * @return void
     */
    private function createBasicSettings($params = [])
    {
        Mile::query()->delete();

        foreach ($params as $param) {
            factory(Mile::class)->create([
                'plan_start_date' => $param['plan_start_date'],
                'amount'          => $param['amount'] ?? 100,
                'mile_type'       => $param['mile_type'] ?? \Constant::MILE_ACCUMULATION
            ]);
        }
    }

    /**
     * Create new a basic setting
     *
     * @param array $params
     * @param boolean $return
     * @return mixed
     */
    private function createBasicSetting($params = [], $return = false)
    {
        Mile::query()->delete();

        $basicSetting = factory(Mile::class)->create([
            'plan_start_date' => $params['plan_start_date'] ?? date('Y-m-d'),
            'amount'          => $params['amount'] ?? 100,
            'mile_type'       => $params['mile_type'] ?? \Constant::MILE_ACCUMULATION,
        ]);

        if ($return === true) {
            return $basicSetting;
        }
    }

    /**
     * mock api
     *
     * @return void
     */
    private function mockApi()
    {
        $bookingData  = json_decode(file_get_contents(base_path('tests/json/getBookingDetail.json')));
        $activityData = json_decode(file_get_contents(base_path('tests/json/getActivityDetail.json')));

        Api::shouldReceive('requestNoCache')
            ->with('get-booking-details', ['booking_id' => 'VELTRA-123456'])
            ->andThrow(new HttpException(404));
        Api::shouldReceive('requestNoCache')
            ->with('get-booking-details', \Mockery::any())
            ->andReturn($bookingData);
        Api::shouldReceive('request')
            ->with('get-activity-details', \Mockery::any())
            ->andReturn($activityData);

        $this->bookingDetail  = $bookingData;
        $this->activityDetail = $activityData;
    }

    /**
     * create promotions for test
     *
     * @param integer $total
     * @return void
     */
    private function createPromotions($total = 1)
    {
        for ($i = 1; $i <= $total; $i++) {
            $asd = $i*24*60*60;
            factory(Promotion::class)->create([
                'activity_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + $asd),
                'activity_end_date' => null,
                'purchase_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + $asd + 3*24*60*60),
                'purchase_end_date' => null,
                'amount' => 100 + $i,
                'mile_type' => self::$mileType
            ]);
        }
    }
}
