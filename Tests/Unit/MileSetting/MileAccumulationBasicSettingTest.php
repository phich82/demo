<?php

namespace Tests\Unit\MileSetting;

use Tests\TestCase;
use App\Models\Mile;
use Tests\StubAccount;
use Illuminate\Support\Facades\DB;
use App\Repositories\MileRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MileAccumulationBasicSettingTest extends TestCase
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
        self::$mileType = \Constant::MILE_ACCUMULATION;
        self::$url = route('admin.mile.basic');
        $this->mileRepo = new MileRepository();
        Mile::query()->delete();

        $this->startSession();
    }
    
    /**
     * [TestCase-2.1] Test redirect to login page when login failed
     *
     * Condition:
     * - Authenticate a user with the wrong information of login
     *
     * Expectation:
     * - Redirect to the login page (/management/login)
     */
    public function test21RedirectToLoginPageWhenLoginFailed()
    {
        $this->get(self::$url)
             ->assertRedirect(route('admin.login'));
    }

    /**
     * [TestCase-2.2] Test access to mile accumulation page when login successfully
     *
     * Condition:
     * - Authenticate a user
     * - Url of Mile Accumulation Basic Setting List Page (/management/mileage/accumulation/basic-setting)
     *
     * Expectation:
     * - See text: 'マイル積算 - 基本設定変更'
     */
    public function test22AccessToMileAccumulationBasicSettingPageWhenLoggedIn()
    {
        $this->login();

        $this->get(self::$url)
             ->assertSee('マイル積算 - 基本設定変更');
    }

    /**
     * [TestCase-2.3] Test redirect to mile accumulation page when click on the link '<< マイル積算トップへ戻る'
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation Basic Setting Page (/management/mileage/accumulation/basic-setting/create)
     * - Click on the link '<< マイル積算トップへ戻る' on screen
     *
     * Expectation:
     * - Redirect to Mile Accumulation List Page (/management/mileage/accumulation)
     * - See text: 'マイル積算'
     */
    public function test23RedirectToMileRedemptionListPageWhenClickOnLinkRedemption()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.4.1] Test no display the current basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Access to Mile Accumulation Basic Setting Page (/management/mileage/accumulation/basic-setting/create)
     * - Database empty
     *
     * Expectation:
     * - Do not see: '円=1マイル'
     */
    public function test241NoDisplayCurrentBasicSettingInfoWhenDatabaseEmpty()
    {
        $this->login();

        Mile::query()->delete();

        $this->get(self::$url)
             ->assertDontSee('円=1マイル');
    }

    /**
     * [TestCase-2.4.2] Test no display mile current basic setting information when returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile basic setting info
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see: '円=1マイル'
     */
    public function test242NoDisplayMileCurrentBasicSettingWhenReturnedDataEmpty()
    {
        $this->login();

        // create a mile current basic setting with type as redemption
        $this->createBasicSetting(['plan_start_date' => date('Y-m-d'), 'mile_type' => \Constant::MILE_REDEMPTION]);

        $this->get(self::$url)
             ->assertDontSee('円=1マイル');
    }

    /**
     * [TestCase-2.5] Test display mile current basic setting information when returned data not empty
     *
     * Condition:
     * - Authenticate a user
     * - Display mile basic setting info
     * - Returned data not empty
     *   + PlanStartDate: current date
     *   + Amount: 100
     *   + mile_type: 2
     *
     * Expectation:
     * - See: '100円=1マイル' & '({currentDate} 以降)'
     */
    public function test25DisplayMileCurrentBasicSettingWhenReturnedDataNotEmpty()
    {
        $this->login();

        $data = [
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) - 2*24*60*60), 'amount' => 150],
        ];
        $this->createBasicSettings($data);


        $this->get(self::$url)
             ->assertSee($data[0]['amount'].'円＝1マイル')
             ->assertSee('('.$data[0]['plan_start_date'].' 以降)');
    }

    /**
     * [TestCase-2.6.1] Test no display mile schedule basic setting information when database empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile schedule basic setting info
     * - Database empty
     *
     * Expectation:
     * - Do not see the mile schedule basic setting list & links '削除'
     */
    public function test261NoDisplayMileScheduleBasicSettingWhenDatabaseEmpty()
    {
        $this->login();

        $scheduleSettings = $this->mileRepo->getScheduleSettings(self::$mileType);

        $this->assertEquals(null, $scheduleSettings);
        $this->get(self::$url)
             ->assertViewHas('scheduledSetting', null);
    }

    /**
     * [TestCase-2.6.2] Test no display mile schedule basic setting information when the returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - No display mile schedule basic setting info
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - Do not see the mile schedule basic setting list & links '削除'
     */
    public function test262NoDisplayMileScheduleBasicSettingWhenReturnedDataEmpty()
    {
        $this->login();

        $this->createBasicSetting(['plan_start_date' => date('Y-m-d'), 'mile_type' => \Constant::MILE_ACCUMULATION]);
        
        $scheduleSettings = $this->mileRepo->getScheduleSettings(self::$mileType);

        $this->assertEquals(null, $scheduleSettings);
        $this->get(self::$url)
             ->assertViewHas('scheduledSetting', null);
    }

    /**
     * [TestCase-2.7] Test display mile schedule basic setting information when returned data not empty
     *
     * Condition:
     * - Authenticate a user
     * - Database not empty
     *
     * Expectation:
     * - See the mile schedule basic setting list & links '削除'
     */
    public function test27DisplayMileScheduleBasicSetting()
    {
        $this->login();

        // create 1 current setting & 2 schedule settings
        $data = [
            ['plan_start_date' => date('Y-m-d'), 'amount' => 100],
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + 3*24*60*60), 'amount' => 150],
            ['plan_start_date' => date('Y-m-d', strtotime(date('Y-m-d')) + 5*24*60*60), 'amount' => 200],
        ];
        $this->createBasicSettings($data);

        $this->get(self::$url)
             ->assertSee($data[1]['plan_start_date'])
             ->assertSee((string)$data[1]['amount'])
             ->assertSee($data[2]['plan_start_date'])
             ->assertSee((string)$data[2]['amount']);
    }

    /**
     * [TestCase-2.8] Test add new row for mile redemption basic setting when click on the link '変更予定を追加する'
     *
     * Condition:
     * - Authenticate a user
     * - Click on button Add New Row
     *
     * Expectation:
     * - A new row inserted at bottom of the schedule settings list
     */
    public function test28AddNewRowWhenClickOnLinkAddRow()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.9] Test display the error message when entering plan start date with the wrong date format
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Plan start date is '2018-13-01xxx' (wrong date)
     * - Amount is 100
     * - Click on Save button
     *
     * Expectation:
     * - See the error message: '開始日付の形式は、'Y-m-d'と合いません。'
     */
    public function test29DisplayErrorMessageWhenEnteringDateWithWrongFormat()
    {
        $this->login();

        $dateWrong = '2018-01-01xxx';
        $data = [
            '_token' => csrf_token(),
            'mile' => [
                [
                    'id'     => null,
                    'date'   => $dateWrong,
                    'amount' => 100,
                    'status' => 3, // 0: old value, 1: update, 2 delete, 3 new
                ]
            ]
        ];

        $this->save($data)
             ->assertSessionHasErrors(['mile.0.date' => "開始日付の形式は、'Y-m-d'と合いません。"]);
    }

    /**
     * [TestCase-2.10] Test display the error message when entering amount with non-numeric
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Plan start date is '2018-07-01'
     * - Amount is '100xxx'
     * - Click on Save button
     *
     * Expectation:
     * - See the error message: '積算値には、数字を指定してください。'
     */
    public function test210DisplayErrorMessageWhenEnteringAmountNonNumeric()
    {
        $this->login();

        $amountWrong = '100xxx';
        $data = [
            '_token' => csrf_token(),
            'mile' => [
                [
                    'id'     => null,
                    'date'   => date('Y-m-d', strtotime(date('Y-m-d')) + 5*24*60*60),
                    'amount' => $amountWrong,
                    'status' => 3, // 0: old value, 1: update, 2 delete, 3 new
                ]
            ]
        ];

        $this->save($data)
            ->assertSessionHasErrors(['mile.0.amount' => '積算値には、数字を指定してください。']);
    }

    /**
     * [TestCase-2.11] Test delete the mile redemption schedule basic setting
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Basic setting ID is 1
     * - Click on the link '削除' for this ID
     * - Click on Save button
     *
     * Expectation:
     * - See column 'deleted_at' in table MileBasicSetting that its value is not null
     */
    public function test211DeleteScheduleBasicSetting()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.12.1] Test disable save button when database empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Database empty
     *
     * Expectation:
     * - See: Save button disabled with text '保存する' and attribute 'disabled="disabled"
     */
    public function test2121DisableSaveButtonWhenDatabaseEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.12.2] Test disable save button when the returned data empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Returned data empty
     *
     * Expectation:
     * - See: Save button disabled with text '保存する' and attribute 'disabled="disabled"
     */
    public function test2122DisableSaveButtonWhenReturnDataEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.13] Test enable save button when the returned data not empty
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Returned data empty
     *
     * Expectation:
     * - See the Save button enabled with text '保存する', but no attribute 'disabled="disabled"
     */
    public function test213EnableSaveButtonWhenReturnDataNotEmpty()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.14] Test save completed when update and insert successfully
     *
     * Condition:
     * - Authenticate a user
     * - Mile type is 2
     * - Returned data empty
     * - Edit a existed row (for update) with the valid values
     * - Add 2 new rows (for insert) & enter the valid values
     * - Click on Save button
     *
     * Expectation:
     * - See 1 row will be affected & 2 new rows will be inserted in database
     * - See a popup with header text '保存完了'
     */
    public function test214SaveCompletedWhenUpdateAndInsertSuccessfully()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.15] Test save failed when server died
     *
     * Condition:
     * - Authenticate a user
     * - Connection failed
     * - Mile type is 2
     * - Some new rows inserted (2)
     * - Click on Save button
     *
     * Expectation:
     * - See the error message 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test215SaveFailedWhenServerDied()
    {
        $this->markTestIncomplete('Mark skip test to find solution');
    }

    /**
     * @param $data
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function save($data)
    {
        return $this->post(route('admin.mile.store'), $params);
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
     * login
     *
     * @param string $role
     * @return void
     */
    private function login($role = 'operator')
    {
        $this->actingAs($role == 'admin' ? $this->createAdmin() : $this->createOperator());
    }
}
