<?php

namespace Tests\Unit\MileSetting;

use Tests\TestCase;
use App\Models\Mile;
use Tests\StubAccount;
use Illuminate\Support\Facades\DB;
use App\Repositories\MileRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MileRedemptionBasicSettingTest extends TestCase
{
    use DatabaseTransactions;
    use StubAccount;

    /**
     * @var MileRepository
     */
    private $mileRepo;

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
        self::$mileType = \Constant::MILE_REDEMPTION;
        self::$url = route('admin.mile.redemption.basic');
        $this->mileRepo = new MileRepository();
    }
    
    /**
     * [TestCase-6.1] Test redirect to login page when login failed
     *
     * Condition:
     * - Authenticate a user with the wrong information of login
     *
     * Expectation:
     * - Redirect to the login page (/management/login)
     */
    public function test61RedirectToLoginPageWhenLoginFailed()
    {
        $response = $this->get(self::$url);
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * [TestCase-6.2] Test access to mile redemption page when login successfully
     *
     * Condition:
     * - Authenticate a user
     * - Url of Mile Redemption List Page (/management/mileage/redemption/basic-setting/create)
     *
     * Expectation:
     * - See text: 'マイル償還 - 基本設定変更'
     */
    public function test62AccessToMileRedemptionBasicSettingPageWhenLoggedIn()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.3] Test redirect to mile redemption page when click on the link '<< マイル償還トップへ戻る'
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Redemption Basic Setting Page (/management/mileage/redemption/basic-setting/create)
     * - Click on the link '<< マイル償還トップへ戻る' on screen
     *
     * Expectation:
     * - Redirect to Mile Redemption List Page (/management/mileage/redemption)
     * - See text: 'マイル償還'
     */
    public function test63RedirectToMileRedemptionListPageWhenClickOnLinkRedemption()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.4.1] Test no display the current basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Redemption Basic Setting Page (/management/mileage/redemption/basic-setting/create)
     * - Database empty
     *
     * Expectation:
     * - Do not see: '1マイル='
     */
    public function test641NoDisplayCurrentBasicSettingInfoWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.4.2] Test no display mile current basic setting information when returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile basic setting info
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see: '1マイル=' & '以降'
     */
    public function test642NoDisplayMileCurrentBasicSettingWhenReturnedDataEmpty()
    {
        $this->checkLogin();

        Mile::query()->delete();

        factory(Mile::class)->create([
            'plan_start_date' => '2018-01-01',
            'amount' => 100,
            'mile_type' => \Constant::MILE_ACCUMULATION,
        ]);

        $currentSetting = $this->mileRepo->getCurrentSetting(self::$mileType);

        $this->assertEquals(null, $currentSetting);
        $this->get(self::$url)
             ->assertViewHas('currentSetting', null)
             ->assertDontSee('<span>1マイル=')
             ->assertDontSee('以降');
    }

    /**
     * [TestCase-6.5] Test display mile current basic setting information when returned data not empty
     *
     * Condition:
     * - Authenticate a user
     * - Display mile basic setting info
     * - Returned data not empty
     *   + PlanStartDate: 2018-01-01
     *   + Amount: 100
     *   + mile_type: 1
     *
     * Expectation:
     * - Do not see: '1マイル=100円' & '(2018-01-01 以降)'
     */
    public function test65DisplayMileCurrentBasicSettingWhenReturnedDataNotEmpty()
    {
        $this->checkLogin();

        $this->createBasicSettings(10, '2018-01-01', 100);

        $currentSetting = $this->mileRepo->getCurrentSetting(self::$mileType);

        $this->assertGreaterThan(0, count($currentSetting));
        $this->get(self::$url)
             ->assertSee('1マイル='.$currentSetting->amount)
             ->assertSee('('.$currentSetting->plan_start_date.' 以降)');
    }

    /**
     * [TestCase-6.6.1] Test no display mile schedule basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile schedule basic setting info
     * - Database empty
     *
     * Expectation:
     * - Do not see: 'より' & link '削除'
     */
    public function test661NoDisplayMileScheduleBasicSettingWhenDatabaseEmpty()
    {
        $this->checkLogin();

        $this->get(self::$url)
             ->assertDontSee('より');
    }

    /**
     * [TestCase-6.6.2] Test no display mile schedule basic setting information when the returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile schedule basic setting info
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see: 'より' & '削除'
     */
    public function test662NoDisplayMileScheduleBasicSettingWhenReturnedDataEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.7] Test display mile schedule basic setting information when returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     * - Plan Start Date is '2019-01-01' & amount is 150
     * - Mile type is 1
     *
     * Expectation:
     * - Display mile schedule basic setting information:
     *   + See: '開始日'
     *   + See: '<input type="text" value="2019-01-01"' & '<input type="number" value="150"'
     */
    public function test67DisplayMileScheduleBasicSetting()
    {
        $this->checkLogin();

        $this->createBasicSettings(20, '2020-01-01', 150);

        $scheduleSetting = $this->mileRepo->getScheduleSetting(self::$mileType);
        
        $this->assertEquals(1, count($scheduleSetting));
        $this->get(self::$url)
             ->assertSee('開始日')
             ->assertSee($scheduleSetting->plan_start_date)
             ->assertSee((string)$scheduleSetting->amount);
    }

    /**
     * [TestCase-6.8] Test add new row for mile redemption basic setting when click on the link '変更予定を追加する'
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     *
     * Expectation:
     * - A new row inserted at bottom of the schedule settings list
     */
    public function test68AddNewRowWhenClickOnLinkAddRow()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.9] Test display the error message when entering plan start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Plan start date is '2018-13-01xxx' (wrong date)
     * - Amount is 100
     * - Click on Save button
     *
     * Expectation:
     * - See the error message: '開始日付の形式は、'Y-m-d'と合いません。'
     */
    public function test69DisplayErrorWhenEnteringDateWithWrongDateFormat()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.10] Test display the error message when entering amount with non-numeric
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Plan start date is '2018-07-01'
     * - Amount is '100xxx'
     * - Click on Save button
     *
     * Expectation:
     * - See the error message: '積算値には、数字を指定してください。'
     */
    public function test610DisplayErrorWhenEnteringAmountNonNumeric()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.11] Test delete the mile redemption schedule basic setting
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Basic setting ID is 1
     * - Click on the link '削除' for this ID
     * - Click on Save button
     *
     * Expectation:
     * - See column 'deleted_at' in table MileBasicSetting that its value is not null
     */
    public function test611DeleteScheduleBasicSetting()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.12.1] Test disable save button when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Database empty
     *
     * Expectation:
     * - See: Save button disabled with text '保存する' and attribute 'disabled="disabled"
     */
    public function test6121DisableSaveButtonWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.12.2] Test disable save button when the returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Returned data empty
     *
     * Expectation:
     * - See: Save button disabled with text '保存する' and attribute 'disabled="disabled"
     */
    public function test6122DisableSaveButtonWhenReturnDataEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.13] Test enable save button when the returned data not empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Returned data empty
     *
     * Expectation:
     * - See the Save button enabled with text '保存する', but no attribute 'disabled="disabled"
     */
    public function test613EnableSaveButtonWhenReturnDataNotEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.14] Test save completed when update and insert successfully
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 1
     * - Returned data empty
     * - Edit a existed row (for update) with the valid values
     * - Add 2 new rows (for insert) & enter the valid values
     * - Click on Save button
     *
     * Expectation:
     * - See 1 row will be affected & 2 new rows will be inserted in database
     * - See a popup with header text '保存完了'
     */
    public function test614SaveCompletedWhenUpdateAndInsertSuccessfully()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-6.15] Test save failed when server died
     *
     * Condition:
     * - Authenticate a user
     * - Connection failed
     * - Mile type is 1
     * - Some new rows inserted (2)
     * - Click on Save button
     *
     * Expectation:
     * - See the error message 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test615SaveFailedWhenServerDied()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
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
        
        $currDate = date('Y-m-d');
        if (!empty($defDate) && !empty($defAmount)) {
            factory(Mile::class)->create([
                'plan_start_date' => $defDate,
                'amount' => $defAmount,
                'mile_type' => \Constant::MILE_REDEMPTION,
                'created_at' => $currDate,
                'updated_at' => $currDate,
                'updated_user' => null,
                'created_user' => 'admin@test.com'
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
                    $m = 0;
                    $y++;
                    $m++;
                }
            }

            $d = $count < 10 && $count <= 30 ? '0'.$count : $count;
            $sDate = $y.'-'.($m < 10 ? '0'.$m : ($m > 12 ? 12 : $m)).'-'.$d;

            factory(Mile::class)->create([
                'plan_start_date' => $sDate,
                'amount' => 150 + ($i + 1),
                'mile_type' => $mileTypes[array_rand($mileTypes)],
                'created_at' => $currDate,
                'updated_at' => $currDate,
                'updated_user' => null,
                'created_user' => 'admin@test.com'
            ]);

            $count++;
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

    /**
     * check the error message whether it is in an error array
     *
     * @param string $msgError
     * @param array $errors
     * @return boolean
     */
    private function checkMsgError($msgError, $errors = [])
    {
        $isErrExpected = false;
        foreach ($errors as $err) {
            if (array_search($msgError, $err) !== false) {
                $isErrExpected = true;
                break;
            }
        }
        return $isErrExpected;
    }
}
