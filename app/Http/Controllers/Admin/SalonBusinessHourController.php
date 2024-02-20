<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonBusinessHour;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalonBusinessHourController extends Controller
{
    //TODO: 휴게시간 필터링하여 불러오는 기능 구현

    public function __construct(protected  SalonBreakTimeController $salonBreakTimeController)
    {
    }
    /**
     * @OA\Get (
     *     path="/api/admin/salon-hour",
     *     tags={"미용실"},
     *     summary="전체 영업시간",
     *     description="모든 요일의 미용실 영업 시간",
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="422", description="Validation Error"),
     * )
     */
    public function index(Request $request)
    {
        return response()->json(['business_hours' => SalonBusinessHour::all(['s_time', 'e_time', 'date'])]);
    }

    /**
     * @OA\Get (
     *     path="/api/admin/salon-hour/{day}",
     *     tags={"미용실"},
     *     summary="특정 요일의 영업시간",
     *     description="특정 요일의 미용실 영업 시간을 한시간 단위로 반환함",
     *      @OA\Parameter(
     *            name="day",
     *            description="요일(MON,TUE... 이런식으로)",
     *            required=true,
     *            in="path",
     *            @OA\Schema(type="string"),
     *        ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="422", description="Validation Error"),
     * )
     */
    public function show(string $day)
    {
        $validator = Validator::make(['day' => $day], [
            'day' => ['required', Rule::in($this->dayList)],
        ]);

        try {
            $validated = $validator->validate();
        } catch (ValidationException $validationException) {
            $errorStatus = $validationException->status;
            $errorMessage = $validationException->getMessage();
            return response()->json(['error' => $errorMessage], $errorStatus);
        }

        $breakTimes = [];
        $salonBreakTimes = $this->salonBreakTimeController->index()->where('day', $day);

        foreach($salonBreakTimes as $salonBreakTime) {
            $breakTimes[] = $salonBreakTime->break_time;
        }

        $b_hour = SalonBusinessHour::where('date' , $day)->first();

        // UNIX 타임스탬프로 변경
        $current = strtotime($b_hour->s_time);
        $end = strtotime($b_hour->e_time);
        $business_hours = [];

        while ($current <= $end) {
            $business_hours[] = (object) ['time' => date('H:i', $current), 'available' => true];
            $current = strtotime('+1 hour', $current);
        }

        foreach ($business_hours as $business_hour) {
            if(in_array($business_hour->time, $breakTimes)) {
                $business_hour->available = false;
            }
        }

        return $business_hours;
    }

    /**
     * @OA\Post (
     *     path="/api/admin/salon-hour",
     *     tags={"미용실"},
     *     summary="영업시간 생성",
     *     description="미용실 영업시간 생성",
     *     @OA\RequestBody(
     *         description="영업시간 관련 정보",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema (
     *                 @OA\Property (property="s_time", type="time", description="영업 시작", example="08:00:00"),
     *                 @OA\Property (property="e_time", type="time", description="영업 종료", example="21:00:00"),
     *                 @OA\Property (property="date", type="date", description="요일(대문자, 기존 DB 내에 중복 값 없어야함)", example="MON")
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Created"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    public function store(Request $request)
    {


        try {
            $validated = $request->validate([
                's_time' => 'required|time',
                'e_time' => 'required|time',
                'date'   => ['required', Rule::in($this->dayList), 'unique:salon_business_hours,date'],
            ]);
        } catch (ValidationException $validationException) {
            $errorStatus = $validationException->status;
            $errorMessage = $validationException->getMessage();
            return response()->json(['error' => $errorMessage], $errorStatus);
        }
        $businessHour = SalonBusinessHour::create([
            's_time' => $validated['s_time'],
            'e_time' => $validated['e_time'],
            'date'   => $validated['date'],
        ]);

        if(!$businessHour) return response()->json(['error' => 'Failed to set businessHour'], 500);

        return response()->json(['reservation' => $businessHour], 201);
    }

    /**
     * @OA\Patch (
     *     path="/api/admin/salon-hour",
     *     tags={"미용실"},
     *     summary="영업시간 업데이트",
     *     description="미용실 영업시간을 업데이트",
     *     @OA\RequestBody(
     *         description="업데이트할 영업시간 및 아이디",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema (
     *                 @OA\Property (property="b_hour_id", type="integer", description="영업시간 아이디", example=1),
     *                 @OA\Property (property="s_time", type="time", description="영업 시작", example="08:00:00"),
     *                 @OA\Property (property="e_time", type="time", description="영업 종료", example="21:00:00"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'b_hour_id' => 'required|numeric',
                's_time' => 'required|time',
                'e_time' => 'required|time',
            ]);
        } catch (ValidationException $validationException) {
            $errorStatus = $validationException->status;
            $errorMessage = $validationException->getMessage();
            return response()->json(['error' => $errorMessage], $errorStatus);
        }

        try {
            $bHour = SalonBusinessHour::findOrFail($validated['b_hour_id']);
        } catch(ModelNotFoundException $modelException) {
            $errorMessage = $modelException->getMessage();
            return response()->json(['error' => $errorMessage], 404);
        }

        $bHour->s_time = $validated['s_time'];
        $bHour->e_time = $validated['e_time'];

        if(!$bHour->save()) return response()->json(['error' => 'Failed to update business hour'], 500);

        return response()->json(['success' => 'Update business hour successfully']);
    }

    /**
     * @OA\Delete (
     *     path="/api/admin/salon-hour/{id}",
     *     tags={"미용실"},
     *     summary="영업시간 삭제",
     *     description="미용실 영업시간 삭제",
     *      @OA\Parameter(
     *            name="id",
     *            description="삭제할 영업시간 값의 아이디",
     *            required=true,
     *            in="path",
     *            @OA\Schema(type="integer"),
     *        ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    public function destroy(string $id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|exists:salon_business_hours,id',
        ]);
        try {
            $validated = $validator->validate();
        } catch (ValidationException $validationException) {
            $errorStatus = $validationException->status;
            $errorMessage = $validationException->getMessage();
            return response()->json(['error' => $errorMessage], $errorStatus);
        }

        if(!SalonBusinessHour::destroy($validated['id'])) return response()->json(['error' => 'Failed to delete business hour'], 500);

        return response()->json(['success' => 'Business hour data delete successfully']);
    }
}
