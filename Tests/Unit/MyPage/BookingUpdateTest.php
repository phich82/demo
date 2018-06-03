<?php

namespace Tests\Unit\BookingManager;

use Tests\TestCase;
use Tests\StubAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Api\Facades\Api;

class BookingUpdateTest extends TestCase
{
    use DatabaseTransactions, StubAccount;

    private $bookingDetail;
    private $activityDetail;

    /**
     * setUp function
     */
    public function setUp()
    {
        parent::setUp();

        $this->actingAs($this->createAdmin());
        if ($this->bookingDetail === null) {
            $this->getMockBookingDetail();
            Booking::query()->delete();
            BookingLog::query()->delete();
        }

        $this->prepareData();
        $this->startSession();
    }

    /**
     * Test init data
     * ExpectedResult:
     *     display screen with details data and see text '予約検索結果'
     *     display booking number 'VELTRA-5YJOYFNT'
     * Condition: call route with correct booking_id
     */
    public function test31()
    {
        $response = $this->call('GET', route('admin.booking.edit', 'VELTRA-5YJOYFNT'));

        $response->followRedirects($this)->assertSee('予約検索結果');
        $response->followRedirects($this)->assertSee('VELTRA-5YJOYFNT');
        $response->followRedirects($this)->assertSee('必須情報（予約毎)');
        $response->followRedirects($this)->assertSee('連絡用メールアドレス');
        $response->followRedirects($this)->assertSee('マイレージクラブお客様番号');
        $response->followRedirects($this)->assertSee('必須情報（参加者毎)');
        $response->followRedirects($this)->assertSee('ホテル情報');
        $response->followRedirects($this)->assertSee('現地情報');
    }

     /**
     * Test input email wrong format
     * ExpectedResult:
     *     display error 'ユーザー名には、有効な正規表現を指定してください。'
     * Condition: Input email incorrect format
     */
    public function test32()
    {
        $data = [
            'contact_mail' => str_random(20).'$%#%'
        ];
        $this->update($data)->assertSessionHasErrors([
            'email' => '連絡用メールアドレスは、有効なメールアドレス形式で指定してください。'
        ]);
    }

    /**
     * Test input email over 255 degit
     * ExpectedResult:
     *     display error 'メールアドレスに255以下の数字を入力してください。,メールアドレスは、有効なメールアドレス形式で指定してください。'
     * Condition: Input email over 255 degit
     */
    public function test33()
    {
        $data = [
            'contact_mail' => str_random(252).'@gmail.com'
        ];

        $this->update($data)->assertSessionHasErrors([
            'email' => '連絡用メールアドレスに255以下の数字を入力してください。'
        ]);
    }

    /**
     * Test amc number over 255 degit
     * ExpectedResult:
     *     display error 'amc numberに255以下の数字を入力してください。'
     * Condition: Input amc_number over 255 degit
     */
    public function test34()
    {
        $data = [
            'amc_number' => str_random(252).'ABCD'
        ];

        $this->update($data)->assertSessionHasErrors([
            'amc_number' => 'ANA マイレージクラに64以下の数字を入力してください。'
        ]);
    }

    /**
     * Test hotel name wrong format
     * ExpectedResult:
     *     display error 'hotel nameに255以下の数字を入力してください。'
     * Condition: Input hotel name not is alpha numerric
     */
    public function test36()
    {
        $data = [
            'hotel_name' => str_random(252).'ABCD'
        ];

        $this->update($data)->assertSessionHasErrors([
            'hotel_name' => '宿泊先に255以下の数字を入力してください。'
        ]);
    }

    //TODO: validate:
        //Hotel address
        //Hotel tel
        //Hotel reservation First Name
        //Hotel reservation Last Name

    /**
     * Test arrival_date wrong format date time
     * ExpectedResult:
     *     arrival_date 'arrival dateは、正しい日付ではありません。'
     * Condition: Input arrival_date wrong format date time
     */
    public function test37()
    {
        $data = [
            'arrival_date' => '2018-04-1300'
        ];

        $this->update($data)->assertSessionHasErrors([
            'arrival_date' => '到着日は、正しい日付ではありません。'
        ]);
    }

