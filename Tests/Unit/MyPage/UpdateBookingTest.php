<?php

namespace Tests\Unit\MyPage;

use Mockery;
use App\Api\Veltra;
use Tests\TestCase;
use Tests\StubSSOData;
use App\Models\Booking;
use App\Api\Facades\Api;
use App\Models\BookingLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\HttpException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Screen: Traveler_MyPage_(MyBooking)_UpdateBookingScreen
 * @author Phat Huynh Nguyen <huynh.phat@mulodo.com>
 */
class UpdateBookingTest extends TestCase
{
    use DatabaseTransactions;
    use StubSSOData;

    /**
     * @var route_name
     */
    private $route_name;

    /**
     * The booking detail from api
     *
     * @var bookingDetail
     */
    private $bookingDetail;

    /**
     * The activity detail from api
     *
     * @var activityDetail
     */
    private $activityDetail;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->route_name  = 'traveler.mypage.booking.edit';
        
        if ($this->bookingDetail === null) {
            $this->getMockBookingDetail();
            Booking::query()->delete();
            BookingLog::query()->delete();
        }
        $this->prepareData();
        $this->startSession();
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
     * [TestCase-2.1] Test redirect to Traveler MyPage page when the booking id not exist
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is invalid
     * - Url: '/mypage/booking/{BookingID}'
     *
     * Expectation:
     * - Redirect to MyPage screen
     */
    public function test21RedirectToMyPageScreenWhenBookingIdNotExist()
    {
        $ssoData = $this->getSSOData();

        $this->get(route($this->route_name, 'XXXXXX'))
             ->assertRedirect(route('traveler.mypage.index'));
    }

