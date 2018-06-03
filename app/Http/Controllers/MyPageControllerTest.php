<?php

namespace Tests\Unit\MyPage;

use Mockery;
use Tests\TestCase;
use Tests\StubSSOData;
use App\Api\BookingApi;
use App\Models\Booking;
use App\Models\BookingLog;
use Illuminate\Support\Facades\DB;
use App\Repositories\BookingRepository;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Screen: Traveler_MyPage_(MyBooking)_ListScreen
 * @author Phat Huynh Nguyen <huynh.phat@mulodo.com>
 */
class MyPageControllerTest extends TestCase
{
    use DatabaseTransactions;
    use StubSSOData;

    /**
     * @var $url
     */
    private static $url;

    /**
     * @var bookingRepository
     */
    private $bookingRepo;

    private $api;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->bookingRepo = new BookingRepository();
        self::$url = route('traveler.mypage.index');
        self::fakeSessionSSO();
    }

    /**
     * clean up the testing environment before the next test
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
     * [TestCase-1.1] Test redirect to login without club ana page when session no exists
     *
     * Condition:
     * - Session login amc no exists
     *
     * Expectation:
     * - Redirect to the login without club ana page (/mypage/login-no-ana)
     * - See a header text 'ANAマイレージクラブでないお客様の予約内容確認・変更・キャンセル'
     */
    public function test11RedirectToLoginWithoutClubAnaScreenWhenSessionNoExists()
    {
        self::clearSession();

        $this->get(self::$url)
             ->assertRedirect(route('traveler.mypage.login-no-ana'));
    }

    /**
     * [TestCase-1.2] Test redirect to MyPage page when session exists
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     *
     * Expectation:
     * - See a header text 'マイページ' on screen
     * - See some session info (first name, last name, balance)
     */
    public function test12AccessToMyPageScreenWhenSessionExists()
    {
        $ssoData = $this->getSSOData();

        $this->get(self::$url)
             ->assertSee('マイページ')
             ->assertSee($ssoData['first_name'])
             ->assertSee($ssoData['last_name'])
             ->assertSee($ssoData['balance']);
    }

    /**
     * [TestCase-1.3.1] Test no display the current booking list when database empty
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database empty
     *
     * Expectation:
     * - See a header text '現在の予約' on screen
     * - Do not see the current booking list
     */
    public function test131NoDisplayCurrentBookingListWhenDatabaseEmpty()
    {
        $ssoData = $this->getSSOData();

        $this->mockApi(['booking_id' => 'VELTRA-3N0RHQ74', 'activity_id' => 'VELTRA-100010613']);

        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'current'
        ];

        $currentBookings = $this->bookingRepo->getCurrentBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $currentBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, '現在の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(0, $totalRecords);
        $this->assertEquals(0, $totalRowsInList);
    }

    /**
     * [TestCase-1.3.2] Test no display the current booking list when the returned data empty
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - See a header text '現在の予約' on screen
     * - Do not see the current booking list
     */
    public function test132NoDisplayCurrentBookingListWhenReturnDataEmpty()
    {
        $ssoData = $this->getSSOData();

        // past date
        $date = date('Y-m-d', strtotime(date('Y-m-d')) - 2*24*60*60);
        $data = [
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'activity_id' => 'VELTRA-100010613',
            'plan_id'     => 'VELTRA-108951-0'
        ];

        $this->createBookingByDateAndParams($date, $data);
        $this->mockApi($data);
        
        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'current'
        ];
        $currentBookings = $this->bookingRepo->getCurrentBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $currentBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, '現在の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(0, $totalRecords);
        $this->assertEquals(0, $totalRowsInList);
    }

    /**
     * [TestCase-1.4] Test display the current booking list
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - See a header text '現在の予約' on screen
     * - See the current booking list
     */
    public function test14DisplayCurrentBookingList()
    {
        $ssoData = $this->getSSOData();

        $data = [
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'activity_id' => 'VELTRA-100010613',
            'plan_id'     => 'VELTRA-108951-0'
        ];

        $this->createBookingByDateAndParams(date('Y-m-d'), $data);
        $this->mockApi($data);
        
        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'current'
        ];
        $currentBookings = $this->bookingRepo->getCurrentBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $currentBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, '現在の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(1, $totalRecords);
        $this->assertEquals(1, $totalRowsInList);
    }

    /**
     * [TestCase-1.5.1] Test no display the past booking list when database empty
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database empty
     *
     * Expectation:
     * - See a header text 'キャンセル済・過去の予約' on screen
     * - Do not see the past booking list
     */
    public function test151NoDisplayPastBookingListWhenDatabaseEmpty()
    {
        $ssoData = $this->getSSOData();

        $this->mockApi(['booking_id' => 'VELTRA-3N0RHQ74', 'activity_id' => 'VELTRA-100010613']);

        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'past'
        ];
        $pastBookings = $this->bookingRepo->getPastBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $pastBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('past_list', $response);

        $list = $response['past_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, 'キャンセル済・過去の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(0, $totalRecords);
        $this->assertEquals(0, $totalRowsInList);
    }

    /**
     * [TestCase-1.5.2] Test no display the past booking list when the returned data empty
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - See a header text 'キャンセル済・過去の予約' on screen
     * - Do not see the past booking list
     */
    public function test152NoDisplayPastBookingListWhenReturnDataEmpty()
    {
        $ssoData = $this->getSSOData();

        // current date
        $date = date('Y-m-d');
        $data = [
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'activity_id' => 'VELTRA-100010613',
            'plan_id'     => 'VELTRA-108775-0'
        ];

        $this->createBookingByDateAndParams($date, $data);
        $this->mockApi($data);
        
        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'past'
        ];
        $currentBookings = $this->bookingRepo->getPastBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $currentBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('past_list', $response);

        $list = $response['past_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, 'キャンセル済・過去の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(0, $totalRecords);
        $this->assertEquals(0, $totalRowsInList);
    }

    /**
     * [TestCase-1.6] Test display the past booking list
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data empty
     *
     * Expectation:
     * - See a header text 'キャンセル済・過去の予約' on screen
     * - See the past booking list
     */
    public function test16DisplayPastBookingList()
    {
        $ssoData = $this->getSSOData();

        $data = [
            [
                'booking_id'  => 'VELTRA-5YJOYFNT',
                'activity_id' => 'VELTRA-100010613',
                'plan_id'     => 'VELTRA-108951-0'
            ],
            [
                'booking_id'  => 'VELTRA-3N0RHQ74',
                'activity_id' => 'VELTRA-100010613',
                'plan_id'     => 'VELTRA-108951-0'
            ]
        ];
        // past date
        $date = date('Y-m-d', strtotime(date('Y-m-d')) - 2*24*60*60);

        $this->createBookingByDateAndParams(date('Y-m-d'), $data[0]);
        $this->createBookingByDateAndParams($date, $data[1]);

        $this->mockApi($data[1]);
        
        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'past'
        ];
        $pastBookings = $this->bookingRepo->getPastBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $totalRecords = $pastBookings->total();
        
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('past_list', $response);

        $list = $response['past_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $textHeaderCount  = substr_count($list, 'キャンセル済・過去の予約');

        $this->assertEquals(1, $textHeaderCount);
        $this->assertEquals(1, $totalRecords);
        $this->assertEquals(1, $totalRowsInList);
    }

    /**
     * [TestCase-1.7] Test display the link 'Logout'
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     *
     * Expectation:
     * - See a header text 'マイページ' on screen
     * - See the logout link with text 'ログアウト'
     */
    public function test17DisplayLinkLogout()
    {
        $ssoData = $this->getSSOData();

        $data = [
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'activity_id' => 'VELTRA-100010613',
            'plan_id'     => 'VELTRA-108951-0',
        ];
        
        $this->createBookingByDateAndParams(date('Y-m-d'), $data);

        $this->mockApi($data);
        
        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];
        $currentBookings = $this->bookingRepo->getCurrentBookingsByAmcAndEmail($ssoData['amc_number'], $ssoData['email'], $params);
        $link = '<a href="'.route('traveler.logout').'"';
        
        $this->get($this->getUrlByParams($params))
                        ->assertSee('マイページ')
                        ->assertSee($link)
                        ->assertSee('ログアウト');
    }

    /**
     * [TestCase-1.8] Test enable the link 'Voucher Download'
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Voucher url exists in the returned data
     *
     * Expectation:
     * - See the link 'Voucher Download' enabled
     */
    public function test18EnableLinkVoucherDownload()
    {
        $ssoData = $this->getSSOData();

        $data = [
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'activity_id' => 'VELTRA-100010613',
            'plan_id'     => 'VELTRA-108951-0',
        ];
        
        $this->createBookingByDateAndParams(date('Y-m-d'), $data);

        //$this->mockApi($data);

        $voucherUrlMockApi = "https://storage.googleapis.com/dev-voucher.vds-connect.com/vouchers/1598181/aa17921456fcaf3c.pdf";
        $link = '<a href="'.$voucherUrlMockApi.'"';

        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'current'
        ];

        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalVoucherUrl = substr_count($list, $link);

        $this->assertEquals(1, $totalVoucherUrl);
    }

    /**
     * [TestCase-1.9] Test disable the link 'Voucher Download'
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Voucher url empty in the returned data
     *
     * Expectation:
     * - See the link 'Voucher Download' disabled
     */
    public function test19DisableLinkVoucherDownload()
    {
        $ssoData = $this->getSSOData();

        $data = [
            "booking_id"  => "VELTRA-43HS4NG7",
            "activity_id" => "VELTRA-100010679",
            "plan_id"     => "VELTRA-108951-0",
        ];
        
        $this->createBookingByDateAndParams(date('Y-m-d'), $data);

        $this->mockApi($data);

        $voucherUrlMockApi = "#";
        $link = '<a href="'.$voucherUrlMockApi.'"';

        $params = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'current'
        ];

        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalVoucherUrl    = substr_count($list, $link);
        $totalClassDisabled = substr_count($list, 'disabled');

        $this->assertEquals(1, $totalVoucherUrl);
        $this->assertEquals(1, $totalClassDisabled);
    }

    /**
     * [TestCase-1.10] Test download voucher
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Voucher url not empty in the returned data
     *
     * Expectation:
     * - See the voucher pdf file will be downloaded
     */
    public function test110DownloadVoucher()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.11] Test redirect to the search index page when logout
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Link logout
     *
     * Expectation:
     * - Redirect to the search index page
     * - See the header text 'オプショナルツアーを探す'
     */
    public function test11RedirectToSearchPageWhenLogout()
    {
        $ssoData = $this->getSSOData();

        $this->get(route('traveler.logout'))
             ->assertRedirect(route('traveler.index'));
        $this->get(route('traveler.index'))
             ->assertSee('オプショナルツアーを探す');
    }

    /**
     * [TestCase-1.12] Test redirect to the booking details page when click on button 'show booking details'
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Click on button '予約詳細を見る'
     *
     * Expectation:
     * - See the header text '予約詳細'
     * - See the booking details information on screen
     */
    public function test12RedirectToBookingDetailsPageWhenClickOnButtonShowBookingDetails()
    {
        $ssoData = $this->getSSOData();

        $data = [
            "booking_id"  => "VELTRA-43HS4NG7",
            "activity_id" => "VELTRA-100010679",
            "plan_id"     => "VELTRA-108951-0",
        ];
        
        $booking = $this->createBookingByDateAndParams(date('Y-m-d'), $data, true);
        $this->createBookingLog($data['booking_id']);

        //$this->mockApi($data, true);

        $linkDetails = route('traveler.mypage.booking.details', [$booking->booking_id]);

        $this->get($linkDetails)->assertSee('予約詳細')
             ->assertSee($booking->booking_id)
             ->assertSee($booking->email);
    }

    /**
     * [TestCase-1.13] Test redirect to the booking details page when click on link 'activity title/plan title'
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Click on link 'activity title/plan title' at '商品名 / プラン名'
     *
     * Expectation:
     * - See the header text '予約詳細'
     * - See the booking details information on screen
     */
    public function test13RedirectToBookingDetailsPageWhenClickOnLinkActivityTitle()
    {
        $ssoData = $this->getSSOData();

        $data = [
            "booking_id"  => "VELTRA-43HS4NG7",
            "activity_id" => "VELTRA-100010679",
            "plan_id"     => "VELTRA-108951-0",
        ];
        
        $booking = $this->createBookingByDateAndParams(date('Y-m-d'), $data, true);
        $this->createBookingLog($data['booking_id']);

        //$this->mockApi($data, true);

        $linkDetails = route('traveler.mypage.booking.details', [$booking->booking_id]);

        $this->get($linkDetails)->assertSee('予約詳細')
             ->assertSee($booking->booking_id)
             ->assertSee($booking->email);
    }

    /**
     * [TestCase-1.14] Test display the booking status text by status type
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Booking status is COMFIRMED
     *
     * Expectation:
     * - See the header text '予約詳細'
     * - See the text ''
    */
    public function test114DisplayBookingStatusTextByStatusType()
    {
        $this->markTestSkipped('Mark skip test to find solution');
        // $ssoData = $this->getSSOData();

        // $statuses = [
        //     \Constant::CANCELED_BY_SUPPLIER               => 'キャンセル済',
        //     \Constant::CANCELED_BY_TRAVELER               => 'キャンセル済',
        //     \Constant::CANCEL_AWAITING_MANUAL_CALCULATION => 'キャンセル済',
        //     \Constant::CANCEL_AWAITING_CS_CONFIRMATION    => 'キャンセル済',
        //     \Constant::WITHDRAWN_BY_TRAVELER              => 'キャンセル済',
        //     \Constant::REQUESTED                          => 'リクエスト中',
        //     \Constant::STANDBY                            => 'リクエスト中',
        //     \Constant::DECLINED                           => 'リクエストNG',
        //     \Constant::CONFIRMED                          => '確定済',
        //     \Constant::CHANGED_BY_SUPPLIER                => '確定済 + チェックイン、ピックアップ情報が確定しました。'
        // ];

        // $keysStatus    = array_keys($statuses);
        // $statusRandom1 = array_rand($keysStatus);
        // $statusRandom2 = array_rand($keysStatus);

        // $data = [
        //     [
        //         "booking_id"  => "VELTRA-43HS4NG7",
        //         "activity_id" => "VELTRA-100010679",
        //         "plan_id"     => "VELTRA-108951-0",
        //         'status'      => $statusRandom1
        //     ],
        //     [
        //         'booking_id'   => 'VELTRA-5YJOYFNT',
        //         'activity_id'  => 'VELTRA-100010679',
        //         'plan_id'      => 'VELTRA-108951-0',
        //         'status'       => $statusRandom2
        //     ]
        // ];
        
        // $currentBooking = $this->createBookingByDateAndParams(date('Y-m-d'), $data[0], true);
        // $pastBooking    = $this->createBookingByDateAndParams(date('Y-m-d', strtotime(date('Y-m-d')) - 2*24*60*60), $data[1], true);

        // $this->mockApi($data);

        // $params = [
        //     'page'     => 1,
        //     'per_page' => 10,
        // ];
        // $response = $this->ajax($this->getUrlByParams($params))->json();

        // $this->assertTrue(is_array($response));
        // $this->assertArrayHasKey('current_list', $response);
        // $this->assertArrayHasKey('past_list', $response);

        // $currentList = $response['current_list'];
        // $totalRowsInCurrentList  = substr_count($currentList, '<div class="contract_item"');
        // $statusTextCurrentCount  = substr_count($currentList, $statuses[$statusRandom1]);
        // $bookingIdCurrentCount   = substr_count($currentList, $currentBooking->booking_id);

        // $this->assertGreaterThanOrEqual(1, $totalRowsInCurrentList);
        // $this->assertGreaterThanOrEqual(1, $statusTextCurrentCount);
        // $this->assertGreaterThanOrEqual(1, $bookingIdCurrentCount);

        // $pastList = $response['past_list'];
        // $totalRowsInPastList  = substr_count($pastList, '<div class="contract_item"');
        // $statusTextPastCount  = substr_count($pastList, $statuses[$statusRandom2]);
        // $bookingIdPastCount   = substr_count($pastList, $pastBooking->booking_id);

        // $this->assertGreaterThanOrEqual(1, $totalRowsInPastList);
        // $this->assertGreaterThanOrEqual(1, $statusTextPastCount);
        // $this->assertGreaterThanOrEqual(1, $bookingIdPastCount);
    }

    /**
     * [TestCase-1.15] Test display the message for notifying the missing booking information
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Responses from api (get-booking-details) is empty
     * - Type from api (get-activity-details) is 'REQUIRED_BY_ACTIVITY_DATE'
     *
     * Expectation:
     * - See the text '予約必須情報に未記入の項目があります。情報の登録は こちら'
     * - See the link 'こちら' in text above
     */
    public function test115DisplayMessageForNotifyingMissingBookingInfo()
    {
        $ssoData = $this->getSSOData();

        $data = [
            "booking_id"  => "VELTRA-43HS4NG7",
            "activity_id" => "VELTRA-100010679",
            "plan_id"     => "VELTRA-108951-0"
        ];
        $messageTarget = '予約必須情報に未記入の項目があります。情報の登録は';
        $this->createBookingByDateAndParams(date('Y-m-d'), $data);

        //$this->mockApi($data, 'empty_ok');

        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');
        $messageCount     = substr_count($list, $messageTarget);
        $linkCount        = substr_count($list, 'こちら');

        $this->assertGreaterThanOrEqual(1, $totalRowsInList);
        $this->assertGreaterThanOrEqual(1, $messageCount);
        $this->assertGreaterThanOrEqual(1, $linkCount);
    }

    /**
     * [TestCase-1.16] Test display the message for notifying the confirmed booking information
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Responses from api (get-booking-details) is not empty
     * - Type from api (get-activity-details) is 'REQUIRED_BY_ACTIVITY_DATE'
     *
     * Expectation:
     * - See the text 'チェックイン、ピックアップ情報が確定しました。'
     */
    public function test116DisplayMessageForNotifyingConfirmedBookingInfo()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-1.17] Test no display the pagination links of the booking when total of the returned data less than 10
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Total of records less than 10
     *
     * Expectation:
     * - Do not see the pagination links of the bookings
     */
    public function test117NoDisplayPaginationLinksWhenTotalRecordsLessThan10()
    {
        $ssoData = $this->getSSOData();

        // current bookings
        $this->createBookings(9, date('Y-m-d'), 9);

        //$this->mockApi($data, 'empty_ok');

        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<div class="contract_item"');

        $this->assertLessThan(10, $totalRowsInList);
    }

    /**
     * [TestCase-1.18] Test display the pagination links of the booking when total of the returned data higher than 10
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Total of records less than 10
     *
     * Expectation:
     * - Do not see the pagination links of the bookings
     */
    public function test118DisplayPaginationLinksWhenTotalRecordsHigherThan10()
    {
        $ssoData = $this->getSSOData();

        // current bookings
        $this->createBookings(11, date('Y-m-d'), 11);

        //$this->mockApi($data, 'empty_ok');

        $params = [
            'page'     => 1,
            'per_page' => 10,
        ];
        $response = $this->ajax($this->getUrlByParams($params))->json();

        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('current_list', $response);

        $list = $response['current_list'];
        $totalRowsInList  = substr_count($list, '<ul class="pagination"');

        $this->assertEquals(1, $totalRowsInList);
    }

    /**
     * [TestCase-1.19] Test display the booking list after click on each link of pagination
     *
     * Condition:
     * - Session login amc existed
     * - Url: '/mypage'
     * - Database not empty
     * - Returned data not empty
     * - Total of records less than 10
     * - Click on each link of pagination
     *
     * Expectation:
     * - See the previous booking list not same the booking list after clicked on the link of pagination
     */
    public function test119DisplayBookingListAfterClickOnEachLinkOfPagination()
    {
        $ssoData = $this->getSSOData();

        // current bookings
        $this->createBookings(20, date('Y-m-d'), 15);

        //$this->mockApi($data, 'empty_ok');

        $responseBefore = $this->ajax($this->getUrlByParams(['page' => 1, 'per_page' => 10]))->json();
        $responseAfter  = $this->ajax($this->getUrlByParams(['page' => 2, 'per_page' => 10]))->json();

        $listBefore = $responseBefore['current_list'];
        $listAfter  = $responseAfter['current_list'];

        $this->assertFalse($listBefore == $listAfter);
    }

    /**
     * [TestCase-1.20] Test display error message when the server failed
     *
     * Condition:
     * - Authenticate a user
     * - Access to MyPage screen (/mypage)
     * - Connection failed
     *
     * Expectation:
     * - See the error message: 'システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。'
     */
    public function test120DisplayErrorWhenServerFailed()
    {
        $this->markTestSkipped('Mark skip test to find solution');
        // $ssoData = $this->getSSOData();

        // DB::disconnect();

        // $this->get(self::$url)
        //      ->assertStatus(500)
        //      ->assertSee('システムエラーが発生しました。ご迷惑をおかけし申し訳ございません。しばらく時間をおいてからもう一度アクセスして下さい。');
    }

    /**
     * create bookings for test
     *
     * @param array $params
     */
    private function getUrlByParams($params = [])
    {
        return is_array($params) && !empty($params) ? route('traveler.mypage.index', $params) : self::$url;
    }

    /**
     * create bookings for test
     *
     * @param integer $totalRecords
     * @param string  $bookingDate
     * @param integer $limit
     * @return void
     */
    private function createBookings($totalRecords = 1, $bookingDate = null, $limit = 1)
    {
        Booking::query()->delete();

        $totalRecords = is_int($totalRecords) && $totalRecords > 1 ? $totalRecords : 1;
        $limit = is_int($limit) && $limit > 1 ? $limit : 1;
        $data = [
            [
                'booking_id'            => 'VELTRA-5YJOYFNT',
                'activity_id'           => 'VELTRA-100010679',
                'plan_id'               => 'VELTRA-108951-0',
            ],
            [
                'booking_id'            => 'VELTRA-3N0RHQ74',
                'activity_id'           => 'VELTRA-100010613',
                'plan_id'               => 'VELTRA-108775-0',
            ],
            [
                'booking_id'            => 'VELTRA-43HS4NG7',
                'activity_id'           => 'VELTRA-100010679',
                'plan_id'               => 'VELTRA-108951-0',
            ],
            [
                'booking_id'            => 'VELTRA-J3PYAXCH',
                'activity_id'           => 'VELTRA-100010655',
                'plan_id'               => 'VELTRA-108896-0',
            ]
        ];
        
        for ($i = 0; $i < $totalRecords; $i++) {
            $randomData = $i < 4 ? $data[$i] : [
                'booking_id'            => 'VELTRA-'.$i,
                'activity_id'           => 'VELTRA-0000'.$i,
                'plan_id'               => 'VELTRA-1111'.$i,
            ];

            if (empty($bookingDate) || (!empty($bookingDate) && $i >= $limit)) {
                $bookingDate = date('Y-m-d', strtotime(date('Y-m-d')) - ($i+1)*24*60*60);
            }

            factory(Booking::class)->create([
                'booking_id'            => $randomData['booking_id'],
                'activity_id'           => $randomData['activity_id'],
                'plan_id'               => $randomData['plan_id'],
                'guest_flag'            => 1,
                'first_name'            => 'Steven',
                'last_name'             => 'Nguyen',
                'email'                 => 'fake@gmail.com',
                'contact_mail'          => 'admin@test.com',
                'amc_number'            => 'AMC75',
                'booking_date'          => $bookingDate,
                'participation_date'    => '2007-01-09',
                'participant_persons'   => rand(1, 100),
                'sales_price'           => '400',
                'booking_unit_price'    => '4',
                'sales_price_mile'        => '4',
                'booking_unit_price_mile' => '4',
                'refund_mile'           => '0',
                'mile_type'             => rand(0, 2),
                'accumulate_flag'       => rand(0, 1),
                'status'                => 'CONFIRMED',
                'create_user'           => 'admin',
                'update_user'           => 'admin'
            ]);
        }
    }

    /**
     * create the booking by booking date and params for test
     *
     * @param  string  $bookingDate
     * @param  array   $params
     * @param  boolean $returned
     * @return void|object
     */
    private function createBookingByDateAndParams($bookingDate, $params = [], $returned = false)
    {
        $params = is_array($params) && !empty($params) ? $params : [
            'booking_id'   => 'VELTRA-5YJOYFNT',
            'activity_id'  => 'VELTRA-100010679',
            'plan_id'      => 'VELTRA-108951-0',
        ];

        Booking::query()->delete();

        $booking = factory(Booking::class)->create([
            'booking_id'            => $params['booking_id'],
            'activity_id'           => $params['activity_id'] ?? 'VELTRA-100010679',
            'plan_id'               => $params['plan_id']     ?? 'VELTRA-108951-0',
            'guest_flag'            => 1,
            'first_name'            => $params['first_name'] ?? 'Steven',
            'last_name'             => $params['last_name']  ?? 'Nguyen',
            'email'                 => $params['email']      ?? 'fake@gmail.com',
            'contact_mail'          => 'admin@test.com',
            'amc_number'            => $params['amc_number'] ?? 'AMC75',
            'booking_date'          => $bookingDate,
            'participation_date'    => '2007-01-09',
            'participant_persons'   => rand(1, 100),
            'sales_price'           => '400',
            'booking_unit_price'    => '4',
            'sales_price_mile'        => '4',
            'booking_unit_price_mile' => '4',
            'refund_mile'           => '0',
            'mile_type'             => rand(0, 2),
            'accumulate_flag'       => rand(0, 1),
            'status'                => $params['status'] ?? 'CONFIRMED',
            'create_user'           => 'admin',
            'update_user'           => 'admin'
        ]);

        if ($returned === true) {
            return $booking;
        }
    }

    /**
     * create the booking log for test
     *
     * @param  string  $bookingID
     * @param  boolean $returned
     * @return void|object
     */
    private function createBookingLog($bookingID, $returned = false)
    {
        BookingLog::query()->delete();

        $bookingLog = factory(BookingLog::class)->create([
            'booking_id'  => $bookingID,
        ]);

        if ($returned === true) {
            return $bookingLog;
        }
    }

    /**
     * mock restful api
     *
     * @param array $params
     * @return void
     */
    private function mockApi($params = [], $flag = false)
    {
        $resBooking1 = (object)[
            "common"                          => (object)["status_code" => 200],
            "booking_id"                      => "VELTRA-3N0RHQ74",
            "booking_status"                  => "CANCELED_BY_TRAVELER",
            "activity_id"                     => "VELTRA-100010613",
            "activity_title"                  => "scenario2-single package voucher-JP",
            "plan_id"                         => "VELTRA-108775-0",
            "voucher_url"                     => "https://storage.googleapis.com/dev-voucher.vds-connect.com/vouchers/1598181/aa17921456fcaf3c.pdf",
            "per_participants_booking_fields" => [
                (object)[
                    "unit_id" => "147000",
                    "responses" => [
                        (object)[
                            "booking_fields_id" => "31396",
                            "response" => [
                                "Le Dinh Huy 1"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31396",
                            "response" => [
                                "Le Dinh Huy 2"
                            ]
                        ]
                    ]
                ],
                (object)[
                    "unit_id" => "147002",
                    "responses" => [
                        (object)[
                            "booking_fields_id" => "31396",
                            "response" => [
                                "Le Dinh Huy 3"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31396",
                            "response" => [
                                "Le Dinh Huy 4"
                            ]
                        ]
                    ]
                ]
            ],
        ];
        $resBooking2 = (object)[
            "common" => (object)["status_code" => 200],
            "booking_id" => "VELTRA-43HS4NG7",
            "booking_status" => "WITHDRAWN_BY_TRAVELER",
            "activity_id" => "VELTRA-100010679",
            "activity_title" => "scenario22-single package voucher-JP",
            "plan_id" => "VELTRA-108951-0",
            "per_booking_fields" => [
                (object)[
                    "booking_fields_id" => "31566",
                    "response" => [
                        "sea"
                    ]
                ]
            ],
        ];
        $resBooking3 = (object)[
            "common"             => (object)["status_code" => 200],
            "booking_id"         => "VELTRA-5YJOYFNT",
            "booking_status"     => "REQUESTED",
            "activity_id"        => "VELTRA-100010679",
            "activity_title"     => "scenario22-single package voucher-JP",
            "plan_id"            => "VELTRA-108951-0",
            "per_booking_fields" => [
                (object)[
                    "booking_fields_id" => "31566",
                    "response" => [
                        "sea"
                    ]
                ]
            ]
        ];
        $resBooking4 = (object)[
            "common"                          => (object)['status_code' => 200],
            "booking_id"                      => "VELTRA-J3PYAXCH",
            "booking_status"                  => "CONFIRMED",
            "activity_id"                     => "VELTRA-100010655",
            "activity_title"                  => "scenario9-multiple package voucher-EN",
            "plan_id"                         => "VELTRA-108896-0",
            "voucher_url"                     => "https://storage.googleapis.com/dev-voucher.vds-connect.com/vouchers/1597797/15a4de833ca5c0d6.pdf",
            "per_participants_booking_fields" => [
                (object)[
                    "unit_id" => "146583",
                    "responses" => [
                        (object)[
                            "booking_fields_id" => "31478",
                            "response" => [
                                "string1"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31478",
                            "response" => [
                                "string2"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31478",
                            "response" => [
                                "string3"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31478",
                            "response" => [
                                "string4"
                            ]
                        ],
                        (object)[
                            "booking_fields_id" => "31478",
                            "response" => [
                                "string5"
                            ]
                        ]
                    ]
                ]
            ],
        ];
        $booking_ids = [
            'VELTRA-3N0RHQ74' => $resBooking1,
            'VELTRA-43HS4NG7' => $resBooking2,
            'VELTRA-5YJOYFNT' => $resBooking3,
            'VELTRA-J3PYAXCH' => $resBooking4,
        ];

        $resActivity1 = (object)[
            "common"         => (object)["status_code" => 200],
            "booking_fields" => [
                (object)[
                    "id"       => "31396",
                    "method"   => "PER_PARTICIPANT",
                    "type"     => "REQUIRED_ON_BOOKING",
                    "format"   => "TEXT",
                    "title"    => "参加者氏名（パスポート表記と同じローマ字でご入力ください　※半角英数）",
                    "plan_ids" => [
                        "VELTRA-108775-0"
                    ]
                ],
                (object)[
                    "id"      => "31398",
                    "method"  => "PER_PARTICIPANT",
                    "type"    => "OPTIONAL",
                    "format"  => "SELECT_ONE",
                    "title"   => "性別",
                    "choices" => [
                        "男性",
                        "女性"
                    ],
                    "plan_ids" => [
                        "VELTRA-108775-0"
                    ]
                ],
                (object)[
                    "id"       => "31591",
                    "method"   => "PER_BOOKING",
                    "type"     => "REQUIRED_BY_ACTIVITY_DATE",
                    "format"   => "TEXT",
                    "title"    => "ご希望の観光スポット",
                    "plan_ids" => [
                        "VELTRA-108775-0"
                    ]
                ]
            ]
        ];
        $resActivity2 = (object)[
            "common"         => (object)["status_code" => 200],
            "booking_fields" => [
                (object)[
                    "id"       => "31564",
                    "method"   => "PER_PARTICIPANT",
                    "type"     => "REQUIRED_BY_ACTIVITY_DATE",
                    "format"   => "YES_OR_NO",
                    "title"    => "ベジタリアンフード希望",
                    "choices"  => [
                        "あり",
                        "なし"
                    ],
                    "plan_ids" => [
                        "VELTRA-108951-0"
                    ]
                ],
                (object)[
                    "id"       => "31565",
                    "method"   => "PER_PARTICIPANT",
                    "type"     => "OPTIONAL",
                    "format"   => "TEXT",
                    "title"    => "食物アレルギー",
                    "plan_ids" => [
                        "VELTRA-108951-0"
                    ]
                ],
                (object)[
                    "id"       => "31566",
                    "method"   => "PER_BOOKING",
                    "type"     => "REQUIRED_ON_BOOKING",
                    "format"   => "TEXT",
                    "title"    => "ご希望の観光スポット",
                    "plan_ids" => [
                        "VELTRA-108951-0"
                    ]
                ]
            ],
            "plans" => [
                (object)[
                    "price_information_items" => [
                        (object)[
                            "unit_items" => [
                                (object)[
                                    "id" => "146763",
                                    "name" => "大人子供共通 (5歳以上)",
                                    "original_amount" => 1000,
                                    "display_amount" => 1000,
                                    "criteria" => "STANDALONE_AND_COUNTABLE"
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ];
        $resActivity4 = (object)[
            "common" => (object)['status_code' => 404],
        ];
        $activity_ids = [
            'VELTRA-100010613' => $resActivity1,
            'VELTRA-100010679' => $resActivity2,
            'VELTRA-100010679' => $resActivity2,
            'VELTRA-100010655' => $resActivity4,
        ];

        $m1 = Mockery::mock(ActivityApi::class);
        $m2 = Mockery::mock(BookingApi::class);
        $useActivityApi = $useBookingApi = false;

        if (array_key_exists('booking_id', $params)) { // fake api 'get-booking-details'
            $useBookingApi = true;
            $returned = array_search($params['booking_id'], array_keys($booking_ids)) === false ?
                            (object)['common' => (object)['status_code' => 404]] :
                                $booking_ids[$params['booking_id']];

            $m2->shouldReceive('getBookingDetail')->andReturn($returned);
        }
        if (array_key_exists('activity_id', $params)) { // fake api 'get-activity-details'
            $useActivityApi = true;
            $returned = array_search($params['activity_id'], array_keys($activity_ids)) === false ?
                            (object)['common' => (object)['status_code' => 404]] :
                                $activity_ids[$params['activity_id']];

            $m1->shouldReceive('find')->andReturn($returned);
        }
        if ($useActivityApi) {
            $this->app->instance(ActivityApi::class, $m1);
        }
        if ($useBookingApi) {
            $this->app->instance(BookingApi::class, $m2);
        }
    }

    /**
     * add the extra properties (keys) to json object from api for test
     *
     * @param  object $objReceive
     * @return void
     */
    private function addProperties(&$objReceive)
    {
        $properties = [
            'target_date', 'voucher_url', 'participant_first_name', 'participant_last_name',
            'activity_title', 'activity_id', 'plan_title', 'plan_unit_items',
            'plan_options',   'plan_transportation_item', 'booked_date', 'plan_start_time',
            'display_amount_gross_final', 'activity_cancel_policies', 'checkin_date',
            'checkin_time', 'checkin_location_title', 'checkin_location_description',
            'pick_up_date', 'pick_up_time', 'pick_up_location_title', 'pick_up_location_description',
            'hotel_name', 'hotel_address', 'hotel_tel', 'hotel_reservation_first_name',
            'hotel_reservation_last_name', 'arrival_date', 'departure_date', 'flight_number',
            'destination_tel',
        ];
        $defArray = ['plan_unit_items', 'plan_options', 'activity_cancel_policies'];
        
        // add properties dynamically
        foreach ($properties as $property) {
            $objReceive->{$property} = in_array($property, $defArray) ? [] : null;
        }
    }
}