    /**
     * Test departure_date wrong format date time
     * ExpectedResult:
     *     display error 'departure dateは、正しい日付ではありません。'
     * Condition: Input arrival_date wrong format date time
     */
    public function test38()
    {
        $data = [
            'departure_date' => '2018-04-1300'
        ];

        $this->update($data)->assertSessionHasErrors([
            'departure_date' => '出発日は、正しい日付ではありません。'
        ]);
    }

    /**
     * Test departure_date wrong format date time
     * ExpectedResult:
     *     display error 'departure dateは、正しい日付ではありません。'
     * Condition: Input arrival_date wrong format date time
     */
    public function test39()
    {
        $data = [
            'arrival_date' => '2018-04-13',
            'departure_date' => '2018-04-11'
        ];

        $this->update($data)->assertSessionHasErrors([
            'departure_date' => '出発日には、到着日以降の日付を指定してください。'
        ]);
    }

    /**
     * Test call api get-booking-details
     * ExpectedResult:
     *     return common status equal 200
     * Condition: booking_id is valid 'VELTRA-5YJOYFNT'
     */
    public function test310()
    {
        $response = $this->call('GET', route('admin.booking.edit', 'VELTRA-5YJOYFNT'));

        $this->assertEquals($this->bookingDetail->common->status_code, 200);
        $this->assertEquals($this->bookingDetail->activity_id, 'VELTRA-100010679');
    }

    /**
     * Test call api get-activity-details
     * ExpectedResult:
     *     return common status equal 200
     * Condition: booking_id is valid 'VELTRA-5YJOYFNT'
     */
    public function test311()
    {
        $response = $this->call('GET', route('admin.booking.edit', 'VELTRA-5YJOYFNT'));

        $this->assertEquals($this->bookingDetail->common->status_code, 200);
        $this->assertEquals($this->activityDetail->id, 'VELTRA-100010679');
        $this->assertEquals($this->activityDetail->status, 'ACTIVE');
        $this->assertEquals($this->activityDetail->title, 'scenario22-single package voucher-JP');
    }

    /**
     * Test save data success to DB
     * ExpectedResult:
     *     updated data to table booking
     *     inserted new row into table bookingLog
     * Condition: booking_id is valid 'VELTRA-5YJOYFNT'
     */
    public function test312()
    {
        // $bookingParams = [
        //     'amc_number' => $request->get('amc_number'),
        //     'contact_mail' => $request->get('email'),
        // ];

        // $response = $this->ajax(route('admin.booking.update', 'VELTRA-5YJOYFNT'));

        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * Test not save data to DB if failed
     * ExpectedResult:
     *     data in table booking, bookingLog is not change
     * Condition: booking_id is valid 'VELTRA-5YJOYFNT'
     */
    public function test313()
    {
        // $bookingParams = [
        //     'amc_number' => $request->get('amc_number'),
        //     'contact_mail' => $request->get('email'),
        // ];

        // $response = $this->ajax(route('admin.booking.update', 'VELTRA-5YJOYFNT'));

        $this->markTestSkipped('Mark skip test to find solution');
    }

    /**
     * Test error dis connected with Database
     * ExpectedResult:
     *     return status 500
     * Condition: Database is disconnected
     */
    public function test314()
    {
        DB::disconnect();

        $response = $this->call('GET', route('admin.booking.edit', 'VELTRA-5YJOYFNT'));

        $response->assertStatus(500);
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

        $response = $this->post(route('admin.booking.update', 'VELTRA-5YJOYFNT'), $bookingParams);

        return $response;
    }

    private function getMockBookingDetail()
    {
        $bookingData = json_decode(file_get_contents(base_path('tests/json/getBookingDetail.json')));
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

        $this->bookingDetail = $bookingData;
        $this->activityDetail = $activityData;
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