    /**
     * [TestCase-2.2] Test redirect to Traveler Booking Details screen when the booking status cancelled
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Booking status is cancelled (CANCELLED_BY_TRAVELER)
     *
     * Expectation:
     * - Redirect to Traveler Booking Details screen
     */
    public function test22RedirectToBookingDetailPagenWhenBookingStatusCancelled()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.3] Test redirect to Traveler Booking Details screen when participation date lower than current date
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Participation date lower than current date
     *
     * Expectation:
     * - Redirect to Traveler Booking Details screen
     */
    public function test23RedirectToBookingDetailPageWhenParticipationDateLowerCurrentDate()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.4] Test display the booking information
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     *
     * Expectation:
     * - See the booking information on screen
     */
    public function test24DisplayBookingInformation()
    {
        $ssoData = $this->getSSOData();

        $bookingID  = 'VELTRA-5YJOYFNT';
        $booking    = $this->createBooking(['booking_id' => $bookingID, 'status' => 'CONFIRMED'], true);
        $bookingLog = $this->createBookingLog($bookingID, true);

        $this->get(route($this->route_name, $bookingID))
              ->assertSee($booking->booking_id)
              ->assertSee($booking->amc_number ? $booking->amc_number : '');
    }

    /**
     * [TestCase-2.5] Test display the booking information
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     *
     * Expectation:
     * - See the text '必須' on screen
     */
    public function test25DisplayRequiredMarkTextWhenTypeOfBookingFieldsIsRequiredOnBooking()
    {
        $ssoData = $this->getSSOData();

        $bookingID  = 'VELTRA-5YJOYFNT';
        $booking    = $this->createBooking(['booking_id' => $bookingID, 'status' => 'CONFIRMED'], true);
        $bookingLog = $this->createBookingLog($bookingID, true);

        $this->get(route($this->route_name, $bookingID))
              ->assertSee('必須');
    }

    /**
     * [TestCase-2.6] Test display the booking information
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     *
     * Expectation:
     * - See the text '参加日まで必須' on screen
     */
    public function test26DisplayRequiredMarkTextWhenTypeOfBookingFieldsIsRequiredByActivityDate()
    {
        $ssoData = $this->getSSOData();

        $bookingID  = 'VELTRA-5YJOYFNT';
        $booking    = $this->createBooking(['booking_id' => $bookingID, 'status' => 'CONFIRMED'], true);
        $bookingLog = $this->createBookingLog($bookingID, true);

        $this->get(route($this->route_name, $bookingID))
              ->assertSee('参加日まで必須');
    }

    /**
     * [TestCase-2.7] Test redirect to Traveler MyPage screen when click on link Go Back MyPage Screen
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Click on link 'Go Back MyPage Screen'
     *
     * Expectation:
     * - Redirect to Traveler MyPage screen
     */
    public function test27RedirectToMyPageScreenWhenClickOnLinkGoBackMyPageScreen()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.8] Test redirect to Traveler MyPage screen when click on link Go Back Booking Details Screen
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Click on link 'Go Back Booking Details Screen'
     *
     * Expectation:
     * - Redirect to Traveler Booking Details screen
     */
    public function test28RedirectToBookingDetailPagenWhenClickOnLinkGoBackDetailScreen()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.9] Test display the error message when entering response date format with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a date in field of response with wrong date (2018-01-1000)
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test29DisplayErrorMessageWhenResponseDateFormatWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.10] Test display the error message when entering response time format with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a time in field of response with wrong date (05:9999)
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test210DisplayErrorMessageWhenResponseTimeFormatWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.11] Test display the error message when change value of response YES_OR_NO format not YES or NO
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Change value of button radio (YES_XXX or NO_XXX)
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test211DisplayErrorMessageWhenResponseYesOrNoFormatNotYesOrNo()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.12] Test display the error message when change value of response SELECT_ONE format not the given list
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Change value of dropdown (SELECT_ONE) not in the given list
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test212DisplayErrorMessageWhenResponseSelectOneFormatNotInGivenList()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.13] Test display the error message when change value of response SELECT_MULTIPLE format not the given list
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Change values of dropdown (SELECT_MULTIPLE) not in the given list
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test213DisplayErrorMessageWhenResponseSelectMultipleFormatNotInGivenList()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.14] Test display the error message when change value of response TEXT format not the text string
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a value not be a text string
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test214DisplayErrorMessageWhenResponseTextFormatNotTextString()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.15] Test display the error message when response alpha-numeric format not be an alpha-numeric
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a value not be an alpha-numeric ('~xxx~!!!!!')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test215DisplayErrorMessageWhenResponseAlphanumericFormatNotAlphanumeric()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.16] Test display the error message when response telephone_number format with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a value telephone number with wrong format ('0123456789')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test216DisplayErrorMessageWhenResponseTelephoneNumberFormatWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.17] Test display the error message when arrival date with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter an arrival date with wrong format ('2018-04-1300')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message '到着日は、正しい日付ではありません。'
     */
    public function test217DisplayErrorMessageWhenArrivalDateWithWrongFormat()
    {
        $data = ['arrival_date' => '2018-04-1300'];

        $this->update($data)
             ->assertSessionHasErrors(['arrival_date' => '到着日は、正しい日付ではありません。']);
    }

    /**
     * [TestCase-2.18] Test display the error message when departure date with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter an departure date with wrong format ('2018-04-1300')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message '出発日は、正しい日付ではありません。'
     */
    public function test218DisplayErrorMessageWhenDepartureDateWithWrongFormat()
    {
        $data = ['departure_date' => '2018-04-1300'];

        $this->update($data)
             ->assertSessionHasErrors(['departure_date' => '出発日は、正しい日付ではありません。']);
    }

    /**
     * [TestCase-2.19] Test display the error message when departure date lower than arrival date
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter an arrival date ('2018-04-13')
     * - Enter an departure date ('2018-04-11')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message '出発日は、正しい日付ではありません。'
     */
    public function test219DisplayErrorMessageWhenDepartureDateLowerThanArrivalDate()
    {
        $data = [
            'arrival_date'   => '2018-04-13',
            'departure_date' => '2018-04-11'
        ];

        $this->update($data)
             ->assertSessionHasErrors(['departure_date' => '出発日には、到着日以降の日付を指定してください。']);
    }

    /**
     * [TestCase-2.20] Test display the error message when destination telephone with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a destination telephone ('0123456xxxx')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test220DisplayErrorMessageWhenDestinationTelephoneWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.21] Test display the error message when hotel name with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter a destination telephone ('0123456xxxx')
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test221DisplayErrorMessageWhenHotelNameWithWrongFormat()
    {
        $data = ['hotel_name' => str_random(252).'xxxxxx'];

        $this->update($data)
             ->assertSessionHasErrors(['hotel_name' => '宿泊先に255以下の数字を入力してください。']);
    }

    /**
     * [TestCase-2.22] Test display the error message when hotel_reservation_first_name with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter hotel_reservation_first_name over 255 chars
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test222DisplayErrorMessageWhenhotelReservationFirstNameWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.23] Test display the error message when hotel_reservation_last_name with wrong format
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Enter hotel_reservation_last_name over 255 chars
     * - Click on button SAVE
     *
     * Expectation:
     * - See the error message
     */
    public function test223DisplayErrorMessageWhenhotelReservationLastNameWithWrongFormat()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.24] Test display links of pagination when per participants not empty
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     *
     * Expectation:
     * - See the error message
     */
    public function test224DisplayLinksOfPaginationWhenPerParticipantsNotEmpty()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.25] Test display responses list after click on each link of pagination
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Click on links of pagination
     *
     * Expectation:
     * - See the error message
     */
    public function test225DisplayResponsesListAfterClickOnEachLinkOfPagination()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.26] Test display a popup when click on button Save
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Click on button SAVE
     *
     * Expectation:
     * - See a popup with text 'こちらの予約の情報を更新しますか？'
     */
    public function test226DisplayPopupWhenClickOnButtonSave()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.27] Test display the completed message when saved successfully
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - Click on button SAVE
     *
     * Expectation:
     * - See a popup with text '送信完了' and '予約情報が更新されました。'
     */
    public function test227DisplayCompletedMessageWhenSavedSuccessfully()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.28] Test display the errormessage when saved failed
     *
     * Condition:
     * - Session login amc existed
     * - BookingID is valid
     * - Url: '/mypage/booking/{BookingID}'
     * - At least a value is wrong
     * - Click on button SAVE
     * - Click on button OK
     *
     * Expectation:
     * - See the error message
     */
    public function test228DisplayErrorMessageWhenSavedFailed()
    {
        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * [TestCase-2.29] Test display the error message when database disconnected
     *
     * Condition:
     * - Session login amc existed
     * - Database is disconnected
     *
     * Expectation::
     * - Return status 500
     */
    public function test229TestDisplayErrorMessageWhenDatabaseDisconnected()
    {
        $this->markTestSkipped('Mark skip test to find solution');
        //DB::disconnect();

        // $this->get(route($this->route_name, 'VELTRA-5YJOYFNT'))
        //      ->assertStatus(500);
    }

    /**
     * @param $data
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function update($data)
    {
        $bookingParams = [
            '_token'         => csrf_token(),
            'email'          => $data['contact_mail'] ?? null,
            'amc_number'     => $data['amc_number'] ?? null,
            'hotel_name'     => $data['hotel_name'] ?? null,
            'arrival_date'   => $data['arrival_date'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
        ];

        $response = $this->post(route('traveler.mypage.booking.update', 'VELTRA-5YJOYFNT'), $bookingParams);

        return $response;
    }

    /**
     * mock api
     *
     * @return void
     */
    private function getMockBookingDetail()
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
     * Create new booking
     *
     * @param array $params
     * @param boolean $return
     * @return mixed
     */
    private function createBooking($params = [], $return = false)
    {
        Booking::query()->delete();

        $booking = factory(Booking::class)->create([
            'booking_id'              => $params['booking_id']  ?? 'VELTRA-5YJOYFNT',
            'activity_id'             => $params['activity_id'] ?? 'VELTRA-100000106',
            'plan_id'                 => $params['plan_id']     ?? rand(1, 100),
            'first_name'              => 'Steven',
            'last_name'               => 'Nguyen',
            'email'                   => 'stevennguyen@gmail.com',
            'contact_mail'            => 'admin@test.com',
            'amc_number'              => 'AMC75',
            'participation_date'      => $params['participation_date'] ?? date('Y-m-d', strtotime(date('Y-m-d')) + 365*24*60*60),
            'status'                  => $params['status'] ?? 'CONFIRMED',
        ]);

        if ($return === true) {
            return $booking;
        }
    }

    /**
     * Create new booking log
     *
     * @param string $bookingID
     * @param boolean $return
     * @return mixed
     */
    private function createBookingLog($bookingID, $return = false)
    {
        BookingLog::query()->delete();

        $bookingLog = factory(BookingLog::class)->create([
            'booking_id'  => $bookingID
        ]);

        if ($return === true) {
            return $bookingLog;
        }
    }

    /**
     * function prepare data
     * @return void
     */
    private function prepareData()
    {
        factory(Booking::class)->create([
            'booking_id'              => 'VELTRA-5YJOYFNT',
            'activity_id'             => 'VELTRA-100000106',
            'plan_id'                 => rand(1, 100),
            'guest_flag'              => 1,
            'first_name'              => 'Steven',
            'last_name'               => 'Nguyen',
            'email'                   => 'stevennguyen@gmail.com',
            'contact_mail'            => 'admin@test.com',
            'amc_number'              => 'AMC1',
            'booking_date'            => '2006-01-09',
            'participation_date'      => '2007-01-09',
            'participant_persons'     => rand(1, 100),
            'sales_price'             => '400',
            'booking_unit_price'      => '4',
            'sales_price_mile'        => '4',
            'booking_unit_price_mile' => '4',
            'refund_mile'             => '0',
            'mile_type'               => rand(0, 2),
            'accumulate_flag'         => rand(0, 1),
            'status'                  => 'CONFIRMED',
            'create_user'             => 'admin',
            'update_user'             => 'admin'
        ]);

        factory(Booking::class)->create([
            'booking_id'              => 'VELTRA-3N0RHQ74',
            'activity_id'             => 'VELTRA-100010613',
            'plan_id'                 => rand(1, 100),
            'guest_flag'              => 1,
            'first_name'              => 'Steven',
            'last_name'               => 'Nguyen',
            'email'                   => 'stevennguyen@gmail.com',
            'contact_mail'            => 'admin@test.com',
            'amc_number'              => 'AMC1222',
            'booking_date'            => '2006-01-09',
            'participation_date'      => '2007-01-09',
            'participant_persons'     => rand(1, 100),
            'sales_price'             => '400',
            'booking_unit_price'      => '4',
            'sales_price_mile'        => '4',
            'booking_unit_price_mile' => '4',
            'refund_mile'             => '0',
            'mile_type'               => 2,
            'accumulate_flag'         => rand(0, 1),
            'status'                  => 'CONFIRMED',
            'create_user'             => 'admin',
            'update_user'             => 'admin'
        ]);

        factory(BookingLog::class)->create([
            'booking_id'  => 'VELTRA-5YJOYFNT',
            'date_time'   => '2005-08-16 20:39:21',
            'log_name'    => 'log test',
            'memo'        => 'test_comment',
            'user'        => 'test',
            'create_user' => 'admin',
            'update_user' => 'admin'
        ]);

        factory(BookingLog::class)->create([
            'booking_id'  => 'VELTRA-3N0RHQ74',
            'date_time'   => '2005-08-16 20:39:21',
            'log_name'    => 'log test',
            'memo'        => 'test_comment',
            'user'        => 'test',
            'create_user' => 'admin',
            'update_user' => 'admin'
        ]);
    }
}
