<?php

namespace App\Http\Controllers;

use App\Models\BusRound;
use App\Models\BusRoute;
use App\Models\BusSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusScheduleController extends Controller
{
    public function store(Request $request)
    /**
     * @OA\Post (
     *     path="/api/bus/schedule",
     *     tags={"버스"},
     *     summary="버스 시간표 추가",
     *     description="버스 시간표를 추가",
     *     @OA\RequestBody(
     *         description="추가할 시간표 내용,노선,회차",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema (
     *                 @OA\Property (property="weekend", type="boolean", description="주말/평일", example=true),
     *                 @OA\Property (property="semester", type="boolean", description="학기/방학", example=true),
     *                 @OA\Property (property="bus_round_id", type="string", description="버스회차 아이디", example="1"),
     *                 @OA\Property (property="bus_route_direction", type="string", description="버스 노선 ", example="B"),
     *                 @OA\Property (property="station", type="string", description="정류장", example="태전역"),
     *                 @OA\Property (property="bus_time", type="date_format", description="버스시간", example="08:00"),
     *              )
     *         )
     *     ),
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    {
        try {
            // 유효성 검사
            $validatedData = $request->validate([
                'weekend' => 'required|boolean',
                'semester' => 'required|boolean',
                'bus_route_direction' => 'required|string|size:1',
                'bus_round_id' => 'required|string',
                
                'station' => 'required|string',
                'bus_time' => 'required|date_format:H:i'
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }


        try{
            $busRoute = BusRoute::where('weekend', $validatedData['weekend'])
                             ->where('semester', $validatedData['semester'])
                             ->where('bus_route_direction', $validatedData['bus_route_direction'])
                             ->firstOrFail(); // 일치하는 항목이 없으면 예외 발생
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 404);
        }   

        
        try{
            BusSchedule::create([
                'bus_route_id' => $busRoute->id,
                'bus_round_id' => $validatedData['bus_round_id'],
                'station' => $validatedData['station'],
                'bus_time' => $validatedData['bus_time'],
            ]);
            return response()->json(['message' => '버스 시간표 등록이 확인 되었습니다.']);
        }catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
     /**
     * @OA\Patch (
     *     path="/api/bus/schedule/update/{id}",
     *     tags={"버스"},
     *     summary="버스 시간표 업데이트",
     *     description="버스 시간표를 업데이트",
     *     @OA\RequestBody(
     *         description="업데이트할 시간표 내용,노선,회차",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema (
     *                 @OA\Property (property="weekend", type="boolean", description="주말/평일", example=true),
     *                 @OA\Property (property="semester", type="boolean", description="학기/방학", example=true),
     *                 @OA\Property (property="bus_round_id", type="string", description="버스회차 아이디", example="1"),
     *                 @OA\Property (property="bus_route_direction", type="string", description="버스 노선 ", example="B"),
     *                 @OA\Property (property="station", type="string", description="정류장", example="태전역"),
     *                 @OA\Property (property="bus_time", type="date_format", description="버스시간", example="08:00"),
     *              )
     *         )
     *     ),
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    {
        try {
            // 유효성 검사
            $validatedData = $request->validate([
                'weekend' => 'required|boolean',
                'semester' => 'required|boolean',
                'bus_round_id' => 'required|string',
                'bus_route_direction' => 'required|string|size:1',
                'station' => 'required|string',
                'bus_time' => 'required|date_format:H:i'
            ]);


            // 수정할 버스 시간표id 찾기
            $busSchedule = BusSchedule::findOrFail($id);

            // 해당 버스 시간표 노선 찾기
            $busRoute = BusRoute::where('weekend', $validatedData['weekend'])
                             ->where('semester', $validatedData['semester'])
                             ->where('bus_route_direction', $validatedData['bus_route_direction'])
                             ->firstOrFail();

            // 버스 시간표 정보 업데이트
            $busSchedule->update([
                'bus_route_id' => $busRoute->id,
                'bus_round_id' => $validatedData['bus_round_id'],
                'station' => $validatedData['station'],
                'bus_time' => $validatedData['bus_time'],
            ]);

            return response()->json(['message' => '버스 시간표 수정이 완료되었습니다.']);
        } catch (ValidationException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function destroy($id)
    /**
     * @OA\Delete (
     *     path="/api/bus/schedule/delete",
     *     tags={"버스"},
     *     summary="버스 시간표 삭제",
     *     description="버스 시간표 삭제",
     *     @OA\Parameter(
     *           name="id",
     *           description="삭제할 버스 시간표의 아이디",
     *           required=true,
     *           in="path",
     *           @OA\Schema(type="integer"),
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    {
        try {
            // 삭제할 버스 시간표를 찾습니다.
            $busSchedule = BusSchedule::findOrFail($id);

            // 버스 시간표를 삭제합니다.
            $busSchedule->delete();

            return response()->json(['message' => '버스 시간표가 성공적으로 삭제되었습니다.']);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function addRound(Request $request)
    /**
     * @OA\Post (
     *     path="/api/bus/addRound",
     *     tags={"버스"},
     *     summary="버스 회차 추가",
     *     description="버스 회차 추가 ",
     *     @OA\RequestBody(
     *         description="추가할 버스 회차",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema (
     *                 @OA\Property (property="round", type="string", description="회차", example="1"),
     *              )
     *         )
     *     ),
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="422", description="Validation Exception"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    {
        try {
            // 유효성 검사
            $validatedData = $request->validate([
              'round' => 'required|string',
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }


        try{
            BusRound::create([
                'round' => $validatedData['round']
            ]);
        }catch(\Exception $exception){
            return response()->json(['error' => $exception->getMessage()], 500);
        }
        return response()->json(['message' => '버스 회차가 추가 되었습니다.']);
    }

    public function roundDestroy($id)
    /**
     * @OA\Delete (
     *     path="/api/bus/round/delete",
     *     tags={"버스"},
     *     summary="버스 회차 삭제",
     *     description="버스 회차 삭제",
     *     @OA\Parameter(
     *           name="id",
     *           description="삭제할 버스 회차의 아이디",
     *           required=true,
     *           in="path",
     *           @OA\Schema(type="integer"),
     *     ),
     *     @OA\Response(response="200", description="Success"),
     *     @OA\Response(response="500", description="Fail"),
     * )
     */
    {
        try {
            // 삭제할 버스 시간표를 찾습니다.
            $busRound = BusRound::findOrFail($id);

            // 버스 시간표를 삭제합니다.
            $busRound->delete();

            return response()->json(['message' => '버스 시간표가 성공적으로 삭제되었습니다.']);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}