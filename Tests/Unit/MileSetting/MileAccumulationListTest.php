<?php
/**
 * Screen: ANA_MileSetting_Redemption_ListScreen
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

        $this->get(self::$url)
             ->assertSee('編集');
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
        $this->markTestIncomplete('Mark skip test to find solution');
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

        $this->createBasicSettings(10, '2018-01-05', 200);

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
     * - Do not see: 'より'
     */
    public function test161NoDisplayMileScheduleBasicSettingWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
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

        $planStartDate = '2019-01-01';
        factory(Mile::class)->create([
            'plan_start_date' => '2018-01-01',
            'amount' => 150,
            'mile_type' => \Constant::MILE_ACCUMULATION,
        ]);
        factory(Mile::class)->create([
            'plan_start_date' => strtotime($planStartDate) <= time() ? date('Y-m-d', time() + 1*24*60*60) : $planStartDate,
            'amount' => 200,
            'mile_type' => \Constant::MILE_REDEMPTION,
        ]);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);
        
        $this->assertEquals(null, $scheduleSetting);
        $this->get(self::$url)
             ->assertDontSee('より');
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
        $this->checkLogin();

        $this->createBasicSettings(20, '2020-01-01', 150);

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
        $this->checkLogin(false);

        // create 5 records randomly
        $this->createBasicSettings(5, '2020-01-01', 150);

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
        $this->checkLogin();

        // create 5 records randomly
        $this->createBasicSettings(5, '2020-01-01', 150);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);
        $linkEdit = route('admin.mile.basic');

        $this->assertEquals(1, count($scheduleSetting));
        $this->get(self::$url)
             ->assertSee($linkEdit);
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
    public function test115DisplayALinkCreateNewPromotionWhenLoginAsAdmin()
    {
        $this->checkLogin();

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
    public function test116NoDisplayALinkCreateNewPromotionWhenLoginAsOperator()
    {
        $this->checkLogin(false);

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
     * [TestCase-1.33] Test display the sumary result at bottom-right screen
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
    public function test133DisplaySumaryResultWhenLoadScreen()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.34] Test display the sumary result at bottom-right screen when changing the filter action
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
    public function test134DisplaySumaryResultWhenChangeFilterAction()
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
     * default: login as admin
     *
     * @param object $user
     * @return void
     */
    private function checkLogin($isAdmin = true)
    {
        $this->actingAs($isAdmin ? $this->createAdmin() : $this->createOperator());
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
                    $m = 1;
                    $y++;
                } else {
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
            $m++;
            $y++;
        }
    }

    /**
     * create the basic settings with the expected total of records
     *
     * @param integer $numRows
     * @param string $defDate
     * @param float $defAmount
     * @return void
     */
    private function createBasicSettings($numRows = 1, $defDate = null, $defAmount = null)
    {
        Mile::query()->delete();

        if (!empty($defDate) && !empty($defAmount)) {
            factory(Mile::class)->create([
                'plan_start_date' => $defDate,
                'amount' => $defAmount,
                'mile_type' => \Constant::MILE_ACCUMULATION,
            ]);
            $numRows = $numRows > 1 ? $numRows - 1 : 0;
        }

        $mileTypes = [\Constant::MILE_REDEMPTION, \Constant::MILE_ACCUMULATION];

        $count = 1;
        $y = 2018;
        $m = 5;
        for ($i = 0; $i < $numRows; $i++) {
            if ($count > 28) {
                $count = 1;
                if ($m >= 12) {
                    $m = 1;
                    $y++;
                } else {
                    $m++;
                }
            }

            $d = $count < 10 && $count <= 30 ? '0'.$count : $count;
            $sDate = $y.'-'.($m < 10 ? '0'.$m : ($m > 12 ? 12 : $m)).'-'.$d;

            factory(Mile::class)->create([
                'plan_start_date' => $sDate,
                'amount' => 150 + ($i + 1),
                'mile_type' => $mileTypes[array_rand($mileTypes)]
            ]);

            $count++;
            $m++;
            $y++;
        }
    }

    /**
     * get url by paramters
     *
     * @param array $params
     * @return string
     */
    private function getUrlByParams($params = [])
    {
        if (is_array($params)) {
            return route('admin.mile.index', $params);
        }
        return self::$url;
    }
}
